<?php
require_once '../includes/auth_functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Debug info
    error_log("Login attempt - Username: $username");
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $error = 'Both username and password are required';
        error_log("Login error: empty fields");
    } else {
        // Login user
        $result = loginUser($username, $password);
        error_log("Login result: " . json_encode($result));
        
        if ($result['status']) {
            // Redirect to home page
            header('Location: ../index.php');
            exit;
        } else {
            $error = $result['message'];
            error_log("Login failed: " . $error);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WinXtream - Login</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="login.css">
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
                <a href="login.php" id="login-btn" class="nav-button active">Login</a>
                <a href="signup.php" id="signup-btn" class="nav-button signup-btn">Signup</a>
            </div>
        </div>
    </nav>

    <div class="auth-container">
        <div class="auth-header">
            <h2>Login to Your Account</h2>
        </div>
        <form class="auth-form" id="login-form" method="POST" action="login.php">
            <?php if (!empty($error)): ?>
                <div class="error-message" style="display: block;"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <div class="error-message" id="login-error" style="display: none;">Invalid username or password</div>
            </div>
            <button type="submit" class="auth-btn">Login</button>
            <div class="auth-links">
                <p>Don't have an account? <a href="signup.php">Sign up</a></p>
            </div>
        </form>
    </div>
</body>
</html> 