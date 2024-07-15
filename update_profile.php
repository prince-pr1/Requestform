<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $contract = $_POST['contract'];
    $position = $_POST['position'];

    $query = "UPDATE users SET firstname = ?, lastname = ?, email = ?, phone = ?, contract = ?, position = ? WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssssi", $firstname, $lastname, $email, $phone, $contract, $position, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: profile_view.php");
    exit();
}

$query = "SELECT firstname, lastname, email, phone, contract, position FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($firstname, $lastname, $email, $phone, $contract, $position);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Profile</title>
</head>
<body>
    <h1>Update Profile</h1>
    <form action="update_profile.php" method="post">
        <p><label>First Name: <input type="text" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>"></label></p>
        <p><label>Last Name: <input type="text" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>"></label></p>
        <p><label>Email: <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>"></label></p>
        <p><label>Phone: <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>"></label></p>
        <p><label>Contract: 
            <select name="contract">
                <option value="ITTCO" <?php if ($contract == 'ITTCO') echo 'selected'; ?>>ITTCO</option>
                <option value="ITEC" <?php if ($contract == 'ITEC') echo 'selected'; ?>>ITEC</option>
                <option value="G.E.P.S" <?php if ($contract == 'G.E.P.S') echo 'selected'; ?>>G.E.P.S</option>
            </select>
        </label></p>
        <p><label>Position: 
            <select name="position">
                <option value="ACCOUNTANT" <?php if ($position == 'ACCOUNTANT') echo 'selected'; ?>>ACCOUNTANT</option>
                <option value="MANAGING DIRECTOR" <?php if ($position == 'MANAGING DIRECTOR') echo 'selected'; ?>>MANAGING DIRECTOR</option>
                <option value="BUSINESS MANAGER" <?php if ($position == 'BUSINESS MANAGER') echo 'selected'; ?>>BUSINESS MANAGER</option>
                <option value="PROJECT MANAGER" <?php if ($position == 'PROJECT MANAGER') echo 'selected'; ?>>PROJECT MANAGER</option>
                <option value="EMPLOYEE" <?php if ($position == 'EMPLOYEE') echo 'selected'; ?>>EMPLOYEE</option>
            </select>
        </label></p>
        <p><button type="submit">Update</button></p>
    </form>
</body>
</html>

<?php
$conn->close();
?>
