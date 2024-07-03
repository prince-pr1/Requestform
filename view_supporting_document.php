<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name']) || !isset($_SESSION['username'])) {
    // Redirect to login page or handle the error
    header('Location: login.php');
    exit();
}

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

// Debugging: Check if id is set
if (!isset($_GET['rqst_id'])) {
    echo "Error: No request ID provided.";
    exit();
}

$rqst_id = $_GET['rqst_id'];

// Debugging: Print request ID
echo "Request ID: $rqst_id<br>";

$sql = "SELECT file_column FROM request WHERE rqst_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    // Debugging: SQL prepare error
    echo "SQL Prepare Error: " . $conn->error;
    exit();
}

$stmt->bind_param("i", $rqst_id);
$stmt->execute();
$stmt->store_result();

// Debugging: Check number of rows
$num_rows = $stmt->num_rows;
echo "Number of rows: $num_rows<br>";

if ($stmt->num_rows > 0) {
    $stmt->bind_result($fileContent);
    $stmt->fetch();

    // Debugging: Check if file content is empty
    if (empty($fileContent)) {
        echo "Supporting document not found.";
        exit();
    }

    // Determine file type and set appropriate headers
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $fileType = $finfo->buffer($fileContent);

    // Debugging: Print file type
    echo "File Type: $fileType<br>";

    if ($fileType === "application/pdf") {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="supporting_document.pdf"');
    } elseif (strpos($fileType, 'image/') === 0) {
        header('Content-Type: ' . $fileType);
        header('Content-Disposition: inline; filename="supporting_document.' . explode('/', $fileType)[1] . '"');
    } else {
        echo "Unsupported file type.";
        exit();
    }

    echo $fileContent;
} else {
    echo "Supporting document not found.";
}

$stmt->close();
$conn->close();
?>
