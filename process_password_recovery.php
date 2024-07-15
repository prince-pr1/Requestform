<?php
require 'vendor\autoload.php'; // Make sure this is the correct path to your autoload.php file

use Mailgun\Mailgun;

include('config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // Check if the email exists in the database
    $query = "SELECT user_id FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($user_id);
    
    if ($stmt->fetch()) {
        // Generate a new random password
        $new_password = bin2hex(random_bytes(4)); // Generates a random 8-character password
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        // Update the password in the database
        $stmt->close();
        $query = "UPDATE users SET password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $hashed_password, $user_id);
        $stmt->execute();
        $stmt->close();
       
        // Send the new password to the user's email
        $mgClient = Mailgun::create('79c19fafafa6adca548e6fc7514fa938-8a084751-dc23ae9a'); // Replace with your Mailgun API key
        $domain = "sandbox18cda1b6121545c9a84f829acfecfaf4.mailgun.org";
        $params = [
            'from'    => 'Support <support@sandbox18cda1b6121545c9a84f829acfecfaf4.mailgun.org>',
            'to'      => $email,
            'subject' => 'Password Recovery',
            'text'    => "Your new password is: $new_password"
        ];

        $mgClient->messages()->send($domain, $params);

        $success = "A new password has been sent to your email.";
    } else {
        $error = "No account found with that email.";
    }

    $stmt->close();
    $conn->close();
}

if (isset($error)) {
    header("Location: password_recover.php?error=" . urlencode($error));
} else {
    header("Location: password_recover.php?success=" . urlencode($success));
}
exit();
?>
