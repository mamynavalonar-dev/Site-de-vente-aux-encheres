<?php
// Charger ce fichier avant toute sortie HTML et avant le démarrage de la session.
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? '1' : '0');
}
require_once __DIR__ . '/config.php';

if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Cache-Control: no-store');
}

if (isset($_SESSION['user_id'])) {
    $now = time();
    if (($now - (int) ($_SESSION['last_activity'] ?? $now)) > 1800 ||
        ($now - (int) ($_SESSION['authenticated_at'] ?? $now)) > 28800) {
        $_SESSION = [];
        session_regenerate_id(true);
    } else {
        $_SESSION['authenticated_at'] ??= $now;
        $_SESSION['last_activity'] = $now;
    }
}

function app_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_json(int $status, array $data): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function app_csrf_token(): string
{
    if (!is_string($_SESSION['csrf_enchere'] ?? null) || strlen($_SESSION['csrf_enchere']) !== 64) {
        $_SESSION['csrf_enchere'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_enchere'];
}

function app_csrf_valid(): bool
{
    $expected = $_SESSION['csrf_enchere'] ?? null;
    $received = $_POST['csrf_token'] ?? null;
    return is_string($expected) && strlen($expected) === 64 && is_string($received) &&
        hash_equals($expected, $received);
}

function app_require_csrf(bool $json = true): void
{
    if (app_csrf_valid()) {
        return;
    }
    $message = 'Le formulaire a expiré. Actualisez la page puis réessayez.';
    if ($json) {
        app_json(403, ['success' => false, 'message' => $message, 'code' => 'csrf_invalid']);
    }
    http_response_code(403);
    exit($message);
}

function app_current_user(): ?array
{
    global $pdo;
    static $resolved = false, $user = null;
    if ($resolved) {
        return $user;
    }
    $resolved = true;
    $id = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) {
        return null;
    }
    $query = $pdo->prepare('SELECT id, nom, email, is_admin FROM utilisateurs WHERE id = ?');
    $query->execute([$id]);
    $user = $query->fetch() ?: null;
    if (!$user) {
        $_SESSION = [];
        session_regenerate_id(true);
        return null;
    }
    // Une révocation en base s'applique dès la prochaine requête.
    $_SESSION['user_nom'] = $user['nom'];
    $_SESSION['is_admin'] = (int) $user['is_admin'];
    return $user;
}

function app_require_user(bool $json = true): array
{
    $user = app_current_user();
    if ($user) {
        return $user;
    }
    if ($json) {
        app_json(401, ['success' => false, 'message' => 'Connectez-vous pour continuer.', 'code' => 'authentication_required']);
    }
    header('Location: login.php', true, 303);
    exit;
}

function app_logout_form(): void
{
    echo '<form method="post" action="auth.php" style="display:inline">' .
        '<input type="hidden" name="action" value="logout">' .
        '<input type="hidden" name="csrf_token" value="' . app_escape(app_csrf_token()) . '">' .
        '<button type="submit" class="logout" style="border:0;background:none;cursor:pointer;font:inherit;color:crimson">Déconnexion</button></form>';
}

function app_amount($raw): string
{
    if (!is_string($raw) || !preg_match('/\A[0-9]{1,13}(?:[.,][0-9]{1,2})?\z/', trim($raw))) {
        throw new InvalidArgumentException('Indiquez un prix positif avec deux décimales au maximum.');
    }
    $parts = explode('.', str_replace(',', '.', trim($raw)), 2);
    $value = (ltrim($parts[0], '0') ?: '0') . '.' . str_pad($parts[1] ?? '', 2, '0');
    if ($value === '0.00') {
        throw new InvalidArgumentException('Le prix doit être strictement positif.');
    }
    return $value;
}

function app_text($raw, int $max, string $label): string
{
    $value = is_string($raw) ? trim($raw) : '';
    $length = preg_match_all('/./us', $value);
    if ($length === false || $length < 1 || $length > $max) {
        throw new InvalidArgumentException($label . ' : indiquez entre 1 et ' . $max . ' caractères.');
    }
    return $value;
}

function app_upload_dir(): string
{
    $configured = getenv('ENCHERES_UPLOAD_DIR');
    $path = $configured !== false && $configured !== '' ? $configured : dirname(__DIR__) . '/encheres-stockage/photos';
    if (!is_dir($path) && !@mkdir($path, 0750, true) && !is_dir($path)) {
        throw new RuntimeException('Le stockage des photos est indisponible.');
    }
    $real = realpath($path);
    if ($real === false || !is_writable($real)) {
        throw new RuntimeException('Le stockage des photos est indisponible.');
    }
    $normalized = strtolower(str_replace('\\', '/', $real));
    foreach ([__DIR__, $_SERVER['DOCUMENT_ROOT'] ?? ''] as $publicRoot) {
        $root = $publicRoot !== '' ? realpath($publicRoot) : false;
        if ($root !== false) {
            $root = strtolower(rtrim(str_replace('\\', '/', $root), '/'));
            if ($normalized === $root || str_starts_with($normalized, $root . '/')) {
                throw new RuntimeException('Configurez ENCHERES_UPLOAD_DIR en dehors du répertoire public du serveur.');
            }
        }
    }
    return $real;
}
