<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/cron_terminer_encheres.php';
$user = app_current_user();
$csrf = app_csrf_token();
$categories = $pdo->query('SELECT id, nom FROM categories ORDER BY nom')->fetchAll();
$category = isset($_GET['categorie']) ? filter_var($_GET['categorie'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null;
if ($category === false) { http_response_code(400); exit('Catégorie invalide.'); }
$favoriteIds = [];
if ($user) {
    $query = $pdo->prepare('SELECT produit_id FROM favoris WHERE utilisateur_id = ?');
    $query->execute([$user['id']]);
    $favoriteIds = array_map('intval', $query->fetchAll(PDO::FETCH_COLUMN));
}
$slides = $pdo->query("SELECT id, nom, prix_actuel FROM produits WHERE statut = 'en_cours' AND date_fin > NOW() AND archive_le IS NULL ORDER BY date_fin DESC, id DESC LIMIT 3")->fetchAll();
$statement = "SELECT p.*, c.nom AS categorie_nom FROM produits p LEFT JOIN categories c ON p.categorie_id = c.id WHERE p.statut = 'en_cours' AND p.date_fin > NOW() AND p.archive_le IS NULL";
$params = [];
$title = 'Toutes les enchères';
if ($category !== null) {
    $statement .= ' AND p.categorie_id = ?';
    $params[] = $category;
    $title = 'Catégorie introuvable';
    foreach ($categories as $cat) {
        if ((int) $cat['id'] === $category) { $title = 'Catégorie : ' . $cat['nom']; break; }
    }
}
$query = $pdo->prepare($statement . ' ORDER BY p.date_fin DESC, p.id DESC LIMIT 50');
$query->execute($params);
$produits = $query->fetchAll();
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
            
            <?php if($user): ?>
                <li>
                    <a href="creer_enchere.php">
                        <i class='bx bx-plus-circle'></i>
                        <span class="links_name">Créer une enchère</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($user): ?>
            <li><a href="mes_annonces.php"><i class="bx bx-list-ul"></i><span class="links_name">Mes annonces</span></a></li>
            <?php if ((int) $user['is_admin'] === 1): ?><li><a href="admin.php"><i class="bx bx-shield"></i><span class="links_name">Administration</span></a></li><?php endif; ?>
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
                        <span class="links_name"><?= app_escape($cat['nom']) ?></span>
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
                <input type="search" aria-label="Rechercher un produit" maxlength="100" placeholder="Rechercher un produit...">
            </div>
            <div class="user-area">
                <?php if($user): ?>
                    <span>Bonjour, <strong><?= app_escape($user['nom']) ?></strong></span>
                    <?php app_logout_form(); ?>
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
                        <img src="photo.php?id=<?= (int) $slide['id'] ?>" alt="">
                        <div class="slide-content">
                            <h2><?= app_escape($slide['nom']) ?></h2>
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

        <h1 class="page-title"><?= app_escape($title) ?></h1>
        <p id="catalog-message" role="status" aria-live="polite" style="padding:12px 30px 0"></p>

        <div class="product-grid">
            <?php if(count($produits) > 0): ?>
                <?php foreach($produits as $prod): ?>
                    <div class="card">
                        <img src="photo.php?id=<?= (int) $prod['id'] ?>" alt="">
                        <div class="card-content">
                            <!-- NOUVEAU : Bouton Favori -->
                            <?php if($user): ?>
                                <button type="button" class="fav-btn" aria-label="Modifier les favoris" aria-pressed="<?= in_array((int) $prod['id'], $favoriteIds, true) ? 'true' : 'false' ?>" data-favori="<?= in_array((int) $prod['id'], $favoriteIds, true) ? '1' : '0' ?>" data-id="<?= (int) $prod['id'] ?>" onclick="toggleFavori(<?= (int) $prod['id'] ?>, this)">
                                    <i class='bx <?= in_array((int) $prod['id'], $favoriteIds, true) ? 'bxs-heart' : 'bx-heart' ?>' style="font-size: 24px; color: <?= in_array((int) $prod['id'], $favoriteIds, true) ? 'red' : '#aaa' ?>;"></i>
                                </button>
                            <?php endif; ?>

                            <?php if($prod['categorie_nom']): ?>
                                <span class="cat-badge"><?= app_escape($prod['categorie_nom']) ?></span>
                            <?php endif; ?>

                            <h3><?= app_escape($prod['nom']) ?></h3>
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
        const config = <?= json_encode(['csrf' => $csrf, 'authenticated' => (bool) $user, 'category' => $category, 'favorites' => $favoriteIds], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const sidebar = document.getElementById('sidebar');
        document.querySelector('.sidebar #btn')?.addEventListener('click', () => sidebar.classList.toggle('open'));
        document.getElementById('mobile-btn')?.addEventListener('click', () => sidebar.classList.toggle('open'));
        const mainContent = document.querySelector('.main-content');
        mainContent.addEventListener('click', event => {
            if (event.target === mainContent && window.innerWidth <= 768) sidebar.classList.remove('open');
        });
        const grid = document.querySelector('.product-grid');
        const title = document.querySelector('.page-title');
        const message = document.getElementById('catalog-message');
        const initialTitle = title.textContent;
        const initialCards = [...grid.childNodes].map(node => node.cloneNode(true));
        const favorites = new Map(config.favorites.map(id => [Number(id), true]));
        const pendingFavorites = new Set();
        function showFavorite(button, state) {
            button.dataset.favori = state ? '1' : '0';
            button.setAttribute('aria-pressed', String(state));
            button.setAttribute('aria-label', state ? 'Retirer des favoris' : 'Ajouter aux favoris');
            const icon = button.querySelector('i');
            icon.className = state ? 'bx bxs-heart' : 'bx bx-heart';
            icon.style.color = state ? 'red' : '#aaa';
        }
        function refreshFavorites() {
            grid.querySelectorAll('.fav-btn').forEach(button => {
                const id = Number(button.dataset.id);
                showFavorite(button, favorites.has(id) ? favorites.get(id) : button.dataset.favori === '1');
                button.disabled = pendingFavorites.has(id);
            });
        }
        function element(tag, className, text) {
            const node = document.createElement(tag);
            if (className) node.className = className;
            if (text !== undefined) node.textContent = text;
            return node;
        }
        function displayResults(products, query) {
            grid.replaceChildren();
            title.textContent = 'Résultats pour « ' + query + ' »';
            if (products.length === 0) {
                grid.append(element('p', '', 'Aucun résultat pour « ' + query + ' ».'));
                return;
            }
            for (const product of products) {
                const id = Number(product.id);
                if (!Number.isSafeInteger(id) || id < 1) continue;
                const card = element('div', 'card');
                const image = element('img');
                image.src = 'photo.php?id=' + id;
                image.alt = '';
                const content = element('div', 'card-content');
                if (config.authenticated) {
                    const button = element('button', 'fav-btn');
                    button.type = 'button';
                    button.dataset.id = String(id);
                    button.append(element('i', 'bx bx-heart'));
                    const state = favorites.has(id) ? favorites.get(id) : Number(product.favori) === 1;
                    showFavorite(button, state);
                    button.disabled = pendingFavorites.has(id);
                    button.addEventListener('click', () => toggleFavori(id, button));
                    content.append(button);
                }
                if (product.categorie_nom) content.append(element('span', 'cat-badge', product.categorie_nom));
                content.append(element('h3', '', product.nom));
                const meta = element('div', 'meta');
                meta.append(element('span', 'price', Number(product.prix_actuel).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'));
                const date = String(product.date_fin).slice(0, 10).split('-').reverse().join('/');
                meta.append(element('span', 'end-date', 'Fin le ' + date));
                const link = element('a', 'btn-view', 'Voir l’enchère');
                link.href = 'detprod.php?id=' + id;
                content.append(meta, link);
                card.append(image, content);
                grid.append(card);
            }
        }
        const input = document.querySelector('.search-box input');
        let timer, controller, sequence = 0;
        input.addEventListener('input', () => {
            clearTimeout(timer);
            controller?.abort();
            const current = ++sequence;
            const query = input.value.trim();
            message.textContent = '';
            if (query.length < 2) {
                title.textContent = initialTitle;
                grid.replaceChildren(...initialCards.map(node => node.cloneNode(true)));
                refreshFavorites();
                return;
            }
            timer = setTimeout(async () => {
                controller = new AbortController();
                const params = new URLSearchParams({ q: query });
                if (config.category !== null) params.set('categorie', String(config.category));
                try {
                    const response = await fetch('recherche.php?' + params, { signal: controller.signal });
                    const data = await response.json();
                    if (!response.ok || !Array.isArray(data)) throw new Error(data.message || 'Recherche indisponible.');
                    if (current === sequence) displayResults(data, query);
                } catch (error) {
                    if (error.name !== 'AbortError' && current === sequence) message.textContent = 'La recherche a échoué. Réessayez.';
                }
            }, 300);
        });
        const slides = document.querySelectorAll('.slide');
        if (slides.length > 1) {
            let currentSlide = 0;
            setInterval(() => {
                slides[currentSlide].classList.remove('active');
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].classList.add('active');
            }, 5000);
        }
        async function toggleFavori(id, button) {
            if (pendingFavorites.has(id)) return;
            const desired = button.dataset.favori !== '1';
            pendingFavorites.add(id);
            refreshFavorites();
            message.textContent = '';
            try {
                const response = await fetch('traitement_favori.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ produit_id: id, favori: desired ? '1' : '0', csrf_token: config.csrf })
                });
                const data = await response.json();
                if (!response.ok || data.success !== true || typeof data.favori !== 'boolean') throw new Error(data.message || 'Modification impossible.');
                favorites.set(id, data.favori);
            } catch (error) {
                message.textContent = error instanceof TypeError ? 'Connexion interrompue. Actualisez la page pour vérifier vos favoris.' : error.message;
            } finally {
                pendingFavorites.delete(id);
                refreshFavorites();
            }
        }
        refreshFavorites();
    </script>
</body>
</html>
