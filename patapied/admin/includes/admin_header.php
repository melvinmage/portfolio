<?php
// admin/includes/admin_header.php — En-tête et Navbar de l'Espace d'Administration Patapied
if (!function_exists('is_admin') || !is_admin()) {
    http_response_code(403); die('Accès refusé.');
}
$adminSection = $_GET['section'] ?? 'dashboard';
$lang = current_lang();

// Structure sémantique de la barre latérale
$navItems = [
    'dashboard'    => ['icon' => 'grid',       'fr' => 'Tableau de bord',   'en' => 'Dashboard'],
    'products'     => ['icon' => 'box',        'fr' => 'Produits',           'en' => 'Products'],
    'orders'       => ['icon' => 'list',       'fr' => 'Commandes',          'en' => 'Orders'],
    'users'        => ['icon' => 'users',      'fr' => 'Utilisateurs',       'en' => 'Users'],
    'soc'          => ['icon' => 'shield',     'fr' => 'Supervision SOC',    'en' => 'SOC Supervision'],
];

// Dictionnaire des icônes SVG vectorielles
$icons = [
    'grid'   => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
    'box'    => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
    'list'   => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
    'users'  => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>',
    'shield' => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
];
?>
<!DOCTYPE html>
<html lang="<?= current_lang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($adminPageTitle) ? h($adminPageTitle) . ' — ' : '' ?>Admin · Patapied</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Inter:wght@300;400;500;600;700&family=Space+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
    
    <style>
        body.admin-body {
            padding-top: 0 !important; /* On annule le padding de la boutique publique */
            background-color: var(--cream);
            margin: 0;
            font-family: var(--font-sans);
        }

        /* Conteneur principal en grille split */
        .admin-layout-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ─── BARRE LATÉRALE VERT FORÊT PATAPIED (Inspiration 54c983) ─── */
        .admin-sidebar {
            width: 260px;
            background-color: var(--forest-dark);
            color: var(--white);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1000;
            box-shadow: var(--shadow-lg);
        }

        .admin-sidebar-header {
            padding: 24px 20px;
            background-color: rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .admin-logo {
            display: flex;
            align-items: baseline;
            text-decoration: none;
        }
        .admin-logo .logo-mark { color: var(--cream); font-family: var(--font-serif); font-size: 1.5rem; }
        .admin-logo .logo-text { color: var(--white); font-family: var(--font-serif); font-size: 1.3rem; font-weight: bold; margin-left: 2px; }

        /* Liens de navigation interne */
        .admin-nav {
            display: flex;
            flex-direction: column;
            padding: 24px 12px;
            gap: 4px;
            flex: 1;
        }

        .admin-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: all 0.15s ease;
        }

        .admin-nav-item svg {
            transition: transform 0.2s ease;
            flex-shrink: 0;
        }

        .admin-nav-item:hover {
            color: var(--white);
            background-color: rgba(255, 255, 255, 0.05);
        }

        /* État actif calqué sur ton image d'inspiration */
        .admin-nav-item.active {
            background-color: var(--forest-mid);
            color: var(--white);
            font-weight: 600;
        }
        .admin-nav-item.active svg {
            transform: scale(1.05);
        }

        /* Zone basse (Retour site et déconnexion) */
        .admin-sidebar-footer {
            padding: 16px 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            flex-direction: column;
            gap: 4px;
            background-color: rgba(0, 0, 0, 0.1);
        }

        .admin-sidebar-footer .admin-nav-item {
            padding: 10px 16px;
            font-size: 13px;
        }

        .admin-logout {
            color: #fca5a5 !important;
        }
        .admin-logout:hover {
            background-color: rgba(220, 38, 38, 0.15) !important;
        }

        /* ─── ZONE DE CONTENU PRINCIPALE (A DROITE) ─── */
        .admin-content-container {
            flex: 1;
            margin-left: 260px; /* Largeur exacte de la sidebar pour ne pas chevaucher */
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        /* Topbar d'action supérieure blanche */
        .admin-topbar {
            height: 64px;
            background-color: var(--white);
            border-bottom: 1px solid var(--cream-dark);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            box-shadow: var(--shadow-sm);
        }

        .admin-topbar-title {
            font-family: var(--font-serif);
            font-size: 18px;
            color: var(--ink);
            font-weight: 400;
        }

        .admin-topbar-right {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .admin-topbar-user {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--ink-mid);
            font-weight: 500;
        }
        .admin-topbar-user svg {
            color: var(--ink-light);
        }

        /* Bouton de redirection vers la boutique publique */
        .btn-view-store {
            font-size: 12px;
            font-weight: 600;
            color: var(--ink-mid);
            border: 1px solid var(--stone);
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            background-color: var(--white);
            transition: all 0.15s ease;
        }
        .btn-view-store:hover {
            background-color: var(--cream);
            border-color: var(--ink-light);
        }

        /* Bouton burger pour la gestion responsive minimale */
        .admin-burger {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--ink);
        }

        .admin-main {
            padding: 40px;
            flex: 1;
        }

        /* Ajustements pour les petits écrans de tablettes/portables */
        @media (max-width: 992px) {
            .admin-burger { display: block; margin-right: 16px; }
            .admin-sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
            .admin-sidebar.open { transform: translateX(0); }
            .admin-content-container { margin-left: 0; }
            .admin-topbar { padding: 0 20px; justify-content: flex-start; }
            .admin-topbar-right { margin-left: auto; }
        }
    </style>
</head>
<body class="admin-body lang-<?= current_lang() ?>">

<div class="admin-layout-wrapper">
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-header">
            <a href="/index.php" class="admin-logo">
                <span class="logo-mark">P</span>
                <span class="logo-text">atapied</span>
            </a>
        </div>

        <nav class="admin-nav">
            <?php foreach ($navItems as $key => $item): ?>
            <a href="/admin/index.php?section=<?= $key ?>"
               class="admin-nav-item <?= ($adminSection === $key || str_starts_with($adminSection, $key)) ? 'active' : '' ?>">
                <?= $icons[$item['icon']] ?>
                <span><?= $lang === 'fr' ? $item['fr'] : $item['en'] ?></span>
            </a>
            <?php endforeach; ?>
        </nav>

        <div class="admin-sidebar-footer">
            <a href="/index.php" class="admin-nav-item admin-back-site">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <span><?= $lang === 'fr' ? 'Retour au site' : 'Back to site' ?></span>
            </a>
            <a href="/index.php?page=logout" class="admin-nav-item admin-logout">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span><?= $lang === 'fr' ? 'Déconnexion' : 'Sign out' ?></span>
            </a>
        </div>
    </aside>

    <div class="admin-content-container">
        <header class="admin-topbar">
            <button class="admin-burger" id="adminBurger" aria-label="Toggle menu">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            
            <div class="admin-topbar-title"><?= isset($adminPageTitle) ? h($adminPageTitle) : ($lang === 'fr' ? 'Administration' : 'Administration') ?></div>
            
            <div class="admin-topbar-right">
                <a href="/" class="btn-view-store">
                    <?= $lang === 'fr' ? 'Voir la boutique ↗' : 'View Store ↗' ?>
                </a>
                <div class="admin-topbar-user">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                    <span><?= h(current_user()['first_name'] . ' ' . current_user()['last_name']) ?></span>
                </div>
            </div>
        </header>

        <main class="admin-main">
        <?php
        $flashSuccess = get_flash('success');
        $flashError   = get_flash('error');
        if ($flashSuccess): ?>
        <div class="flash flash-success" role="alert"><?= h($flashSuccess) ?></div>
        <?php endif;
        if ($flashError): ?>
        <div class="flash flash-error" role="alert"><?= h($flashError) ?></div>
        <?php endif; ?>