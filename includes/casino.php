<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slot Machine</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(to bottom, #4a1a6c, #2c1053);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .slot-machine {
            background: linear-gradient(to bottom, #8B4513, #654321);
            border: 4px solid #b38728;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 400px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .credits {
            background-color: black;
            color: #ffd700;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .reels-container {
            background-color: black;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            position: relative;
            overflow: hidden;
        }

        .reels {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .reel {
            width: 80px;
            height: 80px;
            background-color: white;
            border: 2px solid #333;
            border-radius: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 40px;
            overflow: hidden;
            position: relative;
        }

        .reel-container {
            height: 80px;
            overflow: hidden;
            position: relative;
        }

        .reel-strip {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            transition: top 0.5s ease-out;
        }

        .reel-strip.spinning {
            transition: none;
            animation: spin-reel 0.2s linear infinite;
        }

        .symbol {
            width: 80px;
            height: 80px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 40px;
        }

        @keyframes spin-reel {
            0% { transform: translateY(0); }
            100% { transform: translateY(-80px); }
        }

        .controls {
            display: flex;
            gap: 10px;
        }

        .spin-button {
            background: linear-gradient(to right, #d32f2f, #b71c1c);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: bold;
            font-size: 16px;
            flex-grow: 1;
            cursor: pointer;
            transition: all 0.2s;
        }

        .spin-button:hover:not(:disabled) {
            transform: scale(1.05);
            background: linear-gradient(to right, #c62828, #a31515);
        }

        .spin-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .add-credits {
            background: linear-gradient(to right, #2e7d32, #1b5e20);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: bold;
            cursor: pointer;
        }

        .win-effect {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 10;
            animation: bounce 0.5s infinite alternate;
        }

        @keyframes bounce {
            from { transform: translateY(0); }
            to { transform: translateY(-10px); }
        }

        .win-text {
            color: #ffd700;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .win-amount {
            color: white;
            font-size: 24px;
        }

        .lose-effect {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.8);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }

        .lose-text {
            color: #ff5252;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .lose-subtext {
            color: white;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="slot-machine">
        <div class="header">
            <div class="credits">
                <span class="coin-icon">🪙</span>
                <span id="credits-display">100</span>
            </div>
        </div>
        
        <div class="reels-container" id="reels-container">
            <div class="reels">
                <div class="reel-container">
                    <div class="reel-strip" id="reel1">
                        <div class="symbol">🍒</div>
                        <div class="symbol">🍋</div>
                        <div class="symbol">🍊</div>
                        <div class="symbol">🍇</div>
                        <div class="symbol">7️⃣</div>
                        <div class="symbol">🍒</div> <!-- Wiederholen für nahtloses Scrollen -->
                    </div>
                </div>
                <div class="reel-container">
                    <div class="reel-strip" id="reel2">
                        <div class="symbol">🍒</div>
                        <div class="symbol">🍋</div>
                        <div class="symbol">🍊</div>
                        <div class="symbol">🍇</div>
                        <div class="symbol">7️⃣</div>
                        <div class="symbol">🍒</div> <!-- Wiederholen für nahtloses Scrollen -->
                    </div>
                </div>
                <div class="reel-container">
                    <div class="reel-strip" id="reel3">
                        <div class="symbol">🍒</div>
                        <div class="symbol">🍋</div>
                        <div class="symbol">🍊</div>
                        <div class="symbol">🍇</div>
                        <div class="symbol">7️⃣</div>
                        <div class="symbol">🍒</div> <!-- Wiederholen für nahtloses Scrollen -->
                    </div>
                </div>
            </div>
        </div>
        
        <div class="controls">
            <button class="spin-button" id="spin-button">DREHEN (10 CREDITS)</button>
            <button class="add-credits" id="add-credits">+100</button>
        </div>
    </div>

    <script>
        // Elemente
        const reelsContainer = document.getElementById('reels-container');
        const reel1 = document.getElementById('reel1');
        const reel2 = document.getElementById('reel2');
        const reel3 = document.getElementById('reel3');
        const spinButton = document.getElementById('spin-button');
        const addCreditsButton = document.getElementById('add-credits');
        const creditsDisplay = document.getElementById('credits-display');

        // Spielvariablen
        let credits = 100;
        let spinning = false;
        const symbols = ['🍒', '🍋', '🍊', '🍇', '7️⃣'];
        let results = [0, 0, 0];

        // Aktualisiere die Anzeige der Credits
        function updateCreditsDisplay() {
            creditsDisplay.textContent = credits;
            
            // Deaktiviere den Spin-Button, wenn nicht genug Credits vorhanden sind
            if (credits < 10) {
                spinButton.disabled = true;
            } else {
                spinButton.disabled = false;
            }
        }

        // Zeige Gewinn-Effekt an
        function showWinEffect(amount) {
            const winEffect = document.createElement('div');
            winEffect.className = 'win-effect';
            
            const winText = document.createElement('div');
            winText.className = 'win-text';
            winText.textContent = 'GEWINN!';
            
            const winAmount = document.createElement('div');
            winAmount.className = 'win-amount';
            winAmount.textContent = `+${amount} CREDITS`;
            
            winEffect.appendChild(winText);
            winEffect.appendChild(winAmount);
            reelsContainer.appendChild(winEffect);
            
            setTimeout(() => {
                if (reelsContainer.contains(winEffect)) {
                    reelsContainer.removeChild(winEffect);
                }
            }, 3000);
        }

        // Zeige Verlust-Effekt an
        function showLoseEffect() {
            const loseEffect = document.createElement('div');
            loseEffect.className = 'lose-effect';
            
            const loseText = document.createElement('div');
            loseText.className = 'lose-text';
            loseText.textContent = 'KEINE CREDITS MEHR';
            
            const loseSubtext = document.createElement('div');
            loseSubtext.className = 'lose-subtext';
            loseSubtext.textContent = 'Füge mehr hinzu, um weiterzuspielen';
            
            loseEffect.appendChild(loseText);
            loseEffect.appendChild(loseSubtext);
            reelsContainer.appendChild(loseEffect);
            
            setTimeout(() => {
                if (reelsContainer.contains(loseEffect)) {
                    reelsContainer.removeChild(loseEffect);
                }
            }, 3000);
        }

        // Prüfe auf Gewinn
        function checkWin(results) {
            // Prüfe, ob alle Symbole gleich sind
            if (results[0] === results[1] && results[1] === results[2]) {
                const multiplier = results[0] === 4 ? 50 : results[0] === 3 ? 25 : results[0] === 2 ? 15 : results[0] === 1 ? 10 : 5;
                const amount = 10 * multiplier;
                credits += amount;
                showWinEffect(amount);
            } else if (results[0] === results[1] || results[1] === results[2]) {
                // Zwei übereinstimmende Symbole
                const amount = 5;
                credits += amount;
                showWinEffect(amount);
            } else {
                // Zeige Verlust-Effekt, wenn keine Credits mehr vorhanden sind
                if (credits < 10) {
                    showLoseEffect();
                }
            }
            
            updateCreditsDisplay();
        }

        // Stoppe eine Walze an einer bestimmten Position
        function stopReel(reelElement, position) {
            return new Promise(resolve => {
                reelElement.classList.remove('spinning');
                reelElement.style.top = `-${position * 80}px`;
                
                setTimeout(() => {
                    resolve();
                }, 500); // Warte auf das Ende der Übergangsanimation
            });
        }

        // Drehen der Walzen
        async function spin() {
            if (spinning || credits < 10) return;
            
            credits -= 10;
            updateCreditsDisplay();
            spinning = true;
            
            // Entferne vorhandene Effekte
            const effects = reelsContainer.querySelectorAll('.win-effect, .lose-effect');
            effects.forEach(effect => reelsContainer.removeChild(effect));
            
            // Starte Spin-Animation
            reel1.classList.add('spinning');
            reel2.classList.add('spinning');
            reel3.classList.add('spinning');
            
            // Generiere zufällige Ergebnisse
            results = [
                Math.floor(Math.random() * symbols.length),
                Math.floor(Math.random() * symbols.length),
                Math.floor(Math.random() * symbols.length)
            ];
            
            // Stoppe die Walzen nacheinander
            await new Promise(resolve => setTimeout(resolve, 800));
            await stopReel(reel1, results[0]);
            
            await new Promise(resolve => setTimeout(resolve, 400));
            await stopReel(reel2, results[1]);
            
            await new Promise(resolve => setTimeout(resolve, 400));
            await stopReel(reel3, results[2]);
            
            // Prüfe auf Gewinn
            checkWin(results);
            spinning = false;
        }

        // Event-Listener
        spinButton.addEventListener('click', spin);
        
        addCreditsButton.addEventListener('click', () => {
            credits += 100;
            updateCreditsDisplay();
            
            // Entferne Verlust-Effekt, wenn Credits hinzugefügt werden
            const loseEffect = reelsContainer.querySelector('.lose-effect');
            if (loseEffect) {
                reelsContainer.removeChild(loseEffect);
            }
        });
        
        // Initialisierung
        updateCreditsDisplay();
    </script>
</body>
</html>