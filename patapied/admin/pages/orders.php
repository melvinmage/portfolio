<?php
// admin/pages/orders.php — Centralisation et statuts des commandes
require_once __DIR__ . '/../../includes/core.php';
require_admin();

$lang = current_lang();
$pdo  = getDB();

// Traitement du changement d'état de livraison / paiement
if (isset($_POST['update_order_id']) && isset($_POST['new_status'])) {
    $orderId   = (int)$_POST['update_order_id'];
    $newStatus = $_POST['new_status'];
    
    $allowedStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
    if (in_array($newStatus, $allowedStatuses)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = current_timestamp() WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        set_flash('success', $lang === 'fr' ? 'Cycle de vie de la commande réaligné.' : 'Order status transaction committed.');
    }
    header('Location: /admin/index.php?section=orders');
    exit;
}

// Extraction globale raccordée aux comptes clients
$stmt = $pdo->query("
    SELECT o.id, o.status, o.total_amount, o.created_at, u.first_name, u.last_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll();

$adminPageTitle = $lang === 'fr' ? 'Gestion des Commandes' : 'Order Management';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-page-header" style="margin-bottom: 24px; border-bottom: 1px solid var(--cream-dark); padding-bottom: 12px;">
    <h1 class="admin-page-title" style="font-family: var(--font-serif); color: var(--ink); font-size: 28px; font-weight: 400; margin:0;"><?= $lang === 'fr' ? 'Commandes Clients' : 'Client Orders' ?></h1>
    <span class="admin-page-sub" style="font-family: var(--font-sans); color: var(--ink-light); font-size: 14px;"><?= $lang === 'fr' ? 'Suivi logistique, validation des paiements et édition des états de vente.' : 'Track logistics, confirm payments and dispatch status.' ?></span>
</div>

<div class="admin-card" style="background: var(--white); border: 1px solid var(--cream-dark); border-radius: 8px; padding: 24px; box-shadow: var(--shadow-sm);">
    <div class="table-wrap" style="overflow-x: auto;">
        <table class="admin-table" style="width: 100%; border-collapse: collapse; font-family: var(--font-sans); font-size: 13px;">
            <thead>
                <tr style="text-align: left;">
                    <th style="padding: 10px 8px; color: var(--ink-light); font-weight: 600; border-bottom: 2px solid var(--cream-dark); text-transform: uppercase; font-size: 11px;">Facture #</th>
                    <th style="padding: 10px 8px; color: var(--ink-light); font-weight: 600; border-bottom: 2px solid var(--cream-dark); text-transform: uppercase; font-size: 11px;">Acheteur</th>
                    <th style="padding: 10px 8px; color: var(--ink-light); font-weight: 600; border-bottom: 2px solid var(--cream-dark); text-transform: uppercase; font-size: 11px;">Montant</th>
                    <th style="padding: 10px 8px; color: var(--ink-light); font-weight: 600; border-bottom: 2px solid var(--cream-dark); text-transform: uppercase; font-size: 11px;">Date d'achat</th>
                    <th style="padding: 10px 8px; color: var(--ink-light); font-weight: 600; border-bottom: 2px solid var(--cream-dark); text-transform: uppercase; font-size: 11px;">État Logique</th>
                    <th style="padding: 10px 8px; color: var(--ink-light); font-weight: 600; border-bottom: 2px solid var(--cream-dark); text-transform: uppercase; font-size: 11px; text-align: right;">Mutation</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr style="border-bottom: 1px solid var(--cream-dark);">
                    <td style="padding: 12px 8px; font-family: var(--font-mono); font-weight: 600; color: var(--forest);">#<?= $o['id'] ?></td>
                    <td style="padding: 12px 8px; color: var(--ink); font-weight: 500;"><?= h($o['first_name'] . ' ' . $o['last_name']) ?></td>
                    <td style="padding: 12px 8px; font-family: var(--font-mono); color: var(--ink); font-weight: 600;"><?= number_format($o['total_amount'], 2, ',', ' ') ?> €</td>
                    <td style="padding: 12px 8px; font-family: var(--font-mono); font-size: 12px; color: var(--ink-light);"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                    <td style="padding: 12px 8px;">
                        <?php
                        // Association dynamique des badges de couleur
                        $colorStyle = "background: var(--cream-dark); color: var(--ink-mid);";
                        if ($o['status'] === 'pending')   { $colorStyle = "background: rgba(233, 118, 43, 0.1); color: var(--status-pending);"; }
                        if ($o['status'] === 'confirmed') { $colorStyle = "background: rgba(45, 106, 79, 0.1); color: var(--forest);"; }
                        if ($o['status'] === 'shipped')   { $colorStyle = "background: rgba(37, 99, 235, 0.1); color: var(--status-shipped);"; }
                        if ($o['status'] === 'cancelled') { $colorStyle = "background: rgba(220, 38, 38, 0.1); color: var(--status-cancelled);"; }
                        ?>
                        <span class="status-badge" style="display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; <?= $colorStyle ?>">
                            <?= h($o['status']) ?>
                        </span>
                    </td>
                    <td style="padding: 12px 8px; text-align: right;">
                        <form method="POST" action="/admin/index.php?section=orders" style="margin:0; display:inline-flex; gap:4px;">
                            <input type="hidden" name="update_order_id" value="<?= $o['id'] ?>">
                            <select name="new_status" style="padding: 4px 8px; border: 1px solid var(--cream-dark); border-radius: 4px; font-family: var(--font-sans); font-size: 12px; color: var(--ink);">
                                <option value="pending" <?= $o['status'] === 'pending' ? 'selected' : '' ?>>En attente</option>
                                <option value="confirmed" <?= $o['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmé</option>
                                <option value="shipped" <?= $o['status'] === 'shipped' ? 'selected' : '' ?>>Expédié</option>
                                <option value="delivered" <?= $o['status'] === 'delivered' ? 'selected' : '' ?>>Livré</option>
                                <option value="cancelled" <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>Annulé</option>
                            </select>
                            <button type="submit" class="btn-ghost" style="font-family: var(--font-sans); font-size: 11px; font-weight: 600; color: var(--forest); background: none; padding: 4px 8px; border-radius: 4px; border: 1px solid var(--cream-dark); cursor: pointer;">Mutation</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--ink-light); padding: 30px; font-style: italic; background: var(--white);"><?= $lang === 'fr' ? 'Aucune transaction détectée sur la plateforme.' : 'No active orders found.' ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>