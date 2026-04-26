<?php
require_once 'config.php';
header('Content-Type: application/json');

// On récupère ce que l'utilisateur tape
 $query = trim($_GET['q'] ?? '');

if(empty($query)) {
    echo json_encode([]);
    exit;
}

// Recherche dans le nom ET la description
 $req = $pdo->prepare("SELECT id, nom, prix_actuel, image_url, date_fin FROM produits WHERE nom LIKE :q OR description LIKE :q ORDER BY prix_actuel DESC LIMIT 20");
 $req->execute(['q' => '%' . $query . '%']);
 $resultats = $req->fetchAll();

echo json_encode($resultats);
?>