<?php
// index.php — Routeur principal Patapied
// ============================================================

require_once __DIR__ . '/includes/core.php';

// ── Changement de langue ────────────────────────────────────
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    // Redirige sans le paramètre lang mais garde les autres
    $params = $_GET;
    unset($params['lang']);
    $qs = http_build_query($params);
    header('Location: /index.php' . ($qs ? '?' . $qs : ''));
    exit;
}

// ── Routage ─────────────────────────────────────────────────
$page = preg_replace('/[^a-z0-9_-]/', '', strtolower($_GET['page'] ?? 'home'));

$allowed = [
    'home', 'catalog', 'product',
    'login', 'register', 'logout',
    'account', 'orders', 'order-detail',
    'cart', 'checkout', 'checkout-confirm',
    'report',
];

if (!in_array($page, $allowed)) {
    $page = 'home';
}

$pageFile = __DIR__ . '/pages/' . $page . '.php';
if (!file_exists($pageFile)) {
    $page     = 'home';
    $pageFile = __DIR__ . '/pages/home.php';
}

require $pageFile;
