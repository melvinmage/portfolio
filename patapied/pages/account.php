<?php
// pages/account.php
require_once __DIR__ . '/../includes/core.php';
require_login();
$pdo  = getDB();
$user = current_user();
$lang = current_lang();
$error   = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fname   = trim($_POST['first_name']  ?? '');
        $lname   = trim($_POST['last_name']   ?? '');
        $phone   = trim($_POST['phone']       ?? '');
        $address = trim($_POST['address']     ?? '');
        $city    = trim($_POST['city']        ?? '');
        $postal  = trim($_POST['postal_code'] ?? '');
        $country = trim($_POST['country']     ?? '');

        if (empty($fname) || empty($lname)) {
            $error = raw_t('required_field');
        } else {
            $upd = $pdo->prepare(
                "UPDATE users SET first_name=:fn, last_name=:ln, phone=:ph,
                 address=:addr, city=:city, postal_code=:postal, country=:country
                 WHERE id=:id"
            );
            $upd->bindValue(':fn',     $fname,   PDO::PARAM_STR);
            $upd->bindValue(':ln',     $lname,   PDO::PARAM_STR);
            $upd->bindValue(':ph',     $phone,   PDO::PARAM_STR);
            $upd->bindValue(':addr',   $address, PDO::PARAM_STR);
            $upd->bindValue(':city',   $city,    PDO::PARAM_STR);
            $upd->bindValue(':postal', $postal,  PDO::PARAM_STR);
            $upd->bindValue(':country',$country, PDO::PARAM_STR);
            $upd->bindValue(':id',     $user['id'], PDO::PARAM_INT);
            $upd->execute();

            // Mettre à jour la session
            $_SESSION['user']['first_name'] = $fname;
            $_SESSION['user']['last_name']  = $lname;
            flash('success', raw_t('account_saved'));
            redirect('/index.php?page=account');
        }
    } elseif ($action === 'change_pw') {
        $current = $_POST['current_pw'] ?? '';
        $new     = $_POST['new_pw']     ?? '';
        $confirm = $_POST['confirm_pw'] ?? '';
        

        $row = $pdo->prepare("SELECT password FROM users WHERE id = :id");
        $row->bindValue(':id', $user['id'], PDO::PARAM_INT);
        $row->execute();
        $dbPw = $row->fetchColumn();
// ligne modifier pour le fail2ban
     if (!password_verify($current, $dbPw)) {
            $error = $lang==='fr' ? 'Mot de passe actuel incorrect.' : 'Current password is wrong.';
            
            // Notification silencieuse pour le SOC (Fail2Ban)
            $ip_attaquant = $_SERVER['REMOTE_ADDR'];
            error_log("Failed password for web user from $ip_attaquant\n", 3, "/var/log/auth.log");
            
        } elseif (strlen($new) < 8) {
            $error = $lang==='fr' ? '8 caractères minimum.' : 'Minimum 8 characters.';
        } elseif ($new !== $confirm) {
            $error = raw_t('auth_pw_mismatch');
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $upd  = $pdo->prepare("UPDATE users SET password = :pw WHERE id = :id");
            $upd->bindValue(':pw', $hash, PDO::PARAM_STR);
            $upd->bindValue(':id', $user['id'], PDO::PARAM_INT);
            $upd->execute();
            flash('success', $lang==='fr' ? 'Mot de passe modifié.' : 'Password updated.');
            redirect('/index.php?page=account');
        }
    }
}

// Récupérer les données complètes
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
$stmt->execute();
$userData = $stmt->fetch();

$pageTitle = t('account_title');
require_once __DIR__ . '/../includes/header.php';
?>

<section class="account-section">
    <div class="container">
        <h1><?= t('account_title') ?></h1>

        <?php if ($error): ?>
        <div class="flash flash-error"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="account-grid">

            <!-- Informations personnelles -->
            <div class="account-card">
                <h2><?= t('account_info') ?></h2>
                <form method="POST" action="/index.php?page=account">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-row">
                        <div class="form-group">
                            <label><?= t('auth_firstname') ?></label>
                            <input type="text" name="first_name" value="<?= h($userData['first_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label><?= t('auth_lastname') ?></label>
                            <input type="text" name="last_name" value="<?= h($userData['last_name']) ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= t('auth_email') ?></label>
                        <input type="email" value="<?= h($userData['email']) ?>" disabled class="input-disabled">
                    </div>
                    <div class="form-group">
                        <label><?= t('account_phone') ?></label>
                        <input type="tel" name="phone" value="<?= h($userData['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label><?= t('account_address') ?></label>
                        <input type="text" name="address" value="<?= h($userData['address'] ?? '') ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><?= t('account_postal') ?></label>
                            <input type="text" name="postal_code" value="<?= h($userData['postal_code'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label><?= t('account_city') ?></label>
                            <input type="text" name="city" value="<?= h($userData['city'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= t('account_country') ?></label>
                        <input type="text" name="country" value="<?= h($userData['country'] ?? 'France') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary"><?= t('account_save') ?></button>
                </form>
            </div>

            <!-- Changer mot de passe -->
            <div class="account-card">
                <h2><?= $lang==='fr' ? 'Changer le mot de passe' : 'Change password' ?></h2>
                <form method="POST" action="/index.php?page=account">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_pw">
                    <div class="form-group">
                        <label><?= $lang==='fr' ? 'Mot de passe actuel' : 'Current password' ?></label>
                        <input type="password" name="current_pw" required>
                    </div>
                    <div class="form-group">
                        <label><?= $lang==='fr' ? 'Nouveau mot de passe' : 'New password' ?></label>
                        <input type="password" name="new_pw" minlength="8" required>
                    </div>
                    <div class="form-group">
                        <label><?= t('auth_confirm_pw') ?></label>
                        <input type="password" name="confirm_pw" required>
                    </div>
                    <button type="submit" class="btn btn-outline"><?= $lang==='fr' ? 'Modifier' : 'Update' ?></button>
                </form>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
