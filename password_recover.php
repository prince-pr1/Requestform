<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles/login.css">
    <title>Password Recovery</title>
</head>
<body>
  <form method="POST" action="process_password_recovery.php">
    <div class="login-container">
        <div class="login-box">
            <div class="logos">
                <img src="Requestform/userAuth/images/Geps_LOGO.jpg" alt="Logo 1">
                <img src="images/ITEC_LOGO.png" alt="Logo 2">
                <img src="images/ITTCO_LOGO.png" alt="Logo 3">
            </div>
            <php
            <?php if (isset($_GET['error'])): ?>
                <div>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['success'])): ?>
                <div><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
                <button type="submit">Recover Password</button>
            </form>
        </div>
    </div>
</body>
</html>
