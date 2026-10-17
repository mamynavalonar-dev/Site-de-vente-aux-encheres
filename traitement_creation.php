<?php
require_once __DIR__ . '/bootstrap.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    app_json(405, ['success' => false, 'message' => 'Méthode non autorisée.']);
}
$user = app_require_seller();
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 6 * 1024 * 1024) {
    app_json(413, ['success' => false, 'message' => 'Le fichier doit faire au maximum 5 Mo.']);
}
app_require_csrf();
session_write_close();
$path = null;
$commitAttempted = false;
try {
    $name = app_text($_POST['nom'] ?? null, 180, 'Nom');
    $description = app_text($_POST['description'] ?? null, 5000, 'Description');
    $amount = app_amount($_POST['prix_depart'] ?? null);
    $category = filter_var($_POST['categorie_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $query = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
    $query->execute([$category ?: 0]);
    if (!$category || !$query->fetchColumn()) { throw new InvalidArgumentException('Choisissez une catégorie existante.'); }
    $rawDate = $_POST['date_fin'] ?? null;
    $date = is_string($rawDate) && preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}\z/', $rawDate)
        ? DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $rawDate) : false;
    $dateErrors = DateTimeImmutable::getLastErrors();
    if (!$date || ($dateErrors !== false && ($dateErrors['warning_count'] || $dateErrors['error_count'])) || $date->format('Y-m-d\TH:i') !== $rawDate) {
        throw new InvalidArgumentException('Indiquez une date et une heure de fin valides.');
    }
    $end = $date->format('Y-m-d H:i:s');
    $query = $pdo->prepare('SELECT ? > NOW()');
    $query->execute([$end]);
    if (!(int) $query->fetchColumn()) { throw new InvalidArgumentException('La date de fin doit être dans le futur.'); }
    $upload = $_FILES['image'] ?? null;
    if (!is_array($upload) || !isset($upload['error']) || !is_int($upload['error'])) {
        throw new InvalidArgumentException('Ajoutez une photo au format JPEG, PNG ou WebP.');
    }
    if (in_array($upload['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
        app_json(413, ['success' => false, 'message' => 'Le fichier doit faire au maximum 5 Mo.']);
    }
    if ($upload['error'] !== UPLOAD_ERR_OK || !is_string($upload['tmp_name'] ?? null) || !is_uploaded_file($upload['tmp_name'])) {
        throw new InvalidArgumentException('Le transfert de la photo a échoué.');
    }
    $size = filesize($upload['tmp_name']);
    if (!$size || $size > 5 * 1024 * 1024) {
        app_json(413, ['success' => false, 'message' => 'Le fichier doit faire entre 1 octet et 5 Mo.']);
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
    $dimensions = @getimagesize($upload['tmp_name']);
    if (!isset($allowed[$mime]) || !$dimensions || ($dimensions['mime'] ?? '') !== $mime) {
        app_json(415, ['success' => false, 'message' => 'La photo doit être un véritable fichier JPEG, PNG ou WebP.']);
    }
    if ($dimensions[0] < 1 || $dimensions[1] < 1 || $dimensions[0] > 8192 || $dimensions[1] > 8192 || $dimensions[0] * $dimensions[1] > 20000000) {
        throw new InvalidArgumentException('La photo est trop grande : 8192 pixels par côté et 20 millions de pixels au maximum.');
    }
    // Ni le nom, ni l'extension, ni le type déclaré par le navigateur ne sont utilisés.
    $filename = 'lot_' . bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $path = app_upload_dir() . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($upload['tmp_name'], $path)) { throw new RuntimeException('Stockage de la photo impossible.'); }
    @chmod($path, 0640);
    $pdo->beginTransaction();
    app_lock_seller((int) $user['id']);
    $query = $pdo->prepare("INSERT INTO produits(nom, categorie_id, description, prix_depart, prix_actuel, date_fin, image_url, statut, vendeur_id)
        SELECT ?, ?, ?, ?, ?, ?, ?, 'en_cours', ? FROM DUAL WHERE ? > NOW()");
    $query->execute([$name, $category, $description, $amount, $amount, $end, $filename, $user['id'], $end]);
    if ($query->rowCount() !== 1) { throw new InvalidArgumentException('La date de fin doit être dans le futur.'); }
    $id = (int) $pdo->lastInsertId();
    $commitAttempted = true;
    $pdo->commit();
    app_json(201, ['success' => true, 'message' => 'Votre annonce est publiée.', 'id' => $id, 'url' => 'detprod.php?id=' . $id]);
} catch (Throwable $error) {
    try { if ($pdo->inTransaction()) { $pdo->rollBack(); } }
    catch (Throwable $rollbackError) { error_log('Annulation de publication impossible : ' . $rollbackError->getMessage()); }
    // Un accusé de réception du COMMIT perdu ne justifie pas d'effacer la photo d'un lot éventuellement enregistré.
    if ($path !== null && !$commitAttempted && is_file($path)) { @unlink($path); }
    if ($error instanceof InvalidArgumentException) { app_json(422, ['success' => false, 'message' => $error->getMessage()]); }
    if ($error instanceof DomainException) { app_json(403, ['success' => false, 'message' => $error->getMessage(), 'code' => 'seller_required']); }
    error_log('Publication impossible : ' . $error->getMessage());
    app_json(500, ['success' => false, 'message' => 'Impossible de confirmer la publication. Vérifiez « Mes annonces » avant de réessayer.']);
}
