<link rel="stylesheet" href="css/sports.css">
<?php
// Prüfen, ob der Benutzer angemeldet ist
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;
$user = null;

// Benutzer abrufen, wenn angemeldet
if ($isLoggedIn) {
    $user = getUserById($userId);
}

// API-Schlüssel
$apiKey = '614d4cd73268e09019d4308930b3a2e9';

// Sportarten und Ligen
$sports = [
    ['key' => 'soccer_germany_bundesliga', 'name' => 'Bundesliga'],
    ['key' => 'soccer_epl', 'name' => 'Premier League'],
    ['key' => 'soccer_spain_la_liga', 'name' => 'La Liga'],
    ['key' => 'soccer_italy_serie_a', 'name' => 'Serie A'],
    ['key' => 'soccer_france_ligue_one', 'name' => 'Ligue 1'],
    ['key' => 'soccer_uefa_champs_league', 'name' => 'Champions League']
];

// Funktion zum Abrufen der Spiele aus der Datenbank
function getMatches() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM tblMatch 
            WHERE dtStartTime > NOW() 
            AND dtStartTime < DATE_ADD(NOW(), INTERVAL 7 DAY) 
            ORDER BY dtLeague, dtStartTime
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// Funktion zum Aktualisieren der Spiele und Quoten
function updateMatchesFromAPI() {
    global $pdo, $sports, $apiKey;
    
    $updatedMatches = 0;
    
    foreach ($sports as $sport) {
        $url = "https://api.the-odds-api.com/v4/sports/{$sport['key']}/odds/?apiKey=$apiKey&regions=eu&markets=h2h";
        
        try {
            $response = file_get_contents($url);
            $data = json_decode($response, true);
            
            if (is_array($data)) {
                foreach ($data as $match) {
                    // Nur kommende Spiele berücksichtigen
                    $matchDate = new DateTime($match['commence_time']);
                    $now = new DateTime();
                    $oneWeekLater = new DateTime('+7 days');
                    
                    if ($matchDate > $now && $matchDate < $oneWeekLater) {
                        // Quoten extrahieren
                        $homeOdds = null;
                        $drawOdds = null;
                        $awayOdds = null;
                        
                        if (!empty($match['bookmakers'])) {
                            $bookmaker = $match['bookmakers'][0];
                            
                            if (!empty($bookmaker['markets'])) {
                                $h2hMarket = null;
                                
                                foreach ($bookmaker['markets'] as $market) {
                                    if ($market['key'] == 'h2h') {
                                        $h2hMarket = $market;
                                        break;
                                    }
                                }
                                
                                if ($h2hMarket && !empty($h2hMarket['outcomes'])) {
                                    foreach ($h2hMarket['outcomes'] as $outcome) {
                                        if ($outcome['name'] == $match['home_team']) {
                                            $homeOdds = $outcome['price'];
                                        } else if ($outcome['name'] == 'Draw') {
                                            $drawOdds = $outcome['price'];
                                        } else if ($outcome['name'] == $match['away_team']) {
                                            $awayOdds = $outcome['price'];
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Spiel in die Datenbank eintragen oder aktualisieren
                        saveMatch(
                            $match['id'],
                            $sport['name'],
                            $match['home_team'],
                            $match['away_team'],
                            $match['commence_time'],
                            $homeOdds,
                            $drawOdds,
                            $awayOdds
                        );
                        
                        $updatedMatches++;
                    }
                }
            }
        } catch (Exception $e) {
            // Fehler beim Abrufen der API ignorieren und mit der nächsten Sportart fortfahren
            continue;
        }
    }
    
    return $updatedMatches;
}

// Funktion zum Abrufen der Spiele nach Ligen gruppiert
function getMatchesByLeague() {
    $matches = getMatches();
    $matchesByLeague = [];
    
    if (is_array($matches)) {
        foreach ($matches as $match) {
            $league = $match['dtLeague'];
            
            if (!isset($matchesByLeague[$league])) {
                $matchesByLeague[$league] = [];
            }
            
            $matchesByLeague[$league][] = $match;
        }
    }
    
    return $matchesByLeague;
}

// AJAX-Anfragen verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Daten aus dem Request-Body lesen
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data['action'] == 'place_bet') {
        if (!$isLoggedIn) {
            echo json_encode(['success' => false, 'message' => 'Bitte melde dich an, um eine Wette zu platzieren.']);
            exit;
        }
        
        $stake = floatval($data['stake']);
        $totalOdds = floatval($data['totalOdds']);
        $potentialWin = floatval($data['potentialWin']);
        $betslipItems = $data['betslip'];
        
        // Prüfen, ob der Benutzer genug Guthaben hat
        if ($user['dtBalance'] < $stake) {
            echo json_encode(['success' => false, 'message' => 'Nicht genug Guthaben.']);
            exit;
        }
        
        // Wettschein in die Datenbank eintragen
        try {
            $pdo->beginTransaction();
            
            // Guthaben abziehen
            updateUserBalance($userId, -$stake);
            
            // Wettschein erstellen
            $betslipId = saveBetslip($userId, $totalOdds, $stake, $potentialWin);
            
            // Wettschein-Positionen erstellen
            foreach ($betslipItems as $item) {
                saveBetslipItem($betslipId, $item['matchId'], $item['betType'], $item['odds']);
            }
            
            // Audit-Log erstellen
            createAuditLog($userId, "Wettschein #$betslipId platziert: €$stake");
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Wette erfolgreich platziert!', 'betslipId' => $betslipId]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Fehler beim Platzieren der Wette: ' . $e->getMessage()]);
        }
        
        exit;
    }
}

// Hauptinhalt der Sportwetten-Seite
?>

<div class="container">
    <!-- Loading Indicator -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
        <p>Quoten werden geladen...</p>
    </div>

    <!-- Error Message -->
    <div class="error-message" id="error-message"></div>
    
    <!-- Success Message -->
    <div class="success-message" id="success-message"></div>

    <div class="betting-section" id="betting-section" style="display: none;">
        <div class="matches-container">
            <h2 class="section-title">Aktuelle Spiele</h2>
            <div id="leagues-container">
                <!-- Ligen werden per JavaScript eingefügt -->
            </div>
        </div>

        <div class="betslip-container">
            <div class="betslip" id="betslip">
                <h2 class="section-title">Wettschein</h2>
                <div id="betslip-content">
                    <div class="betslip-empty" id="betslip-empty">
                        <p>Dein Wettschein ist leer</p>
                        <p>Wähle Wetten aus, um zu beginnen</p>
                    </div>
                    <div id="betslip-items" class="betslip-items" style="display: none;"></div>
                    <div id="betslip-stake" class="betslip-stake" style="display: none;">
                        <label for="stake">Einsatz (€):</label>
                        <input type="number" id="stake" class="stake-input" min="1" value="10">
                    </div>
                    <div id="betslip-summary" class="betslip-summary" style="display: none;">
                        <div class="summary-item">
                            <span>Gesamtquote:</span>
                            <span id="total-odds">0.00</span>
                        </div>
                        <div class="summary-item total">
                            <span>Möglicher Gewinn:</span>
                            <span id="potential-win">€0.00</span>
                        </div>
                    </div>
                    <button id="place-bet-btn" class="place-bet-btn" disabled>Wette platzieren</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ergebnis-Modal -->
<div id="result-modal" class="modal">
    <div class="modal-content">
        <h2 class="result-title">Wette platziert</h2>
        <div id="result-message" class="result-message"></div>
        <button id="close-modal" class="close-modal">Schließen</button>
    </div>
</div>

<script>
    // DOM-Elemente
    const loadingEl = document.getElementById('loading');
    const errorMessageEl = document.getElementById('error-message');
    const successMessageEl = document.getElementById('success-message');
    const bettingSectionEl = document.getElementById('betting-section');
    const leaguesContainerEl = document.getElementById('leagues-container');
    const betslipItemsEl = document.getElementById('betslip-items');
    const betslipEmptyEl = document.getElementById('betslip-empty');
    const betslipStakeEl = document.getElementById('betslip-stake');
    const betslipSummaryEl = document.getElementById('betslip-summary');
    const totalOddsEl = document.getElementById('total-odds');
    const potentialWinEl = document.getElementById('potential-win');
    const stakeInputEl = document.getElementById('stake');
    const placeBetBtn = document.getElementById('place-bet-btn');
    const resultModal = document.getElementById('result-modal');
    const resultMessageEl = document.getElementById('result-message');
    const closeModalBtn = document.getElementById('close-modal');

    // Wettschein-Daten
    let betslip = [];
    let matches = [];
    
    // Beim Laden der Seite Daten abrufen
    window.addEventListener('DOMContentLoaded', async () => {
        showLoading(true);
        hideError();
        
        try {
            // Spiele aus der Datenbank abrufen
            const response = await fetch('includes/get_matches.php');
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                matches = data.matches;
                
                // Spiele nach Ligen gruppieren
                const matchesByLeague = {};
                
                if (Array.isArray(matches)) {
                    matches.forEach(match => {
                        if (!matchesByLeague[match.dtLeague]) {
                            matchesByLeague[match.dtLeague] = [];
                        }
                        matchesByLeague[match.dtLeague].push(match);
                    });
                }
                
                // Ligen rendern
                renderLeagues(matchesByLeague);
                
                // Wettbereich anzeigen
                bettingSectionEl.style.display = 'flex';
            } else {
                showError(data.message || 'Fehler beim Laden der Spiele.');
            }
        } catch (error) {
            showError('Fehler beim Laden der Spiele: ' + error.message);
        } finally {
            showLoading(false);
        }
    });

    // Ligen rendern
    function renderLeagues(matchesByLeague) {
        leaguesContainerEl.innerHTML = '';
        
        // Prüfen, ob Daten vorhanden sind
        const hasData = Object.keys(matchesByLeague).length > 0;
        
        if (!hasData) {
            leaguesContainerEl.innerHTML = `
                <div class="no-matches">
                    <p>Keine aktuellen Spiele gefunden.</p>
                    <p>Bitte versuche es später erneut.</p>
                </div>
            `;
            return;
        }
        
        // Für jede Liga ein Accordion erstellen
        Object.keys(matchesByLeague).forEach((league, index) => {
            const leagueMatches = matchesByLeague[league];
            
            const leagueEl = document.createElement('div');
            leagueEl.className = 'league-accordion';
            
            const leagueHeader = document.createElement('div');
            leagueHeader.className = 'league-header';
            leagueHeader.innerHTML = `
                <div class="league-name">${league}</div>
                <div class="league-toggle">▼</div>
            `;
            
            const leagueContent = document.createElement('div');
            leagueContent.className = 'league-content';
            
            // Spiele für diese Liga rendern
            const matchListEl = document.createElement('div');
            matchListEl.className = 'match-list';
            
            if (leagueMatches.length === 0) {
                matchListEl.innerHTML = '<p class="no-matches">Keine aktuellen Spiele in dieser Liga.</p>';
            } else {
                leagueMatches.forEach(match => {
                    const matchCard = document.createElement('div');
                    matchCard.className = 'match-card';
                    
                    matchCard.innerHTML = `
                        <div class="match-header">
                            <div class="match-time">${formatDate(new Date(match.dtStartTime))}</div>
                        </div>
                        <div class="match-details">
                            <div class="match-teams">
                                <div class="team">
                                    <div class="team-logo">${match.dtHomeTeam.charAt(0)}</div>
                                    <div class="team-name">${match.dtHomeTeam}</div>
                                </div>
                                <div class="vs">vs</div>
                                <div class="team">
                                    <div class="team-logo">${match.dtAwayTeam.charAt(0)}</div>
                                    <div class="team-name">${match.dtAwayTeam}</div>
                                </div>
                            </div>
                            <div class="match-odds">
                                <div class="odd-button" data-match-id="${match.idMatch}" data-bet-type="home" data-odds="${match.dtHomeOdds || '-'}">
                                    <div class="odd-label">1</div>
                                    <div class="odd-value">${match.dtHomeOdds ? parseFloat(match.dtHomeOdds).toFixed(2) : '-'}</div>
                                </div>
                                ${match.dtDrawOdds ? `
                                    <div class="odd-button" data-match-id="${match.idMatch}" data-bet-type="draw" data-odds="${match.dtDrawOdds}">
                                        <div class="odd-label">X</div>
                                        <div class="odd-value">${parseFloat(match.dtDrawOdds).toFixed(2)}</div>
                                    </div>
                                ` : ''}
                                <div class="odd-button" data-match-id="${match.idMatch}" data-bet-type="away" data-odds="${match.dtAwayOdds || '-'}">
                                    <div class="odd-label">2</div>
                                    <div class="odd-value">${match.dtAwayOdds ? parseFloat(match.dtAwayOdds).toFixed(2) : '-'}</div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    matchListEl.appendChild(matchCard);
                });
            }
            
            leagueContent.appendChild(matchListEl);
            leagueEl.appendChild(leagueHeader);
            leagueEl.appendChild(leagueContent);
            leaguesContainerEl.appendChild(leagueEl);
            
            // Event-Listener für Accordion-Toggle
            leagueHeader.addEventListener('click', () => {
                const isOpen = leagueContent.classList.contains('open');
                
                // Toggle-Klasse umschalten
                leagueContent.classList.toggle('open');
                leagueHeader.querySelector('.league-toggle').classList.toggle('open');
            });
            
            // Erste Liga automatisch öffnen
            if (index === 0) {
                leagueContent.classList.add('open');
                leagueHeader.querySelector('.league-toggle').classList.add('open');
            }
        });
        
        // Event-Listener für Quoten-Buttons
        document.querySelectorAll('.odd-button').forEach(button => {
            const odds = parseFloat(button.dataset.odds);
            if (!isNaN(odds) && odds > 0) {
                button.addEventListener('click', handleOddSelection);
            } else {
                button.style.opacity = '0.5';
                button.style.cursor = 'not-allowed';
            }
        });
    }

    // Datum formatieren
    function formatDate(date) {
        const now = new Date();
        const tomorrow = new Date(now);
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        const isToday = date.getDate() === now.getDate() && 
                       date.getMonth() === now.getMonth() && 
                       date.getFullYear() === now.getFullYear();
                       
        const isTomorrow = date.getDate() === tomorrow.getDate() && 
                          date.getMonth() === tomorrow.getMonth() && 
                          date.getFullYear() === tomorrow.getFullYear();
        
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        const time = `${hours}:${minutes}`;
        
        if (isToday) {
            return `Heute, ${time}`;
        } else if (isTomorrow) {
            return `Morgen, ${time}`;
        } else {
            const day = date.getDate().toString().padStart(2, '0');
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            return `${day}.${month}, ${time}`;
        }
    }

    // Wette zum Wettschein hinzufügen
    function handleOddSelection(e) {
        const button = e.currentTarget;
        const matchId = button.dataset.matchId;
        const betType = button.dataset.betType;
        const odds = parseFloat(button.dataset.odds);
        
        if (isNaN(odds) || odds <= 0) {
            return; // Ungültige Quote
        }
        
        // Bereits ausgewählte Wette entfernen
        const existingBetIndex = betslip.findIndex(bet => bet.matchId === matchId);
        if (existingBetIndex !== -1) {
            betslip.splice(existingBetIndex, 1);
            document.querySelectorAll(`.odd-button[data-match-id="${matchId}"]`).forEach(btn => {
                btn.classList.remove('selected');
            });
        }
        
        // Neue Wette hinzufügen
        if (existingBetIndex === -1 || button.dataset.betType !== betslip[existingBetIndex]?.betType) {
            const match = matches.find(m => m.idMatch == matchId);
            
            let betTypeLabel = '';
            if (betType === 'home') {
                betTypeLabel = '1 (Heimsieg)';
            } else if (betType === 'draw') {
                betTypeLabel = 'X (Unentschieden)';
            } else if (betType === 'away') {
                betTypeLabel = '2 (Auswärtssieg)';
            }
            
            betslip.push({
                matchId,
                homeTeam: match.dtHomeTeam,
                awayTeam: match.dtAwayTeam,
                betType,
                odds,
                betTypeLabel
            });
            
            button.classList.add('selected');
        }
        
        updateBetslip();
    }

    // Wettschein aktualisieren
    function updateBetslip() {
        if (betslip.length === 0) {
            betslipEmptyEl.style.display = 'block';
            betslipItemsEl.style.display = 'none';
            betslipStakeEl.style.display = 'none';
            betslipSummaryEl.style.display = 'none';
            placeBetBtn.disabled = true;
            return;
        }
        
        betslipEmptyEl.style.display = 'none';
        betslipItemsEl.style.display = 'block';
        betslipStakeEl.style.display = 'block';
        betslipSummaryEl.style.display = 'block';
        placeBetBtn.disabled = false;
        
        // Wettschein-Einträge rendern
        betslipItemsEl.innerHTML = '';
        betslip.forEach((bet, index) => {
            const betItem = document.createElement('div');
            betItem.className = 'betslip-item';
            
            betItem.innerHTML = `
                <div class="betslip-teams">${bet.homeTeam} vs ${bet.awayTeam}</div>
                <div class="betslip-bet">
                    <span class="betslip-bet-type">${bet.betTypeLabel}</span>
                    <span class="betslip-odds">${bet.odds.toFixed(2)}</span>
                </div>
                <button class="betslip-remove" data-index="${index}">Entfernen</button>
            `;
            
            betslipItemsEl.appendChild(betItem);
        });
        
        // Event-Listener für Entfernen-Buttons
        document.querySelectorAll('.betslip-remove').forEach(button => {
            button.addEventListener('click', (e) => {
                const index = parseInt(e.target.dataset.index);
                const removedBet = betslip[index];
                
                // Auswahl im Spiel zurücksetzen
                document.querySelectorAll(`.odd-button[data-match-id="${removedBet.matchId}"]`).forEach(btn => {
                    btn.classList.remove('selected');
                });
                
                betslip.splice(index, 1);
                updateBetslip();
            });
        });
        
        // Gesamtquote berechnen
        const totalOdds = betslip.reduce((total, bet) => total * bet.odds, 1);
        totalOddsEl.textContent = totalOdds.toFixed(2);
        
        // Möglichen Gewinn berechnen
        calculatePotentialWin();
        
        // Event-Listener für Einsatz-Änderungen
        stakeInputEl.addEventListener('input', calculatePotentialWin);
    }

    // Möglichen Gewinn berechnen
    function calculatePotentialWin() {
        const stake = parseFloat(stakeInputEl.value) || 0;
        const totalOdds = betslip.reduce((total, bet) => total * bet.odds, 1);
        const potentialWin = stake * totalOdds;
        
        potentialWinEl.textContent = `€${potentialWin.toFixed(2)}`;
    }

    // Wette platzieren
    placeBetBtn.addEventListener('click', async () => {
        const stake = parseFloat(stakeInputEl.value) || 0;
        
        if (stake <= 0 || betslip.length === 0) {
            return;
        }
        
        // Gesamtquote und möglichen Gewinn berechnen
        const totalOdds = betslip.reduce((total, bet) => total * bet.odds, 1);
        const potentialWin = stake * totalOdds;
        
        try {
            // Wette an den Server senden
            const response = await fetch('index.php?page=sports', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'place_bet',
                    stake,
                    totalOdds,
                    potentialWin,
                    betslip
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Erfolgsmeldung anzeigen
                resultMessageEl.textContent = data.message;
                resultMessageEl.className = 'result-message result-win';
                resultModal.style.display = 'flex';
                
                // Wettschein zurücksetzen
                betslip = [];
                updateBetslip();
                document.querySelectorAll('.odd-button').forEach(btn => {
                    btn.classList.remove('selected');
                });
                
                // Guthaben aktualisieren (ohne Seite neu zu laden)
                const balanceEl = document.querySelector('.balance');
                if (balanceEl) {
                    const currentBalance = parseFloat(balanceEl.textContent.replace('Guthaben: €', '').replace(',', ''));
                    const newBalance = currentBalance - stake;
                    balanceEl.textContent = `Guthaben: €${newBalance.toFixed(2)}`;
                }
            } else {
                // Fehlermeldung anzeigen
                showError(data.message);
            }
        } catch (error) {
            showError('Fehler beim Platzieren der Wette: ' + error.message);
        }
    });

    // Modal schließen
    closeModalBtn.addEventListener('click', () => {
        resultModal.style.display = 'none';
    });

    // Hilfsfunktionen
    function showLoading(show) {
        loadingEl.style.display = show ? 'block' : 'none';
    }

    function showError(message) {
        errorMessageEl.textContent = message;
        errorMessageEl.style.display = 'block';
        
        // Nach 5 Sekunden ausblenden
        setTimeout(() => {
            errorMessageEl.style.display = 'none';
        }, 5000);
    }

    function showSuccess(message) {
        successMessageEl.textContent = message;
        successMessageEl.style.display = 'block';
        
        // Nach 5 Sekunden ausblenden
        setTimeout(() => {
            successMessageEl.style.display = 'none';
        }, 5000);
    }

    function hideError() {
        errorMessageEl.style.display = 'none';
    }
</script>