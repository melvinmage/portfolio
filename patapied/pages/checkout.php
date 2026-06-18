<?php
// pages/checkout.php
require_once __DIR__ . '/../includes/core.php';
require_login();
$lang = current_lang();
$pdo  = getDB();
$user = current_user();

if (empty($_SESSION['cart'])) {
    redirect('/index.php?page=cart');
}

// Charger les produits
$ids          = array_map('intval', array_keys($_SESSION['cart']));
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt         = $pdo->prepare(
    "SELECT id, name_{$lang} AS name, price, stock FROM products
     WHERE id IN ({$placeholders}) AND is_active = 1"
);
$stmt->execute($ids);
$products = $stmt->fetchAll(PDO::FETCH_UNIQUE);

$items = [];
$total = 0.0;
foreach ($_SESSION['cart'] as $pid => $qty) {
    if (isset($products[$pid])) {
        $p              = $products[$pid];
        $p['quantity']  = $qty;
        $p['subtotal']  = $p['price'] * $qty;
        $items[]        = $p;
        $total         += $p['subtotal'];
    }
}

$error = null;

// ── Traitement commande ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $address = trim($_POST['address'] ?? '');
    if (empty($address)) {
        $error = $lang === 'fr' ? 'L\'adresse de livraison est requise.' : 'Shipping address is required.';
    }

    if (!$error) {
        $shippingFee = $total >= 100 ? 0 : 5.90;
        $grandTotal  = $total + $shippingFee;

        try {
            $pdo->beginTransaction();

            // Créer la commande
            $ins = $pdo->prepare(
                "INSERT INTO orders (user_id, status, total_amount, shipping_address, payment_method, notes)
                 VALUES (:uid, 'pending', :total, :addr, :pay, :notes)"
            );
            $ins->bindValue(':uid',   $user['id'],                           PDO::PARAM_INT);
            $ins->bindValue(':total', $grandTotal,                           PDO::PARAM_STR);
            $ins->bindValue(':addr',  $address,                              PDO::PARAM_STR);
            $ins->bindValue(':pay',   $_POST['payment_method'] ?? 'card',    PDO::PARAM_STR);
            $ins->bindValue(':notes', trim($_POST['notes'] ?? ''),           PDO::PARAM_STR);
            $ins->execute();
            $orderId = (int)$pdo->lastInsertId();

            // Insérer les lignes + décrémenter stock
            foreach ($items as $item) {
                $li = $pdo->prepare(
                    "INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                     VALUES (:oid, :pid, :qty, :price)"
                );
                $li->bindValue(':oid',   $orderId,           PDO::PARAM_INT);
                $li->bindValue(':pid',   $item['id'],        PDO::PARAM_INT);
                $li->bindValue(':qty',   $item['quantity'],  PDO::PARAM_INT);
                $li->bindValue(':price', $item['price'],     PDO::PARAM_STR);
                $li->execute();

                $upd = $pdo->prepare(
                    "UPDATE products SET stock = stock - :qty WHERE id = :id AND stock >= :qty"
                );
                $upd->bindValue(':qty', $item['quantity'], PDO::PARAM_INT);
                $upd->bindValue(':id',  $item['id'],       PDO::PARAM_INT);
                $upd->execute();
            }

            $pdo->commit();
            $_SESSION['cart'] = [];
            flash('success', raw_t('checkout_ok') . ' #' . $orderId);
            redirect('/index.php?page=orders');

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            $error = raw_t('error_generic');
        }
    }
}

// Préremplir l'adresse depuis le compte
$userFull = $pdo->prepare("SELECT address, city, postal_code, country FROM users WHERE id = :id");
$userFull->bindValue(':id', $user['id'], PDO::PARAM_INT);
$userFull->execute();
$userAddr = $userFull->fetch();
$defaultAddr = implode(', ', array_filter([
    $userAddr['address'] ?? '',
    $userAddr['postal_code'] ?? '',
    $userAddr['city'] ?? '',
    $userAddr['country'] ?? '',
]));

$pageTitle = t('checkout_title');
require_once __DIR__ . '/../includes/header.php';
?>

<section class="checkout-section">
    <div class="container">
        <h1><?= t('checkout_title') ?></h1>

        <?php if ($error): ?>
        <div class="flash flash-error"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="checkout-layout">
            <form method="POST" action="/index.php?page=checkout" class="checkout-form">
                <?= csrf_field() ?>

                <div class="checkout-block">
                    <h2><?= t('checkout_address') ?></h2>
                    <div class="form-group">
                        <textarea name="address" rows="3" required
                                  placeholder="<?= $lang==='fr' ? 'Numéro, rue, ville, code postal...' : 'Street, city, postal code...' ?>"
                        ><?= h($_POST['address'] ?? $defaultAddr) ?></textarea>
                    </div>
                </div>

                <div class="checkout-block">
                    <h2><?= t('checkout_payment') ?></h2>
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="card" checked>
                            <span class="payment-label">
                                💳 <?= $lang==='fr' ? 'Carte bancaire' : 'Credit card' ?>
                            </span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="paypal">
                            <span class="payment-label">🅿 PayPal</span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="virement">
                            <span class="payment-label">🏦 <?= $lang==='fr' ? 'Virement bancaire' : 'Bank transfer' ?></span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label><?= $lang==='fr' ? 'Notes (optionnel)' : 'Notes (optional)' ?></label>
                    <textarea name="notes" rows="2" placeholder="<?= $lang==='fr' ? 'Instructions de livraison...' : 'Delivery instructions...' ?>"><?= h($_POST['notes'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-lg"><?= t('checkout_confirm') ?> →</button>
            </form>

            <!-- Résumé commande -->
            <aside class="checkout-summary">
                <h2><?= $lang==='fr' ? 'Votre commande' : 'Your order' ?></h2>
                <?php foreach ($items as $item): ?>
                <div class="checkout-item">
                    <span><?= h($item['name']) ?> × <?= $item['quantity'] ?></span>
                    <span><?= number_format($item['subtotal'], 2, ',', ' ') ?> €</span>
                </div>
                <?php endforeach; ?>
                <hr>
                <div class="summary-line">
                    <span><?= $lang==='fr' ? 'Livraison' : 'Shipping' ?></span>
                    <span class="<?= $total >= 100 ? 'free' : '' ?>">
                        <?= $total >= 100 ? ($lang==='fr' ? 'Offerte' : 'Free') : '5,90 €' ?>
                    </span>
                </div>
                <div class="summary-line summary-total">
                    <span><?= t('cart_total') ?></span>
                    <span><?= number_format($total + ($total >= 100 ? 0 : 5.90), 2, ',', ' ') ?> €</span>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
