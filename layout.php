<?php
require_once __DIR__ . '/bootstrap.php';

function app_icon(string $name): string
{
    $paths = [
        'hammer' => '<path d="m14 4 6 6-3 3-6-6zM9 9l6 6-3 3-6-6zM4 20l6-6M14 21h7"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'heart' => '<path d="M20.5 5.5a5 5 0 0 0-7 0L12 7l-1.5-1.5a5 5 0 0 0-7 7L12 21l8.5-8.5a5 5 0 0 0 0-7Z"/>',
        'user' => '<circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
        'arrow' => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'list' => '<path d="M8 5h13M8 12h13M8 19h13M3 5h.01M3 12h.01M3 19h.01"/>',
        'shield' => '<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6zM8 12l3 3 5-6"/>',
        'tag' => '<path d="M3 3h8l10 10-8 8L3 11z"/><circle cx="7.5" cy="7.5" r="1"/>',
        'chevron' => '<path d="m6 9 6 6 6-6"/>',
        'plus' => '<path d="M12 4v16M4 12h16"/>',
    ];
    return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['tag']) . '</svg>';
}

function app_page_start(string $title, string $active = '', string $search = ''): void
{
    global $pdo;
    $user = app_current_user();
    $categories = $pdo->query('SELECT id, nom FROM categories ORDER BY nom')->fetchAll();
    ?>
<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= app_escape($title) ?> · Enchères</title>
<meta name="csrf-token" content="<?= app_escape(app_csrf_token()) ?>">
<link rel="stylesheet" href="assets/site.css?v=20261017b"><script src="assets/site.js?v=20261017b" defer></script>
</head><body>
<a class="skip-link" href="#main">Aller au contenu</a>
<header class="site-header"><div class="header-inner container">
<a class="brand" href="index.php" aria-label="Enchères, accueil"><?= app_icon('hammer') ?><span>Enchères<span class="brand-dot">.</span></span></a>
<details class="categories-menu"><summary>Catégories <?= app_icon('chevron') ?></summary><div class="dropdown"><a href="index.php">Toutes les catégories</a><?php foreach ($categories as $category): ?><a href="index.php?categorie=<?= (int) $category['id'] ?>"><?= app_escape($category['nom']) ?></a><?php endforeach; ?></div></details>
<form class="nav-search" action="index.php" method="get" role="search"><label class="sr-only" for="nav-search">Rechercher un bien</label><button aria-label="Rechercher" type="submit"><?= app_icon('search') ?></button><input type="search" id="nav-search" name="q" maxlength="100" placeholder="Rechercher un bien, une catégorie…" value="<?= app_escape($search) ?>"></form>
<nav class="header-actions" aria-label="Navigation principale">
<a class="icon-link" href="<?= $user ? 'compte.php?onglet=favoris' : 'login.php' ?>" aria-label="Mes favoris"><?= app_icon('heart') ?></a>
<?php if ($user): ?>
<a class="button button-yellow sell-nav" href="<?= app_is_seller($user) ? 'mes_annonces.php' : 'devenir_vendeur.php' ?>"><?= app_is_seller($user) ? 'Espace vendeur' : 'Devenir vendeur' ?></a>
<details class="account-menu"><summary class="account-summary"><?= app_icon('user') ?><span class="account-name"><?= app_escape($user['nom']) ?></span><?= app_icon('chevron') ?></summary><div class="dropdown"><span class="menu-caption">Compte acheteur<?= app_is_seller($user) ? ' · Vendeur' : '' ?></span><a href="compte.php">Mon compte</a><a href="compte.php?onglet=encheres">Mes enchères</a><a href="compte.php?onglet=favoris">Mes favoris</a><a href="compte.php?onglet=profil">Mes informations</a><a href="<?= app_is_seller($user) ? 'mes_annonces.php' : 'devenir_vendeur.php' ?>"><?= app_is_seller($user) ? 'Mes annonces' : 'Devenir vendeur' ?></a><?php if ((int) $user['is_admin'] === 1): ?><a href="admin.php">Administration</a><a href="admin_vendeurs.php">Demandes vendeur</a><?php endif; ?><div class="menu-logout"><?php app_logout_form(); ?></div></div></details>
<?php else: ?><a class="login-link" href="login.php">Connexion</a><a class="button button-blue" href="login.php?inscription=1">S’inscrire</a><?php endif; ?>
</nav></div></header>
<main id="main" data-page="<?= app_escape($active) ?>">
<?php
}

function app_page_end(): void
{
    ?>
</main><footer class="site-footer"><div class="container footer-grid"><div><a class="brand" href="index.php"><?= app_icon('hammer') ?><span>Enchères.</span></a><p>Découvrez des biens, suivez les ventes<br>et participez aux enchères en ligne.</p></div><div><h2>Acheter</h2><a href="index.php">Toutes les enchères</a><a href="compte.php?onglet=encheres">Mes enchères</a><a href="compte.php?onglet=favoris">Mes favoris</a></div><div><h2>Mon espace</h2><a href="compte.php">Mon compte</a><a href="devenir_vendeur.php">Devenir vendeur</a><a href="mes_annonces.php">Mes annonces</a></div></div><div class="container footer-bottom">© <?= date('Y') ?> Enchères · Les informations et les offres de chaque lot sont consultables sur sa fiche.</div></footer>
</body></html>
<?php
}

function app_notice(string $message, string $kind = 'info'): void
{
    ?><div class="notice notice-<?= app_escape($kind) ?>" role="status"><?= app_escape($message) ?></div><?php
}

function app_product_card(array $product): void
{
    $user = app_current_user();
    $id = (int) $product['id'];
    $favorite = (bool) ($product['favori'] ?? false);
    $closed = $product['statut'] !== 'en_cours' || (int) ($product['secondes_restantes'] ?? 0) <= 0;
    ?>
<article class="lot-card" data-lot="<?= $id ?>"><a class="lot-image" href="detprod.php?id=<?= $id ?>"><img src="photo.php?id=<?= $id ?>" alt="<?= app_escape($product['nom']) ?>" loading="lazy"><span class="image-label">Vente aux enchères</span></a><div class="lot-content"><div class="lot-top"><span class="lot-reference">LOT #<?= str_pad((string) $id, 5, '0', STR_PAD_LEFT) ?></span><?php if ($user): ?><button type="button" class="favorite-button" data-favorite data-product="<?= $id ?>" data-state="<?= $favorite ? '1' : '0' ?>" aria-pressed="<?= $favorite ? 'true' : 'false' ?>" aria-label="<?= $favorite ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>"><?= app_icon('heart') ?></button><?php else: ?><a class="favorite-button" href="login.php" aria-label="Se connecter pour ajouter aux favoris"><?= app_icon('heart') ?></a><?php endif; ?></div><p class="lot-category"><?= app_escape($product['categorie_nom'] ?? '') ?></p><h2><a href="detprod.php?id=<?= $id ?>"><?= app_escape($product['nom']) ?></a></h2><p class="lot-price"><?= number_format($product['prix_actuel'], 2, ',', ' ') ?> <span>€</span></p><div class="lot-bottom"><span class="lot-time"><?= app_icon('clock') ?><span data-remaining="<?= max(0, (int) ($product['secondes_restantes'] ?? 0)) ?>"><?= $closed ? 'Terminée' : 'Fin le ' . app_escape(date('d/m à H:i', strtotime($product['date_fin']))) ?></span></span><span class="status-pill <?= $closed ? 'status-muted' : '' ?>"><?= $closed ? 'Terminée' : 'En cours' ?></span></div></div></article>
<?php
}

function app_error_page(int $status, string $message): void
{
    http_response_code($status);
    app_page_start('Votre demande');
    ?><section class="container narrow section"><h1>Votre demande</h1><?php app_notice($message, 'error'); ?><a class="button button-blue" href="compte.php">Retour à mon compte</a></section><?php
    app_page_end();
    exit;
}
