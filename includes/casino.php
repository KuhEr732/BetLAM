
    <link rel="stylesheet" href="css/casino.css">
    <div class="slot-machine-container">
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