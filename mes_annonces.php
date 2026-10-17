<?php
require_once __DIR__ . '/bootstrap.php';
$user = app_require_user(false);
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]) ?: 1;
$offset = ($page - 1) * 50;
$query = $pdo->prepare("SELECT id, nom, prix_actuel, statut, date_fin, archive_le FROM produits WHERE vendeur_id = ? ORDER BY id DESC LIMIT 51 OFFSET $offset");
$query->execute([$user['id']]);
$products = $query->fetchAll();
$next = count($products) > 50;
$products = array_slice($products, 0, 50);
?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Mes annonces</title><style>body{font-family:Arial,sans-serif;background:#f4f4f4;color:#222;margin:0;padding:24px}main{max-width:1000px;margin:auto}nav{display:flex;gap:24px;flex-wrap:wrap;align-items:center}article{background:#fff;border-radius:8px;padding:20px;margin:20px 0}a{color:#2458b2}h2{font-size:1.15rem}</style></head><body><main>
<nav><a href="index.php">← Catalogue</a><a href="creer_enchere.php">Créer une annonce</a><?php app_logout_form(); ?></nav><h1>Mes annonces</h1>
<?php if (!$products): ?><p>Vous n’avez pas encore publié d’annonce.</p><?php endif; ?>
<?php foreach ($products as $product): ?><article><h2><?= app_escape($product['nom']) ?></h2><p><?= number_format($product['prix_actuel'], 2, ',', ' ') ?> € · <?= $product['archive_le'] !== null ? 'Archivée' : ($product['statut'] === 'terminee' || $product['date_fin'] <= date('Y-m-d H:i:s') ? 'Clôturée' : 'En cours') ?></p><p>Fin : <?= app_escape($product['date_fin']) ?></p><?php if ($product['archive_le'] === null): ?><a href="detprod.php?id=<?= (int) $product['id'] ?>">Voir l’annonce et les offres</a><?php endif; ?></article><?php endforeach; ?>
<nav><?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>">Page précédente</a><?php endif; ?><span>Page <?= $page ?></span><?php if ($next): ?><a href="?page=<?= $page + 1 ?>">Page suivante</a><?php endif; ?></nav>
</main></body></html>
