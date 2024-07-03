<?php
session_start();
include('config.php');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require('C:\xampp\htdocs\project\Requestform\userAuth\fpdf186\fpdf.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rqst_id'])) {
    $rqst_id = intval($_POST['rqst_id']); // Ensure rqst_id is an integer

    // Fetch the PDF content and additional details from the database
    $query = $conn->prepare("
        SELECT r.pdf_view, r.rqst_title, u.firstname, u.lastname, r.status 
        FROM request r
        JOIN users u ON r.rqst_by = u.user_id
        WHERE r.rqst_id = ?
    ");
    if ($query === false) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }
    $query->bind_param("i", $rqst_id);
    $query->execute();
    $query->bind_result($pdf_content, $rqst_title, $firstname, $lastname, $status);
    $query->fetch();
    $query->close();

    // Check if PDF content is fetched correctly
    if (!$pdf_content) {
        echo "PDF content is empty. Please check the request ID.";
        $conn->close();
        exit();
    }

    // Convert binary PDF data to temporary file
    $temp_pdf = tempnam(sys_get_temp_dir(), 'request_pdf');
    file_put_contents($temp_pdf, $pdf_content);

    $requester_name = $firstname . ' ' . $lastname;

    // Fetch approval details
    $query = $conn->prepare("
        SELECT 
            ra.approval_status, 
            ra.approved_at, 
            ra.reject_comment, 
            u.name, 
            u.position 
        FROM request_approvals ra
        JOIN users u ON ra.approver_id = u.user_id
        WHERE ra.reqst_id = ?
    ");
    if ($query === false) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }
    $query->bind_param("i", $rqst_id);
    $query->execute();
    $result = $query->get_result();

    $approvals = [];
    while ($row = $result->fetch_assoc()) {
        $approvals[] = $row;
    }
    $query->close();

    // Create a new PDF using FPDF
    class PDF extends FPDF {
        function Header() {
            // Add a "REJECTED" stamp if the request is denied
            global $status;
            if ($status == 'DENIED') {
                $this->SetTextColor(255, 0, 0);
                $this->SetFont('Arial', 'B', 24);
                $this->Cell(0, 10, 'REJECTED', 0, 1, 'C');
                $this->Ln(10);
            }
        }

        function Footer() {
            // Footer content
        }
    }

    $pdf = new PDF();
    $pdf->AddPage(); // Add first page for the original PDF content

    // Include the original PDF document
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0); // Reset text color
    $pdf->MultiCell(0, 10, 'Original PDF:', 0, 1);
    $pdf->Ln(10);

    // Output the fetched PDF content
    $pdf->Output($temp_pdf, 'F');

    // Add approval details on a new page
    $pdf->AddPage(); // This caused the document closed error previously

    // Output approval details
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Approval Details', 0, 1);
    $pdf->Ln(10);
    $pdf->SetFont('Arial', '', 12);

    foreach ($approvals as $approval) {
        $pdf->Cell(0, 10, 'Status: ' . $approval['approval_status'], 0, 1);
        $pdf->Cell(0, 10, 'Name: ' . $approval['name'], 0, 1);
        $pdf->Cell(0, 10, 'Position: ' . $approval['position'], 0, 1);
        $pdf->Cell(0, 10, 'Date: ' . $approval['approved_at'], 0, 1);
        if ($approval['approval_status'] == 'REJECTED') {
            $pdf->Cell(0, 10, 'Comment: ' . $approval['reject_comment'], 0, 1);
        }
        $pdf->Ln(10);
    }

    // Output the final PDF to the browser
    $pdf->Output('I', $rqst_title . ' - ' . $requester_name . '.pdf');

    // Clean up: delete temporary PDF file
    unlink($temp_pdf);

    $conn->close();
} else {
    echo "Request ID is required.";
    exit();
}
?>
