<?php
require_once 'includes/auth_functions.php';
require_once 'includes/db_connect.php';

// Get current user
$user = getCurrentUser();
$isLoggedIn = isLoggedIn();
$wallet_balance = $isLoggedIn ? ($_SESSION['balance'] ?? 1000) : 1000;

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
    <link rel="stylesheet" href="responsive.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .add-money-btn {
            display: inline-block;
            margin-left: 5px;
            width: 20px;
            height: 20px;
            background-color: #4CAF50;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 20px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .add-money-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>

<body>
    <?php require_once 'includes/navigation.php'; ?>

    <!-- Payment Success Message -->
    <?php if (isset($_SESSION['payment_success'])): ?>
    <div class="payment-success-message" style="max-width: 800px; margin: 20px auto; padding: 15px; background-color: rgba(76, 175, 80, 0.1); color: #4CAF50; border-radius: 5px; text-align: center;">
        <i class="fas fa-check-circle" style="margin-right: 10px;"></i>
        <?php echo $_SESSION['payment_success']; ?>
        <?php unset($_SESSION['payment_success']); ?>
    </div>
    <?php endif; ?>

    <!-- Game Cards Container -->
    <div class="game-cards-container games-container">
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
            <div class="footer-section">
                <p>Contact Us: support@winxtream.com</p>
                <p>Customer Support: +1 (555) 123-4567</p>
            </div>
            <div class="footer-section">
                &copy; 2024 WinXtream. All Rights Reserved.
            </div>
        </div>
    </footer>

    <!-- JavaScript for mobile menu -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const navMenu = document.getElementById('nav-menu');
            
            if (mobileMenuToggle && navMenu) {
                mobileMenuToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                    
                    // Change icon based on menu state
                    const icon = mobileMenuToggle.querySelector('i');
                    if (navMenu.classList.contains('active')) {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-times');
                    } else {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                });
                
                // Close menu when clicking on a link
                const navLinks = navMenu.querySelectorAll('a');
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        navMenu.classList.remove('active');
                        const icon = mobileMenuToggle.querySelector('i');
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    });
                });
            }
        });
    </script>
</body>
</html> 