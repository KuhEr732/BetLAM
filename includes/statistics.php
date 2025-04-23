<?php
// Datenbankverbindung einbinden
require 'db.php';

// Zugriff auf das globale PDO-Objekt
global $pdo;

// Eingaben aus dem Formular mit Fallback-Werten lesen
$minBalance = $_POST['minBalance'] ?? 0;          
$usernameFilter = $_POST['username'] ?? '';       
$sort = $_POST['sort'] ?? 'dtBalance';            
$order = $_POST['order'] ?? 'DESC';               

// Erlaubte Werte für Sortierung und Reihenfolge
$validSorts = ['dtUsername', 'dtBalance', 'dtCreatedAt'];
$validOrders = ['ASC', 'DESC'];

// Ungültige Eingaben durch Standardwerte ersetzen
if (!in_array($sort, $validSorts)) $sort = 'dtBalance';
if (!in_array($order, $validOrders)) $order = 'DESC';

// SQL-Abfrage vorbereiten mit Platzhaltern für Parameter
$sql = "SELECT idUser, dtUsername, dtEmail, dtBalance, dtCreatedAt, dtLastLogin
        FROM tblUser
        WHERE dtBalance >= :minBalance
          AND dtUsername LIKE :username
        ORDER BY $sort $order
        LIMIT 50";

// SQL-Statement vorbereiten und Parameter binden
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':minBalance' => $minBalance,
    ':username' => '%' . $usernameFilter . '%'  // LIKE-Filter mit Wildcards
]);

// Ergebnis als Array abholen
$users = $stmt->fetchAll();
?>

    <meta charset="UTF-8">
    <title>Top User Statistiken</title>
    <style>
        body { font-family: sans-serif; background: #0D2A4A; color: white; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #2D4356; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #444; }
        th { background-color: #129B7F; }
        input, select, button { padding: 6px; margin: 5px; }
    </style>
    <h1>🎰 Benutzer mit den meisten Coins</h1>

    <form method="post">
        <label>Min. Guthaben: <input type="number" step="0.01" name="minBalance" value="<?= htmlspecialchars($minBalance) ?>"></label>
        <label>Benutzername: <input type="text" name="username" value="<?= htmlspecialchars($usernameFilter) ?>"></label>
        <label>Sortieren nach:
            <select name="sort">
                <option value="dtBalance" <?= $sort == 'dtBalance' ? 'selected' : '' ?>>Guthaben</option>
                <option value="dtUsername" <?= $sort == 'dtUsername' ? 'selected' : '' ?>>Benutzername</option>
                <option value="dtCreatedAt" <?= $sort == 'dtCreatedAt' ? 'selected' : '' ?>>Registriert am</option>
            </select>
        </label>
        <label>Reihenfolge:
            <select name="order">
                <option value="DESC" <?= $order == 'DESC' ? 'selected' : '' ?>>Absteigend</option>
                <option value="ASC" <?= $order == 'ASC' ? 'selected' : '' ?>>Aufsteigend</option>
            </select>
        </label>
        <button type="submit">Filter anwenden</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Benutzer</th>
                <th>Email</th>
                <th>Guthaben (€)</th>
                <th>Registriert</th>
                <th>Letzter Login</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['dtUsername']) ?></td>
                <td><?= htmlspecialchars($user['dtEmail']) ?></td>
                <td><?= number_format($user['dtBalance'], 2, ',', '.') ?></td>
                <td><?= $user['dtCreatedAt'] ?></td>
                <td><?= $user['dtLastLogin'] ?? '-' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>