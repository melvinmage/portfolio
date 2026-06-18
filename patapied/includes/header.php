<?php
// includes/header.php — En-tête HTML commun
// ============================================================
$currentPage = $_GET['page'] ?? 'home';
$cartCount   = cart_count();
?>
<!DOCTYPE html>
<html lang="<?= current_lang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' — ' : '' ?>Patapied</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Inter:wght@300;400;500;600&family=Space+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body class="lang-<?= current_lang() ?>">

<!-- ─── TOP BAR ────────────────────────────────────────────── -->
<div class="topbar">
    <span class="topbar-msg">Livraison offerte dès 100€ · Free shipping from €100</span>
    <a href="?lang=<?= current_lang() === 'fr' ? 'en' : 'fr' ?>&page=<?= h($currentPage) ?>"
       class="lang-switch">
        <?= t('lang_toggle') ?> ↗
    </a>
</div>

<!-- ─── HEADER ─────────────────────────────────────────────── -->
<header class="site-header">
    <a href="/index.php" class="logo">
        <span class="logo-mark">P</span>
        <span class="logo-text">atapied</span>
    </a>

    <nav class="main-nav" aria-label="Navigation principale">
        <a href="/index.php" class="<?= $currentPage==='home' ? 'active' : '' ?>"><?= t('nav_home') ?></a>
        <a href="/index.php?page=catalog" class="<?= $currentPage==='catalog' ? 'active' : '' ?>"><?= t('nav_catalog') ?></a>
        <a href="/index.php?page=report" class="<?= $currentPage==='report' ? 'active' : '' ?>"><?= t('nav_report') ?></a>
        <?php if (is_admin()): ?>
        <a href="/admin/index.php" class="nav-admin"><?= t('nav_admin') ?></a>
        <?php endif; ?>
    </nav>

    <div class="header-actions">
        <?php if (is_logged()): ?>
            <a href="/index.php?page=account" class="btn-icon" title="<?= t('nav_account') ?>">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
            </a>
            <a href="/index.php?page=orders" class="btn-icon" title="<?= t('nav_orders') ?>">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
            </a>
        <?php else: ?>
            <a href="/index.php?page=login" class="btn-link"><?= t('nav_login') ?></a>
        <?php endif; ?>

        <a href="/index.php?page=cart" class="btn-cart" title="<?= t('nav_cart') ?>">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
            <?php if ($cartCount > 0): ?>
                <span class="cart-badge"><?= $cartCount ?></span>
            <?php endif; ?>
        </a>

        <?php if (is_logged()): ?>
            <a href="/index.php?page=logout" class="btn-link btn-logout"><?= t('nav_logout') ?></a>
        <?php endif; ?>
    </div>

    <button class="burger" id="burgerBtn" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>
</header>

<!-- Flash messages -->
<?php
$flashSuccess = get_flash('success');
$flashError   = get_flash('error');
if ($flashSuccess): ?>
<div class="flash flash-success" role="alert"><?= h($flashSuccess) ?></div>
<?php endif;
if ($flashError): ?>
<div class="flash flash-error" role="alert"><?= h($flashError) ?></div>
<?php endif; ?>

<main class="site-main">
