<?php
// pages/orders.php
require_once __DIR__ . '/../includes/core.php';
require_login();
$pdo  = getDB();
$user = current_user();
$lang = current_lang();

// Détail d'une commande ?
$detailId = (int)($_GET['id'] ?? 0);
$detail   = null;
$detailItems = [];

if ($detailId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id AND user_id = :uid LIMIT 1");
    $stmt->bindValue(':id',  $detailId,    PDO::PARAM_INT);
    $stmt->bindValue(':uid', $user['id'],  PDO::PARAM_INT);
    $stmt->execute();
    $detail = $stmt->fetch();

    if ($detail) {
        $iStmt = $pdo->prepare(
            "SELECT oi.quantity, oi.unit_price,
                    p.name_{$lang} AS name, p.reference, p.id AS pid
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = :oid"
        );
        $iStmt->bindValue(':oid', $detailId, PDO::PARAM_INT);
        $iStmt->execute();
        $detailItems = $iStmt->fetchAll();
    }
}

// Liste des commandes
$stmt = $pdo->prepare(
    "SELECT id, status, total_amount, created_at
     FROM orders WHERE user_id = :uid ORDER BY created_at DESC"
);
$stmt->bindValue(':uid', $user['id'], PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();

$statusLabels = [
    'pending'   => t('status_pending'),
    'confirmed' => t('status_confirmed'),
    'shipped'   => t('status_shipped'),
    'delivered' => t('status_delivered'),
    'cancelled' => t('status_cancelled'),
];
$statusClasses = [
    'pending'   => 'status-pending',
    'confirmed' => 'status-confirmed',
    'shipped'   => 'status-shipped',
    'delivered' => 'status-delivered',
    'cancelled' => 'status-cancelled',
];

$pageTitle = t('orders_title');
require_once __DIR__ . '/../includes/header.php';
?>

<section class="orders-section">
    <div class="container">

        <?php if ($detail): ?>
        <!-- ─── Détail d'une commande ──────────────────────────── -->
        <div class="order-detail-header">
            <a href="/index.php?page=orders" class="link-back">← <?= t('orders_title') ?></a>
            <h1><?= t('orders_id') ?><?= $detail['id'] ?></h1>
            <span class="status-badge <?= $statusClasses[$detail['status']] ?? '' ?>">
                <?= $statusLabels[$detail['status']] ?? $detail['status'] ?>
            </span>
        </div>

        <div class="order-detail-grid">
            <div class="order-items-list">
                <h2><?= $lang==='fr' ? 'Articles' : 'Items' ?></h2>
                <?php foreach ($detailItems as $item): ?>
                <div class="order-item-row">
                    <span class="order-item-name"><?= h($item['name']) ?></span>
                    <span class="order-item-ref"><?= h($item['reference']) ?></span>
                    <span>× <?= $item['quantity'] ?></span>
                    <span><?= number_format($item['unit_price'] * $item['quantity'], 2, ',', ' ') ?> €</span>
                </div>
                <?php endforeach; ?>
            </div>
            <aside class="order-detail-aside">
                <div class="detail-info">
                    <span><?= t('orders_date') ?></span>
                    <span><?= date('d/m/Y H:i', strtotime($detail['created_at'])) ?></span>
                </div>
                <div class="detail-info">
                    <span><?= t('checkout_address') ?></span>
                    <span><?= nl2br(h($detail['shipping_address'])) ?></span>
                </div>
                <div class="detail-info">
                    <span><?= t('checkout_payment') ?></span>
                    <span><?= h($detail['payment_method']) ?></span>
                </div>
                <div class="detail-info summary-total">
                    <span><?= t('cart_total') ?></span>
                    <span><?= number_format($detail['total_amount'], 2, ',', ' ') ?> €</span>
                </div>
            </aside>
        </div>

        <?php else: ?>
        <!-- ─── Liste des commandes ────────────────────────────── -->
        <h1><?= t('orders_title') ?></h1>

        <?php if (empty($orders)): ?>
        <div class="empty-state">
            <p><?= t('orders_empty') ?></p>
            <a href="/index.php?page=catalog" class="btn btn-primary"><?= t('nav_catalog') ?></a>
        </div>
        <?php else: ?>
        <div class="orders-table-wrap">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th><?= t('orders_id') ?></th>
                        <th><?= t('orders_date') ?></th>
                        <th><?= t('orders_status') ?></th>
                        <th><?= t('orders_amount') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><strong>#<?= $o['id'] ?></strong></td>
                        <td><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
                        <td>
                            <span class="status-badge <?= $statusClasses[$o['status']] ?? '' ?>">
                                <?= $statusLabels[$o['status']] ?? $o['status'] ?>
                            </span>
                        </td>
                        <td><?= number_format($o['total_amount'], 2, ',', ' ') ?> €</td>
                        <td>
                            <a href="/index.php?page=orders&id=<?= $o['id'] ?>" class="btn btn-sm btn-ghost"><?= t('orders_details') ?></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
