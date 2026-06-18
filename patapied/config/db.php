<?php

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'patapied');
define('DB_USER', 'patapied_admin');   // À adapter selon votre config MariaDB
define('DB_PASS', 'piedpied');   // À adapter selon votre config MariaDB
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT
             . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En production, ne jamais afficher le message brut
            error_log("DB Connection failed: " . $e->getMessage());
            die(json_encode(['error' => 'Erreur de connexion à la base de données.']));
        }
    }
    return $pdo;
}
