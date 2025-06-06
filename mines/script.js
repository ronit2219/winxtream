document.addEventListener('DOMContentLoaded', () => {
    // Game elements
    const minesGrid = document.getElementById('mines-grid');
    const walletDisplay = document.getElementById('wallet-display');
    const nextMultiplier = document.getElementById('next-multiplier');
    const currentMultiplier = document.getElementById('current-multiplier');
    const currentProfit = document.getElementById('current-profit');
    const betAmountInput = document.getElementById('bet-amount');
    const startGameBtn = document.getElementById('start-game');
    const cashoutBtn = document.getElementById('cashout-btn');
    const notificationEl = document.getElementById('notification');
    const lastWinEl = document.getElementById('last-win');

    // Game state
    let currentBet = 0;
    let profit = 0;
    let multiplier = 1;
    let gameActive = false;
    let revealedCells = 0;
    let minePositions = [];
    const totalCells = 25; // 5x5 grid
    const mineCount = 4;  // Number of mines

    // Multiplier values - increase with each diamond revealed
    const multiplierValues = [
        1.00, 1.03, 1.06, 1.09, 1.12,
        1.18, 1.24, 1.30, 1.40, 1.54,
        1.70, 1.90, 2.20, 2.70, 3.30,
        4.10, 5.20, 6.70, 8.80, 12.00,
        17.00
    ];

    // Show notification
    function showNotification(message, isError = false) {
        if (!notificationEl) return;
        
        notificationEl.textContent = message;
        notificationEl.className = 'notification';
        notificationEl.classList.add(isError ? 'error' : 'success');
        notificationEl.style.display = 'block';
        
        setTimeout(() => {
            notificationEl.style.display = 'none';
        }, 3000);
    }

    // Generate random mine positions
    function generateMinePositions() {
        console.log('Generating mine positions...');
        const positions = [];
        
        while (positions.length < mineCount) {
            const position = Math.floor(Math.random() * totalCells);
            if (!positions.includes(position)) {
                positions.push(position);
            }
        }
        
        console.log('Mine positions:', positions);
        return positions;
    }

    // Update multiplier based on revealed cells
    function updateMultiplier() {
        if (revealedCells < multiplierValues.length) {
            multiplier = multiplierValues[revealedCells];

            // Set next multiplier preview
            let nextMultiplierValue = revealedCells + 1 < multiplierValues.length ?
                multiplierValues[revealedCells + 1] :
                multiplierValues[multiplierValues.length - 1];

            if (nextMultiplier) {
                nextMultiplier.textContent = `${nextMultiplierValue.toFixed(2)}x`;
            }
        }
        updateStats();
    }

    // Update game stats
    function updateStats() {
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
    
    // Handle cell click - function for direct click handling
    function onCellClick(index) {
        console.log('Cell clicked:', index);
        handleCellClick(index);
    }

    // Initialize game grid
    function createGameGrid() {
        console.log('Creating game grid');
        
        // Clear existing grid
        if (minesGrid) {
            minesGrid.innerHTML = '';
        } else {
            console.error('minesGrid element not found!');
            return;
        }
        
        // Create new grid cells
        for (let i = 0; i < totalCells; i++) {
            const cell = document.createElement('div');
            cell.className = 'mine-cell';
            cell.dataset.index = i;
            
            // IMPORTANT: Using direct function reference instead of a closure
            cell.onclick = function() {
                onCellClick(parseInt(this.dataset.index));
            };
            
            minesGrid.appendChild(cell);
        }
        
        console.log('Grid created with', minesGrid.children.length, 'cells');
    }

    // Game over
    async function gameOver(isWin, isCashout = false) {
        gameActive = false;

        try {
            // Reveal all cells - both mines and diamonds
            revealAllCells();
            
            // Reveal all mines if game lost
            if (!isWin && !isCashout) {
                // Show game over message
                showNotification('Game Over! You hit a mine.', true);
                
                // Reset last win to 0 when player loses
                if (lastWinEl) {
                    lastWinEl.textContent = "0.00";
                }
            }

            // If won but not cashed out (all diamonds found)
            if (isWin && !isCashout) {
                // Auto-cashout
                const profit = currentBet * multiplier - currentBet;
                const totalAmount = currentBet * multiplier;
                
                console.log('Auto-cashout calculations:', {
                    currentBet: currentBet,
                    multiplier: multiplier,
                    totalAmount: totalAmount,
                    profit: profit
                });
                
                // Add the TOTAL amount to wallet with wallet.js
                // This should be bet + profit, since the bet was removed earlier
                const winSuccess = await addWinnings(totalAmount, 'mines', currentBet, multiplier);
                
                if (winSuccess) {
                    // Update last win display
                    if (lastWinEl) {
                        lastWinEl.textContent = profit.toFixed(2);
                    }
                    
                    showNotification(`Congratulations! You found all diamonds and won $${profit.toFixed(2)}!`);
                } else {
                    showNotification('You found all diamonds, but there was an error processing your winnings.', true);
                }
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
    
    // Function to reveal all cells
    function revealAllCells() {
        const cells = document.querySelectorAll('.mine-cell');
        
        // First reveal all cells that have mines
        minePositions.forEach(pos => {
            if (pos >= 0 && pos < cells.length) {
                const cell = cells[pos];
                
                // Skip if already revealed
                if (cell.classList.contains('revealed')) {
                    return;
                }
                
                cell.classList.add('revealed', 'mine');
                
                // Add mine icon
                const mineIcon = document.createElement('div');
                mineIcon.className = 'cell-content';
                mineIcon.innerHTML = '💣';
                cell.appendChild(mineIcon);
            }
        });
        
        // Then reveal all remaining cells (diamonds)
        for (let i = 0; i < cells.length; i++) {
            // Skip already revealed cells and mines
            if (cells[i].classList.contains('revealed') || minePositions.includes(i)) {
                continue;
            }
            
            const cell = cells[i];
            cell.classList.add('revealed', 'diamond');
            
            // Add diamond icon
            const diamondIcon = document.createElement('div');
            diamondIcon.className = 'cell-content';
            diamondIcon.innerHTML = '💎';
            cell.appendChild(diamondIcon);
        }
    }
    
    // Cashout - collect current winnings
    async function cashout() {
        if (!gameActive) {
            return;
        }

        try {
            // Disable cashout button to prevent double-clicks
            cashoutBtn.disabled = true;
            
            // Calculate profit (not including the original bet)
            const profit = currentBet * multiplier - currentBet;
            
            // Calculate total amount to return to wallet (bet + profit)
            const totalAmount = currentBet * multiplier;
            
            console.log('Cashout calculations:', {
                currentBet: currentBet,
                multiplier: multiplier,
                totalAmount: totalAmount,
                profit: profit
            });
            
            // Add the TOTAL amount to wallet with wallet.js
            // This should be bet + profit, since the bet was removed earlier
            const cashoutSuccess = await addWinnings(totalAmount, 'mines', currentBet, multiplier);
            
            if (cashoutSuccess) {
                showNotification(`Successfully cashed out $${profit.toFixed(2)} profit!`);
                
                // Update last win display
                if (lastWinEl) {
                    lastWinEl.textContent = profit.toFixed(2);
                }
                
                gameOver(true, true);
            } else {
                showNotification('Failed to cashout. Please try again.', true);
                cashoutBtn.disabled = false;
            }
        } catch (error) {
            console.error('Error in cashout:', error);
            showNotification('An error occurred during cashout. Please try again.', true);
            cashoutBtn.disabled = false;
        }
    }

    // Handle cell click - main logic
    function handleCellClick(index) {
        console.log('Processing click on cell', index);
        
        if (!gameActive) {
            console.log('Game not active, ignoring click');
            return;
        }

        try {
            const cells = document.querySelectorAll('.mine-cell');
            if (!cells || cells.length === 0) {
                console.error('No cells found in the DOM');
                return;
            }
            
            if (index < 0 || index >= cells.length) {
                console.error('Invalid cell index:', index);
                return;
            }
            
            const cell = cells[index];
            
            // Prevent clicking already revealed cells
            if (cell.classList.contains('revealed')) {
                console.log('Cell already revealed');
                return;
            }

            // Check if cell has a mine
            const hasMine = minePositions.includes(index);
            console.log('Cell has mine:', hasMine);

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
            showNotification('Error revealing cell. Please try again.', true);
        }
    }

    // Start game
    async function startGame() {
        console.log('Start game button clicked');
        const betAmount = parseFloat(betAmountInput.value);

        if (isNaN(betAmount) || betAmount <= 0) {
            showNotification('Please enter a valid bet amount', true);
            return;
        }

        // Disable buttons while processing
        startGameBtn.disabled = true;
        
        try {
            // Place bet using wallet.js
            console.log('Placing bet:', betAmount);
            const betSuccess = await placeBet(betAmount, 'mines');
            console.log('Bet result:', betSuccess);
            
            if (!betSuccess) {
                console.log('Bet failed');
                startGameBtn.disabled = false;
                return;
            }

            // Set game state
            currentBet = betAmount;
            profit = 0;
            multiplier = 1;
            revealedCells = 0;
            
            // Generate mine positions before activating game
            minePositions = generateMinePositions();
            
            // Update UI
            updateStats();
            
            // Create new grid
            createGameGrid();
            
            // Set game as active AFTER grid is created
            gameActive = true;
            
            // Enable/disable buttons
            startGameBtn.disabled = true;
            betAmountInput.disabled = true;
            cashoutBtn.disabled = false;

            showNotification('Game started! Find the diamonds and avoid the mines.');
            console.log('Game initialized, grid created, ready to play');
        } catch (error) {
            console.error('Error in startGame:', error);
            showNotification('Error starting game. Please try again.', true);
            startGameBtn.disabled = false;
        }
    }

    // Initialize game
    function init() {
        console.log('Initializing mines game');
        
        // Create initial empty grid
        createGameGrid();
        
        // Set up event listeners
        startGameBtn.addEventListener('click', startGame);
        cashoutBtn.addEventListener('click', cashout);
        
        // Initialize wallet display
        if (typeof fetchCurrentBalance === 'function') {
            fetchCurrentBalance();
        } else {
            console.error('fetchCurrentBalance function not found');
        }
        
        console.log('Mines game initialization complete');
    }

    // Start initialization
    init();
});