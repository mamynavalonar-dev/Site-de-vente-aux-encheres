<?php
require_once dirname(__DIR__) . '/bootstrap.php';

function seller_plans(): array
{
    return [
        'essentiel' => ['name' => 'Essentiel', 'intro' => 'Pour publier vos premiers lots.', 'features' => ['Publication de vos annonces', 'Photos et description de vos biens', 'Suivi des offres reçues']],
        'standard' => ['name' => 'Standard', 'intro' => 'Pour organiser des ventes régulières.', 'features' => ['Publication et suivi de vos annonces', 'Présentation de votre activité', 'Conditions adaptées à votre projet']],
        'premium' => ['name' => 'Premium', 'intro' => 'Pour préparer un projet de vente plus important.', 'features' => ['Publication et suivi de vos annonces', 'Présentation de vos besoins de vente', 'Conditions étudiées avec l’administration']],
    ];
}

function seller_plan_name(?string $code): string
{
    return seller_plans()[$code]['name'] ?? ($code === 'historique' ? 'Accès existant' : 'Aucune formule');
}

function seller_status_name(?string $status): string
{
    return ['pending' => 'En attente de validation', 'active' => 'Accès vendeur actif', 'rejected' => 'Demande refusée', 'suspended' => 'Accès vendeur suspendu', 'cancelled' => 'Demande retirée'][$status] ?? 'Compte acheteur';
}

function seller_access(int $id, bool $lock = false): ?array
{
    global $pdo;
    $query = $pdo->prepare('SELECT * FROM seller_access WHERE user_id = ?' . ($lock ? ' FOR UPDATE' : ''));
    $query->execute([$id]);
    return $query->fetch() ?: null;
}

function seller_expected_version($raw): int
{
    if (!is_string($raw) || !preg_match('/\A[0-9]{1,10}\z/', $raw) || (float) $raw > 4294967295) {
        throw new InvalidArgumentException('Le formulaire doit être actualisé.');
    }
    return (int) $raw;
}

function seller_lock_user(int $id): void
{
    global $pdo;
    $query = $pdo->prepare('SELECT id FROM utilisateurs WHERE id = ? FOR UPDATE');
    $query->execute([$id]);
    if (!$query->fetchColumn()) { throw new OutOfBoundsException('Compte introuvable.'); }
}

function seller_event(int $id, ?int $actor, string $action, string $status, string $plan, string $reason = ''): void
{
    global $pdo;
    $query = $pdo->prepare('INSERT INTO seller_events(user_id, actor_id, action, status, plan_code, reason) VALUES (?, ?, ?, ?, ?, ?)');
    $query->execute([$id, $actor, $action, $status, $plan, $reason]);
}

function seller_submit(int $id, array $input): void
{
    global $pdo;
    $plan = $input['plan'] ?? null;
    if (!is_string($plan) || !isset(seller_plans()[$plan])) { throw new InvalidArgumentException('Choisissez une formule proposée.'); }
    $organisation = isset($input['organisation']) && $input['organisation'] !== '' ? app_text($input['organisation'], 150, 'Organisation') : '';
    $phone = isset($input['telephone']) && $input['telephone'] !== '' ? app_text($input['telephone'], 40, 'Téléphone') : '';
    $project = app_text($input['projet'] ?? null, 2000, 'Votre projet');
    $expected = seller_expected_version($input['version'] ?? null);
    $pdo->beginTransaction();
    try {
        seller_lock_user($id);
        $access = seller_access($id, true);
        if ($access && $access['status'] === 'pending') { $pdo->commit(); return; }
        if ($access && in_array($access['status'], ['active', 'suspended'], true)) { throw new DomainException('Votre accès vendeur ne permet pas de déposer cette demande.'); }
        if ($expected !== (int) ($access['version'] ?? 0)) { throw new UnexpectedValueException('Votre situation a changé. Actualisez cette page.'); }
        if ($access) {
            $query = $pdo->prepare("UPDATE seller_access SET plan_code = ?, status = 'pending', organisation = ?, telephone = ?, project = ?, requested_at = NOW(), reviewed_at = NULL, reviewed_by = NULL, reason = '', version = version + 1 WHERE user_id = ?");
        } else {
            $query = $pdo->prepare("INSERT INTO seller_access(plan_code, status, organisation, telephone, project, user_id) VALUES (?, 'pending', ?, ?, ?, ?)");
        }
        $query->execute([$plan, $organisation, $phone, $project, $id]);
        seller_event($id, $id, 'request', 'pending', $plan);
        $pdo->commit();
    } catch (Throwable $error) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $error; }
}

function seller_withdraw(int $id, int $expected): void
{
    global $pdo;
    $pdo->beginTransaction();
    try {
        seller_lock_user($id);
        $access = seller_access($id, true);
        if (!$access || $access['status'] !== 'pending' || (int) $access['version'] !== $expected) { throw new UnexpectedValueException('Cette demande ne peut plus être retirée. Actualisez la page.'); }
        $pdo->prepare("UPDATE seller_access SET status = 'cancelled', version = version + 1 WHERE user_id = ?")->execute([$id]);
        seller_event($id, $id, 'withdraw', 'cancelled', $access['plan_code']);
        $pdo->commit();
    } catch (Throwable $error) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $error; }
}

function seller_review(int $id, int $actor, int $expected, string $action, $rawReason): void
{
    global $pdo;
    $transitions = ['approve' => ['pending', 'suspended'], 'reject' => ['pending'], 'suspend' => ['active']];
    if (!isset($transitions[$action])) { throw new InvalidArgumentException('Action inconnue.'); }
    $status = ['approve' => 'active', 'reject' => 'rejected', 'suspend' => 'suspended'][$action];
    $reason = $action === 'approve' ? '' : app_text($rawReason, 1000, 'Motif');
    $pdo->beginTransaction();
    try {
        seller_lock_user($id);
        $access = seller_access($id, true);
        if (!$access || (int) $access['version'] !== $expected || !in_array($access['status'], $transitions[$action], true)) { throw new UnexpectedValueException('La demande a déjà changé. Actualisez la liste avant de décider.'); }
        $query = $pdo->prepare('UPDATE seller_access SET status = ?, reason = ?, reviewed_by = ?, reviewed_at = NOW(), version = version + 1 WHERE user_id = ?');
        $query->execute([$status, $reason, $actor, $id]);
        seller_event($id, $actor, $action, $status, $access['plan_code'], $reason);
        $pdo->commit();
    } catch (Throwable $error) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $error; }
}
