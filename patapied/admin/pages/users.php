<?php
// admin/pages/users.php — Gestion des utilisateurs
require_once __DIR__ . '/../../includes/core.php';
require_admin();

$lang = current_lang();
$pdo  = getDB();

// Traitement du basculement d'état (is_active)
if (isset($_POST['toggle_active_id'])) {
    $userId = (int)$_POST['toggle_active_id'];
    // Sécurité : Impossible de désactiver le compte admin principal
    if ($userId !== 1) {
        $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$userId]);
        set_flash('success', $lang === 'fr' ? 'Statut de l\'utilisateur mis à jour.' : 'User status updated.');
    } else {
        set_flash('error', $lang === 'fr' ? 'Impossible de désactiver l\'administrateur racine.' : 'Cannot deactivate root admin.');
    }
    header('Location: /admin/index.php?section=users');
    exit;
}

// Récupération de l'ensemble des comptes de l'application
$stmt = $pdo->query("SELECT id, email, first_name, last_name, role, is_active, created_at FROM users ORDER BY role ASC, created_at DESC");
$users = $stmt->fetchAll();

$adminPageTitle = $lang === 'fr' ? 'Gestion des Utilisateurs' : 'User Management';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-page-header" style="margin-bottom: 24px; border-bottom: 1px solid var(--cream-dark); padding-bottom: 12px;">
    <h1 class="admin-page-title" style="font-family: var(--font-serif); color: var(--ink); font-size: 28px; font-weight: 400; margin:0;"><?= $lang === 'fr' ? 'Utilisateurs' : 'Users' ?></h1>
    <span class="admin-page-sub" style="font-family: var(--font-sans); color: var(--ink-light); font-size: 14px;"><?= $lang === 'fr' ? 'Contrôle des rôles et accès aux comptes de la plateforme.' : 'Manage platform roles and account access.' ?></span>
</div>

<div class="admin-card" style="background: var(--white); border: 1px solid var(--cream-dark); border-radius: 8px; padding: 24px; box-shadow: var(--shadow-sm);">
    <div class="table-wrap" style="overflow-x: auto;">
        <table class="admin-table" style="width: 100%; border-collapse: collapse; font-family: var(--font-sans); font-size: 13px;">
            <thead>
                <tr style="text-align: left;">
                    <th style="padding: 10px 8px; color: var(--ink-light); font-weight: 600; border-bottom: 2px solid var(--cream-dark); text-transform: uppercase; font-size: 11px;"><?= $lang === 'fr' ? 'Identité' : 'Identity' ?></th>
                    <th style="padding: 10px 8px; color: var(--ink-light); font-weight: 600; border-bottom: 2px solid var(--cream-dark); text-transform: uppercase; font-size: 11px;">Email</th>
                    <th style="padding: 10px 8px; color: var(--ink-light); font-weight: 600; border-bottom: 2px solid var(--cream-dark); text-transform: uppercase; font-size: 11px;">Rôle</th>
                    <th style="padding: 10px 8px; color: var(--ink-light); font-weight: 600; border-bottom: 2px solid var(--cream-dark); text-transform: uppercase; font-size: 11px;">Statut</th>
                    <th style="padding: 10px 8px; color: var(--ink-light); font-weight: 600; border-bottom: 2px solid var(--cream-dark); text-transform: uppercase; font-size: 11px;">Création</th>
                    <th style="padding: 10px 8px; color: var(--ink-light); font-weight: 600; border-bottom: 2px solid var(--cream-dark); text-transform: uppercase; font-size: 11px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr style="border-bottom: 1px solid var(--cream-dark);">
                    <td style="padding: 12px 8px; color: var(--ink); font-weight: 500;"><?= h($u['first_name'] . ' ' . $u['last_name']) ?></td>
                    <td style="padding: 12px 8px; font-family: var(--font-mono); font-size: 12px; color: var(--ink-mid);"><?= h($u['email']) ?></td>
                    <td style="padding: 12px 8px;">
                        <span class="status-badge" style="display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; <?= $u['role'] === 'admin' ? 'background: rgba(37, 99, 235, 0.1); color: var(--status-shipped);' : 'background: var(--cream-dark); color: var(--ink-mid);' ?>">
                            <?= $u['role'] === 'admin' ? 'ADMIN' : 'CUSTOMER' ?>
                        </span>
                    </td>
                    <td style="padding: 12px 8px;">
                        <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: <?= $u['is_active'] ? 'var(--forest)' : 'var(--status-cancelled)' ?>;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: <?= $u['is_active'] ? 'var(--forest)' : 'var(--status-cancelled)' ?>;"></span>
                            <?= $u['is_active'] ? ($lang === 'fr' ? 'Actif' : 'Active') : ($lang === 'fr' ? 'Suspendu' : 'Suspended') ?>
                        </span>
                    </td>
                    <td style="padding: 12px 8px; font-family: var(--font-mono); font-size: 12px; color: var(--ink-light);"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td style="padding: 12px 8px; text-align: right;">
                        <?php if ($u['id'] !== 1): ?>
                        <form method="POST" action="/admin/index.php?section=users" style="margin:0; display:inline;" data-confirm="<?= $lang === 'fr' ? 'Modifier l\'accès de cet utilisateur ?' : 'Toggle access for this user?' ?>">
                            <input type="hidden" name="toggle_active_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn-ghost" style="font-family: var(--font-sans); font-size: 12px; font-weight: 600; color: <?= $u['is_active'] ? 'var(--status-cancelled)' : 'var(--forest)' ?>; background: none; padding: 6px 12px; border-radius: 4px; border: 1px solid var(--cream-dark); cursor: pointer;">
                                <?= $u['is_active'] ? ($lang === 'fr' ? 'Bloquer' : 'Restrict') : ($lang === 'fr' ? 'Réactiver' : 'Activate') ?>
                            </button>
                        </form>
                        <?php else: ?>
                            <span style="font-size: 12px; color: var(--ink-light); font-style: italic;">Protégé</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>