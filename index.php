<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Matteo Cardellini">
    <meta name="author" content="Jayden Wohles">
    <meta name="author" content="Erik Kühnemund">
    <title>BetLAM - Gratis Sportwetten</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/footer.css">
</head>
<body>
    <?php
    include_once "includes/header.php";

    include_once "db.php";



    // Definiere eine Zuordnung von "Seiten"-Identifikatoren zu Dateipfaden
    $pages = [
        "home" => "includes/home.php",
        "login" => "includes/login.php",
        "main" => "includes/main.php",
        "register" => "includes/register.php",
        "sports" => "includes/sports.php",
        "live" => "includes/live.php",
        "casino" => "includes/casino.php",
        "promotions" => "includes/promotions.php",
        "statistics" => "includes/statistics.php",
        "header" => "includes/header.php",
        "footer" => "includes/footer.php",
        "logout" => "includes/logout.php",
        "account" => "includes/account.php"
    ];

    // Funktion zum Abrufen und Einbinden der angeforderten Seite
    function getPage($pages) {
        $page = $_GET['page'] ?? "main"; // Standardseite
        if (array_key_exists($page, $pages)) {
            include $pages[$page];
        } else {
            include $pages["main"]; // Fallback zur Anmeldeseite, wenn nicht gefunden
        }
    }

    // Abrufen und Anzeigen der angeforderten Seite
    getPage($pages);

    include_once "includes/footer.php";
    ?>
    
</body>
</html>