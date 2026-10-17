<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/bootstrap.php';
$ok = true;
function installation_check(string $label, bool $condition): void
{
    global $ok;
    $ok = $ok && $condition;
    echo ($condition ? '[OK] ' : '[ECHEC] ') . $label . "\n";
}
installation_check('PHP 8.2 ou supérieur', PHP_VERSION_ID >= 80200);
foreach (['pdo_mysql', 'fileinfo'] as $extension) {
    installation_check('Extension ' . $extension, extension_loaded($extension));
}
foreach (['utilisateurs', 'categories', 'produits', 'encheres', 'favoris', 'auth_attempts', 'schema_migrations'] as $table) {
    $query = $pdo->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $query->execute([$table]);
    installation_check('Table InnoDB : ' . $table, strtolower((string) $query->fetchColumn()) === 'innodb');
}
$columns = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produits' AND COLUMN_NAME IN ('vendeur_id', 'archive_le')")->fetchColumn();
installation_check('Colonnes vendeur_id et archive_le', (int) $columns === 2);
try { app_upload_dir(); installation_check('Stockage des photos privé et accessible en écriture', true); }
catch (Throwable $error) { installation_check($error->getMessage(), false); }
echo "Ce contrôle local ne remplace pas la vérification du site dans le navigateur ni celle de l’hébergement.\n";
exit($ok ? 0 : 1);
