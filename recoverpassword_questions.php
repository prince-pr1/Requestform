<?php
session_start();
include('config.php');

$step = 1;
$error = '';
$new_password = '';
$questions = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['username'])) {
        $username = $_POST['username'];

        $query = "SELECT user_id FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            $_SESSION['reset_user_id'] = $user_id;
            
            // Fetch the security questions
            $query = "SELECT question1, question2, question3 FROM user_recovery WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($question1, $question2, $question3);
            $stmt->fetch();
            $stmt->close();
            
            $questions = [$question1, $question2, $question3];
            $_SESSION['questions'] = $questions;
            $step = 2;
        } else {
            $error = "Invalid username or email.";
        }
    } elseif (isset($_POST['answer1'], $_POST['answer2'], $_POST['answer3'])) {
        $user_id = $_SESSION['reset_user_id'];
        $questions = $_SESSION['questions'];
        $answer1 = $_POST['answer1'];
        $answer2 = $_POST['answer2'];
        $answer3 = $_POST['answer3'];

        $query = "SELECT answer1, answer2, answer3 FROM user_recovery WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($db_answer1, $db_answer2, $db_answer3);
        $stmt->fetch();
        $stmt->close();

        $correct_answers = 0;
        if (strtolower($answer1) == strtolower($db_answer1)) $correct_answers++;
        if (strtolower($answer2) == strtolower($db_answer2)) $correct_answers++;
        if (strtolower($answer3) == strtolower($db_answer3)) $correct_answers++;

        if ($correct_answers >= 2) {
            $new_password = bin2hex(random_bytes(4)); // generate a temporary password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $query = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $hashed_password, $user_id);
            $stmt->execute();
            $stmt->close();

            $step = 3;
        } else {
            $error = "Incorrect answers.";
            $step = 2;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 50%; margin: 0 auto; }
        form { display: flex; flex-direction: column; }
        label, input { margin: 10px 0; }
        input[type="submit"] { width: fit-content; padding: 10px 20px; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reset Password</h1>
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($step == 1): ?>
            <form method="post" action="recoverpassword_questions.php">
                <label for="username">Username or Email:</label>
                <input type="text" id="username" name="username" required>
                <input type="submit" value="Submit">
            </form>
        <?php elseif ($step == 2): ?>
            <form method="post" action="recoverpassword_questions.php">
                <label for="question1"><?php echo htmlspecialchars($questions[0]); ?></label>
                <input type="text" id="answer1" name="answer1" required>
                <label for="question2"><?php echo htmlspecialchars($questions[1]); ?></label>
                <input type="text" id="answer2" name="answer2" required>
                <label for="question3"><?php echo htmlspecialchars($questions[2]); ?></label>
                <input type="text" id="answer3" name="answer3" required>
                <input type="submit" value="Submit">
            </form>
        <?php elseif ($step == 3): ?>
            <p class="success">Your temporary password is: <strong><?php echo htmlspecialchars($new_password); ?></strong></p>
            <p>Please copy this password and <a href="login.php">login</a> to update your password.</p>
        <?php endif; ?>
    </div>
</body>
</html>
