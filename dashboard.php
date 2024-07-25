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

// Fetch user requests
$query = "SELECT rqst_id, rqst_time, rqst_title, status FROM request WHERE rqst_by = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
$stmt->close();

// Fetch request status counts
$query = "SELECT status, COUNT(*) as count FROM request WHERE rqst_by = ? GROUP BY status";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$request_counts = ['APPROVED' => 0, 'PENDING' => 0, 'DENIED' => 0];
while ($row = $result->fetch_assoc()) {
    $request_counts[$row['status']] = $row['count'];
}
$stmt->close();

// Check if user is an approver
$query = "SELECT position FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($position);
$stmt->fetch();
$stmt->close();

$approver_positions = ['ACCOUNTANT', 'MANAGING DIRECTOR', 'BUSINESS MANAGER', 'PROJECT MANAGER'];
$is_approver = in_array($position, $approver_positions);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 80%; margin: 0 auto; }
        .flex-container { display: flex; align-items: center; justify-content: space-between; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table, th, td { border: 1px solid black; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .approver-btn { margin: 20px 0; }
        #requestChart { width: 40% !important; height: auto !important; }
    </style>
</head>
<button onclick="location.href='logout.php'"> LOG OUT</button>
<body>
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($user_name); ?></h1>
        
        <?php if ($is_approver): ?>
            <div class="approver-btn">
                <button onclick="location.href='<?php echo ($position === 'ACCOUNTANT') ? 'accountant_dashboard.php' : 'admins_dashboard.php'; ?>'">Check New Requests</button>
            </div>
        <?php endif; ?>

        <button onclick="location.href='profile_view.php'">Profile view</button>
        <button onclick="location.href='chatreq.html'">ADD New Requests</button>
        
        
        <!--<canvas id="requestChart" width="400" height="200"></canvas>
        <script>
            var ctx = document.getElementById('requestChart').getContext('2d');
            var requestChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Approved', 'Pending', 'Denied'],
                    datasets: [{
                        data: [
                            <?php echo $request_counts['APPROVED']; ?>,
                            <?php echo $request_counts['PENDING']; ?>,
                            <?php echo $request_counts['DENIED']; ?>
                        ],
                        backgroundColor: ['#4CAF50', '#FFC107', '#F44336']
                    }]
                }
            });
        </script>
                    -->

        <h2>Your Requests</h2>
        <table>
            <thead>
                <tr>
                    <th>Submitted Date</th>
                    <th>Requisition Title</th>
                    <th>Status</th>
                    <th>File</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['rqst_time']); ?></td>
                        <td><?php echo htmlspecialchars($request['rqst_title']); ?></td>
                        <td><?php echo htmlspecialchars($request['status']); ?></td>
                        <td>
                            <?php if ($request['status'] == 'PENDING'): ?>
                                Pending
                            <?php elseif ($request['status'] == 'DENIED' || $request['status'] == 'APPROVED'): ?>
                                <form action="download_pdf.php" method="POST">
                                    <input type="hidden" name="rqst_id" value="<?php echo $request['rqst_id']; ?>">
                                    <button type="submit">Download PDF</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
