<?php
// admin/pages/products.php — Gestion du catalogue produits
require_once __DIR__ . '/../../includes/core.php';
require_admin();

$lang = current_lang();
$pdo  = getDB();

// Traitement de la mise à jour rapide des stocks à la volée
if (isset($_POST['update_stock_id']) && isset($_POST['new_stock'])) {
    $prodId   = (int)$_POST['update_stock_id'];
    $newStock = (int)$_POST['new_stock'];
    if ($newStock >= 0) {
        $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $stmt->execute([$newStock, $prodId]);
        set_flash('success', $lang === 'fr' ? 'Niveau de stock réaligné.' : 'Stock level realigned.');
    }
    header('Location: /admin/index.php?section=products');
    exit;
}

// Extraction avec jointure pour récupérer le nom de la catégorie associée
$stmt = $pdo->query("
    SELECT p.id, p.reference, p.name_fr, p.name_en, p.price, p.stock, c.name_fr as cat_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    ORDER BY p.stock ASC, p.reference ASC
");
$products = $stmt->fetchAll();

$adminPageTitle = $lang === 'fr' ? 'Gestion du Catalogue' : 'Product Inventory';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-page-header" style="margin-bottom: 24px; border-bottom: 1px solid var(--cream-dark); padding-bottom: 12px;">
    <h1 class="admin-page-title" style="font-family: var(--font-serif); color: var(--ink); font-size: 28px; font-weight: 400; margin:0;"><?= $lang === 'fr' ? 'Catalogue Produits' : 'Product Catalogue' ?></h1>
    <span class="admin-page-sub" style="font-family: var(--font-sans); color: var(--ink-light); font-size: 14px;"><?= $lang === 'fr' ? 'Ajustement des prix, des stocks et de la visibilité des modèles.' : 'Ajust prices, stock amounts and product availability.' ?></span>
</div>

<div class="admin-card" style="background: var(--white); border: 1px solid var(--cream-dark); border-radius: 8px; padding: 24px; box-shadow: var(--shadow-sm);">
    <div class="table-wrap" style="overflow-x: auto;">
        <table class="admin-table" style="width: 100%; border-collapse: collapse; font-family: var(--font-sans); font-size: 13px;">
            <thead>
                <tr style="text-align: left;">
                    <th style="padding: 10px 8px; color: var(--ink-light); font-weight: 600; border-bottom: 2px solid var(--cream-dark); text-transform: uppercase; font-size: 11px;">Référence</th>
                    <th style="padding: 10px 8px; color: var(--ink-light); font-weight: 600; border-bottom: 2px solid var(--cream-dark); text-transform: uppercase; font-size: 11px;">Modèle</th>
                    <th style="padding: 10px 8px; color: var(--ink-light); font-weight: 600; border-bottom: 2px solid var(--cream-dark); text-transform: uppercase; font-size: 11px;">Catégorie</th>
                    <th style="padding: 10px 8px; color: var(--ink-light); font-weight: 600; border-bottom: 2px solid var(--cream-dark); text-transform: uppercase; font-size: 11px;">Tarif unique</th>
                    <th style="padding: 10px 8px; color: var(--ink-light); font-weight: 600; border-bottom: 2px solid var(--cream-dark); text-transform: uppercase; font-size: 11px; text-align: center;">Unités en Stock</th>
                    <th style="padding: 10px 8px; color: var(--ink-light); font-weight: 600; border-bottom: 2px solid var(--cream-dark); text-transform: uppercase; font-size: 11px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p): ?>
                <tr style="border-bottom: 1px solid var(--cream-dark);">
                    <td style="padding: 12px 8px; font-family: var(--font-mono); font-size: 12px; color: var(--ink-light);"><?= h($p['reference']) ?></td>
                    <td style="padding: 12px 8px; color: var(--ink); font-weight: 600;"><?= h($lang === 'fr' ? $p['name_fr'] : $p['name_en']) ?></td>
                    <td style="padding: 12px 8px; color: var(--ink-mid);"><?= h($p['cat_name']) ?></td>
                    <td style="padding: 12px 8px; font-family: var(--font-mono); color: var(--ink); font-weight: 500;"><?= number_format($p['price'], 2, ',', ' ') ?> €</td>
                    <td style="padding: 12px 8px; text-align: center;">
                        <span class="stock-badge <?= $p['stock'] <= 5 ? 'stock-low' : '' ?>" style="display: inline-block; padding: 2px 6px; border-radius: 4px; font-family: var(--font-mono); font-size: 12px; font-weight: 700; <?= $p['stock'] <= 5 ? 'background: rgba(220, 38, 38, 0.1); color: var(--status-cancelled);' : 'background: rgba(45, 106, 79, 0.1); color: var(--forest);' ?>">
                            <?= (int)$p['stock'] ?> u
                        </span>
                    </td>
                    <td style="padding: 12px 8px; text-align: right;">
                        <form method="POST" action="/admin/index.php?section=products" style="margin:0; display:inline-flex; gap:6px; align-items:center; justify-content: flex-end;">
                            <input type="hidden" name="update_stock_id" value="<?= $p['id'] ?>">
                            <input type="number" name="new_stock" value="<?= (int)$p['stock'] ?>" min="0" style="width: 54px; padding: 4px; border: 1px solid var(--cream-dark); border-radius: 4px; font-family: var(--font-mono); font-size: 12px; text-align: center;">
                            <button type="submit" class="btn-ghost" style="font-family: var(--font-sans); font-size: 11px; font-weight: 600; color: var(--forest); background: none; padding: 4px 8px; border-radius: 4px; border: 1px solid var(--cream-dark); cursor: pointer;">Mettre à jour</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>