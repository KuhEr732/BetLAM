<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BetLAM - Gratis Wetten & Spaß</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #1a1a2e;
            color: #fff;
            text-align: center;
        }
        .container {
            margin: 50px auto;
            padding: 20px;
            position: relative;
        }
        h1 {
            font-size: 3em;
            color: #f8b400;
            text-transform: uppercase;
            letter-spacing: 2px;
            animation: glow 1.5s infinite alternate;
        }
        @keyframes glow {
            from { text-shadow: 0 0 10px #f8b400; }
            to { text-shadow: 0 0 20px #ffcc00; }
        }
        p {
            font-size: 1.4em;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .cta {
            display: inline-block;
            padding: 15px 30px;
            margin: 10px;
            font-size: 1.3em;
            color: #fff;
            background: #e94560;
            text-decoration: none;
            border-radius: 10px;
            transition: transform 0.2s, background 0.3s;
            box-shadow: 0 5px 15px rgba(233, 69, 96, 0.5);
            position: relative;
            overflow: hidden;
        }
        .cta:hover {
            background: #ff2e63;
            transform: scale(1.1);
        }
        .cta::after {
            content: '\2728';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            font-size: 2em;
            opacity: 0.8;
            transition: transform 0.3s ease-out;
        }
        .cta:hover::after {
            transform: translate(-50%, -50%) scale(1);
        }
        .highlight {
            color: #ffcc00;
            font-size: 1.5em;
            text-shadow: 0 0 10px #ffcc00;
        }
        .jackpot {
            font-size: 2em;
            color: #ffcc00;
            font-weight: bold;
            text-shadow: 0 0 15px #ffcc00;
            animation: jackpot 1s infinite alternate;
        }
        @keyframes jackpot {
            from { transform: scale(1); }
            to { transform: scale(1.1); }
        }
        .slot-machine {
            font-size: 2em;
            font-weight: bold;
            color: #fff;
            background: #e94560;
            padding: 10px;
            border-radius: 10px;
            display: inline-block;
            margin-top: 20px;
            animation: spin 2s infinite;
        }
        @keyframes spin {
            0% { transform: rotateX(0deg); }
            100% { transform: rotateX(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Willkommen bei BetLAM</h1>
        <p>Wetten ohne echtes Geld – <span class="highlight">100% Gratis, 100% Spannung!</span></p>
        <p class="jackpot">💰 JACKPOT: 1.000.000 Coins 💰</p>
        <a href="index.php?page=sports" class="cta">🔥 Jetzt Spielen 🔥</a>
        <a href="#" class="cta">⚡ Mehr Erfahren ⚡</a>
        <br><div class="slot-machine">🎰 7️⃣ 🍒 🔔 🎰</div>
    </div>
</body>
</html>
