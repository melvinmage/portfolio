<?php
// pages/login.php — Connexion & inscription
require_once __DIR__ . '/../includes/core.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged()) redirect('/index.php');

$mode  = ($_GET['mode'] ?? 'login') === 'register' ? 'register' : 'login';
$error = null;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $error = auth_login(
            trim($_POST['email']    ?? ''),
            $_POST['password'] ?? ''
        );
        if (!$error) {
            flash('success', current_lang()==='fr' ? 'Bienvenue !' : 'Welcome back!');
            $redir = $_SESSION['redirect_after_login'] ?? '/index.php';
            unset($_SESSION['redirect_after_login']);
            redirect($redir);
        } else {
            // Notification silencieuse pour le SOC (Fail2Ban) avec horodatage
 // Notification silencieuse pour le SOC (Fail2Ban) avec horodatage français
            $ip_attaquant = $_SERVER['REMOTE_ADDR'];
            date_default_timezone_set('Europe/Paris'); // Synchronisation de l'horloge
            $date_exacte = date('Y-m-d H:i:s');
            error_log("[$date_exacte] Failed password for web user from $ip_attaquant\n", 3, "/var/log/patapied_web.log");
        }
    } elseif ($action === 'register') {
        $error = auth_register($_POST);
        if (!$error) {
            flash('success', current_lang()==='fr' ? 'Compte créé. Bienvenue !' : 'Account created. Welcome!');
            redirect('/index.php?page=account');
        }
        $mode = 'register';
    }
}

$pageTitle = $mode === 'login' ? t('auth_login_title') : t('auth_register_title');
require_once __DIR__ . '/../includes/header.php';
?>

<section class="auth-section">
    <div class="auth-card">
        <div class="auth-tabs">
            <a href="/index.php?page=login&mode=login"
               class="auth-tab <?= $mode==='login' ? 'active' : '' ?>"><?= t('auth_login_title') ?></a>
            <a href="/index.php?page=login&mode=register"
               class="auth-tab <?= $mode==='register' ? 'active' : '' ?>"><?= t('auth_register_title') ?></a>
        </div>

        <?php if ($error): ?>
        <div class="flash flash-error"><?= h($error) ?></div>
        <?php endif; ?>

        <?php if ($mode === 'login'): ?>
        <form method="POST" action="/index.php?page=login&mode=login" class="auth-form" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="login">

            <div class="form-group">
                <label for="email"><?= t('auth_email') ?></label>
                <input type="email" id="email" name="email"
                       value="<?= h($_POST['email'] ?? '') ?>"
                       autocomplete="email" required>
            </div>
            <div class="form-group">
                <label for="password"><?= t('auth_password') ?></label>
                <input type="password" id="password" name="password"
                       autocomplete="current-password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-full"><?= t('auth_login_btn') ?></button>
        </form>

        <?php else: ?>
        <form method="POST" action="/index.php?page=login&mode=register" class="auth-form" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="register">

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name"><?= t('auth_firstname') ?></label>
                    <input type="text" id="first_name" name="first_name"
                           value="<?= h($_POST['first_name'] ?? '') ?>"
                           autocomplete="given-name" required>
                </div>
                <div class="form-group">
                    <label for="last_name"><?= t('auth_lastname') ?></label>
                    <input type="text" id="last_name" name="last_name"
                           value="<?= h($_POST['last_name'] ?? '') ?>"
                           autocomplete="family-name" required>
                </div>
            </div>
            <div class="form-group">
                <label for="email"><?= t('auth_email') ?></label>
                <input type="email" id="email" name="email"
                       value="<?= h($_POST['email'] ?? '') ?>"
                       autocomplete="email" required>
            </div>
            <div class="form-group">
                <label for="password"><?= t('auth_password') ?></label>
                <input type="password" id="password" name="password"
                       autocomplete="new-password" minlength="8" required>
                <small><?= current_lang()==='fr' ? '8 caractères minimum' : 'Minimum 8 characters' ?></small>
            </div>
            <div class="form-group">
                <label for="confirm_pw"><?= t('auth_confirm_pw') ?></label>
                <input type="password" id="confirm_pw" name="confirm_pw"
                       autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-full"><?= t('auth_register_btn') ?></button>
        </form>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>