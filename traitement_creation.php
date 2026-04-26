<?php
require_once 'config.php';
header('Content-Type: application/json');

// Sécurité
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

 $nom = trim($_POST['nom'] ?? '');
 $categorie_id = $_POST['categorie_id'] ?? '';
 $description = trim($_POST['description'] ?? '');
 $prix_depart = floatval($_POST['prix_depart'] ?? 0);
 $date_fin = $_POST['date_fin'] ?? '';

// 1. Validations de base
if(empty($nom) || empty($categorie_id) || empty($description) || $prix_depart <= 0 || empty($date_fin)) {
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont obligatoires.']);
    exit;
}

// 2. Vérification de la date (doit être dans le futur)
if(strtotime($date_fin) <= time()) {
    echo json_encode(['success' => false, 'message' => 'La date de fin doit être dans le futur.']);
    exit;
}

// 3. Gestion de l'upload de l'image
if(!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors du téléchargement de l\'image.']);
    exit;
}

 $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
if(!in_array($_FILES['image']['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Format d\'image invalide (JPG, PNG ou WEBP uniquement).']);
    exit;
}

// Générer un nom de fichier unique pour éviter les écrasements (ex: 654987.jpg)
 $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
 $fileName = uniqid() . '.' . $extension;
 $uploadPath = 'img-prod/' . $fileName;

if(!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
    echo json_encode(['success' => false, 'message' => 'Impossible de sauvegarder l\'image sur le serveur.']);
    exit;
}

// 4. Insertion en base de données
try {
    $insert = $pdo->prepare("INSERT INTO produits (nom, categorie_id, description, prix_depart, prix_actuel, date_fin, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if($insert->execute([$nom, $categorie_id, $description, $prix_depart, $prix_depart, $date_fin, $fileName])) {
        echo json_encode(['success' => true, 'message' => 'Enchère créée avec succès ! Redirection...']);
    } else {
        // Si la BDD échoue, on supprime l'image uploadée pour ne pas polluer le serveur
        unlink($uploadPath);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement en base de données.']);
    }
} catch(Exception $e) {
    unlink($uploadPath);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
}
?>