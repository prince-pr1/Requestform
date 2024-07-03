<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "verite";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "db connected...";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $contract = $_POST['contract']; // Assuming these fields are coming from the form
    $position = $_POST['position'];

    $sql = $conn->prepare("INSERT INTO users (firstname, lastname, username, password, email, phone, contract, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $sql->bind_param("ssssssss", $firstname, $lastname, $username, $password, $email, $phone, $contract, $position);

    echo "query executed";
    
    if ($sql->execute()) {
        echo 'Record added successfully.';
    } else {
        echo 'Error: ' . $sql->error;
    }
    $sql->close();
}

$conn->close();

