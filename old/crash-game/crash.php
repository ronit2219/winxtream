<?php
// Include authentication and game header
require_once '../includes/game_header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WinXtream - Crash Game</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="game-container">
        <nav class="navbar">
            <div class="logo-section">
                <div class="logo"><img src="/Home-Page/Asset/WinXStream-removebg-preview.png" alt="WinXtream"></div>
                <div class="nav-links">
                    <a href="/index.php">Home</a>
                    <a href="/roulette/roullete.php">Roulette</a>
                    <a href="/mines/mines.php">Mines</a>
                    <a href="#" class="active">Crash</a>
                    <a href="/ColorTrad/color-trade.php">Color Trading</a>
                </div>
            </div>
            <div class="wallet-section">
                <div class="wallet-balance">
                    <svg class="wallet-icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path
                            d="M12.136.326A1.5 1.5 0 0 1 14 1.78V3h.5A1.5 1.5 0 0 1 16 4.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-9a1.5 1.5 0 0 1 1.432-1.499L12.136.326zM5.562 3H13V1.78a.5.5 0 0 0-.621-.484L5.562 3zM1.5 4a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-13z" />
                    </svg>
                    $<span id="wallet-display"><?php echo number_format($wallet_balance, 2); ?></span>
                </div>
                <div class="wallet-balance">
                    Last Win: $<span id="last-win">0</span>
                </div>
            </div>
        </nav>

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