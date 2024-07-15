<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['position'] === 'ACCOUNTANT' || $_SESSION['position'] === 'EMPLOYEE') {
    header("Location: login.php");
    exit();
}

$query = "SELECT user_id, firstname, lastname, username, email, phone, contract, position FROM users";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Other Users</title>
</head>
<body>
    <h1>Other Users</h1>
    <table border="1">
        <thead>
            <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Contract</th>
                <th>Position</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['firstname']); ?></td>
                    <td><?php echo htmlspecialchars($row['lastname']); ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><?php echo htmlspecialchars($row['contract']); ?></td>
                    <td><?php echo htmlspecialchars($row['position']); ?></td>
                    <td>
                        <a href="update_other_profile.php?user_id=<?php echo $row['user_id']; ?>">Update</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>

<?php
$conn->close();
?>
