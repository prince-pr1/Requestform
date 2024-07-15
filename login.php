<?php
session_start();
include('config.php'); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Use a prepared statement to prevent SQL injection
    $query = "SELECT user_id, password, name, position FROM users WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($user_id, $hashed_password, $name, $position);

    if ($stmt->fetch() && password_verify($password, $hashed_password)) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username; // Store the username in the session
        $_SESSION['user_name'] = $name; // Store the user's name in the session
        $_SESSION['position'] = $position; // Store the user's position in the session
        header("Location: dashboard.php"); // Correct the redirection to dashboard.php
        exit(); // Ensure to exit after redirecting
    } else {
        $error = "Invalid login credentials";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles/login.css">
    <title>Login</title>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logos">
                <img src="Requestform/userAuth/images/Geps_LOGO.jpg" alt="Logo 1"> 
                <img src="images/ITEC_LOGO.png" alt="Logo 2">
                <img src="images/ITTCO_LOGO.png" alt="Logo 3">
            </div>
            <?php if (isset($error)): ?>
                <div><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="login.php">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required><br>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required><br>
                <button type="submit">Login</button>
            </form>
            <a href="password_recover.php" class="btn btn-primary">Forgot password</a>
        </div>
    </div>
</body>
</html>
