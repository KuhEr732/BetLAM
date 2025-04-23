<?php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

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

// Prüfen, wann die Daten zuletzt aktualisiert wurden
function shouldUpdateData($pdo) {
    try {
        // Letzte Aktualisierung aus der Datenbank prüfen
        $stmt = $pdo->query("SELECT MAX(dtUpdatedAt) as lastUpdate FROM tblMatch");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result['lastUpdate']) {
            return true; // Keine Daten vorhanden, Update erforderlich
        }
        
        $lastUpdate = new DateTime($result['lastUpdate']);
        $now = new DateTime();
        $diff = $now->diff($lastUpdate);
        
        // Wenn die letzte Aktualisierung länger als 4 Stunden her ist, aktualisieren
        $hoursDiff = $diff->h + ($diff->days * 24);
        return $hoursDiff >= 4;
        
    } catch (PDOException $e) {
        // Bei Fehlern lieber aktualisieren
        return true;
    }
}

// Spiele aus API laden und in Datenbank speichern
function updateMatchesFromAPI($pdo, $sports, $apiKey) {
    $updated = 0;
    $errors = [];

    foreach ($sports as $sport) {
        $url = "https://api.the-odds-api.com/v4/sports/{$sport['key']}/odds/?apiKey=$apiKey&regions=eu&markets=h2h";

        try {
            $response = file_get_contents($url);
            if ($response === false) {
                $errors[] = "Fehler beim Abrufen der Daten für {$sport['name']}";
                continue;
            }
            
            $matches = json_decode($response, true);
            if (!is_array($matches)) {
                $errors[] = "Ungültige Antwort für {$sport['name']}";
                continue;
            }

            foreach ($matches as $match) {
                // Prüfen, ob das Spiel innerhalb der nächsten 7 Tage stattfindet
                $startTime = new DateTime($match['commence_time']);
                $now = new DateTime();
                $oneWeekLater = (clone $now)->modify('+7 days');

                if ($startTime <= $now || $startTime > $oneWeekLater) {
                    continue; // Spiel nicht innerhalb des gültigen Zeitraums
                }

                // Quoten extrahieren
                $homeOdds = null;
                $drawOdds = null;
                $awayOdds = null;

                if (!empty($match['bookmakers'])) {
                    foreach ($match['bookmakers'] as $bookmaker) {
                        foreach ($bookmaker['markets'] as $market) {
                            if ($market['key'] === 'h2h') {
                                foreach ($market['outcomes'] as $outcome) {
                                    if ($outcome['name'] === $match['home_team']) {
                                        $homeOdds = $outcome['price'];
                                    } elseif ($outcome['name'] === 'Draw') {
                                        $drawOdds = $outcome['price'];
                                    } elseif ($outcome['name'] === $match['away_team']) {
                                        $awayOdds = $outcome['price'];
                                    }
                                }
                                break; // Sobald wir h2h-Quoten gefunden haben, brechen wir ab
                            }
                        }
                        
                        if ($homeOdds !== null) {
                            break; // Wir haben bereits die Quoten von einem Bookmaker
                        }
                    }
                }

                // Spiel in Datenbank speichern oder aktualisieren
                try {
                    $stmt = $pdo->prepare("CALL saveMatch(?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $match['id'],
                        $sport['name'],
                        $match['home_team'],
                        $match['away_team'],
                        $startTime->format('Y-m-d H:i:s'),
                        $homeOdds,
                        $drawOdds,
                        $awayOdds
                    ]);
                    $updated++;
                } catch (PDOException $e) {
                    $errors[] = "Datenbankfehler: " . $e->getMessage();
                }
            }
        } catch (Exception $e) {
            $errors[] = "Fehler: " . $e->getMessage();
        }
    }

    return [
        'updated' => $updated,
        'errors' => $errors
    ];
}

// Spiele aus Datenbank laden
function getMatchesFromDB($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT idMatch, dtLeague, dtHomeTeam, dtAwayTeam, dtStartTime, dtHomeOdds, dtDrawOdds, dtAwayOdds
            FROM tblMatch
            WHERE dtStartTime > NOW() AND dtStartTime < DATE_ADD(NOW(), INTERVAL 7 DAY)
            ORDER BY dtLeague, dtStartTime
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Fehler beim Abrufen der Spiele: " . $e->getMessage());
    }
}

// Hauptlogik
try {
    // Prüfen, ob die Daten aktualisiert werden sollten
    $needsUpdate = shouldUpdateData($pdo);
    $updateInfo = null;

    if ($needsUpdate) {
        $updateInfo = updateMatchesFromAPI($pdo, $sports, $apiKey);
    }

    // Spiele aus der Datenbank laden
    $matches = getMatchesFromDB($pdo);

    // Erfolgsmeldung zurückgeben
    echo json_encode([
        'success' => true,
        'matches' => $matches,
        'updated' => $needsUpdate ? $updateInfo['updated'] : 0,
        'lastUpdate' => date('Y-m-d H:i:s'),
        'updateErrors' => $needsUpdate && !empty($updateInfo['errors']) ? $updateInfo['errors'] : []
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}