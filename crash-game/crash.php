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
    <title>WinXtream - Crash Game</title>
    <?php require_once '../fix-mobile.php'; ?>
    <link rel="stylesheet" href="style.css">
    <!-- Add responsive CSS -->
    <link rel="stylesheet" href="/shared-responsive.css">
</head>

<body>
    <!-- Include common navigation bar outside the game container -->
    <?php require_once '../includes/navigation.php'; ?>
    
    <div class="game-container">
        <div class="game-header">
            <h2 class="game-title">Crash</h2>
            <span class="game-description">Try to cash out before the rocket crashes!</span>
        </div>

        <div class="game-area">
            <div class="game-grid">
                <div class="game-display">
                    <div class="multiplier">1.00x</div>
                    <div class="game-status">READY</div>

                    <div class="stars"></div>

                    <div class="rocket-container">
                        <div class="rocket">
                            <div class="rocket-body"></div>
                            <div class="rocket-tip"></div>
                            <div class="rocket-window"></div>
                            <div class="rocket-fin-left"></div>
                            <div class="rocket-fin-right"></div>
                            <div class="flame"></div>
                        </div>
                    </div>

                    <div class="explosion"></div>
                </div>

                <div class="controls">
                    <div class="bet-controls">
                        <h3>PLACE BET</h3>
                        <div class="input-group">
                            <label for="bet-amount">Bet Amount</label>
                            <input type="number" id="bet-amount" min="1" value="10">
                        </div>
                    </div>

                    <div class="game-controls">
                        <div class="controls-flex">
                            <div>
                                <button class="bet-btn" id="bet-btn">PLACE BET</button>
                            </div>
                            <div>
                                <button class="cashout-btn" id="cashout-btn" disabled>CASHOUT</button>
                            </div>
                        </div>
                    </div>

                    <div class="history">
                        <h3>RECENT GAMES</h3>
                        <div class="history-items" id="history">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="notification" id="notification"></div>
    </div>

    <!-- Add shared responsive JavaScript file -->
    <script src="/shared-responsive.js"></script>
    <!-- Add debugging script -->
    <script>
    // Log all network requests
    const originalFetch = window.fetch;
    window.fetch = function() {
        console.log('FETCH REQUEST URL:', arguments[0]);
        console.log('FETCH REQUEST PARAMS:', arguments[1]);
        return originalFetch.apply(this, arguments);
    };
    </script>

    <!-- Add wallet.js for database integration with cache busting -->
    <script src="/includes/wallet.js?v=<?php echo time(); ?>"></script>
    <script src="script.js"></script>
</body>

</html> 