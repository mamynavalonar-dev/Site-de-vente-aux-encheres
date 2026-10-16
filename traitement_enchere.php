<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function enchere_repondre(int $statut, bool $succes, string $message, array $details = []): void
{
    http_response_code($statut);
    echo json_encode(array_merge(['success' => $succes, 'message' => $message], $details),
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

// Les comparaisons monétaires utilisent des chaînes de centimes, jamais des flottants.
function enchere_centimes(string $montant): string
{
    if (!preg_match('/\A[0-9]+(?:\.[0-9]{1,2})?\z/', $montant)) {
        throw new RuntimeException('Montant stocké incompatible avec les centimes.');
    }
    $parties = explode('.', $montant, 2);
    return ltrim($parties[0] . str_pad($parties[1] ?? '', 2, '0'), '0') ?: '0';
}

function enchere_formater(string $montant): string
{
    $parties = explode('.', $montant, 2);
    $entier = ltrim($parties[0], '0') ?: '0';
    return preg_replace('/\B(?=(\d{3})+(?!\d))/', ' ', $entier) . ',' . str_pad($parties[1] ?? '', 2, '0');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    enchere_repondre(405, false, 'Méthode non autorisée.', ['code' => 'method_not_allowed']);
}

$utilisateurId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$utilisateurId) {
    enchere_repondre(401, false, 'Vous devez être connecté pour enchérir.', ['code' => 'authentication_required']);
}

$jetonSession = $_SESSION['csrf_enchere'] ?? null;
$jetonRecu = $_POST['csrf_token'] ?? null;
if (!is_string($jetonSession) || !is_string($jetonRecu) || strlen($jetonSession) !== 64 ||
    !hash_equals($jetonSession, $jetonRecu)) {
    enchere_repondre(403, false, 'Le formulaire a expiré. Actualisez la page puis réessayez.', ['code' => 'csrf_invalid']);
}

// Le verrou SQL protège l'offre ; la session ne doit pas sérialiser les requêtes.
session_write_close();

$produitId = filter_var($_POST['produit_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$montantRecu = $_POST['montant'] ?? null;
if (!$produitId || !is_string($montantRecu) ||
    !preg_match('/\A[0-9]{1,13}(?:[.,][0-9]{1,2})?\z/', trim($montantRecu))) {
    enchere_repondre(422, false, 'Indiquez un lot valide et un montant positif avec deux décimales au maximum.', ['code' => 'invalid_bid']);
}

$parties = explode('.', str_replace(',', '.', trim($montantRecu)), 2);
$montant = (ltrim($parties[0], '0') ?: '0') . '.' . str_pad($parties[1] ?? '', 2, '0');
$centimes = enchere_centimes($montant);
if ($centimes === '0') {
    enchere_repondre(422, false, 'Votre offre doit être strictement positive.', ['code' => 'invalid_bid']);
}

try {
    $pdo->beginTransaction();
    $lecture = $pdo->prepare('SELECT prix_actuel, date_fin, statut FROM produits WHERE id = ? FOR UPDATE');
    $lecture->execute([$produitId]);
    $produit = $lecture->fetch();

    if (!$produit) {
        $pdo->rollBack();
        enchere_repondre(404, false, 'Produit introuvable.', ['code' => 'auction_not_found']);
    }

    // Cette nouvelle requête prend l'heure APRÈS l'attente éventuelle du verrou.
    $maintenant = $pdo->query('SELECT CURRENT_TIMESTAMP')->fetchColumn();
    if ($produit['statut'] !== 'en_cours' || $produit['date_fin'] <= $maintenant) {
        $pdo->rollBack();
        enchere_repondre(409, false, 'Cette enchère est terminée. Les offres ne sont plus acceptées.', ['code' => 'auction_closed']);
    }

    $centimesActuels = enchere_centimes((string) $produit['prix_actuel']);
    if (strlen($centimes) < strlen($centimesActuels) ||
        (strlen($centimes) === strlen($centimesActuels) && strcmp($centimes, $centimesActuels) <= 0)) {
        $pdo->rollBack();
        $prixActuel = enchere_formater((string) $produit['prix_actuel']);
        enchere_repondre(409, false, 'Votre offre doit être strictement supérieure au prix actuel (' . $prixActuel . ' €).',
            ['code' => 'bid_too_low', 'prix_actuel' => $prixActuel]);
    }

    // La condition temporelle couvre aussi le passage de l'échéance pendant la validation.
    $miseAJour = $pdo->prepare("UPDATE produits SET prix_actuel = ? WHERE id = ? AND statut = 'en_cours' AND date_fin > NOW() AND prix_actuel < CAST(? AS DECIMAL(15,2))");
    $miseAJour->execute([$montant, $produitId, $montant]);
    if ($miseAJour->rowCount() !== 1) {
        $pdo->rollBack();
        enchere_repondre(409, false, 'Cette enchère est terminée. Les offres ne sont plus acceptées.', ['code' => 'auction_closed']);
    }

    $insertion = $pdo->prepare('INSERT INTO encheres (produit_id, utilisateur_id, montant, date_enchere) VALUES (?, ?, ?, NOW())');
    $insertion->execute([$produitId, $utilisateurId, $montant]);
    $pdo->commit();

    enchere_repondre(200, true, 'Enchère réussie !', ['nouveau_prix' => enchere_formater($montant)]);
} catch (Throwable $erreur) {
    try {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    } catch (Throwable $erreurAnnulation) {
        error_log('Annulation de l offre impossible : ' . $erreurAnnulation->getMessage());
    }
    error_log('Enregistrement de l offre impossible : ' . $erreur->getMessage());
    enchere_repondre(500, false, 'Impossible de confirmer l’enregistrement de l’offre. Actualisez la page pour vérifier votre offre avant de réessayer.', ['code' => 'bid_failed']);
}
