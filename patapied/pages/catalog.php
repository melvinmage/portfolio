<?php
// pages/catalog.php — Version Intégrale Optimisée avec Images Réelles
// ============================================================================
require_once __DIR__ . '/../includes/core.php';
$pageTitle = t('cat_title');
$lang = current_lang();
$pdo  = getDB();

// Récupération des catégories pour les filtres
$cats = $pdo->query("SELECT id, slug, name_{$lang} AS name FROM categories ORDER BY sort_order")->fetchAll();

// Extraction des filtres de recherche et catégorie
$catSlug = preg_replace('/[^a-z0-9_-]/', '', $_GET['cat'] ?? '');
$search  = trim($_GET['q'] ?? '');

// Construction de la requête sécurisée
$where  = ["p.is_active = 1"];
$params = [];

if ($catSlug) {
    $where[]              = "c.slug = :slug";
    $params[':slug']      = $catSlug;
}
if ($search) {
    $where[]              = "(p.name_{$lang} LIKE :q OR p.reference LIKE :q2)";
    $params[':q']         = '%' . $search . '%';
    $params[':q2']         = '%' . $search . '%';
}

$whereSQL = implode(' AND ', $where);
$stmt = $pdo->prepare(
    "SELECT p.id, p.reference, p.name_{$lang} AS name,
            p.description_{$lang} AS description,
            p.price, p.stock, p.image_path,
            c.name_{$lang} AS category, c.slug AS cat_slug
     FROM products p
     JOIN categories c ON c.id = p.category_id
     WHERE {$whereSQL}
     ORDER BY p.created_at DESC"
);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
}
$stmt->execute();
$products = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="catalog-section">
    <div class="container">

        <div class="catalog-header">
            <h1><?= t('cat_title') ?></h1>
            <form method="GET" action="/index.php" class="search-form" role="search">
                <input type="hidden" name="page" value="catalog">
                <?php if ($catSlug): ?>
                <input type="hidden" name="cat" value="<?= h($catSlug) ?>">
                <?php endif; ?>
                <input type="search" name="q" value="<?= h($search) ?>"
                       placeholder="<?= t('cat_search') ?>" class="search-input">
                <button type="submit" class="btn btn-sm">→</button>
                <?php if ($search): ?>
                <a href="/index.php?page=catalog<?= $catSlug ? '&cat='.$catSlug : '' ?>" class="btn btn-ghost btn-sm">✕</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="cat-filters">
            <a href="/index.php?page=catalog<?= $search ? '&q='.urlencode($search) : '' ?>"
               class="cat-pill <?= !$catSlug ? 'active' : '' ?>"><?= t('cat_all') ?></a>
            <?php foreach ($cats as $c): ?>
            <a href="/index.php?page=catalog&cat=<?= h($c['slug']) ?><?= $search ? '&q='.urlencode($search) : '' ?>"
               class="cat-pill <?= $catSlug===$c['slug'] ? 'active' : '' ?>"><?= h($c['name']) ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($products)): ?>
        <div class="empty-state">
            <p><?= t('cat_no_results') ?></p>
            <a href="/index.php?page=catalog" class="btn btn-ghost"><?= t('back') ?></a>
        </div>
        <?php else: ?>
        <div class="products-grid products-grid--full">
            <?php foreach ($products as $p): ?>
            <article class="product-card" data-id="<?= $p['id'] ?>">
                <a href="/index.php?page=product&id=<?= $p['id'] ?>" class="product-img-link">
                    <div class="product-img">
                        <?php 
                        // Résolution du chemin d'image basé sur la référence (ex: images/pat-urb-001.png)
                        $imgFilename = 'images/' . strtolower($p['reference']) . '.png';
                        $displayImg = (file_exists(__DIR__ . '/../' . $imgFilename)) ? $imgFilename : $p['image_path'];
                        
                        if ($displayImg): 
                        ?>
                            <img src="<?= h($displayImg) ?>" alt="<?= h($p['name']) ?>" class="shoe-catalog-image" loading="lazy">
                        <?php else: ?>
                            <div class="product-img-placeholder">
                                <svg viewBox="0 0 80 60" fill="none" aria-hidden="true"><path d="M8 42 Q10 28 22 24 L52 22 Q64 22 67 30 L68 42 Q62 48 40 49 Q16 49 8 42Z" fill="var(--forest)"/><path d="M8 42 Q8 49 18 51 L62 51 Q70 49 70 42" fill="var(--forest-dark)"/></svg>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($p['stock'] === 0): ?>
                            <span class="badge-out"><?= t('cat_out_stock') ?></span>
                        <?php elseif ($p['stock'] <= 5): ?>
                            <span class="badge-low"><?= current_lang()==='fr' ? 'Stock limité' : 'Low stock' ?></span>
                        <?php endif; ?>
                    </div>
                </a>
                
                <div class="product-info">
                    <span class="product-cat"><?= h($p['category']) ?></span>
                    <h3 class="product-name">
                        <a href="/index.php?page=product&id=<?= $p['id'] ?>"><?= h($p['name']) ?></a>
                    </h3>
                    <span class="product-ref"><code><?= h($p['reference']) ?></code></span>
                    
                    <?php if ($p['description']): ?>
                        <p class="product-desc-short"><?= h(mb_substr($p['description'], 0, 80)) ?>…</p>
                    <?php endif; ?>
                    
                    <div class="product-footer">
                        <span class="product-price"><?= number_format($p['price'], 2, ',', ' ') ?> €</span>
                        <div class="product-actions">
                            <?php if ($p['stock'] > 0): ?>
                            <form method="POST" action="/index.php?page=cart" class="add-cart-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action"     value="add">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-primary btn-sm"><?= t('cat_add_cart') ?></button>
                            </form>
                            <?php else: ?>
                                <span class="btn btn-disabled btn-sm"><?= t('cat_out_stock') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>