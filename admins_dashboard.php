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

// Fetch pending requests with total price of associated products, excluding already approved/rejected requests by the user
$query = "SELECT r.rqst_id, r.rqst_time, r.rqst_title, r.projectname, r.rqst_by, 
                 COALESCE(SUM(p.total_price), 0) AS total_price,
                 u.name AS requestor_name,
                 IF(r.file_column IS NOT NULL AND r.file_column != '', 'yes', 'no') AS has_supporting_doc,
                 r.pdf_view
          FROM request r
          LEFT JOIN users u ON r.rqst_by = u.user_id
          LEFT JOIN request_product rp ON r.rqst_id = rp.rqst_id
          LEFT JOIN product p ON rp.product_id = p.product_number
          LEFT JOIN request_approvals ra_accountant 
                ON r.rqst_id = ra_accountant.reqst_id 
                AND ra_accountant.approver_id = (SELECT user_id FROM users WHERE position = 'ACCOUNTANT') 
                AND ra_accountant.approval_status = 'APPROVED'
          LEFT JOIN request_approvals ra_admin 
                ON r.rqst_id = ra_admin.reqst_id 
                AND ra_admin.approver_id = ?
          WHERE r.status = 'PENDING' 
                AND ra_accountant.approval_id IS NOT NULL
                AND ra_admin.approval_id IS NULL
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

// SQL query to fetch the latest 20 requests with their approval statuses and requester info
$sql = "
    SELECT 
        r.rqst_id,
        r.rqst_title,
        r.rqst_time,
        u_requestor.name AS requestor_name,
        COALESCE(MAX(CASE WHEN u.position = 'MANAGING DIRECTOR' THEN ra.approval_status END), 'PENDING') AS 'Managing Director',
        COALESCE(MAX(CASE WHEN u.position = 'PROJECT MANAGER' THEN ra.approval_status END), 'PENDING') AS 'Project Manager',
        COALESCE(MAX(CASE WHEN u.position = 'OFFICE MANAGER' THEN ra.approval_status END), 'PENDING') AS 'Office Manager',
        COALESCE(MAX(CASE WHEN u.position = 'ACCOUNTANT' THEN ra.approval_status END), 'PENDING') AS 'Accountant'
    FROM 
        request r
    LEFT JOIN 
        request_approvals ra ON r.rqst_id = ra.reqst_id
    LEFT JOIN 
        users u ON ra.approver_id = u.user_id
    LEFT JOIN
        users u_requestor ON r.rqst_by = u_requestor.user_id
    GROUP BY 
        r.rqst_id, r.rqst_title, r.rqst_time, u_requestor.name
    ORDER BY 
        r.rqst_time DESC
    LIMIT 20;
";

$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to Admin Dashboard</title>
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
        .PENDING {
            background-color: lightblue;
        }
        .APPROVED {
            background-color: lightgreen;
        }
        .REJECTED {
            background-color: lightcoral;
        }
    </style>
</head>
<body>
    <button onclick="location.href='userAuth/signup.html'">ADD New USER</button>
    <button onclick="location.href='dashboard.php'">Return to Dashboard</button>
    <button onclick="location.href='approvals_dashboard.php'">Enter Accountant Dashboard</button>
    <button onclick="location.href='analyze_dashboard.php'">ENTER ANALYSIS Dashboard</button>
    <div class="container">
        <h1>Welcome to Admin Dashboard, <?php echo htmlspecialchars($user_name); ?></h1>
        
        <h2>Request Approval Tracking</h2>
        <table>
            <thead>
                <tr>
                    <th>Date of Request</th>
                    <th>Request Title</th>
                    <th>Requested By</th>
                    <th>Managing Director</th>
                    <th>Project Manager</th>
                    <th>Office Manager</th>
                    <th>Accountant</th>
                </tr>
            </thead>
            
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['rqst_time']); ?></td>
                            <td><?php echo htmlspecialchars($row['rqst_title']); ?></td>
                            <td><?php echo htmlspecialchars($row['requestor_name']); ?></td>
                            <td class="<?php echo htmlspecialchars($row['Managing Director']); ?>"><?php echo htmlspecialchars($row['Managing Director']); ?></td>
                            <td class="<?php echo htmlspecialchars($row['Project Manager']); ?>"><?php echo htmlspecialchars($row['Project Manager']); ?></td>
                            <td class="<?php echo htmlspecialchars($row['Office Manager']); ?>"><?php echo htmlspecialchars($row['Office Manager']); ?></td>
                            <td class="<?php echo htmlspecialchars($row['Accountant']); ?>"><?php echo htmlspecialchars($row['Accountant']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No requests found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <h2>Requests for Approval</h2>
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
