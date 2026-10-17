<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/config.php';
$email = is_string($argv[1] ?? null) ? strtolower(trim($argv[1])) : '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { fwrite(STDERR, "Usage : php bin/promouvoir-admin.php adresse@exemple.fr\n"); exit(1); }
$query = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ?');
$query->execute([$email]);
$id = $query->fetchColumn();
if (!$id) { fwrite(STDERR, "Compte introuvable. Inscrivez d’abord ce compte sur le site.\n"); exit(1); }
$pdo->prepare('UPDATE utilisateurs SET is_admin = 1 WHERE id = ?')->execute([$id]);
echo "[OK] Le compte existant dispose du rôle administrateur.\n";
