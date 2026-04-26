<?php
require_once 'config.php';
require_once 'cron_terminer_encheres.php';

// Sécurité de l'ID
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

 $req = $pdo->prepare("SELECT p.*, c.nom as categorie_nom FROM produits p LEFT JOIN categories c ON p.categorie_id = c.id WHERE p.id = ?");
 $req->execute([$_GET['id']]);
 $produit = $req->fetch();
 // Récupération de l'historique des enchères pour ce produit (Jointure pour avoir le nom de l'utilisateur)
 $hist_req = $pdo->prepare("
    SELECT e.montant, e.date_enchere, u.nom 
    FROM encheres e 
    JOIN utilisateurs u ON e.utilisateur_id = u.id 
    WHERE e.produit_id = ? 
    ORDER BY e.date_enchere DESC
");
 $hist_req->execute([$_GET['id']]);
 $historique = $hist_req->fetchAll();
if(!$produit) {
    echo "<h1 style='text-align:center;margin-top:50px;'>Ce produit n'existe pas.</h1>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($produit['nom']) ?></title>
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <style>
        #mobile-btn { display: none; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { display: flex; min-height: 100vh; background: #f4f4f4; }

        /* --- SIDEBAR & HEADER (Identique à index.php) --- */
        .sidebar { width: 78px; background: #11101D; position: fixed; height: 100vh; z-index: 100; display: flex; flex-direction: column; transition: width 0.3s ease; }
        .sidebar.open { width: 250px; }
        .sidebar .logo-details { height: 60px; display: flex; align-items: center; padding: 0 15px; color: #fff; }
        .sidebar .logo-details .logo_name { opacity: 0; margin-left: 15px; white-space: nowrap; transition: opacity 0.3s; }
        .sidebar.open .logo_name { opacity: 1; }
        .sidebar .logo-details #btn { margin-left: auto; cursor: pointer; font-size: 25px; }
        .sidebar .nav-list { list-style: none; margin-top: 20px; }
        .sidebar .nav-list li a { display: flex; align-items: center; height: 50px; padding: 0 20px; color: #fff; text-decoration: none; transition: 0.3s; }
        .sidebar .nav-list li a i { font-size: 20px; min-width: 50px; text-align: center; }
        .sidebar .nav-list li a .links_name { opacity: 0; white-space: nowrap; transition: opacity 0.3s; }
        .sidebar.open .nav-list li a .links_name { opacity: 1; }
        .sidebar .nav-list li a:hover { background: #fff; color: #11101D; }

        .main-content { margin-left: 78px; flex: 1; display: flex; flex-direction: column; transition: margin-left 0.3s ease; }
        .sidebar.open ~ .main-content { margin-left: 250px; }
        
        header { background: #fff; height: 60px; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 50; }
        .user-area { display: flex; align-items: center; gap: 15px; font-size: 14px; }
        .user-area a { text-decoration: none; color: #333; font-weight: 500; }
        .user-area .logout { color: crimson; }

        /* --- LAYOUT PRODUIT --- */
        .product-layout { display: flex; gap: 30px; padding: 30px; max-width: 1200px; margin: 0 auto; width: 100%; }
        .product-image { flex: 1; background: #fff; border-radius: 8px; padding: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .product-image img { width: 100%; height: 450px; object-fit: cover; border-radius: 8px; }
        
        .product-details { flex: 1; display: flex; flex-direction: column; gap: 20px; }
        .product-info { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .product-info .cat-badge { font-size: 12px; color: #664AFF; background: #e8e5ff; padding: 3px 8px; border-radius: 4px; display: inline-block; margin-bottom: 10px; }
        .product-info h1 { font-size: 22px; color: #222; margin-bottom: 10px; }
        .product-info p { color: #555; line-height: 1.6; font-size: 14px; }

        /* --- BOX ENCHÈRE --- */
        .auction-box { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-top: 4px solid crimson; }
        .auction-status { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .current-price { font-size: 28px; font-weight: bold; color: #222; }
        .current-price span { color: crimson; }
        
        .timer-box { background: #11101D; color: #fff; padding: 15px; border-radius: 6px; text-align: center; margin-bottom: 20px; }
        .timer-box .label { font-size: 12px; color: #aaa; margin-bottom: 5px; }
        .timer-box .time { font-size: 24px; font-weight: bold; font-family: monospace; letter-spacing: 2px; }

        .bid-form { display: flex; gap: 10px; }
        .bid-form input { flex: 1; padding: 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; }
        .bid-form button { padding: 12px 25px; background: crimson; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; transition: 0.2s; }
        .bid-form button:hover { background: darkred; }
        
        #bid-message { margin-top: 15px; font-size: 14px; font-weight: 500; text-align: center; padding: 10px; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; text-decoration: none; display: block;}


        .history-box { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-top: 0; }
        .history-box h3 { font-size: 16px; color: #333; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        .history-box ul { list-style: none; }
        .history-box li { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .history-box li:last-child { border-bottom: none; }
        .history-box .user-name { font-weight: 500; color: #333; flex: 1; }
        .history-box .bid-amount { font-weight: bold; color: crimson; margin: 0 15px; }
        .history-box .bid-date { color: #888; font-size: 12px; }
        .history-box li.empty { justify-content: center; color: #888; font-style: italic; border: none; padding: 20px 0; }

        /* Animation quand une nouvelle enchère apparaît */
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .new-bid { animation: slideIn 0.3s ease; background: #f9f9f9; border-radius: 4px; padding: 10px !important; margin: 5px 0; }


        /* --- RESPONSIVE MOBILE --- */
        @media screen and (max-width: 768px) {
            /* Afficher le bouton hamburger du header */
            #mobile-btn {
                display: block !important;
                font-size: 25px;
                cursor: pointer;
            }

            /* Cacher le bouton hamburger qui est DANS la sidebar (pour éviter les doublons) */
            .sidebar .logo-details #btn { 
                display: none; 
            }
            /* 1. LA SIDEBAR : Cachée à gauche, pas de largeur réduite */
            .sidebar {
                left: -250px; 
                width: 250px; 
            }
            .sidebar.open {
                left: 0; 
            }

            /* 2. LE CONTENU : On force le retour à 0 et on tue l'animation */
            .main-content {
                margin-left: 0 !important; 
                width: 100%;
                transition: none !important; /* C'EST LÀ LE FIX QUI EMPÊCHE DE POUSSER */
            }
            .sidebar.open ~ .main-content {
                margin-left: 0 !important; 
            }
            
            /* 3. LE FOND SOMBRE */
            .sidebar.open ~ .main-content::before {
                content: '';
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 99;
            }

            /* 4. HEADER & GRILLE */
            header {
                flex-direction: column;
                height: auto;
                padding: 10px;
                gap: 10px;
            }
            .search-box input { width: 100%; }
            
            .product-grid {
                padding: 15px;
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }

            .product-layout {
                flex-direction: column;
                padding: 15px;
                gap: 15px;
            }
            .product-image img { height: 250px; }
            .bid-form { flex-direction: column; }
        }

        @media screen and (max-width: 480px) {
            .product-grid {
                grid-template-columns: 1fr; /* Forcé 1 colonne sur tout petits écrans */
            }
        }
    </style>
</head>
<body>

    <div class="sidebar" id="sidebar">
        <div class="logo-details">
            <i class='bx bx-menu' id="btn"></i>
            <span class="logo_name">Enchères</span>
        </div>
        <ul class="nav-list">
            <li>
                <a href="index.php">
                    <i class='bx bx-grid-alt'></i>
                    <span class="links_name">Accueil</span>
                </a>
            </li>
            <li>
                <a href="index.php">
                    <i class='bx bx-category'></i>
                    <span class="links_name">Catégories</span>
                </a>
            </li>
            
            <!-- LE BOUTON POUR CRÉER -->
            <?php if(isset($_SESSION['user_id'])): ?>
            <li>
                <a href="creer_enchere.php">
                    <i class='bx bx-plus-circle'></i>
                    <span class="links_name">Créer une enchère</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="main-content">
        <header>
              <!-- BOUTON MENU MOBILE (Caché sur Desktop) -->
            <i class='bx bx-menu' id="mobile-btn"></i>
            <h2>Détail de l'enchère</h2>
            <div class="user-area">
                <?php if(isset($_SESSION['user_nom'])): ?>
                    <span>Bonjour, <strong><?= htmlspecialchars($_SESSION['user_nom']) ?></strong></span>
                    <a href="auth.php?action=logout" class="logout"><i class='bx bx-log-out'></i> Deconnexion</a>
                <?php else: ?>
                    <a href="login.php"><i class='bx bx-log-in'></i> Connexion</a>
                <?php endif; ?>
            </div>
        </header>

        <div class="product-layout">
            <div class="product-image">
                <img src="img-prod/<?= htmlspecialchars($produit['image_url']) ?>" alt="<?= htmlspecialchars($produit['nom']) ?>">
            </div>
            
            <div class="product-details">
                <div class="product-info">
                    <?php if($produit['categorie_nom']): ?>
                        <span class="cat-badge"><?= htmlspecialchars($produit['categorie_nom']) ?></span>
                    <?php endif; ?>
                    <h1><?= htmlspecialchars($produit['nom']) ?></h1>
                    <p><?= nl2br(htmlspecialchars($produit['description'])) ?></p>
                </div>

                <div class="auction-box">
                    <div class="auction-status">
                        <div class="current-price">Prix : <span id="current-price"><?= number_format($produit['prix_actuel'], 2, ',', ' ') ?> €</span></div>
                        <?php if($produit['statut'] === 'terminee'): ?>
                            <span style="background:crimson; color:#fff; padding:5px 10px; border-radius:4px; font-size:12px; font-weight:bold;">VENTE TERMINÉE</span>
                        <?php endif; ?>
                    </div>

                    <div class="timer-box">
                        <div class="label">Statut</div>
                        <div class="time" id="countdown" style="<?= $produit['statut'] === 'terminee' ? 'color: #ff4d4d;' : '' ?>">
                            <?= $produit['statut'] === 'terminee' ? 'TERMINÉE' : 'Chargement...' ?>
                        </div>
                    </div>

                    <?php if($produit['statut'] === 'en_cours' && isset($_SESSION['user_id'])): ?>
                        <form class="bid-form" id="bid-form">
                            <input type="hidden" id="produit_id" value="<?= $produit['id'] ?>">
                            <input type="number" id="montant" placeholder="Votre offre..." step="0.01" required>
                            <button type="submit">Enchérir</button>
                        </form>
                    <?php elseif($produit['statut'] === 'terminee'): ?>
                        <div style="text-align:center; padding:10px; background:#f8d7da; color:#721c24; border-radius:4px;">
                            Cette enchère est terminée. Les offres ne sont plus acceptées.
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="warning">Connectez-vous pour participer aux enchères.</a>
                    <?php endif; ?>
                    
                    <div id="bid-message"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 1. LOGIQUE SIDEBAR
        const sidebar = document.querySelector('#sidebar');
        const btnDesktop = document.querySelector('.sidebar #btn');
        const btnMobile = document.querySelector('#mobile-btn');
        const mainContent = document.querySelector('.main-content');

        // Clic Desktop
        if(btnDesktop) {
            btnDesktop.onclick = () => sidebar.classList.toggle('open');
        }

        // Clic Mobile
        if(btnMobile) {
            btnMobile.onclick = () => sidebar.classList.toggle('open');
        }

        // Fermer le menu si on clique sur le fond sombre (sur mobile)
        mainContent.onclick = function(e) {
            // On vérifie qu'on a bien cliqué sur le fond et pas sur un élément cliquable
            if(e.target === mainContent && window.innerWidth <= 768 && sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            }
        };

        // 2. LE VRAI COMPTE À REBOURS
        // On passe la date de fin de PHP à Javascript
        const dateFin = new Date("<?= date('Y-m-d H:i:s', strtotime($produit['date_fin'])) ?>").getTime();

        const timerElement = document.getElementById('countdown');
        
        const updateTimer = () => {
            const maintenant = new Date().getTime();
            const difference = dateFin - maintenant;

            if (difference <= 0) {
                timerElement.innerText = "TERMINÉE";
                timerElement.style.color = "#ff4d4d";
                // On cache le formulaire AJAX s'il était là (au cas où le cron n'a pas encore tourné)
                const form = document.getElementById('bid-form');
                if(form) form.style.display = 'none';
                
                const msg = document.getElementById('bid-message');
                if(msg) {
                    msg.className = 'error';
                    msg.innerText = "Temps écoulé !";
                }
                return; 
            }

            const jours = Math.floor(difference / (1000 * 60 * 60 * 24));
            const heures = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
            const secondes = Math.floor((difference % (1000 * 60)) / 1000);

            // Formatage joli (ex: 02j 14h 05m 32s)
            timerElement.innerText = 
                String(jours).padStart(2, '0') + "j " + 
                String(heures).padStart(2, '0') + "h " + 
                String(minutes).padStart(2, '0') + "m " + 
                String(secondes).padStart(2, '0') + "s";
        };

        // Lancement immédiat, puis toutes les secondes
        updateTimer();
        setInterval(updateTimer, 1000);

        // 3. LOGIQUE D'ENCHÈRE AJAX
        document.getElementById('bid-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const montant = document.getElementById('montant').value;
            const produitId = document.getElementById('produit_id').value;
            const messageDiv = document.getElementById('bid-message');
            const priceSpan = document.getElementById('current-price');
            
            fetch('traitement_enchere.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'produit_id=' + produitId + '&montant=' + montant
                    })
                    .then(response => response.json())
                    .then(data => {
                    messageDiv.className = data.success ? 'success' : 'error';
                    messageDiv.innerText = data.message;
                    
                    if(data.success) {
                        priceSpan.innerText = data.nouveau_prix + ' €';
                        document.getElementById('montant').value = '';
                        
                        // NOUVEAU : Ajouter l'enchère dans l'historique en temps réel
                        const historyList = document.getElementById('history-list');
                        
                        // On enlève le message "Aucune enchère" s'il est là
                        const emptyMsg = historyList.querySelector('.empty');
                        if(emptyMsg) emptyMsg.remove();

                        // Création de la nouvelle ligne
                        const newLi = document.createElement('li');
                        newLi.className = 'new-bid';
                        
                        // On récupère le nom de l'utilisateur connecté depuis le header
                        const userName = "<?= isset($_SESSION['user_nom']) ? htmlspecialchars($_SESSION['user_nom']) : 'Moi' ?>";
                        
                        // Formatage de la date actuelle
                        const now = new Date();
                        const timeStr = String(now.getDate()).padStart(2,'0') + '/' + String(now.getMonth()+1).padStart(2,'0') + ' ' + String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');

                        newLi.innerHTML = `
                            <span class="user-name">${userName}</span>
                            <span class="bid-amount">${data.nouveau_prix} €</span>
                            <span class="bid-date">${timeStr}</span>
                        `;
                        
                        // On l'insère en haut de la liste
                        historyList.insertBefore(newLi, historyList.firstChild);
                              }
                          })
                          .catch(() => {
                              messageDiv.className = 'error';
                              messageDiv.innerText = "Erreur de connexion au serveur.";
                          });
              });

              // 4. TEMPS RÉEL (Polling toutes les 5 secondes)
            const produitId = "<?= $_GET['id'] ?>";
            let lastKnownPrice = document.getElementById('current-price').innerText.replace(/\s/g, '').replace('€', '');

            setInterval(() => {
                fetch(`get_live_data.php?id=${produitId}`)
                    .then(res => res.json())
                    .then(data => {
                        if(data.error) return;

                        // Si le statut a changé (quelqu'un a cloturé l'enchère depuis l'admin)
                        if(data.statut === 'terminee') {
                            location.reload(); // On recharge pour bloquer l'interface
                            return;
                        }

                        // Si le prix en BDD est différent du prix affiché
                        const newPrice = data.prix_actuel.replace(/\s/g, '').replace('€', '');
                        if(newPrice !== lastKnownPrice) {
                            // On met à jour le prix
                            document.getElementById('current-price').innerText = data.prix_actuel + ' €';
                            lastKnownPrice = newPrice;

                            // S'il y a une nouvelle enchère d'un autre utilisateur
                            if(data.last_bid) {
                                const historyList = document.getElementById('history-list');
                                const emptyMsg = historyList.querySelector('.empty');
                                if(emptyMsg) emptyMsg.remove();

                                // On vérifie si cette enchère n'est pas déjà affichée (pour éviter les doublons)
                                const firstLi = historyList.querySelector('li');
                                if(!firstLi || firstLi.innerText.includes(data.last_bid.nom) && firstLi.innerText.includes(data.last_bid.montant)) {
                                    const newLi = document.createElement('li');
                                    newLi.className = 'new-bid';
                                    newLi.innerHTML = `
                                        <span class="user-name">${data.last_bid.nom}</span>
                                        <span class="bid-amount">${data.last_bid.montant} €</span>
                                        <span class="bid-date">${data.last_bid.date}</span>
                                    `;
                                    historyList.insertBefore(newLi, historyList.firstChild);
                                }
                            }
                        }
                    });
            }, 5000); // 5000 millisecondes = 5 secondes
    </script>
</body>
</html>