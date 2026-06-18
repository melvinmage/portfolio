<?php
// admin/index.php — Routeur du panneau d'administration Patapied
// ============================================================
// Toutes les pages admin exigent le rôle 'admin'.
// ============================================================

require_once __DIR__ . '/../includes/core.php';
require_admin();

$section = preg_replace('/[^a-z0-9_-]/', '', strtolower($_GET['section'] ?? 'dashboard'));

$allowed = [
    'dashboard',
    'products', 'product-add', 'product-edit', 'product-delete',
    'orders',   'order-detail', 'order-status',
    'users',    'user-edit',    'user-toggle',
    'soc',      'soc_api', // <-- AJOUTE CETTE LIGNE ICI
];

if (!in_array($section, $allowed)) {
    $section = 'dashboard';
}

$file = __DIR__ . '/pages/' . $section . '.php';
if (!file_exists($file)) {
    $section = 'dashboard';
    $file    = __DIR__ . '/pages/dashboard.php';
}

require $file;
