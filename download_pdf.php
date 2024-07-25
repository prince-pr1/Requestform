<?php
require('C:\xampp\htdocs\project\Requestform\userAuth\fpdf186\fpdf.php'); // Include FPDF library

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to fetch data from the database
function fetchDataFromDatabase($reqst_id, $db_connection) {
    $query = "SELECT r.projectname, r.rqst_title, r.return_document, r.credited_company,r.currency,
                     r.rqst_by, u.name AS requester_name,
                     p.product_name, p.description, p.quantity AS quantity, p.price_per_unit, 
                     (p.quantity * p.price_per_unit) AS total_price
              FROM request r
              INNER JOIN request_product rp ON r.rqst_id = rp.rqst_id
              INNER JOIN product p ON rp.product_id = p.product_number
              INNER JOIN users u ON r.rqst_by = u.user_id
              WHERE r.rqst_id = ?";
    
    $stmt = mysqli_prepare($db_connection, $query);
    mysqli_stmt_bind_param($stmt, 'i', $reqst_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        die("Error in query: " . mysqli_error($db_connection));
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    return $data;
}

// Function to fetch approvals information
function fetchApprovalsInfo($reqst_id, $db_connection) {
    $query = "SELECT u.name AS approver_name, u.position AS approver_position, ra.approved_at, ra.approval_status, ra.reject_comment
              FROM request_approvals ra
              JOIN users u ON ra.approver_id = u.user_id
              WHERE ra.reqst_id = ?";
    
    $stmt = mysqli_prepare($db_connection, $query);
    mysqli_stmt_bind_param($stmt, 'i', $reqst_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        die("Error in query: " . mysqli_error($db_connection));
    }

    $approvals = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $approvals[] = $row;
    }

    return $approvals;
}

// FPDF class extension for PDF generation
class PDF extends FPDF {
    private $widths;
    private $aligns;
    private $companyLogo;

    function SetWidths($w) {
        // Set the array of column widths
        $this->widths = $w;
    }

    function SetAligns($a) {
        // Set the array of column alignments
        $this->aligns = $a;
    }

    function Row($data) {
        // Calculate the height of the row
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        }
        $h = 5 * $nb;
        // Issue a page break first if needed
        $this->CheckPageBreak($h);
        // Draw the cells of the row
        for ($i = 0; $i < count($data); $i++) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Rect($x, $y, $w, $h);
            $this->MultiCell($w, 5, $data[$i], 0, $a);
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }

    function CheckPageBreak($h) {
        // If the height h would cause an overflow, add a new page immediately
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
        }
    }

    function NbLines($w, $txt) {
        // Computes the number of lines a MultiCell of width w will take
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") {
            $nb--;
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
            }
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }

    // Page header
    function Header() {
        if ($this->companyLogo) {
            // Adjust the positioning and size of the logo
            $this->Image($this->companyLogo, ($this->GetPageWidth() - 50) / 2, 7, 50); // Adjust position and size as needed
        }
        $this->Ln(13); // Adjust the value to move the title below the logo
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Requisition Form', 0, 1, 'C');
        $this->Ln(10);
    }

    // Page footer
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Set the company logo path
    function setCompanyLogo($company) {
        $basePath = 'C:/xampp/htdocs/project/Requestform/userAuth/images/';
        $logoPath = $basePath . 'ITEC_LOGO.png'; // Default logo path
    
        // Adjust logo path based on credited_company value
        if ($company == 'ITTCO') {
            $logoPath = $basePath . 'ITTCO_LOGO.png';
        } elseif ($company == 'G.E.P.S') {
            $logoPath = $basePath . 'GEPS_LOGO.jpg';
        }
    
        // Check if the logo file exists
        if (!file_exists($logoPath)) {
            throw new Exception("Logo file does not exist: $logoPath");
        }
    
        $this->companyLogo = $logoPath;
    }

    // Display approvals
    function DisplayApprovals($approvalsData) {
        foreach ($approvalsData as $approval) {
            if ($approval['approval_status'] === 'APPROVED') {
                // Check if the approver is an accountant
                if ($approval['approver_position'] === 'ACCOUNTANT') {
                    $this->Cell(0, 10, "Verified By: {$approval['approver_name']} ({$approval['approver_position']})", 0, 1);
                } else {
                    $this->Cell(0, 10, "Approved By: {$approval['approver_name']} ({$approval['approver_position']})", 0, 1);
                }
                $this->Cell(0, 10, "Approval Time: {$approval['approved_at']}", 0, 1);
            } elseif ($approval['approval_status'] === 'REJECTED') {
                $this->Cell(0, 10, "Rejected By: {$approval['approver_name']} ({$approval['approver_position']})", 0, 1);
                $this->Cell(0, 10, "Rejection Comment: {$approval['reject_comment']}", 0, 1);
                $this->Image('C:/xampp/htdocs/project/Requestform/userAuth/images/rejected_stamp.jpg', 10, $this->GetY(), 50);
            }
            $this->Ln(5);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['rqst_id'])) {
        die("Request ID is not set.");
    }

    $reqst_id = intval($_POST['rqst_id']);
    if ($reqst_id <= 0) {
        die("Invalid Request ID.");
    }
      
    // Database connection details
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'verite';

    // Connect to the database
    $db_connection = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if (!$db_connection) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Fetch data from the database
    $data = fetchDataFromDatabase($reqst_id, $db_connection);
    $approvals = fetchApprovalsInfo($reqst_id, $db_connection);
    mysqli_close($db_connection);

    if (empty($data)) {
        die("No data found for the given Request ID.");
    }

    // Create PDF document
    $pdf = new PDF();
    $company = $data[0]['credited_company']; // Fetch credited_company from the data
    try {
        $pdf->setCompanyLogo($company); // Set company logo based on credited_company
    } catch (Exception $e) {
        die($e->getMessage());
    }
    $pdf->AddPage();
    $pdf->AliasNbPages();

    // Adding request details
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, "Requested By: " . $data[0]['requester_name'], 0, 1);
    $pdf->Cell(0, 10, "Project Name: " . $data[0]['projectname'], 0, 1);
    $pdf->Cell(0, 10, "Request Title: " . $data[0]['rqst_title'], 0, 1);
    $pdf->Cell(0, 10, "Return Document: " . $data[0]['return_document'], 0, 1);
    $pdf->Cell(0, 10, "Currency: " . $data[0]['currency'], 0, 1);
    $pdf->Ln(10);

    // Adding product details
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetWidths([40, 70, 20, 30, 30]);
    $pdf->Row(['Product Name', 'Description', 'Quantity', 'Price Per Unit', 'Total Price']);

    $pdf->SetFont('Arial', '', 10);
    foreach ($data as $row) {
        $pdf->Row([
            $row['product_name'],
            $row['description'],
            $row['quantity'],
            $row['price_per_unit'],
            $row['total_price']
        ]);
    }

    $pdf->Ln(10);

    // Adding approval details
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, "Approvals:", 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->DisplayApprovals($approvals);

    // Create a safe filename
    $rqst_title = $data[0]['rqst_title'];
    $requester_name = $data[0]['requester_name'];
    $safe_filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $rqst_title . '_' . $requester_name);

    // Set the correct headers for PDF output
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $safe_filename . '.pdf"');

    // Output PDF as a download
    $pdf->Output('D', $safe_filename . '.pdf');
    exit;
}
?>