<?php
session_start();
include('config.php');

// Check if connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['rqst_id']) && isset($_POST['reject_comment'])) {
        $rqst_id = $_POST['rqst_id'];
        $reject_comment = $_POST['reject_comment'];
        $user_id = $_SESSION['user_id'];

        // Insert rejection action into request_approvals table
        $query = "INSERT INTO request_approvals (reqst_id, approver_id, approval_status, reject_comment)
                  VALUES (?, ?, 'REJECTED', ?)";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param('iis', $rqst_id, $user_id, $reject_comment);
            if ($stmt->execute()) {
                header("Location: admins_dashboard.php");
                exit();
            } else {
                echo "Error executing query: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error preparing statement: " . $conn->error;
        }
    } else {
        echo "Missing request ID or rejection comment.<br>";
    }
}

$conn->close();
?>
