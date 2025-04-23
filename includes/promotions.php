<?php
if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit;
}
?>
    <style>
        body { font-family: sans-serif; background: #0D2A4A; color: white; text-align: center; padding: 50px; }
        button { font-size: 20px; padding: 12px 24px; background: #129B7F; color: white; border: none; border-radius: 10px; cursor: pointer; }
        button:hover { background: #0f7a66; }
        .message { margin-top: 30px; font-size: 18px; font-weight: bold; }
        .error { color: #FF6B6B; }
        .success { color: #4ECA64; }
    </style>

    <h1>🎉 Bonuszeit!</h1>
    <p>Klicke den Button, um deine Belohnung zu erhalten.</p>
    <button onclick="claimBonus()">Bonus einlösen</button>
    <div id="coin-container" style="position: relative; height: 0;"></div>
    <div class="message" id="msg"></div>

    <script>
        function claimBonus() {
            fetch('includes/bonus-handler.php')
                .then(response => response.json())
                .then(data => {
                    const msg = document.getElementById('msg');
                    msg.textContent = data.message;
                    msg.className = 'message ' + (data.status === 'success' ? 'success' : 'error');
                })
                .catch(() => {
                    const msg = document.getElementById('msg');
                    msg.textContent = "Es ist ein Fehler aufgetreten.";
                    msg.className = 'message error';
                });
        }
        
        function claimBonus() {
            fetch('includes/bonus-handler.php')
                .then(response => response.json())
                .then(data => {
                    const msg = document.getElementById('msg');
                    msg.textContent = data.message;
                    msg.className = 'message ' + (data.status === 'success' ? 'success' : 'error');

                    if (data.status === 'success') {
                        showCoins();
                    }
                })
                .catch(() => {
                    const msg = document.getElementById('msg');
                    msg.textContent = "Es ist ein Fehler aufgetreten.";
                    msg.className = 'message error';
                });
        }

        function showCoins() {
            const container = document.getElementById('coin-container');
            for (let i = 0; i < 20; i++) {
                const coin = document.createElement('div');
                coin.textContent = '🪙';
                coin.style.position = 'absolute';
                coin.style.left = Math.random() * 100 + '%';
                coin.style.top = '-30px';
                coin.style.fontSize = '24px';
                coin.style.animation = 'drop 1s ease-out forwards';
                container.appendChild(coin);

                setTimeout(() => container.removeChild(coin), 1000);
            }
        }
</script>
<style>
    @keyframes drop {
        to {
            transform: translateY(300px);
            opacity: 0;
        }
    }
</style>