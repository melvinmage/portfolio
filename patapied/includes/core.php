<?php
// includes/core.php — Démarrage session, i18n, helpers globaux
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

require_once __DIR__ . '/../config/db.php';

// ---- i18n ---------------------------------------------------
$_lang = $_SESSION['lang'] ?? 'fr';
if (!in_array($_lang, ['fr', 'en'])) $_lang = 'fr';
$_t = require __DIR__ . "/../lang/{$_lang}.php";

function t(string $key): string {
    global $_t;
    return htmlspecialchars($_t[$key] ?? $key, ENT_QUOTES, 'UTF-8');
}

function raw_t(string $key): string {
    global $_t;
    return $_t[$key] ?? $key;
}

function current_lang(): string {
    return $_SESSION['lang'] ?? 'fr';
}

// ---- Auth helpers -------------------------------------------
function is_logged(): bool {
    return isset($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged()) return null;
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool {
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

function require_login(string $redirect = '/index.php?page=login'): void {
    if (!is_logged()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        die('<p>Accès refusé.</p>');
    }
}

// ---- CSRF ---------------------------------------------------
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        die('CSRF token invalide.');
    }
}

// ---- Flash messages -----------------------------------------
function flash(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}

function get_flash(string $key): ?string {
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

// ---- Panier (session) helpers --------------------------------
function cart_count(): int {
    return array_sum($_SESSION['cart'] ?? []);
}

function lang_label(string $field): string {
    // Renvoie la colonne adaptée à la langue courante (name_fr / name_en)
    return $field . '_' . current_lang();
}

// ---- XSS helper ----------------------------------------------
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// ---- Redirect -----------------------------------------------
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}
