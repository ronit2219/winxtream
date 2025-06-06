<?php
// Include authentication and game header
require_once '../includes/game_header.php';
$show_last_win = true; // Enable last win display for this game
$hideUsernameAndLogout = true; // Hide username and logout button
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WinXtream - Color Trading</title>
    <?php require_once '../fix-mobile.php'; ?>
    <link rel="stylesheet" href="style.css">
    <!-- Add responsive CSS -->
    <link rel="stylesheet" href="/shared-responsive.css">
</head>
<body>
    <!-- Include common navigation bar outside main content -->
    <?php require_once '../includes/navigation.php'; ?>

    <main>
        <div class="game-header">
            <h1 class="game-title">Color Trading</h1>
            <p class="game-description">Bet on your favorite color and win big! Every 10 seconds, a new round begins. Choose wisely!</p>
        </div>

        <div class="game-container">
            <div class="timer-section">
                <div class="timer">
                    <span>Next Round:</span>
                    <span class="timer-value" id="timer">10</span>
                </div>
                <div class="last-round">
                    <span>Last Round:</span>
                    <span class="last-color" id="lastRoundColor">-</span>
                </div>
            </div>

            <div class="color-buttons">
                <button class="color-btn green-btn" id="greenBtn">Green</button>
                <button class="color-btn red-btn" id="redBtn">Red</button>
                <button class="color-btn violet-btn" id="violetBtn">Violet</button>
            </div>

            <div class="bet-section">
                <div class="input-group">
                    <input type="number" class="bet-input" id="betAmount" placeholder="Enter bet amount" min="1">
                    <button class="bet-btn" id="betButton">Place Bet</button>
                </div>
                <div class="result" id="result"></div>
            </div>
        </div>
    </main>

    <!-- Notification -->
    <div class="notification" id="notification"></div>

    <!-- Add shared responsive JavaScript file -->
    <script src="/shared-responsive.js"></script>
    <!-- Add wallet.js for database integration with cache busting -->
    <script src="/includes/wallet.js?v=<?php echo time(); ?>"></script>
    <script src="script.js"></script>
</body>
</html> 