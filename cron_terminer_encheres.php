<?php
require_once __DIR__ . '/bootstrap.php';
$pdo->exec("UPDATE produits SET statut = 'terminee' WHERE statut = 'en_cours' AND date_fin <= NOW()");
// Ces compteurs sont temporaires ; aucun historique d'enchère n'est supprimé.
$pdo->exec('DELETE FROM auth_attempts WHERE window_start < NOW() - INTERVAL 1 DAY');
