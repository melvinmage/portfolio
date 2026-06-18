<?php
// admin/pages/dashboard.php — Tableau de bord
require_once __DIR__ . '/../../includes/core.php';
require_admin();

$lang = current_lang();
$pdo  = getDB();

// ── KPIs ────────────────────────────────────────────────────
$kpis = [];

$r = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'");
$kpis['customers'] = (int)$r->fetchColumn();

$r = $pdo->query("SELECT COUNT(*), COALESCE(SUM(total_amount),0) FROM orders");
$row = $r->fetch(PDO::FETCH_NUM);
$kpis['orders']  = (int)$row[0];
$kpis['revenue'] = (float)$row[1];

$r = $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 5 AND is_active=1");
$kpis['low_stock'] = (int)$r->fetchColumn();

$r = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'");
$kpis['pending_orders'] = (int)$r->fetchColumn();

// Optionnel pour l'oral : Compteur d'alertes SOC pour faire le lien avec votre sécurité
$r = $pdo->query("SELECT COUNT(*) FROM soc_alerts WHERE status='critical'");
$kpis['soc_critical'] = (int)$r->fetchColumn();

// ── Dernières commandes ──────────────────────────────────────
$stmt = $pdo->query(
    "SELECT o.id, o.status, o.total_amount, o.created_at,
            u.first_name, u.last_name, u.email
     FROM orders o
     JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC LIMIT 8"
);
$lastOrders = $stmt->fetchAll();

// ── Stock bas ────────────────────────────────────────────────
$stmt = $pdo->query(
    "SELECT p.reference, p.name_fr, p.name_en, p.stock
     FROM products p WHERE p.stock <= 5 AND p.is_active=1
     ORDER BY p.stock ASC LIMIT 6"
);
$lowStock = $stmt->fetchAll();

$adminPageTitle = $lang === 'fr' ? 'Tableau de bord' : 'Dashboard';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<style>
    /* Intégration harmonieuse avec le fond crème du site */
    .admin-main {
        background-color: var(--cream);
        padding: 30px;
    }

    .admin-page-header {
        margin-bottom: 24px;
        border-bottom: 1px solid var(--cream-dark);
        padding-bottom: 12px;
    }
    .admin-page-title {
        font-family: var(--font-serif);
        color: var(--ink);
        font-size: 28px;
        font-weight: 400;
    }
    .admin-page-sub {
        font-family: var(--font-sans);
        color: var(--ink-light);
        font-size: 14px;
    }

    /* ── RECONSTRUCTION DES SQUÉLETTES DE CARTES KPI ── */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }
    .kpi-card {
        background: var(--white);
        border: 1px solid var(--cream-dark);
        border-radius: 8px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: var(--shadow-sm);
    }
    .kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    /* Surcharges de nuances basées sur vos variables de jetons */
    .kpi-icon--teal   { background: rgba(64, 145, 108, 0.1); color: var(--forest-mid); }
    .kpi-icon--blue   { background: rgba(37, 99, 235, 0.1); color: var(--status-shipped); }
    .kpi-icon--green  { background: rgba(45, 106, 79, 0.1); color: var(--forest); }
    .kpi-icon--orange { background: rgba(233, 118, 43, 0.1); color: var(--status-pending); }
    .kpi-icon--yellow { background: rgba(220, 38, 38, 0.1); color: var(--status-cancelled); }

    .kpi-data {
        display: flex;
        flex-direction: column;
    }
    .kpi-value {
        font-family: var(--font-mono);
        font-size: 24px;
        font-weight: 700;
        color: var(--ink);
        line-height: 1.2;
    }
    .kpi-label {
        font-family: var(--font-sans);
        font-size: 12px;
        color: var(--ink-light);
        font-weight: 500;
        margin-top: 2px;
    }

    /* Condition de mise en évidence pour les stocks bas et les alertes */
    .kpi-card--warn { border-color: var(--status-pending); background: rgba(233, 118, 43, 0.02); }
    .kpi-card--info { border-color: var(--status-cancelled); background: rgba(220, 38, 38, 0.01); }

    /* ── MISE EN PAGE DU COEUR DE PANNEAU ── */
    .admin-two-cols {
        display: grid;
        grid-template-columns: 1.6fr 1fr;
        gap: 24px;
    }
    .admin-card {
        background: var(--white);
        border: 1px solid var(--cream-dark);
        border-radius: 8px;
        padding: 24px;
        box-shadow: var(--shadow-sm);
    }
    .admin-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--cream-dark);
        padding-bottom: 14px;
        margin-bottom: 18px;
    }
    .admin-card-header h2 {
        font-family: var(--font-serif);
        color: var(--ink);
        font-size: 20px;
        margin: 0;
    }
    
    /* Boutons fantômes d'alignement */
    .btn-ghost {
        font-family: var(--font-sans);
        font-size: 12px;
        font-weight: 600;
        color: var(--forest);
        text-decoration: none;
        padding: 6px 12px;
        border-radius: 4px;
        border: 1px solid var(--cream-dark);
        transition: background 0.15s;
    }
    .btn-ghost:hover {
        background: var(--cream);
    }

    /* ── RE-DESIGN STRUCTUREL DES TABLEAUX ADMINISTRATIVE ── */
    .admin-table {
        width: 100%;
        border-collapse: collapse;
        font-family: var(--font-sans);
        font-size: 13px;
    }
    .admin-table th {
        padding: 10px 8px;
        color: var(--ink-light);
        font-weight: 600;
        border-bottom: 2px solid var(--cream-dark);
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.02em;
    }
    .admin-table td {
        padding: 12px 8px;
        border-bottom: 1px solid var(--cream-dark);
        color: var(--ink-mid);
    }
    .admin-table tr:hover td {
        background: var(--cream);
    }
    .td-mono {
        font-family: var(--font-mono);
        font-size: 12px;
        color: var(--ink-light);
    }

    /* Étiquettes et capsules de statuts */
    .status-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-pending { background: rgba(233, 118, 43, 0.1); color: var(--status-pending); }
    .status-completed { background: rgba(45, 106, 79, 0.1); color: var(--forest); }
    
    .stock-badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 4px;
        font-family: var(--font-mono);
        font-size: 12px;
        font-weight: 700;
    }
    .stock-low { background: rgba(220, 38, 38, 0.1); color: var(--status-cancelled); }

    .admin-empty {
        background: var(--cream);
        border: 1px dashed var(--stone);
        padding: 14px;
        border-radius: 6px;
        color: var(--forest);
        font-size: 13px;
        margin: 0;
    }
</style>

<div class="admin-page-header">
    <h1 class="admin-page-title"><?= $lang === 'fr' ? 'Tableau de bord' : 'Dashboard' ?></h1>
    <span class="admin-page-sub"><?= $lang === 'fr' ? 'Vue d\'ensemble en temps réel de l\'infrastructure et des ventes' : 'Real-time infrastructure and sales overview' ?></span>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon kpi-icon--teal">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
        </div>
        <div class="kpi-data">
            <span class="kpi-value"><?= number_format($kpis['customers']) ?></span>
            <span class="kpi-label"><?= $lang === 'fr' ? 'Clients Patapied' : 'Customers' ?></span>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon kpi-icon--blue">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
        </div>
        <div class="kpi-data">
            <span class="kpi-value"><?= number_format($kpis['orders']) ?></span>
            <span class="kpi-label"><?= $lang === 'fr' ? 'Commandes' : 'Orders' ?></span>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon kpi-icon--green">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
        <div class="kpi-data">
            <span class="kpi-value"><?= number_format($kpis['revenue'], 2, ',', ' ') ?> €</span>
            <span class="kpi-label"><?= $lang === 'fr' ? 'Chiffre d\'affaires' : 'Revenue' ?></span>
        </div>
    </div>
    <div class="kpi-card <?= $kpis['low_stock'] > 0 ? 'kpi-card--warn' : '' ?>">
        <div class="kpi-icon kpi-icon--orange">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
        </div>
        <div class="kpi-data">
            <span class="kpi-value"><?= $kpis['low_stock'] ?></span>
            <span class="kpi-label"><?= $lang === 'fr' ? 'Stocks bas (≤5)' : 'Low stock (≤5)' ?></span>
        </div>
    </div>
    <div class="kpi-card <?= $kpis['soc_critical'] > 0 ? 'kpi-card--info' : '' ?>">
        <div class="kpi-icon kpi-icon--yellow">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="kpi-data">
            <span class="kpi-value" style="color:var(--status-cancelled);"><?= $kpis['soc_critical'] ?></span>
            <span class="kpi-label"><?= $lang === 'fr' ? 'Alertes SOC Actives' : 'SOC Incidents' ?></span>
        </div>
    </div>
</div>

<div class="admin-two-cols">
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h2><?= $lang === 'fr' ? 'Dernières commandes' : 'Latest orders' ?></h2>
            <a href="/admin/index.php?section=orders" class="btn-ghost"><?= $lang === 'fr' ? 'Voir tout' : 'View all' ?></a>
        </div>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= $lang === 'fr' ? 'Client' : 'Customer' ?></th>
                        <th><?= $lang === 'fr' ? 'Montant' : 'Amount' ?></th>
                        <th><?= $lang === 'fr' ? 'Statut' : 'Status' ?></th>
                        <th><?= $lang === 'fr' ? 'Date' : 'Date' ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lastOrders as $o): ?>
                    <tr>
                        <td class="td-mono"><a href="/admin/index.php?section=order-detail&id=<?= $o['id'] ?>" style="color: var(--forest); font-weight: 600;">#<?= $o['id'] ?></a></td>
                        <td><strong style="color: var(--ink);"><?= h($o['first_name'] . ' ' . $o['last_name']) ?></strong></td>
                        <td class="td-mono" style="font-weight: 600; color: var(--ink);"><?= number_format($o['total_amount'], 2, ',', ' ') ?> €</td>
                        <td>
                            <span class="status-badge status-<?= h($o['status'] === 'pending' ? 'pending' : 'completed') ?>">
                                <?= h($o['status'] === 'pending' ? 'En attente' : 'Validé') ?>
                            </span>
                        </td>
                        <td class="td-mono"><?= date('d/m/y H:i', strtotime($o['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($lastOrders)): ?>
                    <tr><td colspan="5" style="text-align: center; color: var(--ink-light); padding: 20px;"><?= $lang === 'fr' ? 'Aucune commande enregistrée.' : 'No orders.' ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h2><?= $lang === 'fr' ? 'Alertes stock bas' : 'Low stock alerts' ?></h2>
            <a href="/admin/index.php?section=products" class="btn-ghost"><?= $lang === 'fr' ? 'Gérer' : 'Manage' ?></a>
        </div>
        <?php if (empty($lowStock)): ?>
            <p class="admin-empty"><?= $lang === 'fr' ? '✓ Tous les stocks sont suffisants.' : '✓ All stock levels are sufficient.' ?></p>
        <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><?= $lang === 'fr' ? 'Référence' : 'Reference' ?></th>
                        <th><?= $lang === 'fr' ? 'Produit' : 'Product' ?></th>
                        <th style="text-align: center;"><?= $lang === 'fr' ? 'Stock' : 'Stock' ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lowStock as $p): ?>
                    <tr>
                        <td class="td-mono"><?= h($p['reference']) ?></td>
                        <td style="font-weight: 500; color: var(--ink);"><?= h($p['name_fr']) ?></td>
                        <td style="text-align: center;"><span class="stock-badge stock-low"><?= (int)$p['stock'] ?> u</span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>