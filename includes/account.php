<?php

// Redirect if not logged in
if (!isset($_SESSION['userId'])) {
    header("Location: index.php?page=login");
    exit();
}

// account.php
$conn = db_connection();

$userId = $_SESSION['userId'];
$successMessage = '';
$errorMessage = '';

// Process form submission for updating profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $currentPassword = trim($_POST['current_password']);
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);
    
    // Validate inputs
    if (empty($username) || empty($email)) {
        $errorMessage = "Username and email are required fields.";
    } else {
        // Check if username is already taken by another user
        $checkQuery = "SELECT idUser FROM tblUser WHERE dtUsername = ? AND idUser != ?";
        $stmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($stmt, 'si', $username, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $errorMessage = "Username already taken. Please choose another one.";
        } else {
            // Check if email is already taken by another user
            $checkQuery = "SELECT idUser FROM tblUser WHERE dtEmail = ? AND idUser != ?";
            $stmt = mysqli_prepare($conn, $checkQuery);
            mysqli_stmt_bind_param($stmt, 'si', $email, $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $errorMessage = "Email already in use. Please use another email address.";
            } else {
                // If changing password, verify current password
                if (!empty($newPassword)) {
                    // Get current password hash
                    $passwordQuery = "SELECT dtPasswordHash FROM tblUser WHERE idUser = ?";
                    $stmt = mysqli_prepare($conn, $passwordQuery);
                    mysqli_stmt_bind_param($stmt, 'i', $userId);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $user = mysqli_fetch_assoc($result);
                    
                    // Verify current password
                    if (!password_verify($currentPassword, $user['dtPasswordHash'])) {
                        $errorMessage = "Current password is incorrect.";
                    } elseif ($newPassword !== $confirmPassword) {
                        $errorMessage = "New passwords do not match.";
                    } else {
                        // Update user with new password
                        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $updateQuery = "UPDATE tblUser SET dtUsername = ?, dtEmail = ?, dtPasswordHash = ? WHERE idUser = ?";
                        $stmt = mysqli_prepare($conn, $updateQuery);
                        mysqli_stmt_bind_param($stmt, 'sssi', $username, $email, $passwordHash, $userId);
                    }
                } else {
                    // Update without changing password
                    $updateQuery = "UPDATE tblUser SET dtUsername = ?, dtEmail = ? WHERE idUser = ?";
                    $stmt = mysqli_prepare($conn, $updateQuery);
                    mysqli_stmt_bind_param($stmt, 'ssi', $username, $email, $userId);
                }
                
                if (empty($errorMessage)) {
                    if (mysqli_stmt_execute($stmt)) {
                        $successMessage = "Profile updated successfully!";
                        
                        // Log the action
                        $action = "Updated profile information";
                        $logQuery = "INSERT INTO tblAuditLog (fiUser, dtAction) VALUES (?, ?)";
                        $logStmt = mysqli_prepare($conn, $logQuery);
                        mysqli_stmt_bind_param($logStmt, 'is', $userId, $action);
                        mysqli_stmt_execute($logStmt);
                    } else {
                        $errorMessage = "Failed to update profile. Please try again.";
                    }
                }
            }
        }
    }
}

// Fetch current user data
$query = "SELECT dtUsername, dtEmail, dtBalance, dtCreatedAt, dtLastLogin FROM tblUser WHERE idUser = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$userData = mysqli_fetch_assoc($result);

// Fetch transaction history
$transactionQuery = "SELECT dtAmount, dtType, dtStatus, dtCreatedAt FROM tblTransaction WHERE fiUser = ? ORDER BY dtCreatedAt DESC LIMIT 10";
$transStmt = mysqli_prepare($conn, $transactionQuery);
mysqli_stmt_bind_param($transStmt, 'i', $userId);
mysqli_stmt_execute($transStmt);
$transResult = mysqli_stmt_get_result($transStmt);

// Fetch betting history
$betQuery = "SELECT b.dtAmount, b.dtOutcome, b.dtWinnings, b.dtPlacedAt, g.dtName, g.dtType 
             FROM tblBet b 
             JOIN tblGame g ON b.fiGame = g.idGame 
             WHERE b.fiUser = ? 
             ORDER BY b.dtPlacedAt DESC 
             LIMIT 10";
$betStmt = mysqli_prepare($conn, $betQuery);
mysqli_stmt_bind_param($betStmt, 'i', $userId);
mysqli_stmt_execute($betStmt);
$betResult = mysqli_stmt_get_result($betStmt);

// Fetch active bonuses
$bonusQuery = "SELECT dtAmount, dtStatus, dtExpiresAt FROM tblBonus WHERE fiUser = ? AND dtStatus = 'active' ORDER BY dtExpiresAt ASC";
$bonusStmt = mysqli_prepare($conn, $bonusQuery);
mysqli_stmt_bind_param($bonusStmt, 'i', $userId);
mysqli_stmt_execute($bonusStmt);
$bonusResult = mysqli_stmt_get_result($bonusStmt);

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mein Konto - BetLAM</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .account-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .account-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .account-title {
            font-size: 24px;
            font-weight: bold;
        }
        
        .account-balance {
            font-size: 18px;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border-radius: 4px;
        }
        
        .account-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab-button {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            position: relative;
        }
        
        .tab-button.active {
            color: #007bff;
        }
        
        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 3px;
            background-color: #007bff;
        }
        
        .tab-content {
            display: none;
            padding: 20px 0;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .table th {
            background-color: #f4f4f4;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-completed, .status-win {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-failed, .status-loss {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .account-info-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .account-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .account-info-label {
            font-weight: 500;
            color: #6c757d;
        }
        
        .account-info-value {
            font-weight: 400;
        }
    </style>
</head>
<body>
    <!-- Header would be included here -->
    
    <div class="account-container">
        <div class="account-header">
            <h1 class="account-title">Mein Konto</h1>
            <div class="account-balance">Guthaben: <?php echo number_format($userData['dtBalance'], 0, ',', '.'); ?> Coins</div>
        </div>
        
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success"><?php echo $successMessage; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
        
        <div class="account-tabs">
            <button class="tab-button active" onclick="openTab(event, 'profile')">Profil</button>
            <button class="tab-button" onclick="openTab(event, 'transactions')">Transaktionen</button>
            <button class="tab-button" onclick="openTab(event, 'bets')">Wettverlauf</button>
            <button class="tab-button" onclick="openTab(event, 'bonuses')">Boni</button>
        </div>
        
        <div id="profile" class="tab-content active">
            <div class="account-info-card">
                <h3>Konto Informationen</h3>
                <div class="account-info-row">
                    <span class="account-info-label">Benutzername:</span>
                    <span class="account-info-value"><?php echo htmlspecialchars($userData['dtUsername']); ?></span>
                </div>
                <div class="account-info-row">
                    <span class="account-info-label">E-Mail:</span>
                    <span class="account-info-value"><?php echo htmlspecialchars($userData['dtEmail']); ?></span>
                </div>
                <div class="account-info-row">
                    <span class="account-info-label">Registriert am:</span>
                    <span class="account-info-value"><?php echo date('d.m.Y H:i', strtotime($userData['dtCreatedAt'])); ?></span>
                </div>
                <div class="account-info-row">
                    <span class="account-info-label">Letzter Login:</span>
                    <span class="account-info-value">
                        <?php echo $userData['dtLastLogin'] ? date('d.m.Y H:i', strtotime($userData['dtLastLogin'])) : 'Nie'; ?>
                    </span>
                </div>
            </div>
            
            <h3>Profil bearbeiten</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Benutzername</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($userData['dtUsername']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">E-Mail</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userData['dtEmail']); ?>" required>
                </div>
                
                <h4>Passwort ändern (optional)</h4>
                
                <div class="form-group">
                    <label for="current_password">Aktuelles Passwort</label>
                    <input type="password" class="form-control" id="current_password" name="current_password">
                </div>
                
                <div class="form-group">
                    <label for="new_password">Neues Passwort</label>
                    <input type="password" class="form-control" id="new_password" name="new_password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Passwort bestätigen</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                </div>
                
                <button type="submit" name="update_profile" class="btn btn-primary">Profil aktualisieren</button>
            </form>
        </div>
        
        <div id="transactions" class="tab-content">
            <h3>Transaktionshistorie</h3>
            <?php if (mysqli_num_rows($transResult) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Typ</th>
                            <th>Betrag</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($transaction = mysqli_fetch_assoc($transResult)): ?>
                            <tr>
                                <td><?php echo date('d.m.Y H:i', strtotime($transaction['dtCreatedAt'])); ?></td>
                                <td>
                                    <?php if ($transaction['dtType'] === 'deposit'): ?>
                                        Einzahlung
                                    <?php else: ?>
                                        Auszahlung
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo number_format($transaction['dtAmount'], 2, ',', '.'); ?> €
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $transaction['dtStatus']; ?>">
                                        <?php 
                                        switch ($transaction['dtStatus']) {
                                            case 'completed':
                                                echo 'Abgeschlossen';
                                                break;
                                            case 'pending':
                                                echo 'Ausstehend';
                                                break;
                                            case 'failed':
                                                echo 'Fehlgeschlagen';
                                                break;
                                        }
                                        ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Keine Transaktionen vorhanden.</p>
            <?php endif; ?>
        </div>
        
        <div id="bets" class="tab-content">
            <h3>Wettverlauf</h3>
            <?php if (mysqli_num_rows($betResult) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Spiel</th>
                            <th>Typ</th>
                            <th>Einsatz</th>
                            <th>Ergebnis</th>
                            <th>Gewinn</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($bet = mysqli_fetch_assoc($betResult)): ?>
                            <tr>
                                <td><?php echo date('d.m.Y H:i', strtotime($bet['dtPlacedAt'])); ?></td>
                                <td><?php echo htmlspecialchars($bet['dtName']); ?></td>
                                <td>
                                    <?php 
                                    switch ($bet['dtType']) {
                                        case 'slot':
                                            echo 'Spielautomat';
                                            break;
                                        case 'poker':
                                            echo 'Poker';
                                            break;
                                        case 'blackjack':
                                            echo 'Blackjack';
                                            break;
                                        case 'roulette':
                                            echo 'Roulette';
                                            break;
                                        case 'baccarat':
                                            echo 'Baccarat';
                                            break;
                                        case 'sportsbet':
                                            echo 'Sportwette';
                                            break;
                                    }
                                    ?>
                                </td>
                                <td><?php echo number_format($bet['dtAmount'], 2, ',', '.'); ?> €</td>
                                <td>
                                    <span class="status-badge status-<?php echo $bet['dtOutcome']; ?>">
                                        <?php 
                                        switch ($bet['dtOutcome']) {
                                            case 'win':
                                                echo 'Gewonnen';
                                                break;
                                            case 'loss':
                                                echo 'Verloren';
                                                break;
                                            case 'pending':
                                                echo 'Ausstehend';
                                                break;
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($bet['dtOutcome'] === 'win'): ?>
                                        <strong class="text-success">
                                            +<?php echo number_format($bet['dtWinnings'], 2, ',', '.'); ?> €
                                        </strong>
                                    <?php else: ?>
                                        0,00 €
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Keine Wetten vorhanden.</p>
            <?php endif; ?>
        </div>
        
        <div id="bonuses" class="tab-content">
            <h3>Aktive Boni</h3>
            <?php if (mysqli_num_rows($bonusResult) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Betrag</th>
                            <th>Status</th>
                            <th>Gültig bis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($bonus = mysqli_fetch_assoc($bonusResult)): ?>
                            <tr>
                                <td><?php echo number_format($bonus['dtAmount'], 2, ',', '.'); ?> €</td>
                                <td>
                                    <span class="status-badge status-completed">Aktiv</span>
                                </td>
                                <td>
                                    <?php echo date('d.m.Y H:i', strtotime($bonus['dtExpiresAt'])); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Keine aktiven Boni vorhanden.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function openTab(evt, tabName) {
            // Hide all tab content
            var tabContent = document.getElementsByClassName("tab-content");
            for (var i = 0; i < tabContent.length; i++) {
                tabContent[i].classList.remove("active");
            }
            
            // Remove active class from all tab buttons
            var tabButtons = document.getElementsByClassName("tab-button");
            for (var i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove("active");
            }
            
            // Show the selected tab and add active class to the button
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
    </script>
</body>
</html>