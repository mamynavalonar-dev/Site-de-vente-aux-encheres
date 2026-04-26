<?php
require_once 'config.php';

// SÉCURITÉ : Si pas connecté ou pas admin -> dégagement
if(!isset($_SESSION['user_id']) || ($_SESSION['is_admin'] ?? 0) != 1) {
    die("Accès refusé. Vous n'êtes pas administrateur.");
}

// Récupération de toutes les données
 $produits = $pdo->query("SELECT p.*, u.nom as vendeur_nom FROM produits p LEFT JOIN utilisateurs u ON p.categorie_id = u.id ORDER BY p.date_fin DESC")->fetchAll();
 $users = $pdo->query("SELECT id, nom, email, is_admin FROM utilisateurs")->fetchAll();

// Traitement des actions (Suppression/Clôture)
if(isset($_GET['action'])) {
    if($_GET['action'] == 'delete_product' && is_numeric($_GET['id'])) {
        // On récupère l'image pour la supprimer du serveur aussi
        $req = $pdo->prepare("SELECT image_url FROM produits WHERE id = ?");
        $req->execute([$_GET['id']]);
        $prod = $req->fetch();
        if($prod && file_exists('img-prod/' . $prod['image_url'])) {
            unlink('img-prod/' . $prod['image_url']);
        }
        $pdo->prepare("DELETE FROM encheres WHERE produit_id = ?")->execute([$_GET['id']]);
        $pdo->prepare("DELETE FROM produits WHERE id = ?")->execute([$_GET['id']]);
        header("Location: admin.php"); exit;
    }
    if($_GET['action'] == 'close_auction' && is_numeric($_GET['id'])) {
        $pdo->prepare("UPDATE produits SET statut = 'terminee', date_fin = NOW() WHERE id = ?")->execute([$_GET['id']]);
        header("Location: admin.php"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Panneau Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: sans-serif; }
        body { background: #f4f4f4; padding: 20px; }
        h1 { margin-bottom: 20px; }
        .card { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
        th { background: #11101D; color: #fff; }
        .btn { padding: 5px 10px; text-decoration: none; color: #fff; border-radius: 4px; font-size: 12px; }
        .btn-danger { background: #dc3545; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-back { display: inline-block; margin-bottom: 20px; background: #007bff; padding: 10px 15px; color: #fff; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>

    <a href="index.php" class="btn-back">← Retour au site</a>
    <h1>Administration</h1>

    <div class="card">
        <h2>Produits (<?= count($produits) ?>)</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Prix Actuel</th>
                <th>Statut</th>
                <th>Fin</th>
                <th>Actions</th>
            </tr>
            <?php foreach($produits as $p): ?>
            <tr style="<?= $p['statut'] === 'terminee' ? 'background:#ffe6e6;' : '' ?>">
                <td>#<?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['nom']) ?></td>
                <td><?= number_format($p['prix_actuel'], 2, ',', ' ') ?> €</td>
                <td style="font-weight:bold; color:<?= $p['statut'] === 'en_cours' ? 'green' : 'red' ?>;"><?= strtoupper($p['statut']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($p['date_fin'])) ?></td>
                <td>
                    <?php if($p['statut'] === 'en_cours'): ?>
                        <a href="?action=close_auction&id=<?= $p['id'] ?>" class="btn btn-warning" onclick="return confirm('Clôturer cette enchère ?')">Clôturer</a>
                    <?php endif; ?>
                    <a href="?action=delete_product&id=<?= $p['id'] ?>" class="btn btn-danger" onclick="return confirm('Supprimer définitivement ?')">Supprimer</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card">
        <h2>Utilisateurs (<?= count($users) ?>)</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Email</th>
                <th>Statut</th>
            </tr>
            <?php foreach($users as $u): ?>
            <tr>
                <td>#<?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['nom']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= $u['is_admin'] == 1 ? '<span style="color:red;font-weight:bold;">ADMIN</span>' : 'Client' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

</body>
</html>