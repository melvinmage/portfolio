<?php
// admin/pages/soc_api.php — Endpoint JSON pour la supervision asynchrone (SOC Patapied)
require_once __DIR__ . '/../../includes/core.php';
require_admin();

$pdo = getDB();

// ─── 1. TRAITEMENT DU DÉBANNISSEMENT ASYNCHRONE (POST) ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ip_a_debannir']) && !empty($_POST['ip_a_debannir'])) {
    $ip_clean = htmlspecialchars($_POST['ip_a_debannir']);
    
    // Exécution du script système de purge d'iptables
    shell_exec("sudo /usr/local/bin/deban_ip.sh " . escapeshellarg($ip_clean));
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => "L'IP $ip_clean a été débannie avec succès."]);
    exit();
}

// ─── 2. CONFIGURATION DES DROITS ET FONCTIONS DE STATUT ───
$scripts = [
    'gardien-web' => ['pid_file' => '/tmp/mon_gardien.pid'],
    'sonde-cisco' => ['pid_file' => '/tmp/sonde_cisco.pid']
];

function est_il_actif($pid_file) {
    if (!file_exists($pid_file)) return false;
    $pid = trim(file_get_contents($pid_file));
    if (empty($pid) || !is_numeric($pid)) return false;
    $output = shell_exec("ps -p $pid");
    return (strpos($output, $pid) !== false);
}

// Fonction de vérification hybride (Locale pour la Debian / Réseau pour Asterisk)
function verifier_statut_service($nom_service) {
    if ($nom_service === 'asterisk') {
        $ip_asterisk = '192.168.30.22'; // IP de la machine VoIP distante
        
        // 1. Test de présence physique par Ping ICMP (1 paquet, timeout de 1s max)
        $ping_output = shell_exec("ping -c 1 -W 1 $ip_asterisk 2>&1");
        if (strpos($ping_output, '100% packet loss') !== false || strpos($ping_output, 'unreachable') !== false || empty($ping_output)) {
            return false; // La machine distante est complètement éteinte
        }
        
        // 2. Si la machine est allumée, on valide que le port UDP 5060 d'Asterisk répond (via netcat)
        $udp_check = shell_exec("nc -z -v -u -w 1 $ip_asterisk 5060 2>&1");
        return (strpos($udp_check, 'succeeded') !== false || strpos($udp_check, 'open') !== false || strpos($udp_check, 'Connected') !== false);
    }
    
    // Pour les services locaux exécutés sur la Debian (Apache2, MariaDB, Samba)
    $state = trim(shell_exec("systemctl is-active $nom_service 2>/dev/null"));
    return ($state === 'active');
}

// ─── 3. RÉCUPÉRATION DE L'ÉTAT DES SONDES ET PARE-FEU ───
$web_actif   = est_il_actif($scripts['gardien-web']['pid_file']);
$cisco_actif = est_il_actif($scripts['sonde-cisco']['pid_file']);

$banned_count = 0;
$banned_ips_list = [];
$iptables_output = shell_exec("sudo /usr/sbin/iptables -S INPUT 2>/dev/null");

if ($iptables_output) {
    $lines = explode("\n", trim($iptables_output));
    foreach ($lines as $line) {
        if (strpos($line, '-j DROP') !== false && preg_match('/-s\s+([0-9\.\/]+)/', $line, $matches)) {
            $banned_count++;
            $banned_ips_list[] = str_replace('/32', '', $matches[1]);
        }
    }
}
$banned_ips_list = array_values(array_unique($banned_ips_list));

// ─── 4. LECTURE DES ALERTES FILTRÉES EN BASE DE DONNÉES ───
$filtre = isset($_GET['filtre']) ? $_GET['filtre'] : 'all';
$whereClause = "";

if ($filtre === 'web') {
    $whereClause = "WHERE source LIKE '%Apache2%'";
} elseif ($filtre === 'cisco') {
    $whereClause = "WHERE source LIKE '%Cisco%'";
}

$stmt = $pdo->query("SELECT date_time, source, ip_target, description, status FROM soc_alerts $whereClause ORDER BY date_time DESC LIMIT 12");
$alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($alerts as &$a) {
    $a['date_format'] = date('d/m/y H:i:s', strtotime($a['date_time']));
}

// ─── 5. ENVOI DE LA RÉPONSE PAYLOAD JSON CENTRALISÉE ───
header('Content-Type: application/json');
echo json_encode([
    'sondes' => [
        'web'   => $web_actif,
        'cisco' => $cisco_actif
    ],
    'services' => [
        'apache2'  => verifier_statut_service('apache2'),
        'mariadb'  => verifier_statut_service('mariadb'),
        'smbd'     => verifier_statut_service('smbd'),
        'asterisk' => verifier_statut_service('asterisk')
    ],
    'counters' => [
        'alertes_actives' => count($alerts),
        'ips_bannies'     => $banned_count
    ],
    'banned_ips' => $banned_ips_list,
    'alerts'     => $alerts
]);