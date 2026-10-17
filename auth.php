<?php
require_once __DIR__ . '/bootstrap.php';

function auth_redirect(string $query): void
{
    header('Location: login.php?' . $query, true, 303);
    exit;
}

function auth_attempt(string $scope, string $identity, int $limit): string
{
    global $pdo;
    // Un nouveau cookie ne réinitialise pas ce compteur stocké en base.
    $key = hash('sha256', $scope . "\0" . $identity . "\0" . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $query = $pdo->prepare("INSERT INTO auth_attempts(attempt_key, attempts, window_start) VALUES (?, 1, NOW())
        ON DUPLICATE KEY UPDATE attempts = IF(window_start <= NOW() - INTERVAL 15 MINUTE, 1, LEAST(attempts + 1, 250)),
        window_start = IF(window_start <= NOW() - INTERVAL 15 MINUTE, NOW(), window_start)");
    $query->execute([$key]);
    $query = $pdo->prepare('SELECT attempts FROM auth_attempts WHERE attempt_key = ?');
    $query->execute([$key]);
    if ((int) $query->fetchColumn() > $limit) { auth_redirect('error=rate'); }
    return $key;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Méthode non autorisée.');
}
if (!app_csrf_valid()) { auth_redirect('error=csrf'); }
$action = $_POST['action'] ?? '';
if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $params['path'],
            'domain' => $params['domain'], 'secure' => $params['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    session_destroy();
    header('Location: index.php', true, 303);
    exit;
}
if ($action !== 'login' && $action !== 'register') {
    http_response_code(422);
    exit('Action invalide.');
}
$email = is_string($_POST['email'] ?? null) ? strtolower(trim($_POST['email'])) : '';
$password = $_POST['password'] ?? null;
if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) { auth_redirect('error=email'); }
if (!is_string($password) || $password === '' || strlen($password) > 1024) { auth_redirect('error=invalid'); }
try {
    $algorithm = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
    if ($action === 'register') {
        auth_attempt('register', '', 5);
        $name = app_text($_POST['nom'] ?? null, 150, 'Nom public');
        $length = preg_match_all('/./us', $password);
        if ($length === false || $length < 15 || $length > 128) { auth_redirect('error=password'); }
        if ($algorithm === PASSWORD_BCRYPT && strlen($password) > 72) { auth_redirect('error=password_long'); }
        $hash = password_hash($password, $algorithm);
        // Le rôle demandé par un formulaire n'est jamais utilisé.
        $query = $pdo->prepare('INSERT INTO utilisateurs(nom, email, mot_de_passe, is_admin) VALUES (?, ?, ?, 0)');
        $query->execute([$name, $email, $hash]);
        auth_redirect('success=registered');
    }
    auth_attempt('login-ip', '', 50);
    $accountKey = auth_attempt('login-account', $email, 5);
    $query = $pdo->prepare('SELECT id, nom, mot_de_passe, is_admin FROM utilisateurs WHERE email = ?');
    $query->execute([$email]);
    $user = $query->fetch();
    $hash = $user['mot_de_passe'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
    if (!password_verify($password, $hash) || !$user) { auth_redirect('error=invalid'); }
    // Ne pas réencoder avec bcrypt un ancien mot de passe dépassant sa limite.
    if (($algorithm !== PASSWORD_BCRYPT || strlen($password) <= 72) && password_needs_rehash($hash, $algorithm)) {
        $query = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?');
        $query->execute([password_hash($password, $algorithm), $user['id']]);
    }
    $pdo->prepare('DELETE FROM auth_attempts WHERE attempt_key = ?')->execute([$accountKey]);
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_nom'] = $user['nom'];
    $_SESSION['is_admin'] = (int) $user['is_admin'];
    $_SESSION['authenticated_at'] = time();
    $_SESSION['last_activity'] = time();
    app_csrf_token();
    header('Location: index.php', true, 303);
    exit;
} catch (InvalidArgumentException $error) {
    auth_redirect('error=empty');
} catch (Throwable $error) {
    error_log('Authentification indisponible : ' . $error->getMessage());
    auth_redirect('error=unavailable');
}
