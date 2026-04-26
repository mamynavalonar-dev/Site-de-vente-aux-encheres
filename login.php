<?php
require_once 'config.php';
// Si déjà connecté, on le jette sur l'accueil
if(isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion / Inscription</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: #11101D; display: flex; justify-content: center; align-items: center; height: 100vh; color: #fff; }
        .box { background: #1d1b31; padding: 30px; border-radius: 8px; width: 350px; }
        h2 { margin-bottom: 20px; text-align: center; }
        input, button { width: 100%; padding: 10px; margin: 8px 0; border: none; border-radius: 4px; box-sizing: border-box; }
        input { background: #fff; color: #000; }
        button { background: crimson; color: #fff; cursor: pointer; font-weight: bold; }
        button:hover { background: darkred; }
        .msg { text-align: center; margin-bottom: 15px; font-size: 14px; padding: 8px; border-radius: 4px; }
        .error { background: #ff4d4d33; color: #ff4d4d; border: 1px solid #ff4d4d; }
        .success { background: #4dff4d33; color: #4dff4d; border: 1px solid #4dff4d; }
        .toggle { text-align: center; margin-top: 15px; font-size: 13px; color: #aaa; cursor: pointer; }
        .toggle a { color: crimson; text-decoration: none; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="box">
        <?php if(isset($_GET['error'])): ?>
            <div class="msg error">
                <?php 
                    if($_GET['error'] == 'empty') echo "Remplissez tous les champs.";
                    if($_GET['error'] == 'exists') echo "Cet email est déjà utilisé.";
                    if($_GET['error'] == 'invalid') echo "Email ou mot de passe incorrect.";
                ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['success'])): ?>
            <div class="msg success">Inscription réussie ! Connectez-vous.</div>
        <?php endif; ?>

        <!-- FORMULAIRE CONNEXION -->
        <form id="login-form" method="POST" action="auth.php">
            <input type="hidden" name="action" value="login">
            <h2>Connexion</h2>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
            <div class="toggle">Pas de compte ? <a href="#" onclick="toggleForms()">S'inscrire</a></div>
        </form>

        <!-- FORMULAIRE INSCRIPTION -->
        <form id="register-form" class="hidden" method="POST" action="auth.php">
            <input type="hidden" name="action" value="register">
            <h2>Inscription</h2>
            <input type="text" name="nom" placeholder="Nom complet" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Créer mon compte</button>
            <div class="toggle">Déjà un compte ? <a href="#" onclick="toggleForms()">Se connecter</a></div>
        </form>
    </div>

    <script>
        function toggleForms() {
            document.getElementById('login-form').classList.toggle('hidden');
            document.getElementById('register-form').classList.toggle('hidden');
        }
    </script>
</body>
</html>