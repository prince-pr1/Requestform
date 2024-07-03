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

if (!isset($_GET['rqst_id'])) {
    echo "Request ID is required.";
    exit();
}

$rqst_id = $_GET['rqst_id'];

// Fetch the PDF content from the database
$query = $conn->prepare("SELECT pdf_view FROM request WHERE rqst_id = ?");
$query->bind_param("i", $rqst_id);
$query->execute();
$query->bind_result($pdf_content);
$query->fetch();
$query->close();

if ($pdf_content) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="request.pdf"');
    echo $pdf_content;
} else {
    echo "PDF not found.";
}

$conn->close();
?>
