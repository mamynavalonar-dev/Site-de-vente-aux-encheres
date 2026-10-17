<?php
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/cron_terminer_encheres.php';
$user = app_current_user();
$q = $_GET['q'] ?? '';
if (!is_string($q) || strlen($q) > 400 || preg_match_all('/./us', $q) === false || preg_match_all('/./us', $q) > 100) { app_error_page(422, 'Votre recherche doit contenir au maximum 100 caractères.'); }
$q = trim($q);
$category = isset($_GET['categorie']) && $_GET['categorie'] !== '' ? filter_var($_GET['categorie'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null;
if ($category === false) { app_error_page(422, 'Choisissez une catégorie valide.'); }
$sort = $_GET['tri'] ?? 'fin';
$view = $_GET['vue'] ?? 'grille';
$orders = ['fin' => 'p.date_fin ASC, p.id DESC', 'recent' => 'p.id DESC', 'prix_croissant' => 'p.prix_actuel ASC, p.id DESC', 'prix_decroissant' => 'p.prix_actuel DESC, p.id DESC'];
if (!is_string($sort) || !isset($orders[$sort]) || !in_array($view, ['grille', 'liste'], true)) { app_error_page(422, 'Les options d’affichage sont invalides.'); }
function catalog_price($raw): ?string
{
    if ($raw === null || $raw === '') { return null; }
    if (is_string($raw) && preg_match('/\A0+(?:[.,]0{1,2})?\z/', trim($raw))) { return '0.00'; }
    return app_amount($raw);
}
try { $min = catalog_price($_GET['prix_min'] ?? null); $max = catalog_price($_GET['prix_max'] ?? null); }
catch (InvalidArgumentException $error) { app_error_page(422, 'Indiquez des prix valides avec deux décimales au maximum.'); }
if ($min !== null && $max !== null) {
    $a = ltrim(str_replace('.', '', $min), '0') ?: '0';
    $b = ltrim(str_replace('.', '', $max), '0') ?: '0';
    if (strlen($a) > strlen($b) || (strlen($a) === strlen($b) && strcmp($a, $b) > 0)) { app_error_page(422, 'Le prix maximum doit être supérieur ou égal au minimum.'); }
}
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]);
if (!$page) { app_error_page(422, 'Numéro de page invalide.'); }
$categoryRows = $pdo->query("SELECT c.id, c.nom, COUNT(p.id) AS total FROM categories c LEFT JOIN produits p ON p.categorie_id = c.id AND p.statut = 'en_cours' AND p.archive_le IS NULL AND p.date_fin > NOW() GROUP BY c.id,c.nom ORDER BY c.nom")->fetchAll();
$categoryName = null;
foreach ($categoryRows as $row) { if ((int) $row['id'] === $category) { $categoryName = $row['nom']; } }
if ($category !== null && $categoryName === null) { app_error_page(404, 'Cette catégorie n’existe pas.'); }
$where = "p.statut = 'en_cours' AND p.date_fin > NOW() AND p.archive_le IS NULL";
$params = [];
if ($category !== null) { $where .= ' AND p.categorie_id = ?'; $params[] = $category; }
if ($q !== '') { $where .= ' AND (p.nom LIKE ? OR p.description LIKE ?)'; $params[] = '%' . $q . '%'; $params[] = '%' . $q . '%'; }
if ($min !== null) { $where .= ' AND p.prix_actuel >= CAST(? AS DECIMAL(15,2))'; $params[] = $min; }
if ($max !== null) { $where .= ' AND p.prix_actuel <= CAST(? AS DECIMAL(15,2))'; $params[] = $max; }
$query = $pdo->prepare('SELECT COUNT(*) FROM produits p WHERE ' . $where);
$query->execute($params);
$total = (int) $query->fetchColumn();
$pages = max(1, (int) ceil($total / 24));
$page = min($page, $pages);
$offset = ($page - 1) * 24;
$query = $pdo->prepare('SELECT p.*, c.nom AS categorie_nom, TIMESTAMPDIFF(SECOND,NOW(),p.date_fin) AS secondes_restantes,
    EXISTS(SELECT 1 FROM favoris f WHERE f.produit_id = p.id AND f.utilisateur_id = ?) AS favori
    FROM produits p JOIN categories c ON c.id = p.categorie_id WHERE ' . $where . ' ORDER BY ' . $orders[$sort] . " LIMIT 24 OFFSET $offset");
$query->execute(array_merge([$user['id'] ?? 0], $params));
$products = $query->fetchAll();
$home = $q === '' && $category === null && $min === null && $max === null && $page === 1;
$title = $q !== '' ? 'Résultats pour « ' . $q . ' »' : ($categoryName ?? 'Toutes les enchères');
$catalogState = ['q' => $q, 'categorie' => $category, 'prix_min' => $min, 'prix_max' => $max, 'tri' => $sort, 'vue' => $view];
function catalog_url(array $overrides = []): string
{
    global $catalogState;
    $state = array_filter(array_merge($catalogState, $overrides), fn($value) => $value !== null && $value !== '');
    return 'index.php?' . http_build_query($state, '', '&', PHP_QUERY_RFC3986);
}
app_page_start($home ? 'Des biens à découvrir, des ventes à suivre' : $title, 'catalogue', $q);
?>
<div class="container">
<nav class="category-strip" aria-label="Catégories de biens"><a class="category-chip <?= $category === null ? 'active' : '' ?>" href="index.php"><?= app_icon('grid') ?>Toutes les ventes</a><?php foreach ($categoryRows as $row): ?><a class="category-chip <?= $category === (int) $row['id'] ? 'active' : '' ?>" href="index.php?categorie=<?= (int) $row['id'] ?>"><?= app_escape($row['nom']) ?><span><?= (int) $row['total'] ?></span></a><?php endforeach; ?></nav>
<?php if ($home): ?>
<section class="home-hero"><div><p class="eyebrow">Les ventes en cours</p><h1>Un bien vous plaît ?<br>À vous de jouer.</h1><p>Découvrez les lots disponibles, gardez vos favoris et suivez vos offres depuis votre espace acheteur.</p><a class="button button-yellow" href="#ventes">Explorer les enchères <?= app_icon('arrow') ?></a></div>
<?php if ($products): $hero = $products[0]; ?><a class="hero-image" href="detprod.php?id=<?= (int) $hero['id'] ?>"><img src="photo.php?id=<?= (int) $hero['id'] ?>" alt="<?= app_escape($hero['nom']) ?>"><span class="hero-image-label"><strong><?= app_escape($hero['nom']) ?></strong><span><?= number_format($hero['prix_actuel'], 2, ',', ' ') ?> €</span></span></a><?php else: ?><div class="hero-art" aria-hidden="true"><?= app_icon('hammer') ?></div><?php endif; ?></section>
<div class="trust-strip"><div><?= app_icon('search') ?><div><strong>Des biens à découvrir</strong><span>Parcourez les catégories disponibles</span></div></div><div><?= app_icon('heart') ?><div><strong>Vos favoris à portée de main</strong><span>Retrouvez les lots qui vous intéressent</span></div></div><div><?= app_icon('hammer') ?><div><strong>Des offres suivies</strong><span>Consultez l’historique de chaque vente</span></div></div></div>
<?php endif; ?>
<section id="ventes"><div class="catalog-heading"><div><p class="eyebrow">Le catalogue</p><h1 class="page-title"><?= $home ? 'Nos ventes en cours' : app_escape($title) ?></h1><p><?= $total ?> <?= $total > 1 ? 'biens disponibles' : 'bien disponible' ?><?= $q !== '' ? ' pour votre recherche' : '' ?></p></div></div>
<div id="global-feedback" role="status" aria-live="polite"></div>
<div class="catalog-layout"><aside class="filters" aria-label="Filtres de recherche"><h2>Affiner ma recherche</h2><form id="catalog-filters" action="index.php" method="get">
<input type="hidden" name="q" value="<?= app_escape($q) ?>"><input type="hidden" name="vue" value="<?= app_escape($view) ?>">
<div class="filter-group"><label for="filter-category">Catégorie</label><select name="categorie" id="filter-category"><option value="">Toutes les catégories</option><?php foreach ($categoryRows as $row): ?><option value="<?= (int) $row['id'] ?>" <?= $category === (int) $row['id'] ? 'selected' : '' ?>><?= app_escape($row['nom']) ?> (<?= (int) $row['total'] ?>)</option><?php endforeach; ?></select></div>
<div class="filter-group"><label for="price-min">Prix minimum (€)</label><input type="number" step="0.01" min="0" name="prix_min" id="price-min" placeholder="Minimum" value="<?= app_escape($min ?? '') ?>"></div>
<div class="filter-group"><label for="price-max">Prix maximum (€)</label><input type="number" step="0.01" min="0" name="prix_max" id="price-max" placeholder="Maximum" value="<?= app_escape($max ?? '') ?>"></div>
<button class="button button-blue button-wide" type="submit">Appliquer les filtres</button><a class="filter-reset" href="index.php">Réinitialiser les filtres</a>
</form></aside><div><div class="catalog-toolbar"><span><?= $total ? $offset + 1 : 0 ?>–<?= min($offset + 24, $total) ?> sur <?= $total ?> biens</span><div class="row"><label for="sort">Trier par</label><select id="sort" name="tri" form="catalog-filters"><?php foreach (['fin' => 'Fin de vente la plus proche', 'recent' => 'Nouveautés', 'prix_croissant' => 'Prix croissant', 'prix_decroissant' => 'Prix décroissant'] as $key => $label): ?><option value="<?= $key ?>" <?= $sort === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select><button type="submit" form="catalog-filters" class="button button-light button-small">Trier</button></div><div class="view-toggle"><a href="<?= app_escape(catalog_url(['vue' => 'grille'])) ?>" class="<?= $view === 'grille' ? 'active' : '' ?>" aria-label="Afficher en grille"><?= app_icon('grid') ?></a><a href="<?= app_escape(catalog_url(['vue' => 'liste'])) ?>" class="<?= $view === 'liste' ? 'active' : '' ?>" aria-label="Afficher en liste"><?= app_icon('list') ?></a></div></div>
<?php if ($products): ?><div class="product-grid <?= $view === 'liste' ? 'view-list' : '' ?>"><?php foreach ($products as $product) { app_product_card($product); } ?></div><?php else: ?><div class="empty-state"><?= app_icon('search') ?><h2>Aucun lot pour cette recherche</h2><p>Essayez une autre catégorie ou élargissez votre fourchette de prix.</p><a class="button button-outline" href="index.php">Voir toutes les ventes</a></div><?php endif; ?>
<?php if ($pages > 1): ?><nav class="pagination" aria-label="Pages du catalogue"><?php if ($page > 1): ?><a href="<?= app_escape(catalog_url(['page' => $page - 1])) ?>">Précédente</a><?php endif; ?><span>Page <?= $page ?> / <?= $pages ?></span><?php if ($page < $pages): ?><a href="<?= app_escape(catalog_url(['page' => $page + 1])) ?>">Suivante</a><?php endif; ?></nav><?php endif; ?></div></div></section>
<?php if ($user): ?><section class="seller-banner"><div><p class="eyebrow">Votre prochain projet</p><h2>Vous aussi, proposez vos biens aux enchères.</h2><p>Choisissez une formule vendeur et présentez votre projet. Votre espace acheteur reste accessible à tout moment.</p><a class="button button-blue" href="<?= app_is_seller($user) ? 'creer_enchere.php' : 'devenir_vendeur.php' ?>"><?= app_is_seller($user) ? 'Publier une annonce' : 'Découvrir les formules vendeur' ?> <?= app_icon('arrow') ?></a></div><aside><p><?= app_icon('check') ?> Un compte pour acheter et vendre</p><p><?= app_icon('check') ?> Vos annonces et vos offres au même endroit</p><p><?= app_icon('check') ?> L’historique des enchères conservé</p></aside></section><?php endif; ?>
</div>
<?php app_page_end(); ?>
