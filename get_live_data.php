<?php
require_once __DIR__ . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Cette lecture n'a pas besoin de conserver le verrou de la session PHP.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    http_response_code(400);
    echo json_encode(['error' => 'ID invalide']);
    exit;
}

try {
    // Un seul instantané pour le prix, l'état et les 50 offres les plus récentes.
    // Aucun identifiant d'offre supplémentaire ni changement SQL n'est nécessaire.
    $req = $pdo->prepare("
        SELECT p.prix_actuel, p.statut,
               TIMESTAMPDIFF(SECOND, NOW(), p.date_fin) AS secondes_restantes,
               e.montant, e.date_enchere, u.nom
        FROM produits p
        LEFT JOIN encheres e ON e.produit_id = p.id
        LEFT JOIN utilisateurs u ON u.id = e.utilisateur_id
        WHERE p.id = ? AND p.archive_le IS NULL
        ORDER BY e.date_enchere DESC, e.montant DESC, e.utilisateur_id DESC
        LIMIT 50
    ");
    $req->execute([$id]);
    $rows = $req->fetchAll();

    if (!$rows) {
        http_response_code(404);
        echo json_encode(['error' => 'Produit introuvable']);
        exit;
    }

    $product = $rows[0];
    $history = [];
    foreach ($rows as $row) {
        if ($row['montant'] === null) {
            continue;
        }
        $history[] = [
            'nom' => $row['nom'] ?? 'Participant',
            'montant' => number_format($row['montant'], 2, ',', ' '),
            'date' => date('d/m H:i', strtotime($row['date_enchere'])),
        ];
    }

    $remaining = max(0, (int) $product['secondes_restantes']);
    echo json_encode([
        'statut' => $product['statut'] === 'terminee' || $remaining === 0 ? 'terminee' : 'en_cours',
        'prix_actuel' => number_format($product['prix_actuel'], 2, ',', ' '),
        'secondes_restantes' => $remaining,
        'last_bid' => $history[0] ?? null,
        'historique' => $history,
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (PDOException $error) {
    error_log('Lecture des enchères impossible : ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Actualisation temporairement indisponible.']);
}
