<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once dirname(__DIR__) . '/config.php';

function migration_column(PDO $pdo, string $table, string $column): ?array
{
    $query = $pdo->prepare('SELECT COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $query->execute([$table, $column]);
    return $query->fetch() ?: null;
}

$lock = 'encheres-migration-' . substr(hash('sha256', (string) $pdo->query('SELECT DATABASE()')->fetchColumn()), 0, 32);
$locked = false;
$code = 0;
try {
    $query = $pdo->prepare('SELECT GET_LOCK(?, 5)');
    $query->execute([$lock]);
    if ((int) $query->fetchColumn() !== 1) {
        throw new RuntimeException('Une autre migration est en cours.');
    }
    $locked = true;
    foreach (['utilisateurs', 'categories', 'produits', 'encheres', 'favoris'] as $table) {
        $query = $pdo->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $query->execute([$table]);
        if (strtolower((string) $query->fetchColumn()) !== 'innodb') {
            throw new RuntimeException('La table ' . $table . ' doit exister et utiliser InnoDB. Aucune base n’est recréée par ce script.');
        }
    }
    $id = migration_column($pdo, 'utilisateurs', 'id');
    if (!$id || !preg_match('/\A(?:tinyint|smallint|mediumint|int|bigint)(?:\([0-9]+\))?(?: unsigned)?\z/i', $id['COLUMN_TYPE'])) {
        throw new RuntimeException('Type utilisateurs.id non pris en charge.');
    }
    $seller = migration_column($pdo, 'produits', 'vendeur_id');
    if (!$seller) {
        $pdo->exec('ALTER TABLE produits ADD COLUMN vendeur_id ' . $id['COLUMN_TYPE'] . ' NULL');
        echo "[OK] Ajout de vendeur_id (anciens vendeurs non attribués).\n";
    } elseif (strtolower($seller['COLUMN_TYPE']) !== strtolower($id['COLUMN_TYPE']) || $seller['IS_NULLABLE'] !== 'YES') {
        throw new RuntimeException('vendeur_id existe avec une définition incompatible.');
    }
    $archived = migration_column($pdo, 'produits', 'archive_le');
    if (!$archived) {
        $pdo->exec('ALTER TABLE produits ADD COLUMN archive_le DATETIME NULL');
        echo "[OK] Ajout de archive_le.\n";
    } elseif (strtolower($archived['COLUMN_TYPE']) !== 'datetime' || $archived['IS_NULLABLE'] !== 'YES') {
        throw new RuntimeException('archive_le existe avec une définition incompatible.');
    }
    $foreign = $pdo->query("SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produits' AND COLUMN_NAME = 'vendeur_id' AND REFERENCED_TABLE_NAME = 'utilisateurs' AND REFERENCED_COLUMN_NAME = 'id'")->fetchColumn();
    if (!(int) $foreign) {
        $pdo->exec('ALTER TABLE produits ADD CONSTRAINT fk_produits_vendeur FOREIGN KEY (vendeur_id) REFERENCES utilisateurs(id) ON UPDATE RESTRICT ON DELETE RESTRICT');
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS auth_attempts (
        attempt_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
        attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
        window_start DATETIME NOT NULL,
        KEY ix_auth_attempts_window (window_start)
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
        version VARCHAR(100) CHARACTER SET ascii NOT NULL PRIMARY KEY,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $pdo->exec("INSERT IGNORE INTO schema_migrations(version) VALUES ('20261017_comptes_annonces')");
    echo "[OK] Migration terminée. Les comptes, lots, offres, favoris et photos existants sont conservés.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'Migration interrompue : ' . $error->getMessage() . "\nCorrigez la cause puis relancez ce script : les ajouts déjà effectués sont reconnus.\n");
    $code = 1;
} finally {
    if ($locked) {
        $query = $pdo->prepare('SELECT RELEASE_LOCK(?)');
        $query->execute([$lock]);
    }
}
exit($code);
