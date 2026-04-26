<?php
require_once 'config.php';

// SÉCURITÉ : Vérifier si l'utilisateur est connecté
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour enchérir.']);
    exit;
}

header('Content-Type: application/json'); // On dit qu'on renvoie du JSON



// On vérifie que c'est bien une requête POST
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

 $produit_id = $_POST['produit_id'] ?? null;
 $montant = $_POST['montant'] ?? null;

// On vérifie que ce sont des nombres valides
if(!$produit_id || !is_numeric($produit_id) || !$montant || !is_numeric($montant)) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

 $montant = floatval($montant);

// 1. On récupère le produit EN LECTURE ECRITURE pour éviter les Race Conditions
 $req = $pdo->prepare("SELECT prix_actuel, date_fin FROM produits WHERE id = ? FOR UPDATE");
 $req->execute([$produit_id]);
 $produit = $req->fetch();

if(!$produit) {
    echo json_encode(['success' => false, 'message' => 'Produit introuvable']);
    exit;
}

// 2. Vérification de la date
if(strtotime($produit['date_fin']) < time()) {
    echo json_encode(['success' => false, 'message' => 'L\'enchère est terminée !']);
    exit;
}

// 3. LA VÉRIFICATION FATALE (Celle qui tue la triche)
if($montant <= $produit['prix_actuel']) {
    echo json_encode(['success' => false, 'message' => 'Votre offre ('.$montant.' €) doit être strictement supérieure au prix actuel ('.$produit['prix_actuel'].' €)']);
    exit;
}

// 4. Si on est arrivés ici, c'est que c'est valide. On met à jour la BDD
 $update = $pdo->prepare("UPDATE produits SET prix_actuel = ? WHERE id = ?");
if($update->execute([$montant, $produit_id])) {
    // NOUVEAU : Enregistrer qui a fait l'enchère dans la table 'encheres'
    $req_enchere = $pdo->prepare("INSERT INTO encheres (produit_id, utilisateur_id, montant) VALUES (?, ?, ?)");
    $req_enchere->execute([$produit_id, $_SESSION['user_id'], $montant]);    

    // On renvoie le succès et le NOUVEAU prix formaté pour le JS
    $nouveau_prix = number_format($montant, 2, ',', ' ');
    echo json_encode(['success' => true, 'message' => 'Enchère réussie !', 'nouveau_prix' => $nouveau_prix]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement']);
}
?>