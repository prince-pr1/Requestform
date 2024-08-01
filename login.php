<?php
session_start();
include('config.php');

// CORS headers
header("Access-Control-Allow-Origin: *"); // Replace with your React app's origin
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true"); // Allow cookies to be sent

$error = ""; // Initialize an error message variable

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Determine if the request is JSON or form data
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

    if ($contentType === "application/json") {
        $content = trim(file_get_contents("php://input"));
        $decoded = json_decode($content, true);
        $username = $decoded['username'];
        $password = $decoded['password'];
    } else {
        $username = $_POST['username'];
        $password = $_POST['password'];
    }

    // Use a prepared statement to prevent SQL injection
    $query = "SELECT user_id, password, name, position FROM users WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($user_id, $hashed_password, $name, $position);

    if ($stmt->fetch()) {
        if (password_verify($password, $hashed_password)) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['user_name'] = $name;
            $_SESSION['position'] = $position;

            // Respond with JSON for API request
            if ($contentType === "application/json") {
                echo json_encode(["status" => "success", "message" => "Login successful"]);
            } else {
                // Redirect to dashboard for form submission
                header("Location: dashboard.php");
                exit();
            }
        } else {
            // Incorrect password
            $error = "Invalid login credentials.";

            // Respond with JSON for API request
            if ($contentType === "application/json") {
                echo json_encode(["status" => "error", "message" => $error]);
            }
        }
    } else {
        // No user found
        $error = "Invalid login credentials.";

        // Respond with JSON for API request
        if ($contentType === "application/json") {
            echo json_encode(["status" => "error", "message" => $error]);
        }
    }

    $stmt->close();
    $conn->close();
}
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
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="login.php">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required><br>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required><br>
                <button type="submit">Login</button>
            </form>
            <a href="recoverpassword_questions.php" class="btn btn-primary">Forgot password</a>
        </div>
    </div>
</body>
</html>
