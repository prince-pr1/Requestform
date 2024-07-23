<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $question1 = $_POST['question1'];
    $answer1 = $_POST['answer1'];
    $question2 = $_POST['question2'];
    $answer2 = $_POST['answer2'];
    $question3 = $_POST['question3'];
    $answer3 = $_POST['answer3'];

    $query = "INSERT INTO user_recovery (user_id, question1, answer1, question2, answer2, question3, answer3) VALUES (?, ?, ?, ?, ?, ?, ?)
              ON DUPLICATE KEY UPDATE question1 = VALUES(question1), answer1 = VALUES(answer1), question2 = VALUES(question2), answer2 = VALUES(answer2), question3 = VALUES(question3), answer3 = VALUES(answer3)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issssss", $user_id, $question1, $answer1, $question2, $answer2, $question3, $answer3);
    $stmt->execute();
    $stmt->close();

    if ($stmt->execute()) {
        $success = "Recovery questions updated successfully.";
        // Redirect to dashboard.php after successful update
        header("Location: dashboard.php");
        exit();
    } else {
        // Error during execution
        $error = "THE RECOVERY QUESTIONS ARE NOT SET  " . $stmt->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set Recovery Questions</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 50%; margin: 0 auto; }
        form { display: flex; flex-direction: column; }
        label, input { margin: 10px 0; }
        input[type="submit"] { width: fit-content; padding: 10px 20px; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Set Recovery Questions</h1>
        <?php if (isset($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <form method="post" action="recovery_questions.php">

        <p><b>It is highly advisable to evit spaces and caps in answers</b></p>
            <label for="question1">Question 1:</label>
            <input type="text" id="question1" name="question1" required>
            <label for="answer1">Answer 1:</label>
            <input type="text" id="answer1" name="answer1" required>

            <label for="question2">Question 2:</label>
            <input type="text" id="question2" name="question2" required>
            <label for="answer2">Answer 2:</label>
            <input type="text" id="answer2" name="answer2" required>

            <label for="question3">Question 3:</label>
            <input type="text" id="question3" name="question3" required>
            <label for="answer3">Answer 3:</label>
            <input type="text" id="answer3" name="answer3" required>

            <input type="submit" value="Set Questions">
        </form>
    </div>
</body>
</html>
