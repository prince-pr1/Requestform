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

// Fetch approved or denied requests with total price of associated products and product names
$query = "SELECT r.rqst_id, r.rqst_time, r.rqst_title, r.projectname, r.rqst_by, 
                 COALESCE(GROUP_CONCAT(p.product_name SEPARATOR ', '), '') AS product_names,
                 COALESCE(SUM(p.total_price), 0) AS total_price,
                 u.name AS requestor_name,
                 IF(r.file_column IS NOT NULL AND r.file_column != '', 'yes', 'no') AS has_supporting_doc,
                 r.credited_company, r.status
          FROM request r
          LEFT JOIN users u ON r.rqst_by = u.user_id
          LEFT JOIN request_product rp ON r.rqst_id = rp.rqst_id
          LEFT JOIN product p ON rp.product_id = p.product_number
          WHERE r.status IN ('APPROVED', 'DENIED')
          GROUP BY r.rqst_id, r.rqst_time, r.rqst_title, r.projectname, r.rqst_by, u.name, r.credited_company, r.status";

$result = $conn->query($query);

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

// Fetch data for the chart (limiting to 4 months)
$chartQuery = "SELECT DATE_FORMAT(rqst_time, '%Y-%m') AS month, credited_company, status, COUNT(*) AS count
               FROM request
               WHERE status IN ('APPROVED', 'DENIED')
               GROUP BY month, credited_company, status
               ORDER BY month DESC
               LIMIT 4";
$chartResult = $conn->query($chartQuery);

$chartData = [];
while ($row = $chartResult->fetch_assoc()) {
    $chartData[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Download Requests</title>
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
        .download-btn {
            background-color: blue;
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
        thead input, tfoot input {
            width: 100%;
            padding: 3px;
            box-sizing: border-box;
        }
    </style>
    <!-- Include jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!-- Include DataTables CSS and JS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <button onclick="location.href='dashboard.php'">Return to Dashboard</button>
    <div class="container">
        <h1>Welcome to Download Decided Request Dashboard, <?php echo htmlspecialchars($user_name); ?></h1>
        <h2>Download Requests</h2>

        <!-- Chart container -->
        <canvas id="requestChart" width="400" height="200"></canvas>

        <table id="requestsTable">
            <thead>
                <tr>
                    <th>Submitted Date</th>
                    <th>Credited Company</th>
                    <th>Requisition Title</th>
                    <th>Product Names</th>
                    <th>Requested By</th>
                    <th>Supporting Document</th>
                    <th>Total Price of Products</th>
                    <th>Status</th>
                    <th>Download PDF</th>
                </tr>
                <tr>
                    <th><input type="text" placeholder="Search Date" /></th>
                    <th><input type="text" placeholder="Search Company" /></th>
                    <th><input type="text" placeholder="Search Title" /></th>
                    <th><input type="text" placeholder="Search Products" /></th>
                    <th><input type="text" placeholder="Search Requested By" /></th>
                    <th></th>
                    <th><input type="text" placeholder="Search Total Price" /></th>
                    <th><input type="text" placeholder="Search Status" /></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['rqst_time']); ?></td>
                        <td><?php echo htmlspecialchars($request['credited_company']); ?></td>
                        <td><?php echo htmlspecialchars($request['rqst_title']); ?></td>
                        <td><?php echo htmlspecialchars($request['product_names']); ?></td>
                        <td><?php echo isset($request['requestor_name']) ? htmlspecialchars($request['requestor_name']) : 'N/A'; ?></td>
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
                        <td><?php echo htmlspecialchars($request['status']); ?></td>
                        <td>
                            <form action="download_pdf.php" method="POST">
                                <input type="hidden" name="rqst_id" value="<?php echo $request['rqst_id']; ?>">
                                <button type="submit">Download PDF</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
        $(document).ready(function() {
            var table = $('#requestsTable').DataTable();

            // Apply the search
            $('#requestsTable thead input').on('keyup change clear', function() {
                var index = $(this).parent().index();
                table.column(index).search(this.value).draw();
            });
        });

        // Prepare data for the chart
        var chartData = <?php echo json_encode($chartData); ?>;
        var months = [];
        var companies = ['ITTCO', 'G.E.P.S', 'ITEC'];
        var statuses = ['APPROVED', 'DENIED'];
        
        var dataSets = {};

        companies.forEach(function(company) {
            statuses.forEach(function(status) {
                var key = company + '_' + status;
                dataSets[key] = [];
            });
        });

        chartData.forEach(function(row) {
            if (!months.includes(row.month)) {
                months.push(row.month);
            }
            var key = row.credited_company + '_' + row.status;
            dataSets[key].push(row.count);
        });

        var chartConfig = {
            type: 'bar',
            data: {
                labels: months,
                datasets: []
            },
            options: {
                responsive: true,
                title: {
                    display: true,
                    text: 'Approved and Denied Requests by Credited Company (Monthly)'
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Number of Requests'
                        },
                        beginAtZero: true
                    }
                }
            }
        };

        companies.forEach(function(company) {
            statuses.forEach(function(status) {
                var key = company + '_' + status;
                var bgColor, borderColor;
                switch (company) {
                    case 'ITTCO':
                        bgColor = status === 'APPROVED' ? 'rgba(0, 0, 255, 0.2)' : 'rgba(255, 165, 0, 0.2)';
                        borderColor = status === 'APPROVED' ? 'rgba(0, 0, 255, 1)' : 'rgba(255, 165, 0, 1)';
                        break;
                    case 'G.E.P.S':
                        bgColor = status === 'APPROVED' ? 'rgba(255, 255, 0, 0.2)' : 'rgba(128, 0, 128, 0.2)';
                        borderColor = status === 'APPROVED' ? 'rgba(255, 255, 0, 1)' : 'rgba(128, 0, 128, 1)';
                        break;
                    case 'ITEC':
                        bgColor = status === 'APPROVED' ? 'rgba(0, 128, 0, 0.2)' : 'rgba(255, 0, 0, 0.2)';
                        borderColor = status === 'APPROVED' ? 'rgba(0, 128, 0, 1)' : 'rgba(255, 0, 0, 1)';
                        break;
                }
                chartConfig.data.datasets.push({
                    label: company + ' - ' + status,
                    data: dataSets[key],
                    backgroundColor: bgColor,
                    borderColor: borderColor,
                    borderWidth: 1
                });
            });
        });

        var ctx = document.getElementById('requestChart').getContext('2d');
        new Chart(ctx, chartConfig);
    </script>
</body>
</html>
