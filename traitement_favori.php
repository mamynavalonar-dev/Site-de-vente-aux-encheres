<?php
require_once __DIR__ . '/bootstrap.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    app_json(405, ['success' => false, 'message' => 'Méthode non autorisée.']);
}
$user = app_require_user();
app_require_csrf();
$id = filter_var($_POST['produit_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$state = $_POST['favori'] ?? null;
if (!$id || !in_array($state, ['0', '1'], true)) {
    app_json(422, ['success' => false, 'message' => 'Indiquez un lot et un état de favori valides.']);
}
session_write_close();
try {
    $pdo->beginTransaction();
    $query = $pdo->prepare('SELECT id FROM produits WHERE id = ? AND archive_le IS NULL FOR UPDATE');
    $query->execute([$id]);
    if (!$query->fetchColumn()) {
        $pdo->rollBack();
        app_json(404, ['success' => false, 'message' => 'Lot introuvable.']);
    }
    if ($state === '1') {
        $query = $pdo->prepare('INSERT INTO favoris(utilisateur_id, produit_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE produit_id = VALUES(produit_id)');
    } else {
        $query = $pdo->prepare('DELETE FROM favoris WHERE utilisateur_id = ? AND produit_id = ?');
    }
    $query->execute([$user['id'], $id]);
    $pdo->commit();
    app_json(200, ['success' => true, 'favori' => $state === '1']);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('Favori indisponible : ' . $error->getMessage());
    app_json(500, ['success' => false, 'message' => 'Impossible de confirmer la modification. Actualisez la page.']);
}
