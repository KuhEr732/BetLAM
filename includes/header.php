<?php session_start(); ?>

<!-- Header -->
<header class="site-header">
    <div class="container header-container">
        <button class="mobile-menu-toggle">☰</button>

        <a href="index.php?page=main" class="logo">
            <div class="logo-img"></div>
            <span class="logo-text">SportsBet Pro</span>
        </a>

        <nav class="main-nav">
            <ul>
                <li><a href="index.php?page=sports">Sports</a></li>
                <li><a href="index.php?page=live">Live Wetten</a></li>
                <li><a href="index.php?page=casino">Casino</a></li>
                <li><a href="index.php?page=promotions">Promotionen</a></li>
                <li><a href="index.php?page=statistics">Statistiken</a></li>
            </ul>
        </nav>

        <div class="user-actions">
            <?php if (isset($_SESSION['userId'])): ?>
                <!-- Eingeloggt: Zeige Logout-Button -->
                <a href="index.php?page=logout" class="btn btn-danger">Logout</a>
            <?php else: ?>
                <!-- Nicht eingeloggt: Zeige Login- und Registrieren-Buttons -->
                <a href="index.php?page=login" class="btn btn-outline">Anmelden</a>
                <a href="index.php?page=register" class="btn btn-primary">Registrieren</a>
            <?php endif; ?>
        </div>
    </div>
</header>
