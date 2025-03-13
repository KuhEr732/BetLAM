<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Matteo Cardellini">
    <meta name="author" content="Jayden Wohles">
    <meta name="author" content="Erik Kühnemund">
    <title>BetLAM - Gratis Sportwetten</title>
</head>
<body>
    <?php
    include_once "includes/header.php";



    // Definiere eine Zuordnung von "Seiten"-Identifikatoren zu Dateipfaden
    $pages = [
        "home" => "includes/home.php",
        "login" => "includes/login.php",
        "main" => "includes/main.php",
        "register" => "includes/register.php",
        "header" => "includes/header.php",
        "footer" => "includes/footer.php"
    ];

    // Funktion zum Abrufen und Einbinden der angeforderten Seite
    function getPage($pages) {
        $page = $_GET['page'] ?? "login"; // Standardseite
        if (array_key_exists($page, $pages)) {
            include $pages[$page];
        } else {
            include $pages["login"]; // Fallback zur Anmeldeseite, wenn nicht gefunden
        }
    }

    // Abrufen und Anzeigen der angeforderten Seite
    getPage($pages);

    include_once "includes/footer.php";
    ?>
    
</body>
</html>