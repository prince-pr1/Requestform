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
                 r.credited_company, r.status,
                 r.currency  -- Include the currency field
          FROM request r
          LEFT JOIN users u ON r.rqst_by = u.user_id
          LEFT JOIN request_product rp ON r.rqst_id = rp.rqst_id
          LEFT JOIN product p ON rp.product_id = p.product_number
          WHERE r.status IN ('APPROVED', 'DENIED')
          GROUP BY r.rqst_id, r.rqst_time, r.rqst_title, r.projectname, r.rqst_by, u.name, r.credited_company, r.status, r.currency";

$result = $conn->query($query);

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

 $chartQuery = "SELECT DATE_FORMAT(rqst_time, '%Y-%m') AS month, credited_company, status, COUNT(*) AS count
FROM request
WHERE status IN ('APPROVED', 'DENIED') 
AND rqst_time >= DATE_SUB(CURDATE(), INTERVAL 4 MONTH)
GROUP BY month, credited_company, status
ORDER BY month DESC;
";
$chartResult = $conn->query($chartQuery);

$chartData = [];
while ($row = $chartResult->fetch_assoc()) {
    $chartData[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analyze Requests</title>
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

    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.css">
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.5.0/chart.min.js"></script>

</head>
<body>
    <button onclick="location.href='dashboard.php'">Return to Dashboard</button>
    <div class="container">
        <h1>Welcome to Admin Analysis Dashboard, <?php echo htmlspecialchars($user_name); ?></h1>
        <h2>Analyze Requests</h2>

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
                    <th><input type="text" placeholder="Search Submitted Date" /></th>
                    <th><input type="text" placeholder="Search Credited Company" /></th>
                    <th><input type="text" placeholder="Search Requisition Title" /></th>
                    <th><input type="text" placeholder="Search Product Names" /></th>
                    <th><input type="text" placeholder="Search Requested By" /></th>
                    <th><input type="text" placeholder="Search Supporting Document" /></th>
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
                        <td>
    <?php 
    $totalPrice = $request['total_price'];
    $currency = $request['currency'];
    $currencySymbol = '';

    if ($currency === 'RWF') {
        $currencySymbol = ' rwf';
    } elseif ($currency === 'USD') {
        $currencySymbol = ' $';
    } elseif ($currency === 'EURO') {
        $currencySymbol = ' €';
    }

    echo htmlspecialchars($totalPrice) . $currencySymbol; 
    ?>
</td>
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
            <tfoot>
                <tr>
                    <th>Total:</th>
                    <th>ITEC: <span id="itec-count"></span>, ITTCO: <span id="ittco-count"></span>, G.E.P.S: <span id="geps-count"></span></th>
                    <th></th>
                    <th id="total-products"></th>
                    <th id="requester-count"></th>
                    <th></th>
                    <th id="total-money"></th>
                    <th id="status-count"></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
    <script>
    $(document).ready(function() {
        var table = $('#requestsTable').DataTable({
            "order": [[ 0, "desc" ]],
            footerCallback: function (row, data, start, end, display) {
    var api = this.api(), data;

    // Function to format numbers with commas and remove unnecessary decimals
    var formatNumber = function (num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",").replace(/\.00$/, '');
    };

    // Function to parse and sum numbers
    var intVal = function (i) {
        return typeof i === 'string' ?
            i.replace(/[^0-9.-]+/g, '') * 1 :
            typeof i === 'number' ?
                i : 0;
    };

    // Total over current page for each currency
    var totalByCurrency = function (colIdx) {
        var totalRWF = 0;
        var totalUSD = 0;
        var totalEURO = 0;

        api.column(colIdx, { page: 'current' }).data().each(function (value) {
            if (value.includes('rwf')) {
                totalRWF += intVal(value);
            } else if (value.includes('$')) {
                totalUSD += intVal(value);
            } else if (value.includes('€')) {
                totalEURO += intVal(value);
            }
        });

        return {
            totalRWF: totalRWF,
            totalUSD: totalUSD,
            totalEURO: totalEURO
        };
    };

    var totals = totalByCurrency(6);// assuming the 7th column contains the prices with currencies

                
                // Total over current page
                var pageTotalColumn = function(colIdx) {
                    return api
                        .column(colIdx, { page: 'current'} )
                        .data()
                        .reduce( function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0 );
                };

                // Count occurrences of credited companies
                var countCompanies = function(company) {
                    return api
                        .column(1, { page: 'current' })
                        .data()
                        .filter(function(value) {
                            return value === company;
                        }).length;
                };

                // Count occurrences of requestors
                var countRequestors = function() {
                    var requestorCounts = {};
                    api
                        .column(4, { page: 'current' })
                        .data()
                        .each(function(value) {
                            if (value in requestorCounts) {
                                requestorCounts[value]++;
                            } else {
                                requestorCounts[value] = 1;
                            }
                        });
                    return requestorCounts;
                };

                // Get counts for product names
                var productCount = function() {
                    var productCounts = {};
                    api
                        .column(3, { page: 'current' })
                        .data()
                        .each(function(value) {
                            value.split(', ').forEach(function(product) {
                                if (product in productCounts) {
                                    productCounts[product]++;
                                } else {
                                    productCounts[product] = 1;
                                }
                            });
                        });
                    return productCounts;
                };

                // Count occurrences for status
                var countStatus = function(status) {
                    return api
                        .column(7, { page: 'current' })
                        .data()
                        .filter(function(value) {
                            return value === status;
                        }).length;
                };

                  // Update footer
    $(api.column(0).footer()).html(
        'Total: ' + api.column(0, { page: 'current' }).data().length
    );

    $('#itec-count').html('' + countCompanies('ITEC') + '<br>');
    $('#ittco-count').html('' + countCompanies('ITTCO') + '<br>');
    $('#geps-count').html('' + countCompanies('G.E.P.S') + '<br>');

    var requestorCounts = countRequestors();
    var requestorCountsHtml = Object.keys(requestorCounts).map(function (requestor) {
        return requestor + ': ' + requestorCounts[requestor];
    }).join('<br>');
    $('#requester-count').html(requestorCountsHtml);

    var productCounts = productCount();
    var productCountsHtml = Object.keys(productCounts).map(function (product) {
        return product + ': ' + productCounts[product];
    }).join('<br> ');
    $('#total-products').html(productCountsHtml);

    var approvedCount = countStatus('APPROVED');
            var deniedCount = countStatus('DENIED');
            $('#status-count').html(
                'Approved: ' + approvedCount + '<br>Denied: ' + deniedCount
            );

    $('#total-money').html(
        'Total Money:<br>' +
        formatNumber(totals.totalRWF) + ' rwf,<br>' +
        formatNumber(totals.totalUSD) + ' $,<br>' +
        formatNumber(totals.totalEURO) + ' €'
    );
            }
        });

        // Apply the search
        table.columns().every(function() {
            var that = this;

            $('input', this.header()).on('keyup change clear', function() {
                if (that.search() !== this.value) {
                    that.search(this.value).draw();
                }
            });
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

// Limit months to maximum 4 if available
if (months.length > 4) {
    months = months.slice(months.length - 4);
}

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
                beginAtZero: true,
                precision: 0, // Display whole numbers only
                stepSize: 1 // Ensure the scale goes by 1
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
