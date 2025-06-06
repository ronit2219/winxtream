<?php
// Include authentication and game header
require_once '../includes/game_header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WinXtream - Mines Game (Debug)</title>
    <link rel="stylesheet" href="style.css">
    <style>
        #debug-console {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 150px;
            background-color: rgba(0, 0, 0, 0.8);
            color: #00ff00;
            overflow-y: auto;
            font-family: monospace;
            padding: 10px;
            z-index: 9999;
            border-top: 2px solid #00ff00;
        }
        .debug-entry {
            margin-bottom: 5px;
            border-bottom: 1px dashed #333;
        }
        .debug-error {
            color: #ff3333;
            font-weight: bold;
        }
        .close-debug {
            position: absolute;
            top: 5px;
            right: 10px;
            color: white;
            cursor: pointer;
        }
    </style>
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
                    Mines (Debug Mode)
                </div>
            </div>

            <!-- Game Content -->
            <div class="game-content">
                <!-- Bet Controls -->
                <div class="game-controls">
                    <div class="control-group">
                        <label class="control-label">BET AMOUNT</label>
                        <input type="number" id="bet-amount" class="control-input" placeholder="Enter bet amount"
                            min="1" value="10">
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

                <!-- Game History -->
                <div class="game-history">
                    <div class="history-header">GAME HISTORY</div>
                    <div class="history-content" id="history-content">
                        <!-- History will be populated dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Debug Console -->
    <div id="debug-console">
        <div class="close-debug" onclick="toggleDebugConsole()">×</div>
        <h3>Debug Console</h3>
        <div id="debug-content"></div>
    </div>

    <!-- Notification -->
    <div class="notification" id="notification"></div>

    <!-- Custom debug and fixed script -->
    <script>
    // Debug helpers
    function debug(message, isError = false) {
        const debugContent = document.getElementById('debug-content');
        if (!debugContent) return;

        const entry = document.createElement('div');
        entry.className = isError ? 'debug-entry debug-error' : 'debug-entry';
        entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
        debugContent.appendChild(entry);
        debugContent.scrollTop = debugContent.scrollHeight;
        
        // Also log to console
        isError ? console.error(message) : console.log(message);
    }

    function toggleDebugConsole() {
        const console = document.getElementById('debug-console');
        console.style.display = console.style.display === 'none' ? 'block' : 'none';
    }

    // Override console.log and console.error
    const originalConsoleLog = console.log;
    const originalConsoleError = console.error;
    
    console.log = function() {
        originalConsoleLog.apply(console, arguments);
        debug(Array.from(arguments).join(' '));
    };
    
    console.error = function() {
        originalConsoleError.apply(console, arguments);
        debug(Array.from(arguments).join(' '), true);
    };

    // Override fetch for debugging
    const originalFetch = window.fetch;
    window.fetch = async function() {
        debug(`Fetch request: ${arguments[0]}`);
        try {
            const response = await originalFetch.apply(window, arguments);
            const clonedResponse = response.clone();
            
            try {
                const data = await clonedResponse.json();
                debug(`Fetch response: ${JSON.stringify(data)}`);
            } catch (e) {
                debug(`Couldn't parse JSON response: ${e.message}`, true);
            }
            
            return response;
        } catch (error) {
            debug(`Fetch error: ${error.message}`, true);
            throw error;
        }
    };
    </script>

    <!-- Add wallet.js for database integration with cache busting -->
    <script src="/includes/wallet.js?v=<?php echo time(); ?>"></script>

    <!-- Custom fixed version with improved error handling -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        debug('Game initialized');
        
        // Game elements
        const minesGrid = document.getElementById('mines-grid');
        const nextMultiplier = document.getElementById('next-multiplier');
        const currentMultiplier = document.getElementById('current-multiplier');
        const currentProfit = document.getElementById('current-profit');
        const betAmountInput = document.getElementById('bet-amount');
        const startGameBtn = document.getElementById('start-game');
        const cashoutBtn = document.getElementById('cashout-btn');
        const historyContent = document.getElementById('history-content');

        // Game state
        let currentBet = 0;
        let profit = 0;
        let multiplier = 1;
        let gameActive = false;
        let revealedCells = 0;
        let minePositions = [];
        const totalCells = 25; // 5x5 grid
        const mineCount = 4;  // Number of mines
        const gameHistory = [];

        // Multiplier values - increase with each diamond revealed
        const multiplierValues = [
            1.00, 1.03, 1.06, 1.09, 1.12,
            1.18, 1.24, 1.30, 1.40, 1.54,
            1.70, 1.90, 2.20, 2.70, 3.30,
            4.10, 5.20, 6.70, 8.80, 12.00,
            17.00
        ];

        // Initialize game grid
        function createGameGrid() {
            debug('Creating game grid');
            minesGrid.innerHTML = '';
            for (let i = 0; i < totalCells; i++) {
                const cell = document.createElement('div');
                cell.className = 'mine-cell';
                cell.dataset.index = i;
                cell.addEventListener('click', (e) => {
                    debug(`Cell clicked: ${i}`);
                    e.preventDefault();
                    e.stopPropagation();
                    handleCellClick(i);
                    return false;
                });
                minesGrid.appendChild(cell);
            }
            debug('Game grid created with ' + totalCells + ' cells');
        }

        // Start game
        async function startGame() {
            debug('Starting game');
            const betAmount = parseFloat(betAmountInput.value);

            if (isNaN(betAmount) || betAmount <= 0) {
                showNotification('Please enter a valid bet amount', true);
                debug('Invalid bet amount', true);
                return;
            }

            try {
                // Disable buttons while processing
                startGameBtn.disabled = true;
                debug('Placing bet: $' + betAmount);
                
                // Place bet using wallet API with retry
                let betSuccess = false;
                let attempts = 0;
                const maxAttempts = 3;
                
                while (!betSuccess && attempts < maxAttempts) {
                    attempts++;
                    debug(`Bet attempt ${attempts}`);
                    
                    try {
                        betSuccess = await placeBet(betAmount, 'mines');
                        
                        if (!betSuccess) {
                            debug(`Bet attempt ${attempts} failed`);
                            if (attempts >= maxAttempts) {
                                debug('All bet attempts failed');
                                startGameBtn.disabled = false;
                                return;
                            }
                            await new Promise(resolve => setTimeout(resolve, 1000));
                        }
                    } catch (error) {
                        debug(`Bet error: ${error.message}`, true);
                        if (attempts >= maxAttempts) {
                            showNotification('Error placing bet. Please try again.', true);
                            startGameBtn.disabled = false;
                            return;
                        }
                        await new Promise(resolve => setTimeout(resolve, 1000));
                    }
                }
                
                if (!betSuccess) {
                    debug('Failed to place bet after retries', true);
                    startGameBtn.disabled = false;
                    return;
                }

                // Set game state
                debug('Bet successful, setting up game');
                currentBet = betAmount;
                profit = 0;
                multiplier = 1;
                gameActive = true;
                revealedCells = 0;

                // Generate mine positions
                minePositions = generateMinePositions();
                debug('Mine positions: ' + JSON.stringify(minePositions));

                // Update UI
                updateStats();
                createGameGrid();

                // Enable/disable buttons
                startGameBtn.disabled = true;
                betAmountInput.disabled = true;
                cashoutBtn.disabled = false;

                showNotification('Game started! Find the diamonds and avoid the mines.');
            } catch (error) {
                debug(`Error starting game: ${error.message}`, true);
                console.error('Error starting game:', error);
                startGameBtn.disabled = false;
                showNotification('Error starting game. Please try again.', true);
            }
        }

        // Generate random mine positions
        function generateMinePositions() {
            const positions = [];
            while (positions.length < mineCount) {
                const position = Math.floor(Math.random() * totalCells);
                if (!positions.includes(position)) {
                    positions.push(position);
                }
            }
            return positions;
        }

        // Handle cell click - fixed version
        function handleCellClick(index) {
            debug(`Handle cell click: ${index}, Game active: ${gameActive}`);
            if (!gameActive) {
                debug('Game not active, ignoring click');
                return;
            }

            try {
                const cells = document.querySelectorAll('.mine-cell');
                debug(`Found ${cells.length} cells`);
                
                if (!cells || cells.length === 0) {
                    debug('No mine cells found in the DOM', true);
                    return;
                }
                
                const cell = cells[index];
                if (!cell) {
                    debug(`Cell not found at index: ${index}`, true);
                    return;
                }

                // Prevent multiple clicks on the same cell
                if (cell.classList.contains('revealed')) {
                    debug('Cell already revealed, ignoring click');
                    return;
                }

                // Check if cell has a mine
                const hasMine = minePositions.includes(index);
                debug(`Cell ${index} has mine: ${hasMine}`);

                if (hasMine) {
                    // Reveal the mine
                    debug('Revealing mine');
                    cell.classList.add('revealed', 'mine');

                    // Create and append explosion element
                    const explosion = document.createElement('div');
                    explosion.className = 'explosion';
                    cell.appendChild(explosion);
                    debug('Added explosion element');

                    // Create and append mine icon
                    const mineIcon = document.createElement('div');
                    mineIcon.className = 'cell-content';
                    mineIcon.innerHTML = '💣';
                    cell.appendChild(mineIcon);
                    debug('Added mine icon element');

                    // Force reflow to ensure animations trigger
                    void cell.offsetWidth;

                    // Game over
                    gameOver(false);
                } else {
                    // Reveal diamond
                    debug('Revealing diamond');
                    cell.classList.add('revealed', 'diamond');

                    // Create and append shine element
                    const shine = document.createElement('div');
                    shine.className = 'diamond-shine';
                    cell.appendChild(shine);
                    debug('Added diamond shine element');

                    // Create and append diamond icon
                    const diamondIcon = document.createElement('div');
                    diamondIcon.className = 'cell-content';
                    diamondIcon.innerHTML = '💎';
                    cell.appendChild(diamondIcon);
                    debug('Added diamond icon element');

                    // Force reflow to ensure animations trigger
                    void cell.offsetWidth;

                    // Update game state
                    revealedCells++;
                    debug(`Revealed cells: ${revealedCells}`);
                    updateMultiplier();

                    // Create and append multiplier element
                    const multiplierEl = document.createElement('div');
                    multiplierEl.className = 'cell-multiplier';
                    multiplierEl.textContent = `${multiplier.toFixed(2)}x`;
                    cell.appendChild(multiplierEl);
                    debug('Added multiplier element');

                    // Update current profit
                    profit = currentBet * multiplier - currentBet;
                    updateStats();

                    // Check if all non-mine cells are revealed
                    if (revealedCells === totalCells - mineCount) {
                        debug('All non-mine cells revealed, game over (win)');
                        gameOver(true);
                    }
                }
            } catch (error) {
                debug(`Error in handleCellClick: ${error.message}`, true);
                console.error('Error in handleCellClick:', error);
                showNotification('Error revealing card. Please refresh the page.', true);
            }
        }

        // Update multiplier based on revealed cells
        function updateMultiplier() {
            debug(`Updating multiplier, revealed cells: ${revealedCells}`);
            if (revealedCells < multiplierValues.length) {
                multiplier = multiplierValues[revealedCells];
                debug(`New multiplier: ${multiplier}`);

                // Set next multiplier preview
                let nextMultiplierValue = revealedCells + 1 < multiplierValues.length ?
                    multiplierValues[revealedCells + 1] :
                    multiplierValues[multiplierValues.length - 1];

                if (nextMultiplier) {
                    nextMultiplier.textContent = `${nextMultiplierValue.toFixed(2)}x`;
                    debug(`Next multiplier: ${nextMultiplierValue}`);
                }
            }
            updateStats();
        }

        // Cashout - collect current winnings
        async function cashout() {
            debug('Cashout initiated');
            if (!gameActive) {
                debug('Game not active, cannot cashout');
                return;
            }

            try {
                // Disable cashout button to prevent double-clicks
                cashoutBtn.disabled = true;
                
                // Calculate winnings
                const winnings = currentBet * multiplier - currentBet;
                debug(`Attempting to cashout winnings: $${winnings}`);
                
                // Add winnings to wallet with retry
                let cashoutSuccess = false;
                let attempts = 0;
                const maxAttempts = 3;
                
                while (!cashoutSuccess && attempts < maxAttempts) {
                    attempts++;
                    debug(`Cashout attempt ${attempts}`);
                    
                    try {
                        cashoutSuccess = await addWinnings(winnings, 'mines', currentBet, multiplier);
                        
                        if (!cashoutSuccess) {
                            debug(`Cashout attempt ${attempts} failed`);
                            if (attempts >= maxAttempts) {
                                debug('All cashout attempts failed');
                                showNotification('Failed to cashout. Please try again.', true);
                                cashoutBtn.disabled = false;
                                return;
                            }
                            await new Promise(resolve => setTimeout(resolve, 1000));
                        }
                    } catch (error) {
                        debug(`Cashout error: ${error.message}`, true);
                        if (attempts >= maxAttempts) {
                            showNotification('Error during cashout. Please try again.', true);
                            cashoutBtn.disabled = false;
                            return;
                        }
                        await new Promise(resolve => setTimeout(resolve, 1000));
                    }
                }
                
                if (cashoutSuccess) {
                    debug('Cashout successful');
                    showNotification(`Successfully cashed out $${profit.toFixed(2)} profit!`);
                    
                    // Update last win display
                    const lastWinElement = document.getElementById('last-win');
                    if (lastWinElement) {
                        lastWinElement.textContent = profit.toFixed(2);
                        debug(`Updated last win to: $${profit.toFixed(2)}`);
                    }
                    
                    // Make sure balance is updated
                    await fetchCurrentBalance();
                    
                    gameOver(true, true);
                    
                    // Add to history
                    addToHistory(true);
                } else {
                    debug('Cashout failed after retries', true);
                    showNotification('Failed to cashout. Please try again.', true);
                    cashoutBtn.disabled = false;
                }
            } catch (error) {
                debug(`Error in cashout function: ${error.message}`, true);
                console.error('Error in cashout:', error);
                showNotification('An error occurred during cashout. Please try again.', true);
                cashoutBtn.disabled = false;
            }
        }

        // Game over
        async function gameOver(isWin, isCashout = false) {
            debug(`Game over - isWin: ${isWin}, isCashout: ${isCashout}`);
            gameActive = false;

            try {
                // Reveal all mines if game lost
                if (!isWin && !isCashout) {
                    debug('Revealing all mines');
                    const cells = document.querySelectorAll('.mine-cell');
                    debug(`Found ${cells.length} cells for revealing mines`);
                    
                    if (cells && cells.length > 0) {
                        minePositions.forEach(pos => {
                            if (pos >= 0 && pos < cells.length) {
                                const cell = cells[pos];
                                
                                // Skip if already revealed
                                if (cell.classList.contains('revealed')) {
                                    debug(`Mine at position ${pos} already revealed, skipping`);
                                    return;
                                }
                                
                                cell.classList.add('revealed', 'mine');
                                
                                // Add mine icon
                                const mineIcon = document.createElement('div');
                                mineIcon.className = 'cell-content';
                                mineIcon.innerHTML = '💣';
                                cell.appendChild(mineIcon);
                                
                                // Force reflow
                                void cell.offsetWidth;
                                debug(`Revealed mine at position ${pos}`);
                            }
                        });
                    }
                    
                    // Show game over message
                    showNotification('Game Over! You hit a mine.', true);
                    
                    // Add to history
                    addToHistory(false);
                }

                // If won but not cashed out (all diamonds found)
                if (isWin && !isCashout) {
                    debug('Auto-cashout for win');
                    // Auto-cashout with retry
                    let winSuccess = false;
                    let attempts = 0;
                    const maxAttempts = 3;
                    
                    while (!winSuccess && attempts < maxAttempts) {
                        attempts++;
                        debug(`Auto-cashout attempt ${attempts}`);
                        
                        try {
                            winSuccess = await addWinnings(profit, 'mines', currentBet, multiplier);
                            
                            if (!winSuccess) {
                                debug(`Auto-cashout attempt ${attempts} failed`);
                                if (attempts >= maxAttempts) {
                                    debug('All auto-cashout attempts failed');
                                    break;
                                }
                                await new Promise(resolve => setTimeout(resolve, 1000));
                            }
                        } catch (error) {
                            debug(`Auto-cashout error: ${error.message}`, true);
                            if (attempts >= maxAttempts) break;
                            await new Promise(resolve => setTimeout(resolve, 1000));
                        }
                    }
                    
                    if (winSuccess) {
                        debug('Auto-cashout successful');
                        const lastWinElement = document.getElementById('last-win');
                        if (lastWinElement) {
                            lastWinElement.textContent = profit.toFixed(2);
                            debug(`Updated last win to: $${profit.toFixed(2)}`);
                        }
                        
                        // Make sure balance is updated
                        await fetchCurrentBalance();
                        
                        showNotification(`Congratulations! You found all diamonds and won $${profit.toFixed(2)}!`);
                    } else {
                        debug('Auto-cashout failed', true);
                        showNotification('You found all diamonds, but there was an error processing your winnings.', true);
                    }
                    
                    // Add to history regardless of payment success
                    addToHistory(true);
                }

                // Reset buttons
                debug('Resetting buttons');
                startGameBtn.disabled = false;
                betAmountInput.disabled = false;
                cashoutBtn.disabled = true;
            } catch (error) {
                debug(`Error in gameOver: ${error.message}`, true);
                console.error('Error in gameOver:', error);
                showNotification('Error completing game. Please refresh the page.', true);
                
                // Reset buttons in case of error
                startGameBtn.disabled = false;
                betAmountInput.disabled = false;
                cashoutBtn.disabled = true;
            }
        }

        // Add game to history
        function addToHistory(isWin) {
            debug(`Adding to history: ${isWin ? 'win' : 'loss'}`);
            const historyItem = {
                result: isWin ? 'win' : 'loss',
                bet: currentBet,
                multiplier: multiplier,
                profit: isWin ? profit : -currentBet
            };
            
            gameHistory.unshift(historyItem);
            
            // Keep history limited to 10 items
            if (gameHistory.length > 10) {
                gameHistory.pop();
            }
            
            updateHistory();
        }

        // Update history display
        function updateHistory() {
            debug('Updating history display');
            historyContent.innerHTML = '';
            
            gameHistory.forEach(item => {
                const historyItem = document.createElement('div');
                historyItem.className = `history-item ${item.result}`;
                
                historyItem.innerHTML = `
                    <div class="history-result">${item.result.toUpperCase()}</div>
                    <div class="history-details">
                        <div>Bet: $${item.bet.toFixed(2)}</div>
                        <div>Multiplier: ${item.multiplier.toFixed(2)}x</div>
                        <div class="${item.result}">Profit: ${item.profit >= 0 ? '+' : ''}$${item.profit.toFixed(2)}</div>
                    </div>
                `;
                
                historyContent.appendChild(historyItem);
            });
        }

        // Update game stats
        function updateStats() {
            debug('Updating game stats');
            if (currentMultiplier) currentMultiplier.textContent = `${multiplier.toFixed(2)}x`;
            if (currentProfit) currentProfit.textContent = `$${profit.toFixed(2)}`;
            
            if (currentProfit) {
                if (profit > 0) {
                    currentProfit.classList.add('positive');
                    currentProfit.classList.remove('negative');
                } else if (profit < 0) {
                    currentProfit.classList.add('negative');
                    currentProfit.classList.remove('positive');
                } else {
                    currentProfit.classList.remove('positive', 'negative');
                }
            }
        }

        // Show notification
        function showNotification(message, isError = false) {
            debug(`Notification: ${message} (${isError ? 'error' : 'info'})`);
            const notification = document.getElementById('notification');
            if (!notification) {
                debug('Notification element not found', true);
                return;
            }
            
            notification.textContent = message;
            notification.className = 'notification';
            notification.classList.add(isError ? 'error' : 'success');
            notification.style.display = 'block';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        // Initialize game
        function init() {
            debug('Initializing game');
            createGameGrid();
            
            if (startGameBtn) startGameBtn.addEventListener('click', () => {
                debug('Start game button clicked');
                startGame();
            });
            
            if (cashoutBtn) cashoutBtn.addEventListener('click', () => {
                debug('Cashout button clicked');
                cashout();
            });
            
            debug('Game initialization complete');
        }

        // Start initialization
        init();
    });
    </script>
</body>

</html> 