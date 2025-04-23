<link rel="stylesheet" href="css/casino.css">

<!-- Original Slot Machine Code (Unchanged) -->
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

<!-- New Roulette Game -->
<div class="roulette-container">
    <div class="roulette-game">
        <div class="header">
            <h2>Roulette</h2>
            <div class="credits">
                <span class="coin-icon">🪙</span>
                <span id="roulette-credits-display">100</span>
            </div>
        </div>
        
        <div class="roulette-wheel-container">
            <div class="roulette-wheel" id="roulette-wheel">
                <div class="wheel-center"></div>
                <div class="ball" id="roulette-ball"></div>
            </div>
            <div class="result-display" id="roulette-result">?</div>
        </div>
        
        <div class="betting-area">
            <div class="bet-options">
                <div class="bet-option">
                    <label>Einsatz:</label>
                    <input type="number" id="bet-amount" min="5" max="50" value="10">
                </div>
                <div class="bet-option">
                    <label>Wette auf:</label>
                    <select id="bet-type">
                        <option value="red">Rot (2x)</option>
                        <option value="black">Schwarz (2x)</option>
                        <option value="even">Gerade (2x)</option>
                        <option value="odd">Ungerade (2x)</option>
                        <option value="high">Hoch 19-36 (2x)</option>
                        <option value="low">Niedrig 1-18 (2x)</option>
                        <option value="dozen1">1-12 (3x)</option>
                        <option value="dozen2">13-24 (3x)</option>
                        <option value="dozen3">25-36 (3x)</option>
                        <option value="single">Einzelne Zahl (35x)</option>
                    </select>
                </div>
                <div class="bet-option" id="single-number-container" style="display: none;">
                    <label>Zahl (0-36):</label>
                    <input type="number" id="single-number" min="0" max="36" value="0">
                </div>
            </div>
            
            <div class="controls">
                <button class="spin-button" id="roulette-spin-button">DREHEN</button>
                <button class="add-credits" id="roulette-add-credits">+100</button>
            </div>
        </div>
        
        <div class="message-area" id="roulette-message"></div>
    </div>
</div>

<script>
    // Original Slot Machine Script (Unchanged)
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

    // New Roulette Game Script
    document.addEventListener('DOMContentLoaded', function() {
        // Roulette Elements
        const rouletteWheel = document.getElementById('roulette-wheel');
        const rouletteBall = document.getElementById('roulette-ball');
        const rouletteSpinButton = document.getElementById('roulette-spin-button');
        const rouletteAddCreditsButton = document.getElementById('roulette-add-credits');
        const rouletteCreditsDisplay = document.getElementById('roulette-credits-display');
        const betAmount = document.getElementById('bet-amount');
        const betType = document.getElementById('bet-type');
        const singleNumberContainer = document.getElementById('single-number-container');
        const singleNumber = document.getElementById('single-number');
        const rouletteResult = document.getElementById('roulette-result');
        const rouletteMessage = document.getElementById('roulette-message');

        // Roulette variables
        let rouletteCredits = 100;
        let isSpinning = false;
        
        // Roulette numbers and their properties
        const rouletteNumbers = [
            { number: 0, color: 'green' },
            { number: 32, color: 'red' }, { number: 15, color: 'black' }, { number: 19, color: 'red' }, 
            { number: 4, color: 'black' }, { number: 21, color: 'red' }, { number: 2, color: 'black' }, 
            { number: 25, color: 'red' }, { number: 17, color: 'black' }, { number: 34, color: 'red' }, 
            { number: 6, color: 'black' }, { number: 27, color: 'red' }, { number: 13, color: 'black' }, 
            { number: 36, color: 'red' }, { number: 11, color: 'black' }, { number: 30, color: 'red' }, 
            { number: 8, color: 'black' }, { number: 23, color: 'red' }, { number: 10, color: 'black' }, 
            { number: 5, color: 'red' }, { number: 24, color: 'black' }, { number: 16, color: 'red' }, 
            { number: 33, color: 'black' }, { number: 1, color: 'red' }, { number: 20, color: 'black' }, 
            { number: 14, color: 'red' }, { number: 31, color: 'black' }, { number: 9, color: 'red' }, 
            { number: 22, color: 'black' }, { number: 18, color: 'red' }, { number: 29, color: 'black' }, 
            { number: 7, color: 'red' }, { number: 28, color: 'black' }, { number: 12, color: 'red' }, 
            { number: 35, color: 'black' }, { number: 3, color: 'red' }, { number: 26, color: 'black' }
        ];

        // Update credits display
        function updateRouletteCreditsDisplay() {
            rouletteCreditsDisplay.textContent = rouletteCredits;
            
            // Disable spin button if not enough credits
            if (rouletteCredits < parseInt(betAmount.value)) {
                rouletteSpinButton.disabled = true;
            } else {
                rouletteSpinButton.disabled = false;
            }
        }

        // Show message
        function showRouletteMessage(text, isWin = false) {
            rouletteMessage.textContent = text;
            rouletteMessage.className = isWin ? 'win-message' : 'lose-message';
            
            setTimeout(() => {
                rouletteMessage.textContent = '';
                rouletteMessage.className = '';
            }, 5000);
        }

        // Check win condition
        function checkRouletteWin(result) {
            const bet = parseInt(betAmount.value);
            const selectedType = betType.value;
            const resultNumber = rouletteNumbers[result].number;
            const resultColor = rouletteNumbers[result].color;
            
            let win = false;
            let multiplier = 0;
            
            switch(selectedType) {
                case 'red':
                    win = resultColor === 'red';
                    multiplier = 2;
                    break;
                case 'black':
                    win = resultColor === 'black';
                    multiplier = 2;
                    break;
                case 'even':
                    win = resultNumber !== 0 && resultNumber % 2 === 0;
                    multiplier = 2;
                    break;
                case 'odd':
                    win = resultNumber !== 0 && resultNumber % 2 === 1;
                    multiplier = 2;
                    break;
                case 'high':
                    win = resultNumber >= 19 && resultNumber <= 36;
                    multiplier = 2;
                    break;
                case 'low':
                    win = resultNumber >= 1 && resultNumber <= 18;
                    multiplier = 2;
                    break;
                case 'dozen1':
                    win = resultNumber >= 1 && resultNumber <= 12;
                    multiplier = 3;
                    break;
                case 'dozen2':
                    win = resultNumber >= 13 && resultNumber <= 24;
                    multiplier = 3;
                    break;
                case 'dozen3':
                    win = resultNumber >= 25 && resultNumber <= 36;
                    multiplier = 3;
                    break;
                case 'single':
                    const selectedNumber = parseInt(singleNumber.value);
                    win = resultNumber === selectedNumber;
                    multiplier = 36;
                    break;
            }
            
            if (win) {
                const winAmount = bet * multiplier;
                rouletteCredits += winAmount;
                showRouletteMessage(`GEWINN! +${winAmount} CREDITS`, true);
            } else {
                showRouletteMessage('VERLOREN');
                
                // Show no credits message if needed
                if (rouletteCredits < 5) {
                    showRouletteMessage('KEINE CREDITS MEHR! Füge mehr hinzu, um weiterzuspielen');
                }
            }
            
            updateRouletteCreditsDisplay();
        }

        // Spin the roulette wheel
        async function spinRouletteWheel() {
            if (isSpinning) return;
            
            const bet = parseInt(betAmount.value);
            if (rouletteCredits < bet) return;
            
            rouletteCredits -= bet;
            updateRouletteCreditsDisplay();
            isSpinning = true;
            
            // Reset result display
            rouletteResult.textContent = '?';
            rouletteMessage.textContent = '';
            rouletteMessage.className = '';
            
            // Start animation
            rouletteWheel.classList.add('spinning');
            rouletteBall.classList.add('spinning');
            
            // Random number of rotations plus random final position
            const spinDuration = 5000 + Math.random() * 2000;
            const result = Math.floor(Math.random() * rouletteNumbers.length);
            
            // Wait for spin to finish
            await new Promise(resolve => setTimeout(resolve, spinDuration));
            
            // Stop animation
            rouletteWheel.classList.remove('spinning');
            rouletteBall.classList.remove('spinning');
            
            // Show result
            const resultNumber = rouletteNumbers[result].number;
            const resultColor = rouletteNumbers[result].color;
            
            rouletteResult.textContent = resultNumber;
            rouletteResult.className = `result-${resultColor}`;
            
            // Check win
            checkRouletteWin(result);
            isSpinning = false;
        }

        // Event listeners
        rouletteSpinButton.addEventListener('click', spinRouletteWheel);
        
        rouletteAddCreditsButton.addEventListener('click', () => {
            rouletteCredits += 100;
            updateRouletteCreditsDisplay();
        });
        
        betType.addEventListener('change', () => {
            if (betType.value === 'single') {
                singleNumberContainer.style.display = 'block';
            } else {
                singleNumberContainer.style.display = 'none';
            }
        });
        
        betAmount.addEventListener('input', () => {
            updateRouletteCreditsDisplay();
        });
        
        // Initialize
        updateRouletteCreditsDisplay();
    });
</script>

<style>
/* Original slot machine styles can stay in casino.css */

/* New Roulette Game Styles */
.roulette-container {
    font-family: Arial, sans-serif;
    max-width: 600px;
    margin: 30px auto;
    background: linear-gradient(to bottom, #1a1a1a, #333);
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
    color: white;
    padding: 20px;
}

.roulette-game .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border-bottom: 2px solid #444;
    margin-bottom: 20px;
}

.roulette-wheel-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 20px;
}

.roulette-wheel {
    width: 250px;
    height: 250px;
    border-radius: 50%;
    background: conic-gradient(
        #9e1313, #0a7e07, #9e1313, #0a7e07, #9e1313,
        #0a7e07, #9e1313, #0a7e07, #9e1313, #0a7e07,
        #9e1313, #0a7e07, #9e1313, #0a7e07, #9e1313,
        #0a7e07, #9e1313, #0a7e07, #9e1313
    );
    position: relative;
    overflow: hidden;
    border: 10px solid #444;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.7), inset 0 0 15px rgba(0, 0, 0, 0.7);
    transition: transform 0.2s ease-in-out;
}

.wheel-center {
    position: absolute;
    width: 50px;
    height: 50px;
    background-color: #f0f0f0;
    border-radius: 50%;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 2;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
}

.ball {
    position: absolute;
    width: 15px;
    height: 15px;
    background-color: #f0f0f0;
    border-radius: 50%;
    top: 10%;
    left: 50%;
    transform: translateX(-50%);
    z-index: 3;
    box-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
}

.roulette-wheel.spinning {
    animation: spin 5s cubic-bezier(0.1, 0.7, 0.1, 1);
}

.ball.spinning {
    animation: bounce-ball 5s cubic-bezier(0.1, 0.7, 0.1, 1);
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(3600deg); }
}

@keyframes bounce-ball {
    0% { top: 10%; left: 50%; }
    10% { top: 20%; left: 80%; }
    20% { top: 40%; left: 90%; }
    30% { top: 60%; left: 80%; }
    40% { top: 80%; left: 50%; }
    50% { top: 85%; left: 20%; }
    60% { top: 80%; left: 10%; }
    70% { top: 60%; left: 20%; }
    80% { top: 40%; left: 30%; }
    90% { top: 20%; left: 40%; }
    100% { top: 50%; left: 50%; }
}

.result-display {
    font-size: 32px;
    font-weight: bold;
    margin: 20px 0;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #444;
}

.result-red {
    background-color: #9e1313;
    color: white;
}

.result-black {
    background-color: black;
    color: white;
}

.result-green {
    background-color: #0a7e07;
    color: white;
}

.betting-area {
    background-color: rgba(0, 0, 0, 0.3);
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.bet-options {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
}

.bet-option {
    display: flex;
    flex-direction: column;
    min-width: 120px;
}

.bet-option label {
    margin-bottom: 5px;
    font-size: 14px;
}

.bet-option input, .bet-option select {
    padding: 8px;
    border-radius: 4px;
    border: 1px solid #444;
    background-color: #222;
    color: white;
}

.controls {
    display: flex;
    justify-content: space-between;
    gap: 10px;
}

.spin-button, .add-credits {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.spin-button {
    background-color: #9e1313;
    color: white;
    flex-grow: 3;
}

.spin-button:hover:not(:disabled) {
    background-color: #c41717;
}

.add-credits {
    background-color: #4CAF50;
    color: white;
    flex-grow: 1;
}

.add-credits:hover {
    background-color: #5cbc5f;
}

button:disabled {
    background-color: #555;
    cursor: not-allowed;
}

.message-area {
    height: 40px;
    text-align: center;
    font-size: 18px;
    font-weight: bold;
    margin-top: 10px;
}

.win-message {
    color: #5cff5c;
}

.lose-message {
    color: #ff5c5c;
}
</style>