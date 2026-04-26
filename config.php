<?php
// config.php
session_start();

 $host = 'localhost';
 $dbname = 'enchere_db';
 $username = 'root'; // Ton utilisateur PHPMyAdmin
 $password = ''; // Ton mot de passe (souvent vide sur XAMPP/WAMP)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // active les erreurs PDO pour le debug
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>