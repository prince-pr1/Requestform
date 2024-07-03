<?php
include('config.php'); // Ensure this file includes your database connection details

// Example data to insert
$rqst_title = "Sample Request";
$projectname = "Sample Project";
$rqst_by = 1; // Assuming user_id of the requester

// Prepare the SQL statement
$query = "INSERT INTO request (rqst_title, projectname, rqst_by) 
          VALUES ('$rqst_title', '$projectname', $rqst_by)";

// Execute the query
if ($conn->query($query) === TRUE) {
    echo "New request created successfully";
} else {
    echo "Error: " . $query . "<br>" . $conn->error;
}

// Close the connection
$conn->close();
?>
