<?php
require_once 'includes/auth_functions.php';

// Get current user
$user = getCurrentUser();
$isLoggedIn = isLoggedIn();
$balance = $isLoggedIn ? ($_SESSION['balance'] ?? 1000) : 1000;

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
    header('Location: Home-Page/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WinXtream - Gaming Platform</title>
    <link rel="stylesheet" href="Home-Page/style.css">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo-section">
            <div class="logo"><img src="/Home-Page/Asset/WinXStream-removebg-preview.png" alt="WinXtream"></div>
            <div class="nav-links">
                <a href="#" class="active">Home</a>
                <a href="/roulette/roullete.php">Roulette</a>
                <a href="/mines/mines.php">Mines</a>
                <a href="/crash-game/crash.php">Crash</a>
                <a href="/ColorTrad/color-trade.php">Color Trading</a>
            </div>
        </div>
        <div class="wallet-section">
            <div class="wallet-balance">
                <svg class="wallet-icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path
                        d="M12.136.326A1.5 1.5 0 0 1 14 1.78V3h.5A1.5 1.5 0 0 1 16 4.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-9a1.5 1.5 0 0 1 1.432-1.499L12.136.326zM5.562 3H13V1.78a.5.5 0 0 0-.621-.484L5.562 3zM1.5 4a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-13z" />
                </svg>
                $<span id="wallet-balance"><?php echo number_format($balance, 2); ?></span>
            </div>
            <?php if ($isLoggedIn && $user): ?>
                <div class="user-info" style="display: flex; align-items: center;">
                    <span id="username-display"><?php echo htmlspecialchars($user['username'] ?? 'Guest'); ?></span>
                    <a href="?logout=1" id="logout-btn" class="nav-button">Logout</a>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="Home-Page/login.php" id="login-btn" class="nav-button">Login</a>
                    <a href="Home-Page/signup.php" id="signup-btn" class="nav-button signup-btn">Signup</a>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Game Cards Container -->
    <div class="game-cards-container">
        <!-- Game Card 1 -->
        <div class="game-card">
            <a href="/mines/mines.php">
                <img src="/Home-Page/Asset/mines.webp" alt="Mines Game">
                <div class="play-overlay">
                    <div class="play-icon">▶</div>
                </div>
                <div class="game-card-content">
                    <h3>Mines Game</h3>
                </div>
            </a>
        </div>

        <!-- Game Card 2 -->
        <div class="game-card">
            <a href="/crash-game/crash.php">
                <img src="/Home-Page/Asset/com.pokerjogo.crash.icon.2023-03-11-09-35-58.png" alt="Crash Game">
                <div class="play-overlay">
                    <div class="play-icon">▶</div>
                </div>
                <div class="game-card-content">
                    <h3>Crash Game</h3>
                </div>
            </a>
        </div>

        <!-- Game Card 3 -->
        <div class="game-card">
            <a href="/ColorTrad/color-trade.php">
                <img src="/Home-Page/Asset/compressed_3d5c4402a3f81b12a1c1dad477d6763d.webp" alt="Color Trading Game">
                <div class="play-overlay">
                    <div class="play-icon">▶</div>
                </div>
                <div class="game-card-content">
                    <h3>Color Trading Game</h3>
                </div>
            </a>
        </div>

        <!-- Game Card 4 -->
        <div class="game-card">
            <a href="/roulette/roullete.php">
                <img src="/Home-Page/Asset/512x512bb.jpg" alt="Roulette Game">
                <div class="play-overlay">
                    <div class="play-icon">▶</div>
                </div>
                <div class="game-card-content">
                    <h3>Roulette Game</h3>
                </div>
            </a>
        </div>

        <div class="game-card">
            <a href="#">
                <img src="/Home-Page/Asset/coming-soon-pop-style-typography-text_7102-293.avif" alt="Ludo Game">
                <div class="play-overlay">
                    <div class="play-icon">▶</div>
                </div>
                <div class="game-card-content">
                    <h3>Ludo Game</h3>
                </div>
            </a>
        </div>
        <div class="game-card">
            <a href="#">
                <img src="/Home-Page/Asset/coming-soon-pop-style-typography-text_7102-293.avif" alt="Dice Roll Game">
                <div class="play-overlay">
                    <div class="play-icon">▶</div>
                </div>
                <div class="game-card-content">
                    <h3>Dice Roll Game</h3>
                </div>
            </a>
        </div>
        <div class="game-card">
            <a href="#">
                <img src="/Home-Page/Asset/coming-soon-pop-style-typography-text_7102-293.avif" alt="Bingo Game">
                <div class="play-overlay">
                    <div class="play-icon">▶</div>
                </div>
                <div class="game-card-content">
                    <h3>Bingo Game</h3>
                </div>
            </a>
        </div>
        <div class="game-card">
            <a href="#">
                <img src="/Home-Page/Asset/coming-soon-pop-style-typography-text_7102-293.avif" alt="Lucky Slots Game">
                <div class="play-overlay">
                    <div class="play-icon">▶</div>
                </div>
                <div class="game-card-content">
                    <h3>Lucky Slots Game</h3>
                </div>
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div>
                <p>Contact Us: support@winxtream.com</p>
                <p>Customer Support: +1 (555) 123-4567</p>
            </div>
            <div>
                &copy; 2024 WinXtream. All Rights Reserved.
            </div>
        </div>
    </footer>
</body>
</html> 