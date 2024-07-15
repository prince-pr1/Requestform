<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch the logged-in user's information
$query = "SELECT firstname, lastname, username, email, phone, contract, position FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($firstname, $lastname, $username, $email, $phone, $contract, $position);
$stmt->fetch();
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile</title>
</head>
<body>
    <h1>User Profile</h1>
    <p><strong>First Name:</strong> <?php echo htmlspecialchars($firstname); ?></p>
    <p><strong>Last Name:</strong> <?php echo htmlspecialchars($lastname); ?></p>
    <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
    <p><strong>Phone:</strong> <?php echo htmlspecialchars($phone); ?></p>
    <p><strong>Contract:</strong> <?php echo htmlspecialchars($contract); ?></p>
    <p><strong>Position:</strong> <?php echo htmlspecialchars($position); ?></p>

    <a href="update_profile.php">Update Profile</a>

    <?php if ($position !== 'ACCOUNTANT' && $position !== 'EMPLOYEE'): ?>
        <h2>Other Users</h2>
        <a href="other_users.php">View Other Users</a>
    <?php endif; ?>
</body>
</html>

<?php
$conn->close();
?>
