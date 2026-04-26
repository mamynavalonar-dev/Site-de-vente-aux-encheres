<?php
require_once 'config.php';

// Sécurité : Si l'utilisateur n'est pas connecté, on le jette
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Récupération des catégories pour le menu déroulant
 $categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une enchère</title>
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { display: flex; min-height: 100vh; background: #f4f4f4; }

        /* Layout identique */
        .sidebar { width: 78px; background: #11101D; position: fixed; height: 100vh; z-index: 100; display: flex; flex-direction: column; transition: width 0.3s ease; }
        .sidebar.open { width: 250px; }
        .sidebar .logo-details { height: 60px; display: flex; align-items: center; padding: 0 15px; color: #fff; }
        .sidebar .logo-details .logo_name { opacity: 0; margin-left: 15px; white-space: nowrap; transition: opacity 0.3s; }
        .sidebar.open .logo_name { opacity: 1; }
        .sidebar .logo-details #btn { margin-left: auto; cursor: pointer; font-size: 25px; }
        .sidebar .nav-list { list-style: none; margin-top: 20px; }
        .sidebar .nav-list li a { display: flex; align-items: center; height: 50px; padding: 0 20px; color: #fff; text-decoration: none; transition: 0.3s; }
        .sidebar .nav-list li a i { font-size: 20px; min-width: 50px; text-align: center; }
        .sidebar .nav-list li a .links_name { opacity: 0; transition: opacity 0.3s; }
        .sidebar.open .nav-list li a .links_name { opacity: 1; }
        .sidebar .nav-list li a:hover { background: #fff; color: #11101D; }

        .main-content { margin-left: 78px; flex: 1; padding: 30px; transition: margin-left 0.3s ease; }
        .sidebar.open ~ .main-content { margin-left: 250px; }

        .form-container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-container h1 { margin-bottom: 25px; font-size: 22px; color: #333; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; outline: none;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #664AFF; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        
        .btn-submit { background: #664AFF; color: #fff; border: none; padding: 12px; width: 100%; border-radius: 4px; font-size: 16px; cursor: pointer; font-weight: bold; }
        .btn-submit:hover { background: #5035cc; }
        
        .msg { text-align: center; padding: 10px; margin-bottom: 20px; border-radius: 4px; font-size: 14px; display: none; }
        .msg.error { display: block; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .msg.success { display: block; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>

    <div class="sidebar" id="sidebar">
        <div class="logo-details">
            <a href="index.php" style="color:white; text-decoration:none; display:flex; align-items:center;">
                <i class='bx bx-arrow-back' id="btn" style="font-size: 25px; cursor:pointer;"></i>
                <span class="logo_name">Retour</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="form-container">
            <h1><i class='bx bx-add-to-queue'></i> Créer une nouvelle enchère</h1>
            
            <div id="form-message" class="msg"></div>

            <form action="traitement_creation.php" method="POST" enctype="multipart/form-data" id="create-form">
                <div class="form-group">
                    <label>Nom du produit</label>
                    <input type="text" name="nom" required>
                </div>

                <div class="form-group">
                    <label>Catégorie</label>
                    <select name="categorie_id" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label>Prix de départ (€)</label>
                    <input type="number" name="prix_depart" step="0.01" min="1" required>
                </div>

                <div class="form-group">
                    <label>Date et heure de fin</label>
                    <input type="datetime-local" name="date_fin" required>
                </div>

                <div class="form-group">
                    <label>Photo du produit</label>
                    <input type="file" name="image" accept="image/jpeg, image/png, image/webp" required>
                </div>

                <button type="submit" class="btn-submit">Mettre en ligne</button>
            </form>
        </div>
    </div>

    <script>
        const sidebar = document.querySelector('#sidebar');
        const btn = document.querySelector('#btn');
        btn.onclick = (e) => { e.preventDefault(); sidebar.classList.toggle('open'); };

        // Soumission en AJAX pour ne pas recharger la page si erreur
        document.getElementById('create-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const msgDiv = document.getElementById('form-message');
            const formData = new FormData(this); // Gère automatiquement l'upload de fichier

            fetch('traitement_creation.php', {
                method: 'POST',
                body: formData 
            })
            .then(response => response.json())
            .then(data => {
                msgDiv.className = 'msg ' + (data.success ? 'success' : 'error');
                msgDiv.innerText = data.message;
                
                if(data.success) {
                    // Redirige vers l'accueil après 2 secondes si succès
                    setTimeout(() => window.location.href = 'index.php', 2000);
                }
            })
            .catch(() => {
                msgDiv.className = 'msg error';
                msgDiv.innerText = "Erreur lors de l'envoi.";
            });
        });
    </script>
</body>
</html>