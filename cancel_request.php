<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "verite";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rqst_id = $_POST['rqst_id'];

    // Delete entries from request_product table
    $deleteRequestProductSql = $conn->prepare("DELETE FROM request_product WHERE rqst_id = ?");
    $deleteRequestProductSql->bind_param("i", $rqst_id);
    $deleteRequestProductSql->execute();
    $deleteRequestProductSql->close();

    // Delete PDF from request table
    $updatePdfSql = $conn->prepare("UPDATE request SET pdf_view = NULL WHERE rqst_id = ?");
    $updatePdfSql->bind_param("i", $rqst_id);
    $updatePdfSql->execute();
    $updatePdfSql->close();

    // Delete entry from request table
    $deleteRequestSql = $conn->prepare("DELETE FROM request WHERE rqst_id = ?");
    $deleteRequestSql->bind_param("i", $rqst_id);
    $deleteRequestSql->execute();
    $deleteRequestSql->close();

    echo "Request and associated products have been deleted.";
}

// Close the database connection
$conn->close();
?>
