<?php
// pages/home.php — Version Intégrale Intégrant la Nomenclature des Images Réelles
// ============================================================================
require_once __DIR__ . '/../includes/core.php';
$pageTitle = current_lang() === 'fr' ? 'Accueil' : 'Home';

// Récupération des 4 derniers produits actifs
$pdo  = getDB();
$lang = current_lang();
$stmt = $pdo->prepare(
    "SELECT p.id, p.reference, p.name_{$lang} AS name, p.price, p.stock, p.image_path,
            c.name_{$lang} AS category
     FROM products p
     JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1
     ORDER BY p.created_at DESC LIMIT 4"
);
$stmt->execute();
$featured = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="hero" aria-label="Introduction Patapied">
    <div class="hero-content">
        <span class="hero-eyebrow">Chaussures urbaines ergonomiques · Made in France</span>
        <h1 class="hero-title">Marchez<br><em>autrement.</em></h1>
        <p class="hero-desc">
            <?php if ($lang === 'fr'): ?>
            Chaque paire Patapied fusionne ingénierie biomécanique et esthétique rétro-chic. Conçues pour l'exploration urbaine, adaptées à votre rythme.
            <?php else: ?>
            Every Patapied pair fuses biomechanical engineering with retro-chic aesthetics. Crafted for urban exploration, tailored to your pace.
            <?php endif; ?>
        </p>
        <div class="hero-actions">
            <a href="/index.php?page=catalog" class="btn btn-primary btn-lg">
                <?= t('nav_catalog') ?> →
            </a>
            <a href="/index.php?page=report" class="btn btn-ghost btn-lg">
                <?= t('nav_report') ?>
            </a>
        </div>
    </div>

    <div class="hero-visual">
        <div class="hero-image-container">
            <img src="images/hero-shoe.png" alt="Collection Premium Patapied" class="shoe-main-image">
        </div>
        <div class="hero-stats">
            <div class="stat-pill"><strong>+12K</strong> <span><?= $lang==='fr' ? 'paires vendues' : 'pairs sold' ?></span></div>
            <div class="stat-pill"><strong>4.9★</strong> <span>/ 5</span></div>
        </div>
    </div>
</section>

<section class="values-section" aria-label="<?= $lang==='fr' ? 'Nos engagements' : 'Our values' ?>">
    <div class="container">
        <div class="values-grid">
            
            <div class="value-card">
                <div class="value-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                        <path d="M12 6v10M9 9h6" />
                    </svg>
                </div>
                <h3><?= $lang==='fr' ? 'Ergonomie' : 'Ergonomics' ?></h3>
                <p><?= $lang==='fr' ? 'Semelles orthopédiques intégrées, co-développées avec des spécialistes du mouvement.' : 'Built-in orthopedic insoles, co-developed with movement specialists.' ?></p>
            </div>

            <div class="value-card">
                <div class="value-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
                    </svg>
                </div>
                <h3><?= $lang==='fr' ? 'Matériaux' : 'Materials' ?></h3>
                <p><?= $lang==='fr' ? 'Cuirs sélectionnés de haute qualité et tannage végétal éco-responsable.' : 'Selected high-quality leathers and eco-responsible vegetable tanning.' ?></p>
            </div>

            <div class="value-card">
                <div class="value-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        <path d="M9 6v6M12 5v8M15 6v6" opacity="0.5" />
                    </svg>
                </div>
                <h3><?= $lang==='fr' ? 'Fabrication' : 'Made in France' ?></h3>
                <p><?= $lang==='fr' ? 'Savoir-faire artisanal préservé depuis 1987 au sein de nos ateliers partenaires.' : 'Artisan craftsmanship preserved since 1987 within our partner workshops.' ?></p>
            </div>

            <div class="value-card">
                <div class="value-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                </div>
                <h3><?= $lang==='fr' ? 'Souveraineté' : 'Security' ?></h3>
                <p><?= $lang==='fr' ? 'Infrastructure résiliente, boutique sécurisée et données hébergées localement.' : 'Resilient infrastructure, secure storefront and locally hosted data.' ?></p>
            </div>

        </div>
    </div>
</section>

<section class="featured-section" aria-label="<?= $lang==='fr' ? 'Produits en vedette' : 'Featured products' ?>">
    <div class="container">
        <div class="section-header">
            <h2><?= $lang==='fr' ? 'Dernières Collections' : 'New Arrivals' ?></h2>
            <a href="/index.php?page=catalog" class="link-more"><?= $lang==='fr' ? 'Parcourir le catalogue →' : 'View all →' ?></a>
        </div>
        
        <div class="products-grid">
            <?php foreach ($featured as $p): ?>
            <article class="product-card" data-id="<?= $p['id'] ?>">
                <div class="product-img">
                    <?php 
                    // Génération dynamique du nom de fichier basé sur la référence en minuscules
                    $imgFilename = 'images/' . strtolower($p['reference']) . '.png';
                    $displayImg = (file_exists(__DIR__ . '/../' . $imgFilename)) ? $imgFilename : $p['image_path'];
                    
                    if ($displayImg): 
                    ?>
                        <img src="<?= h($displayImg) ?>" alt="<?= h($p['name']) ?>" class="shoe-catalog-image" loading="lazy">
                    <?php else: ?>
                        <div class="product-img-placeholder">
                            <svg viewBox="0 0 80 60" fill="none" class="shoe-mini" aria-hidden="true">
                                <path d="M8 42 Q10 28 22 24 L52 22 Q64 22 67 30 L68 42 Q62 48 40 49 Q16 49 8 42Z" fill="var(--forest)"/>
                                <path d="M8 42 Q8 49 18 51 L62 51 Q70 49 70 42" fill="var(--forest-dark)"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($p['stock'] === 0): ?>
                        <span class="badge-out"><?= t('cat_out_stock') ?></span>
                    <?php elseif ($p['stock'] <= 5): ?>
                        <span class="badge-low"><?= $lang==='fr' ? 'Stock limité' : 'Low stock' ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="product-info">
                    <span class="product-cat"><?= h($p['category']) ?></span>
                    <h3 class="product-name">
                        <a href="/index.php?page=product&id=<?= $p['id'] ?>"><?= h($p['name']) ?></a>
                    </h3>
                    <span class="product-ref"><code><?= h($p['reference']) ?></code></span>
                    
                    <div class="product-footer">
                        <span class="product-price"><?= number_format($p['price'], 2, ',', ' ') ?> €</span>
                        <a href="/index.php?page=product&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline">
                            <?= t('cat_details') ?>
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="report-teaser" aria-label="Transparence Technique">
    <div class="container">
        <div class="report-teaser-inner">
            <div class="report-teaser-text">
                <span class="report-tag">Architecture & Transparence — SAÉ 2.04</span>
                <h2><?= t('report_title') ?></h2>
                <p><?= t('report_subtitle') ?></p>
                <a href="/index.php?page=report" class="btn btn-primary">
                    <?= $lang==='fr' ? 'Consulter les spécifications' : 'View specifications' ?> →
                </a>
            </div>
            <div class="report-teaser-visual">
                <div class="milestone-mini" aria-hidden="true">
                    <span class="ms-dot done" title="Jalon 1 complété"></span>
                    <span class="ms-dot done" title="Jalon 2 complété"></span>
                    <span class="ms-dot active" title="Jalon 3 en cours"></span>
                    <span class="ms-dot"></span>
                    <span class="ms-dot"></span>
                </div>
                <p class="ms-label">
                    <code>Debian GNU/Linux</code> · <?= $lang==='fr' ? 'Jalon 3 / 5 actif' : 'Milestone 3/5 active' ?>
                </p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>