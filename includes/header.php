<?php session_start(); ?>

<!-- Header -->
<header class="site-header">
    <div class="container header-container">
        <button class="mobile-menu-toggle">☰</button>

        <a href="index.php?page=main" class="logo">
            <div class="logo-img"></div>
            <span class="logo-text">BetLAM</span>
        </a>

        <nav class="main-nav">
            <ul>
                <li><a href="index.php?page=sports">Sports</a></li>
                <li><a href="index.php?page=casino">Casino</a></li>
                <li><a href="index.php?page=promotions">Promotionen</a></li>
                <li><a href="index.php?page=statistics">Statistiken</a></li>
            </ul>
        </nav>

        <div class="user-actions">
            <?php if (isset($_SESSION['userId'])): ?>
                <?php
                    // Fetch user data from database
                    include 'functions.php';
                    $conn = db_connection();
                    $userId = $_SESSION['userId'];
                    
                    $query = "SELECT dtUsername, dtBalance FROM tblUser WHERE idUser = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, 'i', $userId);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        $userData = mysqli_fetch_assoc($result);
                        mysqli_stmt_close($stmt);
                        mysqli_close($conn);
                        
                        $username = $userData['dtUsername'] ?? 'Benutzer';
                        $balance = $userData['dtBalance'] ?? 0;
                    } else {
                        $username = 'Benutzer';
                        $balance = 0;
                    }
                ?>
                <!-- Eingeloggt: Zeige Benutzerinfo und Logout-Button -->
                <div class="user-info">
                    <a href="index.php?page=account" class="username"><?php echo htmlspecialchars($username); ?></a>
                    <div class="balance"><?php echo number_format($balance, 0, ',', '.'); ?> Coins</div>
                </div>
                <a href="index.php?page=logout" class="btn btn-danger">Logout</a>
            <?php else: ?>
                <!-- Nicht eingeloggt: Zeige Login- und Registrieren-Buttons -->
                <a href="index.php?page=login" class="btn btn-outline">Anmelden</a>
                <a href="index.php?page=register" class="btn btn-primary">Registrieren</a>
            <?php endif; ?>
        </div>
    </div>
</header>