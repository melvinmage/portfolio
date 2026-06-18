<?php
// /var/www/html/scripts/soc_insert.php

// 1. Chargement du cœur du projet
require_once __DIR__ . '/../includes/core.php';

// 2. Vérification des arguments
if ($argc < 4) {
    die("Erreur : Paramètres manquants.\nUsage : php soc_insert.php <Cible> <Source> <Description>\n");
}

$cible       = $argv[1]; // L'adresse MAC ou IP
$source      = $argv[2]; // Ex: "Switch Cisco (GigabitEthernet1/0/20)"
$description = $argv[3]; // Ex: "Violation Port-Security"

try {
    // 3. Connexion à la base de données
    $pdo = getDB();

    // 4. SÉCURITÉ ANTI-BOMBARDEMENT (Seuil de 5 secondes)
    // On cherche s'il y a une alerte identique créée il y a moins de 5 secondes
    $checkStmt = $pdo->prepare("
        SELECT id FROM soc_alerts 
        WHERE source = ? 
          AND ip_target = ? 
          AND date_time >= DATE_SUB(NOW(), INTERVAL 5 SECOND)
        LIMIT 1
    ");

    $checkStmt->execute([$source, $cible]);
    
    if ($checkStmt->fetch()) {
        // Un doublon identique existe déjà dans la fenêtre de 5s, on arrête le script sans insérer
        echo "Doublon détecté en BDD (moins de 5s). Insertion annulée.\n";
        exit();
    }

    // 5. Si aucun doublon récent, on procède à l'insertion
    $stmt = $pdo->prepare("INSERT INTO soc_alerts (source, ip_target, description, status) VALUES (?, ?, ?, 'critical')");
    $stmt->execute([$source, $cible, $description]);
    
    echo "Insertion SQL réussie pour le SOC.\n";

} catch (Exception $e) {
    error_log("Erreur SOC Insert: " . $e->getMessage());
    echo "Erreur d'insertion en base de données.\n";
}

?>