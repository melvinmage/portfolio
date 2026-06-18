<?php
// admin/pages/soc.php — Supervision SOC (Projet Patapied)
require_once __DIR__ . '/../../includes/core.php';
require_admin();

$lang = current_lang();
$pdo  = getDB();

$scripts = [
    'gardien-web' => ['chemin' => '/usr/local/bin/mon_gardien.sh', 'pid_file' => '/tmp/mon_gardien.pid'],
    'sonde-cisco' => ['chemin' => '/usr/local/bin/sonde_cisco.sh', 'pid_file' => '/tmp/sonde_cisco.pid']
];

// Traitement des actions (Démarrer/Arrêter)
if (isset($_POST['action_script']) && isset($_POST['nom_script'])) {
    $action = $_POST['action_script'];
    $cle = $_POST['nom_script'];
    if (array_key_exists($cle, $scripts)) {
        $script_path = $scripts[$cle]['chemin'];
        $pid_file = $scripts[$cle]['pid_file'];
        if ($action === 'start') {
            shell_exec("sudo $script_path > /dev/null 2>&1 & echo \$! > $pid_file");
            usleep(250000);
        } elseif ($action === 'stop') {
            if (file_exists($pid_file)) {
                $pid = trim(file_get_contents($pid_file));
                if (!empty($pid) && is_numeric($pid)) { shell_exec("sudo /usr/bin/kill $pid"); }
                unlink($pid_file);
            }
        }
    }
}

// Traitement du débannissement IP
if (isset($_POST['ip_a_debannir']) && !empty($_POST['ip_a_debannir'])) {
    $ip_clean = htmlspecialchars($_POST['ip_a_debannir']);
    shell_exec("sudo /usr/local/bin/deban_ip.sh " . escapeshellarg($ip_clean));
    $message_succes = "L'IP $ip_clean a été purgée du pare-feu.";
}

// Nettoyage de l'historique
if (isset($_POST['action_clear_soc'])) {
    $pdo->exec("TRUNCATE TABLE soc_alerts");
}

function est_il_actif($pid_file) {
    if (!file_exists($pid_file)) return false;
    $pid = trim(file_get_contents($pid_file));
    return (!empty($pid) && is_numeric($pid) && strpos(shell_exec("ps -p $pid"), $pid) !== false);
}

$web_actif = est_il_actif($scripts['gardien-web']['pid_file']);
$cisco_actif = est_il_actif($scripts['sonde-cisco']['pid_file']);

$banned_ips = [];
$iptables_output = shell_exec("sudo /usr/sbin/iptables -S INPUT 2>/dev/null");
if ($iptables_output) {
    $lines = explode("\n", trim($iptables_output));
    foreach ($lines as $line) {
        if (strpos($line, '-j DROP') !== false && preg_match('/-s\s+([0-9\.\/]+)/', $line, $matches)) {
            $banned_ips[] = str_replace('/32', '', $matches[1]);
        }
    }
}
$banned_ips = array_unique($banned_ips);
$filtre = isset($_GET['filtre']) ? $_GET['filtre'] : 'all';

$adminPageTitle = $lang === 'fr' ? 'Supervision SOC' : 'SOC Supervision';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<style>
    :root {
        --soc-text-muted: #6b6b6b;
        --soc-border: #ede8e1;
        --soc-neon-green: #2d6a4f;
        --soc-neon-red: #dc2626;
        --soc-neon-blue: #2563eb;
        --soc-term-bg: #11141a;
    }

    .soc-wrapper {
        color: var(--ink);
        font-family: var(--font-sans);
        margin-top: 20px;
    }

    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 28px;
    }
    .metric-card {
        background: var(--white);
        border: 1px solid var(--soc-border);
        border-radius: 8px;
        padding: 24px;
        box-shadow: var(--shadow-sm);
    }
    .metric-title { 
        font-size: 11px; 
        font-weight: 600; 
        text-transform: uppercase; 
        color: var(--soc-text-muted); 
        letter-spacing: 0.5px;
    }
    .metric-value { 
        font-size: 28px; 
        font-weight: 700; 
        margin-top: 8px; 
        font-family: var(--font-mono);
        color: var(--ink);
    }
    .metric-value.green { color: var(--soc-neon-green); }
    .metric-value.term-red { color: var(--soc-neon-red); }

    .sondes-panel { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); 
        gap: 20px; 
        margin-bottom: 28px;
    }
    .sonde-card { 
        background: var(--white);
        border: 1px solid var(--soc-border);
        border-radius: 8px;
        padding: 24px;
        display: flex; 
        justify-content: space-between; 
        align-items: center;
        box-shadow: var(--shadow-sm);
    }
    .sonde-info h3 { 
        margin: 0 0 6px 0;
        font-family: var(--font-sans);
        font-size: 14px; 
        font-weight: 600; 
        color: var(--ink);
    }
    .badge-status { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; }
    .badge-status.active { color: var(--soc-neon-green); }
    .badge-status.inactive { color: var(--soc-neon-red); }
    .badge-status .dot { width: 8px; height: 8px; border-radius: 50%; }

    .terminal-container { 
        background: var(--soc-term-bg);
        border: 1px solid var(--ink);
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 28px;
        box-shadow: var(--shadow-md);
    }
    .terminal-header { 
        background: #171d26;
        padding: 12px 20px;
        border-bottom: 1px solid #232e3e;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .terminal-header .dot { width: 10px; height: 10px; border-radius: 50%; }
    .terminal-title { font-family: var(--font-mono); font-size: 12px; color: #a8b2c1; margin-left: 10px; }
    .terminal-body { 
        padding: 20px;
        font-family: var(--font-mono);
        font-size: 13px; 
        line-height: 1.6;
        max-height: 380px;
        overflow-y: auto;
        color: #e2e8f0;
    }
    
    .log-line { margin-bottom: 6px; white-space: pre-wrap; }
    .log-time { color: #818cf8; }
    .log-warn { color: #fbbf24; font-weight: bold; }
    .log-error { color: #ef4444; font-weight: bold; }
    .log-info { color: #60a5fa; }

    .bottom-layout { display: grid; grid-template-columns: 1.6fr 1fr; gap: 24px; margin-top: 24px; }
    .bottom-box { 
        background: var(--white);
        border: 1px solid var(--soc-border);
        border-radius: 8px;
        padding: 24px;
        box-shadow: var(--shadow-sm);
    }

    .banned-row { 
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid var(--soc-border);
    }
    .banned-row:last-child { border-bottom: none; }

    .action-btn { 
        padding: 6px 14px;
        border-radius: 4px;
        font-weight: 600;
        cursor: pointer;
        font-size: 12px;
        border: 1px solid transparent;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
    }
    .btn-start { background: var(--forest); color: var(--white); border-color: var(--forest); }
    .btn-start:hover { background: var(--forest-dark); border-color: var(--forest-dark); }
    .btn-stop { background: rgba(220, 38, 38, 0.1); color: var(--soc-neon-red); border-color: rgba(220, 38, 38, 0.2); }
    .btn-stop:hover { background: var(--soc-neon-red); color: var(--white); }
    .btn-purg { background: transparent; border: 1px solid var(--stone); color: var(--ink-light); }
    .btn-purg:hover { background: #ffe0e0; border-color: var(--soc-neon-red); color: var(--soc-neon-red); }
    .btn-unban { background: var(--white); color: var(--forest); border: 1px solid var(--forest); }
    .btn-unban:hover { background: var(--forest); color: var(--white); }

    .filter-tabs { display: flex; gap: 8px; margin-bottom: 14px; border-bottom: 1px solid #232e3e; padding-bottom: 12px; }
    .filter-tabs .tab-link { 
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 6px 14px;
        border-radius: 20px;
        color: #a8b2c1;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.2s;
    }
    .filter-tabs .tab-link:hover { color: var(--white); background: rgba(255, 255, 255, 0.05); }
    .filter-tabs .tab-link.active { background: var(--forest-mid); color: var(--white); }
</style>

<div class="soc-wrapper">
    
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; border-bottom: 1px solid var(--soc-border); padding-bottom: 16px;">
        <div>
            <h1 style="font-family: var(--font-serif); font-size:28px; font-weight:400; color: var(--ink); margin:0;"><?= $lang === 'fr' ? 'Supervision SOC' : 'SOC Supervision' ?></h1>
            <p style="color:var(--ink-light); margin:4px 0 0 0; font-size:14px;">Micro-SOC de corrélation d'événements matériels et applicatifs en arrière-plan.</p>
        </div>
        <span class="badge-status active" style="background: var(--forest-pale); color: var(--forest-dark); padding: 6px 14px; border-radius: 4px; font-weight: 600; border: 1px solid var(--soc-border);">
            <span class="dot" style="background:var(--forest)"></span> SYSTÈME NOMINAL
        </span>
    </div>

    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-title">Alertes Détectées</div>
            <div class="metric-value" id="count-alerts">--</div>
        </div>
        <div class="metric-card">
            <div class="metric-title">IPs Bloquées (iptables)</div>
            <div class="metric-value <?= !empty($banned_ips) ? 'term-red' : '' ?>" id="count-banned"><?= count($banned_ips) ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-title">Disponibilité Système</div>
            <div class="metric-value green">100%</div>
        </div>
    </div>

    <div class="sondes-panel">
        <div class="sonde-card">
            <div class="sonde-info">
                <h3>Sonde Brute-Force (Apache2)</h3>
                <span class="badge-status <?= $web_actif ? 'active' : 'inactive' ?>" id="status-label-web">
                    <span class="dot" style="background:<?= $web_actif ? 'var(--soc-neon-green)' : 'var(--soc-neon-red)' ?>"></span>
                    <?= $web_actif ? 'EN COURS' : 'ARRÊTÉ' ?>
                </span>
            </div>
            <form method="POST" style="margin:0;">
                <input type="hidden" name="nom_script" value="gardien-web">
                <button type="submit" name="action_script" value="<?= $web_actif ? 'stop' : 'start' ?>" class="action-btn <?= $web_actif ? 'btn-stop' : 'btn-start' ?>">
                    <?= $web_actif ? '⏹ Arrêter' : '▶ Lancer' ?>
                </button>
            </form>
        </div>

        <div class="sonde-card">
            <div class="sonde-info">
                <h3>Sonde Port-Security (Cisco Switch)</h3>
                <span class="badge-status <?= $cisco_actif ? 'active' : 'inactive' ?>" id="status-label-cisco">
                    <span class="dot" style="background:<?= $cisco_actif ? 'var(--soc-neon-green)' : 'var(--soc-neon-red)' ?>"></span>
                    <?= $cisco_actif ? 'EN COURS' : 'ARRÊTÉ' ?>
                </span>
            </div>
            <form method="POST" style="margin:0;">
                <input type="hidden" name="nom_script" value="sonde-cisco">
                <button type="submit" name="action_script" value="<?= $cisco_actif ? 'stop' : 'start' ?>" class="action-btn <?= $cisco_actif ? 'btn-stop' : 'btn-start' ?>">
                    <?= $cisco_actif ? '⏹ Arrêter' : '▶ Lancer' ?>
                </button>
            </form>
        </div>
    </div>

    <h3 style="font-family: var(--font-sans); font-size:15px; font-weight:600; margin: 24px 0 14px 0; color: var(--ink);">État des Services Applicatifs (Debian Linux Core)</h3>
    <div class="sondes-panel" id="services-panel-container" style="margin-bottom: 28px;">
        <div class="sonde-card" style="grid-column: 1 / -1; justify-content: center; color: var(--soc-text-muted); font-size: 13px;">
            Analyse des démons système en cours...
        </div>
    </div>

    <div class="terminal-container">
        <div class="terminal-header">
            <div class="dot" style="background:#ff5f56"></div>
            <div class="dot" style="background:#ffbd2e"></div>
            <div class="dot" style="background:#27c93f"></div>
            <div class="terminal-title">soc@patapied:~$ tail -f /var/log/soc/events.log</div>
        </div>

        <div class="filter-tabs" style="padding: 12px 20px 0 20px; background: #141822; border-bottom: 1px solid #232e3e;">
            <button type="button" class="tab-link active" data-filter="all" onclick="changerFiltreSOC('all')">Flux global</button>
            <button type="button" class="tab-link" data-filter="web" onclick="changerFiltreSOC('web')">Échecs d'authentification</button>
            <button type="button" class="tab-link" data-filter="cisco" onclick="changerFiltreSOC('cisco')">Sécurité Commutation</button>
        </div>

        <div class="terminal-body" id="syslog-terminal">
            <div class="log-line" style="color:#6b6b6b">Initialisation du flux de données asynchrone...</div>
        </div>
    </div>

    <div class="admin-two-cols bottom-layout">
        <div class="bottom-box">
            <h3 style="font-family: var(--font-sans); font-size:16px; font-weight:600; margin:0 0 16px 0; color:var(--soc-neon-red)">Adresses IP proscrites (iptables)</h3>
            <?php if(isset($message_succes)): ?>
                <div style="background:rgba(45,106,79,0.05); color:var(--forest); border: 1px solid var(--forest-pale); padding:10px; border-radius:4px; margin-bottom:12px; font-size:13px;">✓ <?= $message_succes ?></div>
            <?php endif; ?>
            
            <div id="banned-list-container">
            </div>
        </div>

        <div class="bottom-box" style="display:flex; flex-direction:column; justify-content:space-between;">
            <div>
                <h3 style="font-family: var(--font-sans); font-size:16px; font-weight:600; margin:0 0 8px 0; color: var(--ink);">Purge du SOC</h3>
                <p style="color:var(--ink-light); font-size:13px; line-height:1.6; margin:0;">Efface l'intégralité des alertes mémorisées dans la table relationnelle MariaDB.</p>
            </div>
            <form method="POST" onsubmit="return confirm('Confirmer la purge définitive des logs MariaDB ?');" style="margin-top:16px;">
                <button type="submit" name="action_clear_soc" class="action-btn btn-purg" style="width:100%; justify-content:center; font-weight:600;">
                    ⚠️ Nettoyer l'Historique
                </button>
            </form>
        </div>
    </div>
</div>

<script>
let currentFiltre = "all";

function rafraichirDonniesSOC() {
    fetch(`/admin/index.php?section=soc_api&filtre=${currentFiltre}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('count-alerts').innerText = data.counters.alertes_actives;
            document.getElementById('count-banned').innerText = data.counters.ips_bannies;

            // 1. RENDU ASYNCHRONE DE L'ÉTAT DES SERVICES (Vision seule épurée avec badges colorés Patapied)
            const servicesContainer = document.getElementById('services-panel-container');
            let htmlServices = "";
            
            const mappingServices = {
                'apache2':  'Serveur Web Apache2',
                'mariadb':  'Base de Données MariaDB',
                'smbd':     'Partage de Fichiers Samba',
                'asterisk': 'Téléphonie IP-PBX Asterisk (Distant)'
            };

            Object.keys(data.services).forEach(srv => {
                let nomLabel = mappingServices[srv] || srv;
                let estEnLigne = data.services[srv];
                let dotColor = estEnLigne ? 'var(--soc-neon-green)' : 'var(--soc-neon-red)';
                let bgBadge = estEnLigne ? 'var(--forest-pale)' : '#ffe0e0';
                let textBadge = estEnLigne ? 'var(--forest-dark)' : '#c0392b';
                let labelTexte = estEnLigne ? 'ACTIF' : 'ARRÊTÉ';

                htmlServices += `<div class="sonde-card">` +
                                `<div style="display:flex; flex-direction:column; gap:2px;">` +
                                `<span style="font-size:10px; font-weight:700; color:var(--soc-text-muted); text-transform:uppercase;">SERVICE DAEMON</span>` +
                                `<h3 style="margin:0; font-size:14px; font-weight:600; color:var(--ink);">${nomLabel}</h3>` +
                                `</div>` +
                                `<span class="badge-status" style="background:${bgBadge}; color:${textBadge}; padding: 4px 10px; border-radius:4px; font-size:11px; font-weight:700;">` +
                                `<span class="dot" style="background:${dotColor}; width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:4px;"></span>` +
                                `${labelTexte}` +
                                `</span>` +
                                `</div>`;
            });
            servicesContainer.innerHTML = htmlServices;

            // 2. Reconstruction asynchrone des IP d'iptables
            const bannedContainer = document.getElementById('banned-list-container');
            if (!data.banned_ips || data.banned_ips.length === 0) {
                bannedContainer.innerHTML = `<p style="color:var(--soc-text-muted); margin:0; font-size:13px;">Aucune adresse IP n'est bridée par Netfilter.</p>`;
            } else {
                let htmlBanned = "";
                data.banned_ips.forEach(ip => {
                    htmlBanned += `<div class="banned-row" id="banned-row-${ip.replace(/\./g, '-')}">` +
                                  `<span style="font-family:var(--font-mono); font-weight:700; color:var(--soc-neon-red);">${ip}</span>` +
                                  `<button type="button" class="action-btn btn-unban" onclick="executerDebanAsynchrone('${ip}')">Débannir</button>` +
                                  `</div>`;
                });
                bannedContainer.innerHTML = htmlBanned;
            }

            // 3. Reconstruction du terminal de logs
            const terminal = document.getElementById('syslog-terminal');
            if (data.alerts.length === 0) {
                terminal.innerHTML = `<div class="log-line" style="color:var(--forest)">Aucun incident à signaler dans cette catégorie.</div>`;
                return;
            }

            let htmlLogs = "";
            data.alerts.forEach(alert => {
                let statusClass = "log-info";
                if (alert.status.toLowerCase() === 'critical') statusClass = "log-error";
                if (alert.status.toLowerCase() === 'warning') statusClass = "log-warn";

                htmlLogs += `<div class="log-line">` +
                            `<span class="log-time">[${alert.date_format}]</span> ` +
                            `<span class="${statusClass}">[${alert.status.toUpperCase()}]</span> ` +
                            `<strong style="color:#ffffff;">${alert.source}</strong> (Cible: ${alert.ip_target}) — ` +
                            `<span>${alert.description}</span>` +
                            `</div>`;
            });
            terminal.innerHTML = htmlLogs;
        })
        .catch(err => console.error("Erreur de synchronisation SOC API:", err));
}

function changerFiltreSOC(nouveauFiltre) {
    currentFiltre = nouveauFiltre;
    document.querySelectorAll('.filter-tabs .tab-link').forEach(btn => {
        if (btn.getAttribute('data-filter') === nouveauFiltre) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
    rafraichirDonniesSOC();
}

// Interrogation asynchrone toutes les 2.5 secondes
rafraichirDonniesSOC();
setInterval(rafraichirDonniesSOC, 2500);

function executerDebanAsynchrone(ip) {
    const rowId = 'banned-row-' + ip.replace(/\./g, '-');
    const rowElement = document.getElementById(rowId);
    if (rowElement) {
        rowElement.style.opacity = '0.3';
        const button = rowElement.querySelector('button');
        if (button) {
            button.innerText = 'Purge...';
            button.disabled = true;
        }
    }

    const formData = new FormData();
    formData.append('ip_a_debannir', ip);

    fetch('/admin/index.php?section=soc_api', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error("Erreur de traitement API.");
        return response.json();
    })
    .then(data => {
        if (data.success) { rafraichirDonniesSOC(); }
    })
    .catch(err => {
        console.error("Erreur débannissement:", err);
        if (rowElement) {
            rowElement.style.opacity = '1';
            const button = rowElement.querySelector('button');
            if (button) {
                button.innerText = 'Débannir';
                button.disabled = false;
            }
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>