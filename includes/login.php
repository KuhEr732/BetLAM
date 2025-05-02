<?php
include 'functions.php';

$conn = db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['BUTTON_login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $query = "SELECT idUser, dtEmail, dtPasswordHash FROM tblUser WHERE dtEmail = ?";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user) {
            if (password_verify($password, $user['dtPasswordHash'])) {
                // Store user ID in session
                $_SESSION['userId'] = $user['idUser'];
                
                // Weiterleitung zur Hauptseite
                header("Location: index.php?page=main");
                exit();
            } else {
                echo "<script>alert('Invalid password. Please try again.');</script>";
            }
        } else {
            echo "<script>alert('User not found. Please check your email and try again.');</script>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "<script>alert('Database query failed. Please try again later.');</script>";
    }
    mysqli_close($conn);
}
?>
<link rel="stylesheet" href="css/login.css">
<main>
  <div class="container login-container">
    <div class="login-form">
      <h2>Benutzer Log-In</h2>
      
      <?php if (!empty($error_message)): ?>
        <div class="login-error">
          <?php echo htmlspecialchars($error_message); ?>
        </div>
      <?php endif; ?>
      
      <form method="POST">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required placeholder="Email eingeben">

        <label for="password">Passwort</label>
        <input type="password" id="password" name="password" required placeholder="Passwort eingeben">

        <button type="submit" name="BUTTON_login" class="login-btn">Anmelden</button>
        
        <div class="register-link">
          Kein Account? <a href="index.php?page=register">Registrieren</a>
        </div>
      </form>
    </div>
  </div>
</main>
