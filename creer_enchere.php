<?php
require_once __DIR__ . '/bootstrap.php';

$user = app_require_user(false);
$csrf = app_csrf_token();

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
            <p style="margin-bottom:18px"><a href="mes_annonces.php">Mes annonces</a></p>
            <h1><i class='bx bx-add-to-queue'></i> Créer une nouvelle enchère</h1>
            
            <div id="form-message" class="msg" role="status" aria-live="polite"></div>

            <form action="traitement_creation.php" method="POST" enctype="multipart/form-data" id="create-form">
                <input type="hidden" name="csrf_token" value="<?= app_escape($csrf) ?>">
                <div class="form-group">
                    <label for="nom">Nom du produit</label>
                    <input type="text" name="nom" id="nom" maxlength="180" required>
                </div>

                <div class="form-group">
                    <label for="categorie_id">Catégorie</label>
                    <select name="categorie_id" id="categorie_id" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" maxlength="5000" required></textarea>
                </div>

                <div class="form-group">
                    <label for="prix_depart">Prix de départ (€)</label>
                    <input type="number" name="prix_depart" id="prix_depart" step="0.01" min="0.01" max="9999999999999.99" required>
                </div>

                <div class="form-group">
                    <label for="date_fin">Date et heure de fin</label>
                    <input type="datetime-local" name="date_fin" id="date_fin" required>
                </div>

                <div class="form-group">
                    <label for="image">Photo du produit (JPEG, PNG ou WebP, 5 Mo maximum)</label>
                    <input type="file" name="image" id="image" accept="image/jpeg, image/png, image/webp" required>
                </div>

                <button type="submit" class="btn-submit">Mettre en ligne</button>
            </form>
        </div>
    </div>

    <script>
        const form = document.getElementById('create-form');
        const button = form.querySelector('button[type="submit"]');
        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            if (button.disabled) return;
            const message = document.getElementById('form-message');
            button.disabled = true;
            let navigating = false;
            try {
                const response = await fetch(form.action, { method: 'POST', body: new FormData(form) });
                const data = await response.json();
                if (!response.ok || data.success !== true) throw new Error(data.message || 'Publication impossible.');
                if (!Number.isSafeInteger(data.id) || data.id < 1) throw new Error('Vérifiez la publication dans « Mes annonces ».');
                message.className = 'msg success';
                message.textContent = data.message;
                navigating = true;
                window.location.assign('detprod.php?id=' + data.id);
            } catch (error) {
                message.className = 'msg error';
                message.textContent = error instanceof TypeError ? 'Connexion interrompue. Vérifiez « Mes annonces » avant de réessayer.' : error.message;
            } finally {
                if (!navigating) button.disabled = false;
            }
        });
    </script>
</body>
</html>
