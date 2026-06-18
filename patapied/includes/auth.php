<?php
// includes/auth.php — Inscription, connexion, déconnexion
// ============================================================
// Toutes les requêtes utilisent PDO préparé (anti-injection SQL)
// Les mots de passe sont hachés avec password_hash() / Bcrypt
// ============================================================

require_once __DIR__ . '/core.php';

/**
 * Tente de connecter un utilisateur.
 * Retourne un tableau d'erreur ou null si succès (et hydrate la session).
 */
function auth_login(string $email, string $password): ?string {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        "SELECT id, email, password, first_name, last_name, role, is_active
         FROM users WHERE email = :email LIMIT 1"
    );
    $stmt->bindValue(':email', trim($email), PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return raw_t('auth_invalid');
    }
    if (!$user['is_active']) {
        return 'Compte désactivé. Contactez l\'administrateur.';
    }

    // Régénérer l'ID de session pour éviter la fixation de session
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user']    = [
        'id'         => $user['id'],
        'email'      => $user['email'],
        'first_name' => $user['first_name'],
        'last_name'  => $user['last_name'],
        'role'       => $user['role'],
    ];

    return null; // succès
}

/**
 * Inscrit un nouvel utilisateur.
 * Retourne un message d'erreur ou null si succès.
 */
function auth_register(array $data): ?string {
    $email    = trim($data['email']    ?? '');
    $password = $data['password']       ?? '';
    $confirm  = $data['confirm_pw']     ?? '';
    $fname    = trim($data['first_name'] ?? '');
    $lname    = trim($data['last_name']  ?? '');

    // Validations basiques
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Adresse e-mail invalide.';
    }
    if (strlen($password) < 8) {
        return 'Le mot de passe doit contenir au moins 8 caractères.';
    }
    if ($password !== $confirm) {
        return raw_t('auth_pw_mismatch');
    }
    if (empty($fname) || empty($lname)) {
        return raw_t('required_field');
    }

    $pdo = getDB();

    // Vérifie unicité de l'email (requête préparée)
    $chk = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $chk->bindValue(':email', $email, PDO::PARAM_STR);
    $chk->execute();
    if ($chk->fetch()) {
        return raw_t('auth_email_used');
    }

    // Hash bcrypt
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $ins = $pdo->prepare(
        "INSERT INTO users (email, password, first_name, last_name, role)
         VALUES (:email, :password, :fname, :lname, 'customer')"
    );
    $ins->bindValue(':email',    $email, PDO::PARAM_STR);
    $ins->bindValue(':password', $hash,  PDO::PARAM_STR);
    $ins->bindValue(':fname',    $fname, PDO::PARAM_STR);
    $ins->bindValue(':lname',    $lname, PDO::PARAM_STR);
    $ins->execute();

    // Auto-login après inscription
    auth_login($email, $password);
    return null;
}

/**
 * Déconnecte l'utilisateur et détruit la session.
 */
function auth_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
