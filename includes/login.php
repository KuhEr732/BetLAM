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

<div>
    <form method="POST">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required placeholder="Enter your email">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required placeholder="Enter your password">

        <button type="submit" name="BUTTON_login" class="login-btn">Sign In</button>
    </form>
</div>
