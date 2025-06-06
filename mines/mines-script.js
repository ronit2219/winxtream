document.addEventListener('DOMContentLoaded', () => {
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
        minesGrid.innerHTML = '';
        for (let i = 0; i < totalCells; i++) {
            const cell = document.createElement('div');
            cell.className = 'mine-cell';
            cell.dataset.index = i;
            cell.addEventListener('click', () => handleCellClick(i));
            minesGrid.appendChild(cell);
        }
    }

    // Start game
    async function startGame() {
        const betAmount = parseFloat(betAmountInput.value);

        if (isNaN(betAmount) || betAmount <= 0) {
            showNotification('Please enter a valid bet amount', true);
            return;
        }

        // Place bet using wallet API
        const betSuccess = await placeBet(betAmount, 'mines');
        
        if (!betSuccess) {
            return; // placeBet function already shows error notification
        }

        // Set game state
        currentBet = betAmount;
        profit = 0;
        multiplier = 1;
        gameActive = true;
        revealedCells = 0;

        // Generate mine positions
        minePositions = generateMinePositions();

        // Update UI
        updateStats();
        createGameGrid();

        // Enable/disable buttons
        startGameBtn.disabled = true;
        betAmountInput.disabled = true;
        cashoutBtn.disabled = false;

        showNotification('Game started! Find the diamonds and avoid the mines.');
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

    // Handle cell click
    function handleCellClick(index) {
        if (!gameActive) return;

        try {
            const cells = document.querySelectorAll('.mine-cell');
            if (!cells || cells.length === 0) {
                console.error('No mine cells found in the DOM');
                return;
            }
            
            const cell = cells[index];
            if (!cell) {
                console.error('Cell not found at index:', index);
                return;
            }

            // Check if cell has a mine
            const hasMine = minePositions.includes(index);

            if (hasMine) {
                // Reveal the mine
                cell.classList.add('revealed', 'mine');

                // Add explosion animation
                const explosion = document.createElement('div');
                explosion.className = 'explosion';
                cell.appendChild(explosion);

                // Add mine icon
                const mineIcon = document.createElement('div');
                mineIcon.className = 'cell-content';
                mineIcon.innerHTML = '💣';
                cell.appendChild(mineIcon);

                // Force a reflow/repaint to ensure animations trigger
                void cell.offsetWidth;

                // Game over
                gameOver(false);
            } else {
                // Reveal diamond
                cell.classList.add('revealed', 'diamond');

                // Add diamond shine animation
                const shine = document.createElement('div');
                shine.className = 'diamond-shine';
                cell.appendChild(shine);

                // Add diamond icon
                const diamondIcon = document.createElement('div');
                diamondIcon.className = 'cell-content';
                diamondIcon.innerHTML = '💎';
                cell.appendChild(diamondIcon);

                // Force a reflow/repaint to ensure animations trigger
                void cell.offsetWidth;

                // Update game state
                revealedCells++;
                updateMultiplier();

                // Add multiplier to cell
                const multiplierEl = document.createElement('div');
                multiplierEl.className = 'cell-multiplier';
                multiplierEl.textContent = `${multiplier.toFixed(2)}x`;
                cell.appendChild(multiplierEl);

                // Update current profit
                profit = currentBet * multiplier - currentBet;
                updateStats();

                // Check if all non-mine cells are revealed
                if (revealedCells === totalCells - mineCount) {
                    gameOver(true);
                }
            }
        } catch (error) {
            console.error('Error in handleCellClick:', error);
            showNotification('Error revealing card. Please refresh the page.', true);
        }
    }

    // Update multiplier based on revealed cells
    function updateMultiplier() {
        if (revealedCells < multiplierValues.length) {
            multiplier = multiplierValues[revealedCells];

            // Set next multiplier preview
            let nextMultiplierValue = revealedCells + 1 < multiplierValues.length ?
                multiplierValues[revealedCells + 1] :
                multiplierValues[multiplierValues.length - 1];

            nextMultiplier.textContent = `${nextMultiplierValue.toFixed(2)}x`;
        }
        updateStats();
    }

    // Cashout - collect current winnings
    async function cashout() {
        if (!gameActive) return;

        // Calculate winnings
        const winnings = currentBet * multiplier - currentBet;
        
        // Add winnings to wallet
        const cashoutSuccess = await addWinnings(winnings, 'mines', currentBet, multiplier);
        
        if (cashoutSuccess) {
            showNotification(`Successfully cashed out $${profit.toFixed(2)} profit!`);
            
            // Update last win display
            document.getElementById('last-win').textContent = profit.toFixed(2);
            
            gameOver(true, true);

            // Add to history
            addToHistory(true);
        } else {
            showNotification('Failed to cashout. Please try again.', true);
        }
    }

    // Game over
    async function gameOver(isWin, isCashout = false) {
        gameActive = false;

        try {
            // Reveal all mines if game lost
            if (!isWin && !isCashout) {
                const cells = document.querySelectorAll('.mine-cell');
                if (cells && cells.length > 0) {
                    minePositions.forEach(pos => {
                        if (pos >= 0 && pos < cells.length) {
                            const cell = cells[pos];
                            cell.classList.add('revealed', 'mine');
                            
                            // Add mine icon
                            const mineIcon = document.createElement('div');
                            mineIcon.className = 'cell-content';
                            mineIcon.innerHTML = '💣';
                            cell.appendChild(mineIcon);
                            
                            // Force a reflow/repaint to ensure animations trigger
                            void cell.offsetWidth;
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
                // Auto-cashout
                await addWinnings(profit, 'mines', currentBet, multiplier);
                
                const lastWinElement = document.getElementById('last-win');
                if (lastWinElement) {
                    lastWinElement.textContent = profit.toFixed(2);
                }
                
                showNotification(`Congratulations! You found all diamonds and won $${profit.toFixed(2)}!`);
                
                // Add to history
                addToHistory(true);
            }

            // Reset buttons
            startGameBtn.disabled = false;
            betAmountInput.disabled = false;
            cashoutBtn.disabled = true;
        } catch (error) {
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
        currentMultiplier.textContent = `${multiplier.toFixed(2)}x`;
        currentProfit.textContent = `$${profit.toFixed(2)}`;
        
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

    // Initialize game
    function init() {
        createGameGrid();
        startGameBtn.addEventListener('click', startGame);
        cashoutBtn.addEventListener('click', cashout);
    }

    // Start initialization
    init();
}); 