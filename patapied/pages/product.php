<?php
// pages/product.php — Vue Détail Produit avec Intégration des Images Réelles
// ============================================================================
require_once __DIR__ . '/../includes/core.php';
$lang = current_lang();
$pdo  = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT p.*, p.name_{$lang} AS name, p.description_{$lang} AS description,
            c.name_{$lang} AS category, c.slug AS cat_slug
     FROM products p
     JOIN categories c ON c.id = p.category_id
     WHERE p.id = :id AND p.is_active = 1 LIMIT 1"
);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$product = $stmt->fetch();

if (!$product) {
    redirect('/index.php?page=catalog');
}

$pageTitle = $product['name'];

require_once __DIR__ . '/../includes/header.php';
?>

<section class="product-detail-section">
    <div class="container">
        
        <nav class="breadcrumb" aria-label="breadcrumb">
            <a href="/index.php"><?= t('nav_home') ?></a> /
            <a href="/index.php?page=catalog"><?= t('nav_catalog') ?></a> /
            <a href="/index.php?page=catalog&cat=<?= h($product['cat_slug']) ?>"><?= h($product['category']) ?></a> /
            <span><?= h($product['name']) ?></span>
        </nav>

        <div class="product-detail-grid">
            
            <div class="product-detail-visual">
                <?php 
                // Résolution dynamique du fichier image (ex: images/pat-urb-001.png)
                $imgFilename = 'images/' . strtolower($product['reference']) . '.png';
                $displayImg = (file_exists(__DIR__ . '/../' . $imgFilename)) ? $imgFilename : $product['image_path'];
                
                if ($displayImg): 
                ?>
                    <div class="product-detail-img-container">
                        <img src="<?= h($displayImg) ?>" alt="<?= h($product['name']) ?>" class="shoe-detail-image">
                    </div>
                <?php else: ?>
                    <div class="product-img-placeholder product-img-placeholder--lg">
                        <svg viewBox="0 0 80 60" fill="none" aria-hidden="true">
                            <path d="M8 42 Q10 28 22 24 L52 22 Q64 22 67 30 L68 42 Q62 48 40 49 Q16 49 8 42Z" fill="var(--forest)"/>
                            <path d="M8 42 Q8 49 18 51 L62 51 Q70 49 70 42" fill="var(--forest-dark)"/>
                        </svg>
                    </div>
                <?php endif; ?>
            </div>

            <div class="product-detail-info">
                <span class="product-cat"><?= h($product['category']) ?></span>
                <h1 class="product-detail-name"><?= h($product['name']) ?></h1>
                <span class="product-ref"><code><?= h($product['reference']) ?></code></span>

                <div class="product-price-container">
                    <span class="product-price product-price--lg"><?= number_format($product['price'], 2, ',', ' ') ?> €</span>
                </div>

                <div class="product-detail-desc">
                    <p><?= nl2br(h($product['description'])) ?></p>
                </div>

                <?php if ($product['stock'] > 0): ?>
                <form method="POST" action="/index.php?page=cart" class="product-purchase-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    
                    <div class="qty-row">
                        <label for=\"qty\"><?= t('cart_qty') ?></label>
                        <div class="qty-control">
                            <button type="button" class="qty-btn" id="qtyMinus">−</button>
                            <input type="number" id="qty" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>" class="qty-input" readonly>
                            <button type="button" class="qty-btn" id="qtyPlus">+</button>
                        </div>
                        <span class="stock-indicator">
                            (<?= $product['stock'] ?> <?= $lang==='fr' ? 'disponibles' : 'available' ?>)
                        </span>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg btn-add-large"><?= t('cat_add_cart') ?></button>
                </form>
                <?php else: ?>
                    <div class="out-of-stock-box">
                        <button class="btn btn-disabled btn-lg" disabled><?= t('cat_out_stock') ?></button>
                    </div>
                <?php endif; ?>

                <a href="/index.php?page=catalog&cat=<?= h($product['cat_slug']) ?>" class="link-back">
                    ← <?= $lang==='fr' ? 'Retour à la catégorie' : 'Back to category' ?>
                </a>
            </div>

        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const qtyInput = document.getElementById('qty');
    const btnMinus = document.getElementById('qtyMinus');
    const btnPlus  = document.getElementById('qtyPlus');

    if (qtyInput && btnMinus && btnPlus) {
        const maxStock = parseInt(qtyInput.getAttribute('max')) || 1;

        btnMinus.addEventListener('click', () => {
            let val = parseInt(qtyInput.value) || 1;
            if (val > 1) qtyInput.value = val - 1;
        });

        btnPlus.addEventListener('click', () => {
            let val = parseInt(qtyInput.value) || 1;
            if (val < maxStock) qtyInput.value = val + 1;
        });
    }
});
</script>
