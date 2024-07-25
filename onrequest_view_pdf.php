onrequest view 

<?php
include('config.php');

if (isset($_GET['id'])) {
    $rqst_id = $_GET['id'];

    // Fetch the PDF content from the database
    $sql = $conn->prepare("SELECT pdf_view FROM request WHERE rqst_id = ?");
    $sql->bind_param("i", $rqst_id);
    $sql->execute();
    $sql->bind_result($pdfContent);
    $sql->fetch();
    $sql->close();

    if ($pdfContent) {
        header('Content-Type: application/pdf');
        echo $pdfContent;
    } else {
        echo "PDF not found.";
    }
} else {
    echo "Invalid request ID.";
}

// Close the database connection
$conn->close();
?>