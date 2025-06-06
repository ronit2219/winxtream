<?php
require_once '../includes/auth_functions.php';

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm-password'] ?? '';
    
    // Debug info
    error_log("Signup attempt - Username: $username, Email: $email");
    
    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'All fields are required';
        error_log("Signup error: empty fields");
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
        error_log("Signup error: passwords don't match");
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
        error_log("Signup error: password too short");
    } else {
        // Register user
        $result = registerUser($username, $email, $password);
        error_log("Register result: " . json_encode($result));
        
        if ($result['status']) {
            // Log the user in
            $login = loginUser($username, $password);
            error_log("Auto-login result: " . json_encode($login));
            
            if ($login['status']) {
                // Redirect to home page
                header('Location: ../index.php');
                exit;
            } else {
                $success = 'Registration successful. Please <a href="login.php">login</a>.';
            }
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WinXtream - Sign Up</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="signup.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo-section">
            <div class="logo">WinXtream</div>
            <div class="nav-links">
                <a href="/index.php">Home</a>
                <a href="/roulette/roullete.php">Roulette</a>
                <a href="/mines/mines.php">Mines</a>
                <a href="/crash-game/crash.php">Crash</a>
                <a href="/ColorTrad/color-trade.php">Color Trading</a>
            </div>
        </div>
        <div class="wallet-section">
            <div class="auth-buttons">
                <a href="login.php" id="login-btn" class="nav-button">Login</a>
                <a href="signup.php" id="signup-btn" class="nav-button signup-btn active">Signup</a>
            </div>
        </div>
    </nav>

    <div class="auth-container">
        <div class="auth-header">
            <h2>Create an Account</h2>
        </div>
        
        <?php if (!empty($success)): ?>
            <div class="success-message" style="display: block; color: green; text-align: center; margin-bottom: 15px;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form class="auth-form" id="signup-form" method="POST" action="signup.php">
            <?php if (!empty($error)): ?>
                <div class="error-message" style="display: block; text-align: center; margin-bottom: 15px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
                <div class="error-message" id="username-error" style="display: none;">Username already exists</div>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm-password">Confirm Password</label>
                <input type="password" id="confirm-password" name="confirm-password" required>
                <div class="error-message" id="password-error" style="display: none;">Passwords do not match</div>
            </div>
            <button type="submit" class="auth-btn">Sign Up</button>
            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
        </form>
    </div>
</body>
</html> 