<?php
require_once __DIR__ . '/bootstrap.php';
$user = app_current_user();
$q = $_GET['q'] ?? '';
$category = isset($_GET['categorie']) ? filter_var($_GET['categorie'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null;
if (!is_string($q) || strlen($q) > 300 || $category === false) {
    app_json(422, ['success' => false, 'message' => 'Recherche invalide.']);
}
session_write_close();
try {
    $statement = "SELECT p.id, p.nom, p.prix_actuel, p.date_fin, c.nom AS categorie_nom,
        EXISTS(SELECT 1 FROM favoris f WHERE f.produit_id = p.id AND f.utilisateur_id = ?) AS favori
        FROM produits p LEFT JOIN categories c ON c.id = p.categorie_id
        WHERE p.archive_le IS NULL AND p.statut = 'en_cours' AND p.date_fin > NOW()";
    $params = [$user['id'] ?? 0];
    if (trim($q) !== '') {
        $statement .= ' AND (p.nom LIKE ? OR p.description LIKE ?)';
        $params[] = '%' . trim($q) . '%';
        $params[] = '%' . trim($q) . '%';
    }
    if ($category !== null) {
        $statement .= ' AND p.categorie_id = ?';
        $params[] = $category;
    }
    $query = $pdo->prepare($statement . ' ORDER BY p.date_fin DESC, p.id DESC LIMIT 50');
    $query->execute($params);
    app_json(200, $query->fetchAll());
} catch (Throwable $error) {
    error_log('Recherche indisponible : ' . $error->getMessage());
    app_json(500, ['success' => false, 'message' => 'Recherche temporairement indisponible.']);
}
