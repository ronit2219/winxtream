<?php
// Include authentication and game header
require_once '../includes/game_header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WinXtream - Color Trading</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo-section">
            <div class="logo"><img src="/Home-Page/Asset/WinXStream-removebg-preview.png" alt="WinXtream"></div>
            <div class="nav-links">
                <a href="/index.php">Home</a>
                <a href="/roulette/roullete.php">Roulette</a>
                <a href="/mines/mines.php">Mines</a>
                <a href="/crash-game/crash.php">Crash</a>
                <a href="#" class="active">Color Trading</a>
            </div>
        </div>
        <div class="wallet-section">
            <div class="wallet-balance">
                <svg class="wallet-icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M12.136.326A1.5 1.5 0 0 1 14 1.78V3h.5A1.5 1.5 0 0 1 16 4.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-9a1.5 1.5 0 0 1 1.432-1.499L12.136.326zM5.562 3H13V1.78a.5.5 0 0 0-.621-.484L5.562 3zM1.5 4a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-13z"/>
                </svg>
                $<span id="wallet-display"><?php echo number_format($wallet_balance, 2); ?></span>
            </div>
            <div class="wallet-balance">
                Last Win: $<span id="last-win">0</span>
            </div>
        </div>
    </nav>

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

    <!-- Add wallet.js for database integration with cache busting -->
    <script src="/includes/wallet.js?v=<?php echo time(); ?>"></script>
    <script src="script.js"></script>
</body>
</html> 