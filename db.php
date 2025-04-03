<?php
// Datenbankverbindung
$host = 'localhost';
$dbname = 'CasinoDB';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
}

// Hilfsfunktionen für die Datenbank

// Benutzer anhand der ID abrufen
function getUserById($userId) {
    global $pdo;
    if (!$pdo) {
        return null; // Fehlerbehandlung, wenn keine Verbindung besteht
    }
    $stmt = $pdo->prepare("SELECT * FROM tblUser WHERE idUser = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

// Guthaben eines Benutzers aktualisieren
function updateUserBalance($userId, $amount) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE tblUser SET dtBalance = dtBalance + ? WHERE idUser = ?");
    return $stmt->execute([$amount, $userId]);
}

// Spiel anhand des Typs abrufen
function getGameByType($gameType) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM tblGame WHERE dtType = ?");
    $stmt->execute([$gameType]);
    return $stmt->fetch();
}

// Wette in die Datenbank eintragen
function placeBet($userId, $gameId, $amount) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO tblBet (fiUser, fiGame, dtAmount) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $gameId, $amount]);
    return $pdo->lastInsertId();
}

// Wette aktualisieren (Ergebnis und Gewinn)
function updateBet($betId, $outcome, $winnings) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE tblBet SET dtOutcome = ?, dtWinnings = ? WHERE idBet = ?");
    return $stmt->execute([$outcome, $winnings, $betId]);
}

// Audit-Log-Eintrag erstellen
function createAuditLog($userId, $action) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO tblAuditLog (fiUser, dtAction) VALUES (?, ?)");
    return $stmt->execute([$userId, $action]);
}

// Spiele in die Datenbank eintragen oder aktualisieren
function saveMatch($matchId, $league, $homeTeam, $awayTeam, $startTime, $homeOdds, $drawOdds, $awayOdds) {
    global $pdo;
    
    // Prüfen, ob das Spiel bereits existiert
    $stmt = $pdo->prepare("SELECT * FROM tblMatch WHERE dtExternalId = ?");
    $stmt->execute([$matchId]);
    $match = $stmt->fetch();
    
    if ($match) {
        // Spiel aktualisieren
        $stmt = $pdo->prepare("UPDATE tblMatch SET 
            dtLeague = ?, 
            dtHomeTeam = ?, 
            dtAwayTeam = ?, 
            dtStartTime = ?, 
            dtHomeOdds = ?, 
            dtDrawOdds = ?, 
            dtAwayOdds = ? 
            WHERE dtExternalId = ?");
        return $stmt->execute([$league, $homeTeam, $awayTeam, $startTime, $homeOdds, $drawOdds, $awayOdds, $matchId]);
    } else {
        // Neues Spiel eintragen
        $stmt = $pdo->prepare("INSERT INTO tblMatch 
            (dtExternalId, dtLeague, dtHomeTeam, dtAwayTeam, dtStartTime, dtHomeOdds, dtDrawOdds, dtAwayOdds) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$matchId, $league, $homeTeam, $awayTeam, $startTime, $homeOdds, $drawOdds, $awayOdds]);
    }
}

// Wettschein in die Datenbank eintragen
function saveBetslip($userId, $totalOdds, $stake, $potentialWin) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO tblBetSlip (fiUser, dtTotalOdds, dtStake, dtPotentialWinnings) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $totalOdds, $stake, $potentialWin]);
    return $pdo->lastInsertId();
}

// Wettschein-Position in die Datenbank eintragen
function saveBetslipItem($betslipId, $matchId, $betType, $odds) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO tblBetItem (fiBetSlip, fiMatch, dtBetType, dtOdds) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$betslipId, $matchId, $betType, $odds]);
}

// Spiele für den aktuellen/kommenden Spieltag abrufen
function getUpcomingMatches() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM tblMatch WHERE dtStartTime > NOW() AND dtStartTime < DATE_ADD(NOW(), INTERVAL 7 DAY) ORDER BY dtStartTime ASC");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Wettscheine eines Benutzers abrufen
function getUserBetslips($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT b.*, 
               COUNT(bi.idBetItem) as itemCount 
        FROM tblBetSlip b 
        LEFT JOIN tblBetItem bi ON b.idBetSlip = bi.fiBetSlip 
        WHERE b.fiUser = ? 
        GROUP BY b.idBetSlip 
        ORDER BY b.dtCreatedAt DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Wettschein-Details abrufen
function getBetslipDetails($betslipId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT bi.*, 
               m.dtHomeTeam, 
               m.dtAwayTeam, 
               m.dtStartTime 
        FROM tblBetItem bi 
        JOIN tblMatch m ON bi.fiMatch = m.idMatch 
        WHERE bi.fiBetSlip = ?
    ");
    $stmt->execute([$betslipId]);
    return $stmt->fetchAll();
}

// Spiel-Ergebnis in die Datenbank eintragen
function saveMatchResult($matchId, $homeScore, $awayScore) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE tblMatch SET dtHomeScore = ?, dtAwayScore = ?, dtStatus = 'completed' WHERE idMatch = ?");
    return $stmt->execute([$homeScore, $awayScore, $matchId]);
}

// Wettscheine auswerten, die auf ein bestimmtes Spiel gesetzt haben
function evaluateBetslipsForMatch($matchId, $homeScore, $awayScore) {
    global $pdo;
    
    // Alle Wettschein-Positionen für dieses Spiel abrufen
    $stmt = $pdo->prepare("
        SELECT bi.*, b.fiUser, b.dtStake, b.dtTotalOdds, b.dtPotentialWinnings 
        FROM tblBetItem bi 
        JOIN tblBetSlip b ON bi.fiBetSlip = b.idBetSlip 
        WHERE bi.fiMatch = ? AND b.dtStatus = 'open'
    ");
    $stmt->execute([$matchId]);
    $betslipItems = $stmt->fetchAll();
    
    foreach ($betslipItems as $item) {
        $outcome = 'lost'; // Standardmäßig Verlust
        
        // Ergebnis bestimmen
        if ($homeScore > $awayScore && $item['dtBetType'] == 'home') {
            $outcome = 'won';
        } else if ($homeScore == $awayScore && $item['dtBetType'] == 'draw') {
            $outcome = 'won';
        } else if ($homeScore < $awayScore && $item['dtBetType'] == 'away') {
            $outcome = 'won';
        }
        
        // Wettschein-Position aktualisieren
        $stmt = $pdo->prepare("UPDATE tblBetItem SET dtOutcome = ? WHERE idBetItem = ?");
        $stmt->execute([$outcome, $item['idBetItem']]);
        
        // Prüfen, ob alle Positionen des Wettscheins ausgewertet wurden
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total, 
                   SUM(CASE WHEN dtOutcome = 'pending' THEN 1 ELSE 0 END) as pending,
                   SUM(CASE WHEN dtOutcome = 'won' THEN 1 ELSE 0 END) as wins
            FROM tblBetItem 
            WHERE fiBetSlip = ?
        ");
        $stmt->execute([$item['fiBetSlip']]);
        $result = $stmt->fetch();
        
        // Wenn keine Positionen mehr ausstehen und alle gewonnen haben
        if ($result['pending'] == 0) {
            $betslipOutcome = ($result['wins'] == $result['total']) ? 'won' : 'lost';
            $winnings = ($betslipOutcome == 'won') ? $item['dtPotentialWinnings'] : 0;
            
            // Wettschein aktualisieren
            $stmt = $pdo->prepare("UPDATE tblBetSlip SET dtStatus = ?, dtSettledAt = NOW() WHERE idBetSlip = ?");
            $stmt->execute([$betslipOutcome, $item['fiBetSlip']]);
            
            // Bei Gewinn das Guthaben des Benutzers aktualisieren
            if ($betslipOutcome == 'won') {
                updateUserBalance($item['fiUser'], $winnings);
                createAuditLog($item['fiUser'], "Wettschein #" . $item['fiBetSlip'] . " gewonnen: €" . $winnings);
            } else {
                createAuditLog($item['fiUser'], "Wettschein #" . $item['fiBetSlip'] . " verloren");
            }
        }
    }
}
?>