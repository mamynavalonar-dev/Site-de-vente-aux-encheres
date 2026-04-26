<?php
require_once 'config.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if($action === 'register') {
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if(empty($nom) || empty($email) || empty($password)) {
            header('Location: login.php?error=empty');
            exit;
        }

        // Vérifier si l'email existe déjà
        $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $check->execute([$email]);
        if($check->fetch()) {
            header('Location: login.php?error=exists');
            exit;
        }

        // Hasher le mot de passe (SÉCURITÉ DE BASE)
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $insert = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe) VALUES (?, ?, ?)");
        if($insert->execute([$nom, $email, $hash])) {
            header('Location: login.php?success=registered');
            exit;
        }
    }

    if($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $req = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $req->execute([$email]);
        $user = $req->fetch();

        // Vérifier si l'utilisateur existe ET si le mot de passe correspond
        if($user && password_verify($password, $user['mot_de_passe'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['is_admin'] = $user['is_admin'];
            header('Location: index.php'); // Redirection vers l'accueil une fois connecté
            exit;
        } else {
            header('Location: login.php?error=invalid');
            exit;
        }
    }
}

// Déconnexion
if(isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>