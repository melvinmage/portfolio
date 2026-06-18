<?php
// pages/cart.php — Panier (session + DB si connecté) avec images réelles et correctif suppression
// ============================================================================
require_once __DIR__ . '/../includes/core.php';
$lang = current_lang();
$pdo  = getDB();

// ── Actions POST ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action    = $_POST['action']     ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty       = max(1, (int)($_POST['quantity'] ?? 1));

    if ($action === 'add' && $productId > 0) {
        // Vérifie que le produit existe et a du stock
        $chk = $pdo->prepare("SELECT id, stock FROM products WHERE id = :id AND is_active = 1 LIMIT 1");
        $chk->bindValue(':id', $productId, PDO::PARAM_INT);
        $chk->execute();
        $prod = $chk->fetch();
        if ($prod && $prod['stock'] >= $qty) {
            $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $qty;
            flash('success', raw_t('cart_updated'));
        }
    } elseif ($action === 'remove' && $productId > 0) {
        unset($_SESSION['cart'][$productId]);
        flash('success', raw_t('cart_updated'));
    } elseif ($action === 'update' && $productId > 0) {
        if ($qty <= 0) {
            unset($_SESSION['cart'][$productId]);
        } else {
            $_SESSION['cart'][$productId] = $qty;
        }
        flash('success', raw_t('cart_updated'));
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
    }
    redirect('/index.php?page=cart');
}

// ── Chargement des produits du panier ────────────────────────
$cartItems = [];
$total     = 0.0;

if (!empty($_SESSION['cart'])) {
    $ids        = array_map('intval', array_keys($_SESSION['cart']));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt       = $pdo->prepare(
        "SELECT id, reference, name_{$lang} AS name, price, stock, image_path
         FROM products WHERE id IN ({$placeholders}) AND is_active = 1"
    );
    $stmt->execute($ids);
    $rows = $stmt->fetchAll(PDO::FETCH_UNIQUE);

    foreach ($_SESSION['cart'] as $pid => $qty) {
        if (isset($rows[$pid])) {
            $item = $rows[$pid];
            $item['quantity'] = $qty;
            $item['subtotal'] = $item['price'] * $qty;
            $cartItems[]      = $item;
            $total           += $item['subtotal'];
        }
    }
}

$pageTitle = t('cart_title');
require_once __DIR__ . '/../includes/header.php';
?>

<section class="cart-section">
    <div class="container">
        <h1><?= t('cart_title') ?></h1>

        <?php if (empty($cartItems)): ?>
        <div class="empty-state">
            <p><?= t('cart_empty') ?></p>
            <a href="/index.php?page=catalog" class="btn btn-primary"><?= t('cat_title') ?></a>
        </div>
        <?php else: ?>

        <div class="cart-layout">
            <div class="cart-items">
                <?php foreach ($cartItems as $item): ?>
                <div class="cart-row">
                    <div class="cart-img">
                        <?php 
                        // Résolution dynamique du chemin d'image basé sur la référence en minuscules
                        $imgFilename = 'images/' . strtolower($item['reference']) . '.png';
                        $displayImg = (file_exists(__DIR__ . '/../' . $imgFilename)) ? $imgFilename : $item['image_path'];
                        
                        if ($displayImg): 
                        ?>
                            <img src="<?= h($displayImg) ?>" alt="<?= h($item['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div class="cart-img-placeholder">
                                <svg viewBox="0 0 60 45" fill="none" aria-hidden="true"><path d="M6 32 Q7 21 17 18 L39 16 Q48 16 50 23 L51 32 Q46 36 30 37 Q12 37 6 32Z" fill="var(--forest)"/><path d="M6 32 Q6 37 13 38 L47 38 Q52 37 52 32" fill="var(--forest-dark)"/></svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="cart-item-info">
                        <a href="/index.php?page=product&id=<?= $item['id'] ?>" class="cart-item-name"><?= h($item['name']) ?></a>
                        <span class="product-ref"><?= t('cat_ref') ?> <code><?= h($item['reference']) ?></code></span>
                    </div>
                    
                    <div class="cart-qty-control">
                        <form method="POST" action="/index.php?page=cart">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action"     value="update">
                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                            <div class="qty-control">
                                <button type="submit" name="quantity" value="<?= $item['quantity'] - 1 ?>" class="qty-btn">−</button>
                                <span class="qty-display"><?= $item['quantity'] ?></span>
                                <button type="submit" name="quantity" value="<?= min($item['quantity'] + 1, $item['stock']) ?>" class="qty-btn">+</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="cart-price">
                        <?= number_format($item['subtotal'], 2, ',', ' ') ?> €
                        <small>(<?= number_format($item['price'], 2, ',', ' ') ?> € / <?= current_lang()==='fr' ? 'unité' : 'unit' ?>)</small>
                    </div>
                    
                    <form method="POST" action="/index.php?page=cart" class="form-remove-item">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action"     value="remove">
                        <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                        <button type="submit" class="btn-icon-remove" title="<?= t('cart_remove') ?>">✕</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>

            <aside class="cart-summary">
                <h2><?= current_lang()==='fr' ? 'Récapitulatif' : 'Summary' ?></h2>
                <div class="summary-line">
                    <span><?= t('cart_subtotal') ?></span>
                    <span><?= number_format($total, 2, ',', ' ') ?> €</span>
                </div>
                <div class="summary-line">
                    <span><?= current_lang()==='fr' ? 'Livraison' : 'Shipping' ?></span>
                    <span class="free"><?= $total >= 100 ? (current_lang()==='fr' ? 'Offerte' : 'Free') : '5,90 €' ?></span>
                </div>
                <div class="summary-line summary-total">
                    <span><?= t('cart_total') ?></span>
                    <span><?= number_format($total + ($total >= 100 ? 0 : 5.90), 2, ',', ' ') ?> €</span>
                </div>

                <?php if (is_logged()): ?>
                <a href="/index.php?page=checkout" class="btn btn-primary btn-full"><?= t('cart_checkout') ?></a>
                <?php else: ?>
                <a href="/index.php?page=login" class="btn btn-primary btn-full">
                    <?= current_lang()==='fr' ? 'Se connecter pour commander' : 'Sign in to order' ?>
                </a>
                <?php endif; ?>

                <a href="/index.php?page=catalog" class="btn btn-ghost btn-full"><?= t('cart_continue') ?></a>

                <form method="POST" action="/index.php?page=cart" style="margin-top:1rem">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="btn-text-danger"><?= current_lang()==='fr' ? 'Vider le panier' : 'Clear cart' ?></button>
                </form>
            </aside>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>