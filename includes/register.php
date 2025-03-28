<?php
session_start();
require "functions.php";

// Funktion zum Generieren eines zufälligen alphanumerischen Captchas
function generateCaptcha($length = 7) {
    return substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789"), 0, $length);
}

// Generiere Captcha nur, wenn es nicht existiert (wird nach Absenden nicht überschrieben)
if (!isset($_SESSION['captcha'])) {
    $_SESSION['captcha'] = generateCaptcha();
}

$conn = db_connection(); // Verbindung zur Datenbank herstellen

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['BUTTON_register'])) {
    $username = trim($_POST['DATA_username']);
    $email = trim($_POST['DATA_eMail']);
    $password = trim($_POST['DATA_password']);
    $passwordVerified = trim($_POST['DATA_passwordVerification']);
    $confirmCaptcha = trim($_POST['DATA_Captcha']);

    // Captcha-Überprüfung
    if ($confirmCaptcha !== $_SESSION['captcha']) {
        echo "<script>alert('Falsches Captcha!');</script>";
    } elseif ($password !== $passwordVerified) {
        echo "<script>alert('Passwörter stimmen nicht überein!');</script>";
    } else {
        // Prüfen, ob die E-Mail bereits existiert
        $checkQuery = "SELECT idUser FROM tblUser WHERE dtEmail = ?";
        $stmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            echo "<script>alert('Diese E-Mail wird bereits verwendet!');</script>";
        } else {
            // Passwort hashen
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Benutzer in die Datenbank einfügen
            $insertQuery = "INSERT INTO tblUser (dtUsername, dtPasswordHash, dtEmail, dtBalance) VALUES (?, ?, ?, 0.00)";
            $stmt = mysqli_prepare($conn, $insertQuery);
            mysqli_stmt_bind_param($stmt, "sss", $username, $hashedPassword, $email);
            $result = mysqli_stmt_execute($stmt);

            if ($result) {
                $_SESSION['userId'] = mysqli_insert_id($conn);
                $_SESSION['role'] = 'user';

                setcookie('userId', $_SESSION['userId'], time() + 3600, "/");
                setcookie('role', 'user', time() + 3600, "/");

                // Nach erfolgreicher Registrierung ein neues Captcha setzen
                $_SESSION['captcha'] = generateCaptcha();

                header('Location: index.php?page=main');
                exit();
            } else {
                echo "<script>alert('Fehler bei der Registrierung!');</script>";
            }
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
}
?>

<!-- Registrierungsformular -->
<form method="post" class="registration-form">
    <label for="username">Benutzername: </label>
    <input id="username" name="DATA_username" type="text" required><br>

    <label for="eMail">E-Mail-Adresse: </label>
    <input id="eMail" name="DATA_eMail" type="email" required><br>

    <label for="password">Passwort: </label>
    <input id="password" name="DATA_password" type="password" required><br>

    <label for="passwordVerification">Passwort bestätigen: </label>
    <input id="passwordVerification" name="DATA_passwordVerification" type="password" required><br>

    <label>Captcha: </label>
    <input type="text" class="captcha" name="captcha" value="<?php echo $_SESSION['captcha']; ?>" readonly><br>

    <label>Captcha bestätigen: </label>
    <input type="text" name="DATA_Captcha" required><br>

    <input type="submit" name="BUTTON_register" value="Registrieren">
</form>