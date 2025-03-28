<?php
// Datenbankverbindung herstellen
require_once '../db.php';

// Header für JSON-Antwort setzen
header('Content-Type: application/json');

try {
    // Spiele aus der Datenbank abrufen
    $stmt = $pdo->prepare("
        SELECT * FROM tblMatch 
        WHERE dtStartTime > NOW() 
        AND dtStartTime < DATE_ADD(NOW(), INTERVAL 7 DAY) 
        ORDER BY dtLeague, dtStartTime
    ");
    $stmt->execute();
    $matches = $stmt->fetchAll();
    
    // Wenn keine Spiele gefunden wurden, Beispielspiele zurückgeben
    if (empty($matches)) {
        $matches = [
            [
                'idMatch' => 1,
                'dtExternalId' => 'example-1',
                'dtLeague' => 'Bundesliga',
                'dtHomeTeam' => 'Bayern München',
                'dtAwayTeam' => 'Borussia Dortmund',
                'dtStartTime' => date('Y-m-d H:i:s', strtotime('+1 day')),
                'dtHomeOdds' => 1.75,
                'dtDrawOdds' => 3.50,
                'dtAwayOdds' => 4.25,
                'dtStatus' => 'scheduled'
            ],
            [
                'idMatch' => 2,
                'dtExternalId' => 'example-2',
                'dtLeague' => 'Bundesliga',
                'dtHomeTeam' => 'RB Leipzig',
                'dtAwayTeam' => 'Bayer Leverkusen',
                'dtStartTime' => date('Y-m-d H:i:s', strtotime('+2 days')),
                'dtHomeOdds' => 2.10,
                'dtDrawOdds' => 3.25,
                'dtAwayOdds' => 3.40,
                'dtStatus' => 'scheduled'
            ],
            [
                'idMatch' => 3,
                'dtExternalId' => 'example-3',
                'dtLeague' => 'Premier League',
                'dtHomeTeam' => 'Manchester City',
                'dtAwayTeam' => 'Liverpool',
                'dtStartTime' => date('Y-m-d H:i:s', strtotime('+1 day')),
                'dtHomeOdds' => 1.90,
                'dtDrawOdds' => 3.60,
                'dtAwayOdds' => 3.80,
                'dtStatus' => 'scheduled'
            ],
            [
                'idMatch' => 4,
                'dtExternalId' => 'example-4',
                'dtLeague' => 'Premier League',
                'dtHomeTeam' => 'Arsenal',
                'dtAwayTeam' => 'Chelsea',
                'dtStartTime' => date('Y-m-d H:i:s', strtotime('+3 days')),
                'dtHomeOdds' => 2.30,
                'dtDrawOdds' => 3.20,
                'dtAwayOdds' => 3.00,
                'dtStatus' => 'scheduled'
            ]
        ];
    }
    
    // Erfolgreiche Antwort zurückgeben
    echo json_encode([
        'success' => true,
        'matches' => $matches
    ]);
} catch (PDOException $e) {
    // Fehlerantwort zurückgeben
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Abrufen der Spiele: ' . $e->getMessage()
    ]);
}
?>