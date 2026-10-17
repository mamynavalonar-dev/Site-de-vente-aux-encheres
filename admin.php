<?php
require_once __DIR__ . '/bootstrap.php';
$user = app_current_user();
if (!$user || (int) $user['is_admin'] !== 1) {
    http_response_code(403); exit('Accès réservé aux administrateurs.');
}
$csrf = app_csrf_token();
if (isset($_GET['action'])) { header('Allow: POST'); http_response_code(405); exit('Utilisez le formulaire de l’administration.'); }
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    app_require_csrf(false);
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $action = $_POST['action'] ?? null;
    if (!$id || !in_array($action, ['close', 'archive', 'restore'], true)) { http_response_code(422); exit('Action invalide.'); }
    try {
        $pdo->beginTransaction();
        $query = $pdo->prepare('SELECT id FROM produits WHERE id = ? FOR UPDATE');
        $query->execute([$id]);
        if (!$query->fetchColumn()) { $pdo->rollBack(); http_response_code(404); exit('Lot introuvable.'); }
        $statement = match ($action) {
            'close' => "UPDATE produits SET statut = 'terminee', date_fin = LEAST(date_fin, NOW()) WHERE id = ?",
            'archive' => "UPDATE produits SET archive_le = COALESCE(archive_le, NOW()), statut = 'terminee', date_fin = LEAST(date_fin, NOW()) WHERE id = ?",
            'restore' => 'UPDATE produits SET archive_le = NULL WHERE id = ?',
        };
        $pdo->prepare($statement)->execute([$id]);
        $pdo->commit();
        header('Location: admin.php?done=' . $action, true, 303); exit;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log('Administration indisponible : ' . $error->getMessage());
        http_response_code(500); exit('Impossible de confirmer la modification. Actualisez la liste pour vérifier son état.');
    }
}
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]) ?: 1;
$offset = ($page - 1) * 50;
$products = $pdo->query("SELECT p.*, u.nom AS vendeur_nom FROM produits p LEFT JOIN utilisateurs u ON u.id = p.vendeur_id ORDER BY p.id DESC LIMIT 51 OFFSET $offset")->fetchAll();
$next = count($products) > 50;
$products = array_slice($products, 0, 50);
$users = $pdo->query('SELECT id, nom, email, is_admin FROM utilisateurs ORDER BY id DESC LIMIT 50')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Administration</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f4f4f4;color:#222;font-family:Arial,sans-serif;padding:24px}main{max-width:1400px;margin:auto}nav{display:flex;gap:20px;align-items:center;flex-wrap:wrap}a{color:#2458b2}.panel{background:white;border-radius:10px;padding:20px;margin:24px 0;overflow-x:auto}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:12px;border-bottom:1px solid #ddd}th{background:#11101d;color:white}td form{display:inline-block;margin:3px}button{padding:8px;cursor:pointer;border:1px solid #aaa;border-radius:4px;background:#fff}h1{margin-top:30px}.status{color:#555;font-size:.9rem}
</style></head><body><main>
<nav><a href="index.php">← Catalogue</a><a href="mes_annonces.php">Mes annonces</a><?php app_logout_form(); ?></nav>
<h1>Administration</h1>
<?php if (in_array($_GET['done'] ?? null, ['close', 'archive', 'restore'], true)): ?><p role="status">Modification enregistrée.</p><?php endif; ?>
<section class="panel"><h2>Annonces</h2><p>L’archivage retire un lot du catalogue en conservant ses photos et ses offres. Restaurer un lot le laisse clôturé.</p>
<table><thead><tr><th>Lot</th><th>Vendeur</th><th>Prix</th><th>État</th><th>Fin</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($products as $product): ?>
<tr><td>#<?= (int) $product['id'] ?> — <?= app_escape($product['nom']) ?></td><td><?= app_escape($product['vendeur_nom'] ?? 'Non renseigné') ?></td><td><?= number_format($product['prix_actuel'], 2, ',', ' ') ?> €</td><td><?= $product['archive_le'] !== null ? 'Archivée' : ($product['statut'] === 'terminee' ? 'Clôturée' : 'En cours') ?></td><td><?= app_escape($product['date_fin']) ?></td><td>
<?php if ($product['archive_le'] === null): ?>
<a href="detprod.php?id=<?= (int) $product['id'] ?>">Voir</a>
<?php if ($product['statut'] === 'en_cours'): ?><form method="post" onsubmit="return confirm('Clôturer cette enchère ?')"><input type="hidden" name="csrf_token" value="<?= app_escape($csrf) ?>"><input type="hidden" name="id" value="<?= (int) $product['id'] ?>"><button name="action" value="close">Clôturer</button></form><?php endif; ?>
<form method="post" onsubmit="return confirm('Archiver cette annonce et clôturer ses enchères ?')"><input type="hidden" name="csrf_token" value="<?= app_escape($csrf) ?>"><input type="hidden" name="id" value="<?= (int) $product['id'] ?>"><button name="action" value="archive">Archiver</button></form>
<?php else: ?><form method="post"><input type="hidden" name="csrf_token" value="<?= app_escape($csrf) ?>"><input type="hidden" name="id" value="<?= (int) $product['id'] ?>"><button name="action" value="restore">Restaurer</button></form><?php endif; ?>
</td></tr><?php endforeach; ?></tbody></table>
<nav><?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>">Page précédente</a><?php endif; ?><span>Page <?= $page ?></span><?php if ($next): ?><a href="?page=<?= $page + 1 ?>">Page suivante</a><?php endif; ?></nav></section>
<section class="panel"><h2>Comptes récents (50 maximum)</h2><table><thead><tr><th>ID</th><th>Nom public</th><th>Email</th><th>Rôle</th></tr></thead><tbody><?php foreach ($users as $account): ?><tr><td><?= (int) $account['id'] ?></td><td><?= app_escape($account['nom']) ?></td><td><?= app_escape($account['email']) ?></td><td><?= (int) $account['is_admin'] === 1 ? 'Administrateur' : 'Membre' ?></td></tr><?php endforeach; ?></tbody></table></section>
</main></body></html>
