<?php
require_once "db.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $pdo;

$userId = $_SESSION['userId'] ?? null;

// Redirect if not logged in
if (!isset($_SESSION['userId'])) {
    header("Location: index.php?page=login");
    exit();
}

// Process balance deposit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_balance'])) {
    $amount = floatval($_POST['amount']);
    
    // Validate amount
    if ($amount <= 0) {
        $errorMessage = "Ungültiger Betrag. Bitte geben Sie einen positiven Wert ein.";
    } else {
        global $pdo;
        
        // Begin transaction
        $pdo->beginTransaction();
        
        try {
            // Add transaction record
            $transStmt = $pdo->prepare("INSERT INTO tblTransaction (fiUser, dtAmount, dtType, dtStatus) VALUES (?, ?, 'deposit', 'completed')");
            $transStmt->execute([$userId, $amount]);
            
            // Update user balance
            $balanceStmt = $pdo->prepare("UPDATE tblUser SET dtBalance = dtBalance + ? WHERE idUser = ?");
            $balanceStmt->execute([$amount, $userId]);
            
            // Commit transaction
            $pdo->commit();
            
            // Log the action
            $action = "Added {$amount} coins to account balance";
            $logStmt = $pdo->prepare("INSERT INTO tblAuditLog (fiUser, dtAction) VALUES (?, ?)");
            $logStmt->execute([$userId, $action]);
            
            $successMessage = "Erfolgreich {$amount} coins zum Guthaben hinzugefügt!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMessage = "Fehler beim Hinzufügen des Guthabens. Bitte versuchen Sie es später erneut.";
        }
    }
}

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
        global $pdo;
        
        // Check if username is already taken by another user
        $stmt = $pdo->prepare("SELECT idUser FROM tblUser WHERE dtUsername = ? AND idUser != ?");
        $stmt->execute([$username, $userId]);
        $result = $stmt->fetch();
        
        if ($result) {
            $errorMessage = "Username already taken. Please choose another one.";
        } else {
            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT idUser FROM tblUser WHERE dtEmail = ? AND idUser != ?");
            $stmt->execute([$email, $userId]);
            $result = $stmt->fetch();
            
            if ($result) {
                $errorMessage = "Email already in use. Please use another email address.";
            } else {
                // If changing password, verify current password
                if (!empty($newPassword)) {
                    // Get current password hash
                    $stmt = $pdo->prepare("SELECT dtPasswordHash FROM tblUser WHERE idUser = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                    
                    // Verify current password
                    if (!password_verify($currentPassword, $user['dtPasswordHash'])) {
                        $errorMessage = "Current password is incorrect.";
                    } elseif ($newPassword !== $confirmPassword) {
                        $errorMessage = "New passwords do not match.";
                    } else {
                        // Update user with new password
                        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE tblUser SET dtUsername = ?, dtEmail = ?, dtPasswordHash = ? WHERE idUser = ?");
                        $stmt->execute([$username, $email, $passwordHash, $userId]);
                    }
                } else {
                    // Update without changing password
                    $stmt = $pdo->prepare("UPDATE tblUser SET dtUsername = ?, dtEmail = ? WHERE idUser = ?");
                    $stmt->execute([$username, $email, $userId]);
                }
                
                if (empty($errorMessage)) {
                    $rowCount = $stmt->rowCount();
                    if ($rowCount > 0) {
                        $successMessage = "Profile updated successfully!";
                        
                        // Log the action
                        $action = "Updated profile information";
                        $logStmt = $pdo->prepare("INSERT INTO tblAuditLog (fiUser, dtAction) VALUES (?, ?)");
                        $logStmt->execute([$userId, $action]);
                    } else {
                        $errorMessage = "Failed to update profile. Please try again.";
                    }
                }
            }
        }
    }
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT dtUsername, dtEmail, dtBalance, dtCreatedAt, dtLastLogin FROM tblUser WHERE idUser = ?");
$stmt->execute([$userId]);
$userData = $stmt->fetch();

// Fetch transaction history
$transStmt = $pdo->prepare("SELECT dtAmount, dtType, dtStatus, dtCreatedAt FROM tblTransaction WHERE fiUser = ? ORDER BY dtCreatedAt DESC LIMIT 10");
$transStmt->execute([$userId]);
$transactions = $transStmt->fetchAll();

// Fetch betting history
$betStmt = $pdo->prepare("SELECT b.dtAmount, b.dtOutcome, b.dtWinnings, b.dtPlacedAt, g.dtName, g.dtType 
             FROM tblBet b 
             JOIN tblGame g ON b.fiGame = g.idGame 
             WHERE b.fiUser = ? 
             ORDER BY b.dtPlacedAt DESC 
             LIMIT 10");
$betStmt->execute([$userId]);
$bets = $betStmt->fetchAll();

// Fetch active bonuses
$bonusStmt = $pdo->prepare("SELECT dtAmount, dtStatus, dtExpiresAt, dtClaimDate 
                           FROM tblBonus 
                           WHERE fiUser = ? AND dtStatus = 'used' 
                           ORDER BY COALESCE(dtExpiresAt, '9999-12-31') ASC");
$bonusStmt->execute([$userId]);
$bonuses = $bonusStmt->fetchAll();
?>
    <link rel="stylesheet" href="css/account.css">
    
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
            <button class="tab-button" onclick="openTab(event, 'balance')">Guthaben aufladen</button>
            <button class="tab-button" onclick="openTab(event, 'transactions')">Transaktionen</button>
            <button class="tab-button" onclick="openTab(event, 'bonuses')">Bonusse</button>
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

        <div id="balance" class="tab-content">
            <h3 class="balance-heading">Guthaben aufladen</h3>
            <p>Wählen Sie einen Betrag aus, um Ihr Guthaben aufzuladen:</p>
            
            <div class="balance-buttons">
                <form method="POST" action="">
                    <input type="hidden" name="amount" value="100">
                    <button type="submit" name="add_balance" class="balance-button">100</button>
                </form>
                
                <form method="POST" action="">
                    <input type="hidden" name="amount" value="250">
                    <button type="submit" name="add_balance" class="balance-button">250</button>
                </form>
                
                <form method="POST" action="">
                    <input type="hidden" name="amount" value="1000">
                    <button type="submit" name="add_balance" class="balance-button">1000</button>
                </form>
            </div>
            
            <div class="account-info-card">
                <h4>Benutzerdefinierter Betrag</h4>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="custom_amount">Geben Sie einen Betrag ein (Coins):</label>
                        <input type="number" min="10" step="0.01" class="form-control" id="custom_amount" name="amount" placeholder="Betrag eingeben">
                    </div>
                    <button type="submit" name="add_balance" class="btn btn-primary">Guthaben aufladen</button>
                </form>
            </div>
        </div>
        
        <div id="transactions" class="tab-content">
            <h3>Transaktionshistorie</h3>
            <?php if (!empty($transactions)): ?>
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
                        <?php foreach ($transactions as $transaction): ?>
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
                                    <?php echo number_format($transaction['dtAmount'], 0, ',', '.'); ?> Coins
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
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Keine Transaktionen vorhanden.</p>
            <?php endif; ?>
        </div>

        <div id="bonuses" class="tab-content">
    <h3>Bonus Historie</h3>
    <?php if (!empty($bonuses)): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Betrag</th>
                    <th>Status</th>
                    <th>Gültig bis</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bonuses as $bonus): ?>
                    <tr>
                        <td><?php echo number_format($bonus['dtAmount'], 0, ',', '.'); ?> Coins</td>
                        <td>
                            <?php 
                            if ($bonus['dtStatus'] === 'used') {
                                echo 'used';
                            } else {
                                echo 'Inaktiv';
                            }
                            ?>
                        </td>
                        <td><?php echo $bonus['dtExpiresAt'] ? date('d.m.Y H:i', strtotime($bonus['dtExpiresAt'])) : 'Unbegrenzt'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Keine Boni vorhanden.</p>
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
<?php
$pdo = null;
?>