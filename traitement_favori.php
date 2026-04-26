<?php
require_once 'config.php';
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

 $produit_id = $_POST['produit_id'] ?? 0;
if(!$produit_id) { echo json_encode(['success' => false]); exit; }

// Vérifier si déjà en favori
 $check = $pdo->prepare("SELECT id FROM favoris WHERE utilisateur_id = ? AND produit_id = ?");
 $check->execute([$_SESSION['user_id'], $produit_id]);

if($check->fetch()) {
    // Déjà en favori -> On supprime
    $del = $pdo->prepare("DELETE FROM favoris WHERE utilisateur_id = ? AND produit_id = ?");
    $del->execute([$_SESSION['user_id'], $produit_id]);
    echo json_encode(['success' => false]);
} else {
    // Pas en favori -> On ajoute
    $ins = $pdo->prepare("INSERT INTO favoris (utilisateur_id, produit_id) VALUES (?, ?)");
    $ins->execute([$_SESSION['user_id'], $produit_id]);
    echo json_encode(['success' => true]);
}
?>