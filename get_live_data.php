<?php
require_once 'config.php';
header('Content-Type: application/json');

 $id = $_GET['id'] ?? 0;
if(!$id || !is_numeric($id)) { echo json_encode(['error' => 'ID invalide']); exit; }

// On récupère le statut et le prix
 $req = $pdo->prepare("SELECT prix_actuel, statut FROM produits WHERE id = ?");
 $req->execute([$id]);
 $prod = $req->fetch();

if(!$prod) { echo json_encode(['error' => 'Produit introuvable']); exit; }

// On récupère la dernière enchère (la plus récente)
 $hist = $pdo->prepare("SELECT e.montant, u.nom, e.date_enchere FROM encheres e JOIN utilisateurs u ON e.utilisateur_id = u.id WHERE e.produit_id = ? ORDER BY e.date_enchere DESC LIMIT 1");
 $hist->execute([$id]);
 $last_bid = $hist->fetch();

echo json_encode([
    'statut' => $prod['statut'],
    'prix_actuel' => number_format($prod['prix_actuel'], 2, ',', ' '),
    'last_bid' => $last_bid ? [
        'nom' => $last_bid['nom'], 
        'montant' => number_format($last_bid['montant'], 2, ',', ' '), 
        'date' => date('d/m H:i', strtotime($last_bid['date_enchere']))
    ] : null
]);
?>