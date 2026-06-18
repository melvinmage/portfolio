<?php
require_once __DIR__ . '/includes/core.php';

// Le nouveau mot de passe que tu veux définir
$nouveau_mdp = "Patapied2026!";
$email_cible = "admin@patapied.fr"; // Change avec le bon email

$hash = password_hash($nouveau_mdp, PASSWORD_BCRYPT, ['cost' => 12]);

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE users SET password = :pw WHERE email = :email");
    $stmt->execute([':pw' => $hash, ':email' => $email_cible]);
    echo "Succès : Le mot de passe a été réinitialisé en '$nouveau_mdp'\n";
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
?>