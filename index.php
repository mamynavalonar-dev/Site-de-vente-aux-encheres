<?php
require_once 'config.php';
require_once 'cron_terminer_encheres.php';

// 1. Récupération de TOUTES les catégories pour le menu
 $categories = $pdo->query("SELECT * FROM categories")->fetchAll();

// Récupération des favoris de l'utilisateur (si connecté)
 $_SESSION['user_fav_ids'] = [];
if(isset($_SESSION['user_id'])) {
    $fav_req = $pdo->prepare("SELECT produit_id FROM favoris WHERE utilisateur_id = ?");
    $fav_req->execute([$_SESSION['user_id']]);
    foreach($fav_req->fetchAll() as $f) {
        $_SESSION['user_fav_ids'][] = $f['produit_id'];
    }
}

// --- NOUVEAU : Récupération des 3 derniers produits pour le Slider ---
 $req_slider = $pdo->query("SELECT id, nom, image_url, prix_actuel FROM produits WHERE statut = 'en_cours' ORDER BY date_fin DESC LIMIT 3");
 $slides = $req_slider->fetchAll();
// ----------------------------------------------------------------

// 2. Récupération des produits (Filtrage dynamique)
if(isset($_GET['categorie']) && is_numeric($_GET['categorie'])) {
    $req = $pdo->prepare("SELECT p.*, c.nom as categorie_nom FROM produits p LEFT JOIN categories c ON p.categorie_id = c.id WHERE p.categorie_id = ? AND p.statut = 'en_cours' ORDER BY p.date_fin DESC");
    $req->execute([$_GET['categorie']]);
} else {
    $req = $pdo->query("SELECT p.*, c.nom as categorie_nom FROM produits p LEFT JOIN categories c ON p.categorie_id = c.id WHERE p.statut = 'en_cours' ORDER BY p.date_fin DESC");
}
 $produits = $req->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes enchères</title>
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <style>
        #mobile-btn { display: none; }
        /* --- RESET & BASE --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { display: flex; min-height: 100vh; background: #f4f4f4; }

        .card-content { position: relative; /* Important pour positionner le coeur */ }
        .fav-btn { 
            position: absolute; top: 10px; right: 10px; background: none; border: none; cursor: pointer; 
            display: flex; align-items: center; justify-content: center; width: 35px; height: 35px; 
            border-radius: 50%; background: rgba(255,255,255,0.8); transition: 0.2s; 
        }
        .fav-btn:hover { transform: scale(1.2); }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 78px; background: #11101D; position: fixed; height: 100vh; z-index: 100;
            display: flex; flex-direction: column; transition: width 0.3s ease; overflow-x: hidden;
        }
        .sidebar.open { width: 250px; }
        .sidebar .logo-details { height: 60px; display: flex; align-items: center; padding: 0 15px; color: #fff; }
        .sidebar .logo-details .logo_name { opacity: 0; margin-left: 15px; white-space: nowrap; transition: opacity 0.3s; }
        .sidebar.open .logo_name { opacity: 1; }
        .sidebar .logo-details #btn { margin-left: auto; cursor: pointer; font-size: 25px; }
        
        .sidebar .nav-list { list-style: none; margin-top: 20px; }
        .sidebar .nav-list li a {
            display: flex; align-items: center; height: 50px; padding: 0 20px; color: #fff;
            text-decoration: none; transition: 0.3s; white-space: nowrap;
        }
        .sidebar .nav-list li a i { font-size: 20px; min-width: 50px; text-align: center; }
        .sidebar .nav-list li a .links_name { opacity: 0; transition: opacity 0.3s; }
        .sidebar.open .nav-list li a .links_name { opacity: 1; }
        .sidebar .nav-list li a:hover { background: #fff; color: #11101D; }
        .sidebar .nav-list li a.active { background: #664AFF; color: #fff; } /* Catégorie active */

        /* --- CONTENU PRINCIPAL --- */
        .main-content { margin-left: 78px; flex: 1; display: flex; flex-direction: column; transition: margin-left 0.3s ease; }
        .sidebar.open ~ .main-content { margin-left: 250px; }

        header { background: #fff; height: 60px; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 50; }
        .search-box { display: flex; align-items: center; background: #f1f1f1; border-radius: 20px; padding: 5px 15px; }
        .search-box input { border: none; background: transparent; outline: none; padding: 5px; width: 200px; }
        .user-area { display: flex; align-items: center; gap: 15px; font-size: 14px; }
        .user-area a { text-decoration: none; color: #333; font-weight: 500; }
        .user-area .logout { color: crimson; }

        .page-title { padding: 20px 30px 0; font-size: 20px; color: #333; }

        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; padding: 30px; }
        .card { background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
        .card img { width: 100%; height: 200px; object-fit: cover; }
        .card-content { padding: 15px; display: flex; flex-direction: column; flex: 1; }
        .card-content .cat-badge { font-size: 11px; color: #664AFF; background: #e8e5ff; padding: 3px 8px; border-radius: 4px; align-self: flex-start; margin-bottom: 8px; }
        .card-content h3 { font-size: 16px; margin-bottom: 10px; color: #222; }
        .card-content .meta { margin-top: auto; display: flex; justify-content: space-between; align-items: center; }
        .price { color: crimson; font-weight: bold; font-size: 18px; }
        .end-date { font-size: 12px; color: #666; }
        .btn-view { display: block; text-align: center; margin-top: 15px; padding: 10px; background: #007bff; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 500; transition: 0.2s; }
        .btn-view:hover { background: #0056b3; }


        /* --- SLIDER DYNAMIQUE --- */
        .slider-container {
            position: relative;
            width: 100%;
            height: 450px;
            overflow: hidden;
            background: #11101D;
        }
        .slide {
            position: absolute;
            top: 0; left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
            display: flex;
            align-items: flex-end;
        }
        .slide.active { opacity: 1; z-index: 1; }
        .slide img { position: absolute; width: 100%; height: 100%; object-fit: cover; filter: brightness(0.5); }
        .slide-content { position: relative; z-index: 2; color: #fff; padding: 40px; width: 100%; }
        .slide-content h2 { font-size: 32px; margin-bottom: 10px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
        .slide-price { font-size: 42px; font-weight: bold; color: crimson; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
        .btn-slider { display: inline-block; padding: 12px 30px; background: #fff; color: #111; text-decoration: none; border-radius: 4px; font-weight: bold; transition: 0.2s; }
        .btn-slider:hover { background: crimson; color: #fff; }
        @media screen and (max-width: 768px) {
            .slider-container { height: 300px; }
            .slide-content h2 { font-size: 20px; }
            .slide-price { font-size: 28px; }
        }



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
            
            <?php if(isset($_SESSION['user_id'])): ?>
                <li>
                    <a href="creer_enchere.php">
                        <i class='bx bx-plus-circle'></i>
                        <span class="links_name">Créer une enchère</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Lien Tous les produits -->
            <li>
                <a href="index.php" class="<?= !isset($_GET['categorie']) ? 'active' : '' ?>">
                    <i class='bx bx-grid-alt'></i><span class="links_name">Tous les produits</span>
                </a>
            </li>
            
            <!-- Menu Catégories Dynamique -->
            <?php foreach($categories as $cat): ?>
                <li>
                    <a href="index.php?categorie=<?= $cat['id'] ?>" class="<?= (isset($_GET['categorie']) && $_GET['categorie'] == $cat['id']) ? 'active' : '' ?>">
                        <i class='bx bx-folder'></i>
                        <span class="links_name"><?= htmlspecialchars($cat['nom']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="main-content">
        <header>
              <!-- BOUTON MENU MOBILE (Caché sur Desktop) -->
            <i class='bx bx-menu' id="mobile-btn"></i>
            <div class="search-box">
                <i class='bx bx-search'></i>
                <input type="text" placeholder="Rechercher un produit...">
            </div>
            <div class="user-area">
                <?php if(isset($_SESSION['user_nom'])): ?>
                    <span>Bonjour, <strong><?= htmlspecialchars($_SESSION['user_nom']) ?></strong></span>
                    <a href="auth.php?action=logout" class="logout"><i class='bx bx-log-out'></i> Déconnexion</a>
                <?php else: ?>
                    <a href="login.php"><i class='bx bx-log-in'></i> Connexion</a>
                <?php endif; ?>
            </div>
        </header>
        
         <!-- NOUVEAU : SLIDER DYNAMIQUE -->
        <div class="slider-container">
            <?php if(count($slides) > 0): ?>
                <?php foreach($slides as $index => $slide): ?>
                    <div class="slide <?= $index === 0 ? 'active' : '' ?>">
                        <img src="img-prod/<?= htmlspecialchars($slide['image_url']) ?>" alt="">
                        <div class="slide-content">
                            <h2><?= htmlspecialchars($slide['nom']) ?></h2>
                            <div class="slide-price"><?= number_format($slide['prix_actuel'], 2, ',', ' ') ?> €</div>
                            <a href="detprod.php?id=<?= $slide['id'] ?>" class="btn-slider">Enchérir</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="slide active">
                    <div class="slide-content" style="justify-content:center; text-align:center; width:100%;">
                        <h2>Bienvenue sur notre site d'enchères</h2>
                        <p>Ajoutez une enchère pour la voir ici !</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Titre dynamique selon la catégorie -->
        <div class="page-title">

        <!-- Titre dynamique selon la catégorie -->
        <div class="page-title">
            <?php 
                if(isset($_GET['categorie'])) {
                    $cat_req = $pdo->prepare("SELECT nom FROM categories WHERE id = ?");
                    $cat_req->execute([$_GET['categorie']]);
                    $cat_name = $cat_req->fetch();
                    echo "Catégorie : " . htmlspecialchars($cat_name['nom']);
                } else {
                    echo "Toutes les enchères";
                }
            ?>
        </div>

        <div class="product-grid">
            <?php if(count($produits) > 0): ?>
                <?php foreach($produits as $prod): ?>
                    <div class="card">
                        <img src="img-prod/<?= htmlspecialchars($prod['image_url']) ?>" alt="">
                        <div class="card-content">
                            <!-- NOUVEAU : Bouton Favori -->
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <button class="fav-btn" data-id="<?= $prod['id'] ?>" onclick="toggleFavori(<?= $prod['id'] ?>, this)">
                                    <i class='bx <?= isset($_SESSION['user_fav_ids']) && in_array($prod['id'], $_SESSION['user_fav_ids']) ? 'bxs-heart' : 'bx-heart' ?>' style="font-size: 24px; color: <?= isset($_SESSION['user_fav_ids']) && in_array($prod['id'], $_SESSION['user_fav_ids']) ? 'red' : '#aaa' ?>;"></i>
                                </button>
                            <?php endif; ?>

                            <?php if($prod['categorie_nom']): ?>
                                <span class="cat-badge"><?= htmlspecialchars($prod['categorie_nom']) ?></span>
                            <?php endif; ?>

                            <h3><?= htmlspecialchars($prod['nom']) ?></h3>
                            <div class="meta">
                                <span class="price"><?= number_format($prod['prix_actuel'], 2, ',', ' ') ?> €</span>
                                <span class="end-date">Fin le <?= date('d/m', strtotime($prod['date_fin'])) ?></span>
                            </div>
                            <a href="detprod.php?id=<?= $prod['id'] ?>" class="btn-view">Voir l'enchère</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <h2 style="grid-column: 1/-1; text-align:center; color:#666; margin-top:50px;">Aucune enchère dans cette catégorie.</h2>
            <?php endif; ?>
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

        // 2. LOGIQUE DE RECHERCHE EN TEMPS RÉEL
        const searchInput = document.querySelector('.search-box input');
        const productGrid = document.querySelector('.product-grid');
        const pageTitle = document.querySelector('.page-title');

        let typingTimer;

        searchInput.addEventListener('input', function() {
            clearTimeout(typingTimer);
            const query = this.value.trim();

            // Si on efface tout, on remet le titre par défaut et on recharge proprement
            if(query.length < 2) {
                pageTitle.innerText = "Toutes les enchères";
                // Au lieu de recharger la page, on relance la requête initiale vide
                fetch('recherche.php?q=')
                    .then(res => res.json())
                    .then(data => afficherResultats(data, ''));
                return;
            }

            typingTimer = setTimeout(() => {
                fetch('recherche.php?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        afficherResultats(data, query);
                    })
                    .catch(error => {
                        console.error("Erreur fetch:", error);
                        productGrid.innerHTML = '<h2 style="grid-column: 1/-1; text-align:center; color:red; margin-top:50px;">Erreur de connexion au serveur.</h2>';
                    });
            }, 300);
        });

                // Fonction séparée pour générer le HTML (plus propre)
                function afficherResultats(data, query) {
                    productGrid.innerHTML = ''; // On vide la grille

                    if(data.length === 0 && query.length >= 2) {
                        pageTitle.innerText = "Résultats de recherche";
                        productGrid.innerHTML = '<h2 style="grid-column: 1/-1; text-align:center; color:#666; margin-top:50px;">Aucun résultat pour "' + query + '"</h2>';
                        return;
                    }

                    if(query.length >= 2) {
                        pageTitle.innerText = 'Résultats pour "' + query + '"';
                    }

                    data.forEach(prod => {
                        const dateFin = new Date(prod.date_fin).getTime();
                        const maintenant = new Date().getTime();
                        const difference = dateFin - maintenant;
                        const dateTexte = difference > 0 ? 'Fin le ' + new Date(prod.date_fin).toLocaleDateString('fr-FR') : '<span style="color:red;">Terminée</span>';

                        const card = document.createElement('div');
                        card.className = 'card';
                        card.innerHTML = `
                            <img src="img-prod/${prod.image_url}" alt="">
                            <div class="card-content">
                                <h3>${prod.nom}</h3>
                                <div class="meta">
                                    <span class="price">${parseFloat(prod.prix_actuel).toLocaleString('fr-FR', {minimumFractionDigits: 2})} €</span>
                                    <span class="end-date">${dateTexte}</span>
                                </div>
                                <a href="detprod.php?id=${prod.id}" class="btn-view">Voir l'enchère</a>
                            </div>
                        `;
                        productGrid.appendChild(card);
                    });
                }
                        // 3. LOGIQUE SLIDER AUTOMATIQUE
        const slides = document.querySelectorAll('.slide');
        let currentSlide = 0;
        setInterval(() => {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }, 5000);


       
        // 4. LOGIQUE FAVORIS
        function toggleFavori(produitId, btn) {
            fetch('traitement_favori.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'produit_id=' + produitId
            })
            .then(res => res.json())
            .then(data => {
                const icon = btn.querySelector('i');
                if(data.success) {
                    // Si ajouté aux favoris
                    icon.className = 'bx bxs-heart';
                    icon.style.color = 'red';
                } else {
                    // Si retiré
                    icon.className = 'bx bx-heart';
                    icon.style.color = '#aaa';
                }
            });
        }
    </script>
</body>
</html>