<?php
header('Content-Type: application/json');
require '../db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start(); 

global $pdo;

if (!isset($_SESSION['userId'])) {
    echo json_encode(['status' => 'error', 'message' => 'Bitte zuerst einloggen.']);
    exit;
}

$userId = $_SESSION['userId'];

// Berechnung der täglichen Belohnung
$today = date('Y-m-d');
$seed = hexdec(substr(sha1($today), 0, 8)); // täglicher Seed
mt_srand($seed);
$reward = mt_rand(100, 1000); // z.B. zwischen 100 und 1000 Coins

// Prüfen, ob der Bonus schon eingelöst wurde
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tblBonus WHERE fiUser = ? AND dtStatus = 'used' AND DATE(dtClaimDate) = ?");
$stmt->execute([$userId, $today]);
$alreadyClaimed = $stmt->fetchColumn();

if ($alreadyClaimed) {
    echo json_encode(['status' => 'error', 'message' => 'Du hast diesen Bonus bereits eingelöst.']);
    exit;
}

// Bonus in der Tabelle speichern
$stmt = $pdo->prepare("INSERT INTO tblBonus (fiUser, dtAmount, dtStatus, dtClaimDate) VALUES (?, ?, 'used', NOW())");
$stmt->execute([$userId, $reward]);

// Benutzerkontostand aktualisieren
$stmt = $pdo->prepare("CALL updateUserBalance(?, ?)");
$stmt->execute([$userId, $reward]);

echo json_encode(['status' => 'success', 'message' => "Du hast $reward Coins erhalten!"]);
exit;