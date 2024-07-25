<?php
session_start();
include('config.php'); // Ensure this file defines $conn

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$user_name = $_SESSION['user_name'];

// Check if recovery questions are set
$query = "SELECT COUNT(*) FROM user_recovery WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($question_count);
$stmt->fetch();
$stmt->close();

// Debug: Print the value of $question_count
echo "Question Count: $question_count<br>";

if ($question_count == 0) {
    header("Location: recovery_questions.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password != $confirm_password) {
        $error = "New password and confirm password do not match.";
    } else {
        $query = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($password_hash);
        $stmt->fetch();
        $stmt->close();

        if (!password_verify($current_password, $password_hash)) {
            $error = "Current password is incorrect.";
        } else {
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $query = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $new_password_hash, $user_id);
            $stmt->execute();
            $stmt->close();
            
            $success = "Password updated successfully.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Password</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 50%; margin: 0 auto; }
        form { display: flex; flex-direction: column; }
        label, input { margin: 10px 0; }
        input[type="submit"] { width: fit-content; padding: 10px 20px; }
        .error { color: red; }
        .success { color: green; }
        .password-requirements { color: red; }
        .password-valid { color: green; }
    </style>
    <script>
        function validatePassword() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const requirements = document.getElementById('password-requirements');
            const submitButton = document.querySelector('input[type="submit"]');
            const requirementsMet = password.match(/[A-Z]/) && password.match(/[a-z]/) && password.match(/[0-9]/) && password.length >= 5;

            if (requirementsMet) {
                requirements.textContent = 'Password meets all requirements.';
                requirements.className = 'password-valid';
                submitButton.disabled = confirmPassword !== password;
            } else {
                requirements.textContent = 'Password must contain at least one uppercase letter, one lowercase letter, one number, and be at least five characters long.';
                requirements.className = 'password-requirements';
                submitButton.disabled = true;
            }
        }

        function validateConfirmPassword() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const submitButton = document.querySelector('input[type="submit"]');
            submitButton.disabled = password !== confirmPassword;
        }
    </script>
</head>
<body>
    <button onclick="location.href='dashboard.php'">Return to Dashboard</button>
    <div class="container">
        <h1>Update Password</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <form method="post" action="update_password.php">
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required>

            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required oninput="validatePassword()">

            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required oninput="validateConfirmPassword()">

            <p id="password-requirements" class="password-requirements">
                Password must contain at least one uppercase letter, one lowercase letter, one number, and be at least five characters long.
            </p>

            <input type="submit" value="Update Password" disabled>
        </form>
    </div>
</body>
</html>
