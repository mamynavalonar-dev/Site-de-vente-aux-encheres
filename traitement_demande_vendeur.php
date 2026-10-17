<?php
require_once __DIR__ . '/lib/vendeurs.php';
require_once __DIR__ . '/layout.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Allow: POST'); app_error_page(405, 'Utilisez le formulaire de demande vendeur.'); }
$user = app_require_user(false);
app_require_csrf(false);
try {
    $action = $_POST['action'] ?? '';
    if ($action === 'request') { seller_submit((int) $user['id'], $_POST); }
    elseif ($action === 'withdraw') { seller_withdraw((int) $user['id'], seller_expected_version($_POST['version'] ?? null)); }
    else { throw new InvalidArgumentException('Action inconnue.'); }
    header('Location: devenir_vendeur.php?resultat=' . ($action === 'withdraw' ? 'retiree' : 'envoyee'), true, 303);
    exit;
} catch (InvalidArgumentException $error) { app_error_page(422, $error->getMessage()); }
catch (UnexpectedValueException $error) { app_error_page(409, $error->getMessage()); }
catch (DomainException $error) { app_error_page(403, $error->getMessage()); }
catch (Throwable $error) {
    error_log('Demande vendeur non confirmée : ' . $error->getMessage());
    app_error_page(500, 'Impossible de confirmer la demande. Consultez votre espace vendeur avant de réessayer.');
}
