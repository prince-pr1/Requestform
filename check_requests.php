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

// Fetch pending requests
$query = "SELECT rqst_id, rqst_time, rqst_title, projectname FROM request WHERE status = 'PENDING'";
$result = $conn->query($query);

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Check New Requests</title>
</head>
<body>
    <div class="container">
        <h1>New Requests for Approval</h1>
        <table>
            <thead>
                <tr>
                    <th>Submitted Date</th>
                    <th>Requisition Title</th>
                    <th>Project Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['rqst_time']); ?></td>
                        <td><?php echo htmlspecialchars($request['rqst_title']); ?></td>
                        <td><?php echo htmlspecialchars($request['projectname']); ?></td>
                        <td>
                            <a href="approve_request.php?rqst_id=<?php echo $request['rqst_id']; ?>">Approve/Deny</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
