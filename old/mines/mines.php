<?php
// Include authentication and game header
require_once '../includes/game_header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WinXtream - Mines Game</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="logo-section">
            <div class="logo"><img src="/Home-Page/Asset/WinXStream-removebg-preview.png" alt="WinXtream"></div>
            <div class="nav-links">
                <a href="/index.php">Home</a>
                <a href="/roulette/roullete.php">Roulette</a>
                <a href="#" class="active">Mines</a>
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
                $<span id="wallet-display"><?php echo number_format($wallet_balance, 2); ?></span>
            </div>
            <div class="wallet-balance">
                Last Win: $<span id="last-win">0</span>
            </div>
        </div>
    </nav>

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