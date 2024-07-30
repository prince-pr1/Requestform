<?php
session_start();
include('config.php');

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$user_name = $_SESSION['user_name'];

// Check if the current user is an accountant
$query_position = "SELECT position FROM users WHERE user_id = ?";
$stmt_position = $conn->prepare($query_position);
$stmt_position->bind_param("i", $user_id);
$stmt_position->execute();
$result_position = $stmt_position->get_result();
$user_position = $result_position->fetch_assoc()['position'];

$stmt_position->close();

if ($user_position != 'ACCOUNTANT') {
    header("Location: dashboard.php");
    exit();
}

// Fetch pending requests that need accountant's approval
$query = "SELECT r.rqst_id, r.rqst_time, r.rqst_title, r.projectname, r.rqst_by, 
                 COALESCE(SUM(p.total_price), 0) AS total_price,
                 u.name AS requestor_name,
                 IF(r.file_column IS NOT NULL AND r.file_column != '', 'yes', 'no') AS has_supporting_doc,
                 r.pdf_view
          FROM request r
          LEFT JOIN users u ON r.rqst_by = u.user_id
          LEFT JOIN request_product rp ON r.rqst_id = rp.rqst_id
          LEFT JOIN product p ON rp.product_id = p.product_number
          LEFT JOIN request_approvals ra ON r.rqst_id = ra.reqst_id AND ra.approver_id = ?
          WHERE r.status = 'PENDING' AND ra.approval_id IS NULL
          GROUP BY r.rqst_id, r.rqst_time, r.rqst_title, r.projectname, r.rqst_by, u.name";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

$stmt->close();

$query_recent = "SELECT r.rqst_title, u.name AS requestor_name, 
                        COALESCE(SUM(p.total_price), 0) AS total_price, r.status
                 FROM request r
                 LEFT JOIN users u ON r.rqst_by = u.user_id
                 LEFT JOIN request_product rp ON r.rqst_id = rp.rqst_id
                 LEFT JOIN product p ON rp.product_id = p.product_number
                 WHERE r.status IN ('APPROVED', 'DENIED') AND r.status_update_time  >= NOW() - INTERVAL 3 DAY
                 GROUP BY r.rqst_id, r.rqst_title, u.name, r.status";
//
$stmt_recent = $conn->prepare($query_recent);
$stmt_recent->execute();
$result_recent = $stmt_recent->get_result();

$recent_requests = [];
while ($row = $result_recent->fetch_assoc()) {
    $recent_requests[] = $row;
}

$stmt_recent->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to Accountant Dashboard</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .approve-btn {
            background-color: green;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
        }
        .reject-btn {
            background-color: red;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
        }
        .eye-icon {
            font-size: 18px;
            color: blue;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <button onclick="location.href='userAuth/signup.html'">ADD New USER</button>
    <button onclick="location.href='dashboard.php'">Return to Dashboard</button>
     <button onclick="location.href='accountant_download.php'"> Download Decided Requests</button>
     <button onclick="location.href='analyze_dashboard.php'"> Analyze dashboard</button>

    <div class="container">
        <h1>Welcome to Accountant Dashboard, <?php echo htmlspecialchars($user_name); ?></h1>
        

        <h2>Recent Responded Requests </h2>
<table>
    <thead>
        <tr>
            <th>Requisition Title</th>
            <th>Requested By</th>
            <th>Total Price of Products</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($recent_requests as $recent_request): ?>
            <tr>
                <td><?php echo htmlspecialchars($recent_request['rqst_title']); ?></td>
                <td><?php echo htmlspecialchars($recent_request['requestor_name']); ?></td>
                <td><?php echo htmlspecialchars($recent_request['total_price']); ?></td>
                <td><?php echo htmlspecialchars($recent_request['status']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<br>
<h2>Requests for Approval</h2>
<br>
        <table>
            <thead>
                <tr>
                    <th>Submitted Date</th>
                    <th>Requisition Title</th>
                    <th>Requested By</th>
                    <th>View PDF</th>
                    <th>Supporting Document</th>
                    <th>Total Price of Products</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['rqst_time']); ?></td>
                        <td><?php echo htmlspecialchars($request['rqst_title']); ?></td>
                        <td><?php echo isset($request['requestor_name']) ? htmlspecialchars($request['requestor_name']) : 'N/A'; ?></td>
                        <td>
                            <?php if (!empty($request['pdf_view'])): ?>
                                <a href="pdf_view.php?rqst_id=<?php echo $request['rqst_id']; ?>" target="_blank">View PDF</a>
                            <?php else: ?>
                                No PDF available
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($request['has_supporting_doc'] === 'yes'): ?>
                                <a href="view_supporting_document.php?rqst_id=<?php echo $request['rqst_id']; ?>" target="_blank">
                                    <span class="eye-icon">&#128065;</span>
                                </a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($request['total_price']); ?></td>
                        <td>
                            <a href="action_approve_request.php?rqst_id=<?php echo $request['rqst_id']; ?>&action=approve" class="approve-btn">Approve</a>
                            <a href="action_approve_request.php?rqst_id=<?php echo $request['rqst_id']; ?>&action=reject" class="reject-btn">Reject</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>