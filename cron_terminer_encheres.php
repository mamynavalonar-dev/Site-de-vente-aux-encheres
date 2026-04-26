<?php
require_once 'config.php';

// On cherche toutes les enchères "en_cours" dont la date de fin est dépassée
 $req = $pdo->prepare("SELECT id FROM produits WHERE statut = 'en_cours' AND date_fin <= NOW()");
 $req->execute();
 $encheres_terminees = $req->fetchAll();

if(count($encheres_terminees) > 0) {
    // On les passe toutes à "terminee"
    $ids = array_column($encheres_terminees, 'id');
    $in  = str_repeat('?,', count($ids) - 1) . '?';
    
    $update = $pdo->prepare("UPDATE produits SET statut = 'terminee' WHERE id IN ($in)");
    $update->execute($ids);
}
?>