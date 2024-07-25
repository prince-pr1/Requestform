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

        // Retrieve the user's position from the database
        $position_query = "SELECT position FROM users WHERE user_id = ?";
        $position_stmt = $conn->prepare($position_query);
        if ($position_stmt) {
            $position_stmt->bind_param('i', $user_id);
            if ($position_stmt->execute()) {
                $position_result = $position_stmt->get_result();
                if ($position_result->num_rows == 1) {
                    $position_row = $position_result->fetch_assoc();
                    $user_position = $position_row['position'];

                    // Insert rejection action into request_approvals table
                    $query = "INSERT INTO request_approvals (reqst_id, approver_id, approval_status, reject_comment)
                              VALUES (?, ?, 'REJECTED', ?)";
                    $stmt = $conn->prepare($query);
                    if ($stmt) {
                        $stmt->bind_param('iis', $rqst_id, $user_id, $reject_comment);
                        if ($stmt->execute()) {
                            if ($user_position == 'ACCOUNTANT') {
                                header("Location: accountant_dashboard.php");
                            } else {
                                header("Location: admins_dashboard.php");
                            }
                            exit();
                        } else {
                            echo "Error executing query: " . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        echo "Error preparing statement: " . $conn->error;
                    }
                } else {
                    echo "User position not found.<br>";
                }
                $position_stmt->close();
            } else {
                echo "Error executing query: " . $position_stmt->error;
            }
        } else {
            echo "Error preparing statement: " . $conn->error;
        }
    } else {
        echo "Missing request ID or rejection comment.<br>";
    }
}

$conn->close();
?>
