<?php
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/lib/vendeurs.php';
$user = app_current_user();
if (!$user || (int) $user['is_admin'] !== 1) { app_error_page(403, 'Accès réservé aux administrateurs.'); }
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    app_require_csrf(false);
    try {
        $id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $action = $_POST['action'] ?? null;
        if (!$id || !is_string($action)) { throw new InvalidArgumentException('Décision invalide.'); }
        seller_review($id, (int) $user['id'], seller_expected_version($_POST['version'] ?? null), $action, $_POST['motif'] ?? null);
        header('Location: admin_vendeurs.php?resultat=enregistre', true, 303); exit;
    } catch (InvalidArgumentException $error) { app_error_page(422, $error->getMessage()); }
    catch (UnexpectedValueException $error) { app_error_page(409, $error->getMessage()); }
    catch (Throwable $error) { error_log('Décision vendeur non confirmée : ' . $error->getMessage()); app_error_page(500, 'Impossible de confirmer la décision. Actualisez la liste avant de réessayer.'); }
}
$status = $_GET['etat'] ?? 'pending';
if (!in_array($status, ['pending', 'active', 'rejected', 'suspended', 'cancelled', 'tous'], true)) { app_error_page(422, 'Filtre invalide.'); }
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]) ?: 1;
$offset = ($page - 1) * 30;
$query = $pdo->prepare('SELECT s.*, u.nom, u.email FROM seller_access s JOIN utilisateurs u ON u.id = s.user_id' . ($status === 'tous' ? '' : ' WHERE s.status = ?') . " ORDER BY s.requested_at DESC, s.user_id DESC LIMIT 31 OFFSET $offset");
$query->execute($status === 'tous' ? [] : [$status]);
$rows = $query->fetchAll(); $next = count($rows) > 30; $rows = array_slice($rows, 0, 30);
app_page_start('Demandes vendeur', 'admin-vendeurs');
?>
<section class="container section"><a class="text-link" href="admin.php">← Administration des annonces</a><div class="catalog-heading"><div><p class="eyebrow">Administration</p><h1>Les accès vendeur.</h1><p>Chaque décision est enregistrée et s’applique dès la prochaine requête du compte concerné.</p></div></div>
<?php if (($_GET['resultat'] ?? '') === 'enregistre') { app_notice('La décision a été enregistrée.', 'success'); } ?>
<nav class="admin-filter" aria-label="État des demandes"><?php foreach (['pending' => 'À examiner', 'active' => 'Actifs', 'suspended' => 'Suspendus', 'rejected' => 'Refusés', 'tous' => 'Tous'] as $key => $label): ?><a class="button button-small <?= $status === $key ? 'button-blue' : 'button-outline' ?>" href="admin_vendeurs.php?etat=<?= $key ?>"><?= $label ?></a><?php endforeach; ?></nav>
<?php if (!$rows): ?><div class="empty-state"><?= app_icon('check') ?><h2>Aucun accès dans cette liste</h2><p>Les nouvelles demandes apparaîtront ici.</p></div><?php endif; ?>
<?php foreach ($rows as $row): ?><article class="admin-review-card"><div class="admin-review-heading"><div><p class="eyebrow">Compte #<?= (int) $row['user_id'] ?></p><h2><?= app_escape($row['nom']) ?></h2></div><span><?= app_escape(seller_status_name($row['status'])) ?></span></div><dl><div><dt>Email du compte</dt><dd><?= app_escape($row['email']) ?></dd></div><div><dt>Formule demandée</dt><dd><?= app_escape(seller_plan_name($row['plan_code'])) ?></dd></div><div><dt>Demande</dt><dd><?= app_escape(date('d/m/Y H:i', strtotime($row['requested_at']))) ?></dd></div><div><dt>Organisation</dt><dd><?= app_escape($row['organisation'] ?: 'Non renseignée') ?></dd></div><div><dt>Téléphone</dt><dd><?= app_escape($row['telephone'] ?: 'Non renseigné') ?></dd></div><div><dt>Origine</dt><dd><?= $row['source'] === 'historique' ? 'Accès existant conservé' : 'Demande du compte' ?></dd></div></dl><p class="admin-project"><?= app_escape($row['project']) ?></p><?php if ($row['reason'] !== ''): ?><p><strong>Dernier motif :</strong> <?= app_escape($row['reason']) ?></p><?php endif; ?>
<?php if (in_array($row['status'], ['pending', 'active', 'suspended'], true)): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= app_escape(app_csrf_token()) ?>"><input type="hidden" name="user_id" value="<?= (int) $row['user_id'] ?>"><input type="hidden" name="version" value="<?= (int) $row['version'] ?>"><div class="field"><label for="motif-<?= (int) $row['user_id'] ?>">Motif (obligatoire pour refuser ou suspendre)</label><textarea rows="2" id="motif-<?= (int) $row['user_id'] ?>" name="motif" maxlength="1000"></textarea></div><div class="row"><?php if ($row['status'] !== 'active'): ?><button name="action" value="approve" class="button button-blue"><?= $row['status'] === 'suspended' ? 'Réactiver l’accès' : 'Valider l’accès vendeur' ?></button><?php endif; ?><?php if ($row['status'] === 'pending'): ?><button name="action" value="reject" class="button button-danger">Refuser la demande</button><?php elseif ($row['status'] === 'active'): ?><button name="action" value="suspend" class="button button-danger">Suspendre la publication</button><?php endif; ?></div></form><?php endif; ?></article><?php endforeach; ?>
<?php if ($page > 1 || $next): ?><nav class="pagination" aria-label="Pages des accès vendeur"><?php if ($page > 1): ?><a href="?etat=<?= $status ?>&page=<?= $page - 1 ?>">Précédente</a><?php endif; ?><span>Page <?= $page ?></span><?php if ($next): ?><a href="?etat=<?= $status ?>&page=<?= $page + 1 ?>">Suivante</a><?php endif; ?></nav><?php endif; ?>
</section><?php app_page_end(); ?>
