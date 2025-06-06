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
    <title>WinXtream - Mines Game</title>
    <?php require_once '../fix-mobile.php'; ?>
    <link rel="stylesheet" href="style.css">
    <!-- Add responsive CSS -->
    <link rel="stylesheet" href="/shared-responsive.css">
</head>

<body>
    <!-- Include common navigation bar outside the main container -->
    <?php require_once '../includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="main-container">
        <div class="game-container">
            <!-- Game Header -->
            <div class="game-header">
                <div class="game-title">
                    <span class="game-icon">💣</span>
                    Mines
                </div>
            </div>

            <!-- Game Content -->
            <div class="game-content">
                <!-- Bet Controls -->
                <div class="game-controls">
                    <div class="control-group">
                        <label class="control-label">BET AMOUNT</label>
                        <input type="number" id="bet-amount" class="control-input" placeholder="Enter bet amount"
                            min="1">
                    </div>

                    <div class="control-group">
                        <label class="control-label">ACTION</label>
                        <div class="control-buttons">
                            <button id="start-game" class="bet-btn">Start Game</button>
                            <button id="cashout-btn" class="bet-btn cashout-btn" disabled>Cashout</button>
                        </div>
                    </div>
                </div>

                <!-- Current Bet Info -->
                <div class="current-bet-info">
                    <div class="info-item">
                        <div class="info-label">MINES</div>
                        <div class="info-value">4</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">NEXT</div>
                        <div class="info-value" id="next-multiplier">1.03x</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">MULTIPLIER</div>
                        <div class="info-value" id="current-multiplier">1.00x</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">PROFIT</div>
                        <div class="info-value profit" id="current-profit">$0.00</div>
                    </div>
                </div>

                <!-- Mines Grid -->
                <div class="mines-grid" id="mines-grid">
                    <!-- Grid will be generated dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Notification -->
    <div class="notification" id="notification"></div>

    <!-- Add shared responsive JavaScript file -->
    <script src="/shared-responsive.js"></script>
    <!-- Add wallet.js for database integration with cache busting -->
    <script src="/includes/wallet.js?v=<?php echo time(); ?>"></script>
    <!-- Add debugging output -->
    <script>
    console.log('Mines game loading: mines.php');
    console.log('Current path:', window.location.pathname);
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM fully loaded');
        console.log('Wallet script loaded:', typeof placeBet === 'function');
        console.log('Mines grid element:', document.getElementById('mines-grid'));
    });
    </script>
    <script src="script.js"></script>
</body>

</html> 