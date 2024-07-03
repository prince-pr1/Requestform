<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$user_name = $_SESSION['user_name'];

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['rqst_id']) && isset($_GET['action'])) {
    $rqst_id = $_GET['rqst_id'];
    $action = $_GET['action'];

    if ($action == 'approve') {
        // Insert approval action into request_approvals table
        $query = "INSERT INTO request_approvals (reqst_id, approver_id, approval_status)
                  VALUES (?, ?, 'APPROVED')";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param('ii', $rqst_id, $user_id);
            if ($stmt->execute()) {
                header("Location:dashboard.php");
                exit();
            } else {
                echo "Error executing query: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error preparing statement: " . $conn->error;
        }
    } elseif ($action == 'reject') {
        // Display rejection form
        echo "<h2>Rejection Comment</h2>";
        echo "<form method='post' action='process_rejection.php'>";
        echo "<input type='hidden' name='rqst_id' value='$rqst_id'>";
        echo "<textarea name='reject_comment' rows='4' cols='50'></textarea><br>";
        echo "<input type='submit' value='Submit'>";
        echo "</form>";
    }
}

$conn->close();
?>