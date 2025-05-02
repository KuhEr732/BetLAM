<?php
// Include database 
require_once "db.php";

// Check if user is logged in
$userLoggedIn = isset($_SESSION['userId']);
$userCredits = 0;

// Get user credits from database if logged in
if ($userLoggedIn) {
    $userId = $_SESSION['userId'];
    $userData = getUserById($userId);
    
    if ($userData) {
        $userCredits = $userData['dtBalance'];
    }
}

// Handle AJAX requests to update balance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$userLoggedIn) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    }
    
    if ($_POST['action'] === 'update_balance') {
        $newBalance = isset($_POST['balance']) ? floatval($_POST['balance']) : 0;
        
        // Make sure updateUserBalance() is working properly
        global $pdo;
        $stmt = $pdo->prepare("UPDATE tblUser SET dtBalance = ? WHERE idUser = ?");
        $success = $stmt->execute([$newBalance, $_SESSION['userId']]);
        
        echo json_encode(['success' => $success]);
        exit;
    }
}
?>

<link rel="stylesheet" href="css/casino.css">

<div class="game-selector">
    <button id="slots-button" class="game-button active">Slot Machine</button>
    <button id="roulette-button" class="game-button">Roulette</button>
</div>

<!-- Slot Machine Game -->
<div class="slot-machine-container game-container" id="slots-game">
    <div class="slot-machine">
        <div class="header">
            <div class="credits">
                <span class="coin-icon">🪙</span>
                <span id="credits-display"><?php echo $userCredits; ?></span>
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
                        <div class="symbol">🍒</div> <!-- Repeat for seamless scrolling -->
                    </div>
                </div>
                <div class="reel-container">
                    <div class="reel-strip" id="reel2">
                        <div class="symbol">🍒</div>
                        <div class="symbol">🍋</div>
                        <div class="symbol">🍊</div>
                        <div class="symbol">🍇</div>
                        <div class="symbol">7️⃣</div>
                        <div class="symbol">🍒</div> <!-- Repeat for seamless scrolling -->
                    </div>
                </div>
                <div class="reel-container">
                    <div class="reel-strip" id="reel3">
                        <div class="symbol">🍒</div>
                        <div class="symbol">🍋</div>
                        <div class="symbol">🍊</div>
                        <div class="symbol">🍇</div>
                        <div class="symbol">7️⃣</div>
                        <div class="symbol">🍒</div> <!-- Repeat for seamless scrolling -->
                    </div>
                </div>
            </div>
        </div>
        
        <div class="controls">
            <button class="spin-button" id="spin-button">DREHEN (10 CREDITS)</button>
            <!-- Removed +100 coins button as requested -->
        </div>
    </div>
</div>

<!-- Roulette Game -->
<div class="roulette-container game-container" id="roulette-game" style="display: none;">
    <div class="roulette-game">
        <div class="header">
            <h2>Roulette</h2>
            <div class="credits">
                <span class="coin-icon">🪙</span>
                <span id="roulette-credits-display"><?php echo $userCredits; ?></span>
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
                <!-- Removed +100 coins button as requested -->
            </div>
        </div>
        
        <div class="message-area" id="roulette-message"></div>
    </div>
</div>

<script>
    // Game selector logic
    document.addEventListener('DOMContentLoaded', function() {
        const slotsButton = document.getElementById('slots-button');
        const rouletteButton = document.getElementById('roulette-button');
        const slotsGame = document.getElementById('slots-game');
        const rouletteGame = document.getElementById('roulette-game');
        
        slotsButton.addEventListener('click', function() {
            slotsGame.style.display = 'block';
            rouletteGame.style.display = 'none';
            slotsButton.classList.add('active');
            rouletteButton.classList.remove('active');
        });
        
        rouletteButton.addEventListener('click', function() {
            slotsGame.style.display = 'none';
            rouletteGame.style.display = 'block';
            rouletteButton.classList.add('active');
            slotsButton.classList.remove('active');
        });
    });

    // Function to update the database - Direct implementation
    function updateDatabaseBalance(balance) {
        return new Promise((resolve, reject) => {
            // Create form data
            const formData = new FormData();
            formData.append('action', 'update_balance');
            formData.append('balance', balance);
            
            // Log for debugging
            console.log('Updating balance to:', balance);
            
            // Send AJAX request
            fetch('index.php?page=casino', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Database update response:', data);
                if (data.success) {
                    resolve(true);
                } else {
                    console.error('Failed to update balance:', data.message);
                    resolve(false);
                }
            })
            .catch(error => {
                console.error('Error updating balance:', error);
                resolve(false);
            });
        });
    }

    // Slot Machine Script
    const reelsContainer = document.getElementById('reels-container');
    const reel1 = document.getElementById('reel1');
    const reel2 = document.getElementById('reel2');
    const reel3 = document.getElementById('reel3');
    const spinButton = document.getElementById('spin-button');
    const creditsDisplay = document.getElementById('credits-display');

    // Game variables
    let credits = parseInt(creditsDisplay.textContent) || 0;
    let spinning = false;
    const symbols = ['🍒', '🍋', '🍊', '🍇', '7️⃣'];
    let results = [0, 0, 0];
    const isLoggedIn = <?php echo $userLoggedIn ? 'true' : 'false'; ?>;

    // Update credits display
    function updateCreditsDisplay() {
        creditsDisplay.textContent = credits;
        
        // Also update roulette credits display to keep in sync
        document.getElementById('roulette-credits-display').textContent = credits;
        
        // Disable the spin button if not enough credits
        if (credits < 10) {
            spinButton.disabled = true;
        } else {
            spinButton.disabled = false;
        }
        
        // Update in database if logged in
        if (isLoggedIn) {
            updateDatabaseBalance(credits);
        }
    }

    // Show win effect
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

    // Show lose effect
    function showLoseEffect() {
        const loseEffect = document.createElement('div');
        loseEffect.className = 'lose-effect';
        
        const loseText = document.createElement('div');
        loseText.className = 'lose-text';
        loseText.textContent = 'KEINE CREDITS MEHR';
        
        const loseSubtext = document.createElement('div');
        loseSubtext.className = 'lose-subtext';
        loseSubtext.textContent = 'Kontaktieren Sie den Support für mehr Credits';
        
        loseEffect.appendChild(loseText);
        loseEffect.appendChild(loseSubtext);
        reelsContainer.appendChild(loseEffect);
        
        setTimeout(() => {
            if (reelsContainer.contains(loseEffect)) {
                reelsContainer.removeChild(loseEffect);
            }
        }, 3000);
    }

    // Check for win
    function checkWin(results) {
        // Check if all symbols are the same
        if (results[0] === results[1] && results[1] === results[2]) {
            const multiplier = results[0] === 4 ? 50 : results[0] === 3 ? 25 : results[0] === 2 ? 15 : results[0] === 1 ? 10 : 5;
            const amount = 10 * multiplier;
            credits += amount;
            showWinEffect(amount);
        } else if (results[0] === results[1] || results[1] === results[2]) {
            // Two matching symbols
            const amount = 5;
            credits += amount;
            showWinEffect(amount);
        } else {
            // Show lose effect if no more credits
            if (credits < 10) {
                showLoseEffect();
            }
        }
        
        updateCreditsDisplay();
    }

    // Stop a reel at a specific position
    function stopReel(reelElement, position) {
        return new Promise(resolve => {
            reelElement.classList.remove('spinning');
            reelElement.style.top = `-${position * 80}px`;
            
            setTimeout(() => {
                resolve();
            }, 500); // Wait for transition animation to end
        });
    }

    // Spin the reels
    async function spin() {
        if (spinning || credits < 10) return;
        
        credits -= 10;
        updateCreditsDisplay();
        spinning = true;
        
        // Remove existing effects
        const effects = reelsContainer.querySelectorAll('.win-effect, .lose-effect');
        effects.forEach(effect => reelsContainer.removeChild(effect));
        
        // Start spin animation
        reel1.classList.add('spinning');
        reel2.classList.add('spinning');
        reel3.classList.add('spinning');
        
        // Generate random results
        results = [
            Math.floor(Math.random() * symbols.length),
            Math.floor(Math.random() * symbols.length),
            Math.floor(Math.random() * symbols.length)
        ];
        
        // Stop the reels one after another
        await new Promise(resolve => setTimeout(resolve, 800));
        await stopReel(reel1, results[0]);
        
        await new Promise(resolve => setTimeout(resolve, 400));
        await stopReel(reel2, results[1]);
        
        await new Promise(resolve => setTimeout(resolve, 400));
        await stopReel(reel3, results[2]);
        
        // Check for win
        checkWin(results);
        spinning = false;
    }

    // Event listeners
    spinButton.addEventListener('click', spin);
    
    // Initialize
    updateCreditsDisplay();

    // Roulette Game Script
    document.addEventListener('DOMContentLoaded', function() {
        // Roulette Elements
        const rouletteWheel = document.getElementById('roulette-wheel');
        const rouletteBall = document.getElementById('roulette-ball');
        const rouletteSpinButton = document.getElementById('roulette-spin-button');
        const rouletteCreditsDisplay = document.getElementById('roulette-credits-display');
        const betAmount = document.getElementById('bet-amount');
        const betType = document.getElementById('bet-type');
        const singleNumberContainer = document.getElementById('single-number-container');
        const singleNumber = document.getElementById('single-number');
        const rouletteResult = document.getElementById('roulette-result');
        const rouletteMessage = document.getElementById('roulette-message');

        // Roulette variables
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

        // Update roulette credits display
        function updateRouletteCreditsDisplay() {
            rouletteCreditsDisplay.textContent = credits;
            
            // Disable spin button if not enough credits
            if (credits < parseInt(betAmount.value)) {
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
                credits += winAmount;
                showRouletteMessage(`GEWINN! +${winAmount} CREDITS`, true);
            } else {
                showRouletteMessage('VERLOREN');
                
                // Show no credits message if needed
                if (credits < 5) {
                    showRouletteMessage('KEINE CREDITS MEHR! Kontaktieren Sie den Support für mehr Credits');
                }
            }
            
            // Update both displays to keep in sync
            updateCreditsDisplay();
            updateRouletteCreditsDisplay();
        }

        // Spin the roulette wheel
        async function spinRouletteWheel() {
            if (isSpinning) return;
            
            const bet = parseInt(betAmount.value);
            if (credits < bet) return;
            
            credits -= bet;
            updateCreditsDisplay();
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

/* Game Selector Styles */
.game-selector {
    max-width: 600px;
    margin: 20px auto;
    display: flex;
    gap: 10px;
    justify-content: center;
}

.game-button {
    padding: 15px 30px;
    font-size: 18px;
    font-weight: bold;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    background-color: #333;
    color: white;
    flex-grow: 1;
    text-align: center;
}

.game-button:hover {
    background-color: #444;
}

.game-button.active {
    background-color: #9e1313;
    box-shadow: 0 0 10px rgba(158, 19, 19, 0.5);
}

/* Game Container Styles */
.game-container {
    transition: display 0.5s ease-in-out;
}

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
    width: 100%;
}

.spin-button:hover:not(:disabled) {
    background-color: #c41717;
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