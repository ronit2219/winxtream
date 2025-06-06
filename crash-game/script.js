document.addEventListener('DOMContentLoaded', () => {
    // DOM Elements
    const walletDisplay = document.getElementById('wallet-display');
    const multiplierDisplay = document.querySelector('.multiplier');
    const gameStatusDisplay = document.querySelector('.game-status');
    const betButton = document.getElementById('bet-btn');
    const cashoutButton = document.getElementById('cashout-btn');
    const betAmountInput = document.getElementById('bet-amount');
    const rocketContainer = document.querySelector('.rocket-container');
    const historyContainer = document.getElementById('history');
    const notificationEl = document.getElementById('notification');
    const explosion = document.querySelector('.explosion');
    const starsContainer = document.querySelector('.stars');
    const lastWinEl = document.getElementById('last-win');

    // Game State
    let gameState = {
        isPlaying: false,
        currentMultiplier: 1.00,
        betAmount: 0,
        gameHistory: [],
        maxHeight: 0,
        updateInterval: null,
        rocketPosition: 100, // Starting position higher
        crashPoint: 0,
        maxRocketHeight: 500 // Limit the rocket's maximum height
    };

    // Initialize stars
    function initStars() {
        for (let i = 0; i < 100; i++) {
            const star = document.createElement('div');
            star.classList.add('star');
            star.style.left = `${Math.random() * 100}%`;
            star.style.top = `${Math.random() * 100}%`;
            star.style.width = `${Math.random() * 3}px`;
            star.style.height = star.style.width;
            starsContainer.appendChild(star);
        }
    }

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

    // Function to check and log game state
    function checkGameState() {
        console.log('Current Game State:', {
            isPlaying: gameState.isPlaying,
            currentMultiplier: gameState.currentMultiplier,
            betAmount: gameState.betAmount,
            rocketPosition: gameState.rocketPosition,
            updateInterval: gameState.updateInterval ? 'Active' : 'Not active',
            gameStatusDisplay: gameStatusDisplay.textContent,
            betButtonDisabled: betButton.disabled,
            cashoutButtonDisabled: cashoutButton.disabled
        });
        
        // Check if updateInterval is working
        if (gameState.isPlaying && !gameState.updateInterval) {
            console.error('Game is playing but updateInterval is not set!');
            // Re-initialize the interval if needed
            if (gameState.isPlaying) {
                gameState.updateInterval = setInterval(updateGame, 100);
                console.log('Re-initialized the update interval');
            }
        }
        
        // Check rocket element visibility
        const rocketStyle = window.getComputedStyle(rocketContainer);
        console.log('Rocket visibility:', {
            opacity: rocketStyle.opacity,
            display: rocketStyle.display,
            bottom: rocketStyle.bottom,
            position: rocketStyle.position
        });
        
        // Ensure rocket is visible
        if (gameState.isPlaying && (rocketStyle.opacity === '0' || rocketStyle.display === 'none')) {
            rocketContainer.style.opacity = '1';
            rocketContainer.style.display = 'block';
            rocketContainer.style.bottom = `${gameState.rocketPosition}px`;
            console.log('Fixed rocket visibility');
        }
    }

    // Update game history
    function updateHistory(multiplier, isCrash) {
        const historyItem = document.createElement('div');
        historyItem.classList.add('history-item');

        if (isCrash) {
            historyItem.classList.add('history-crash');
        } else {
            historyItem.classList.add('history-cashout');
        }

        historyItem.textContent = multiplier.toFixed(2) + 'x';

        // Limit history to 10 items
        if (gameState.gameHistory.length >= 10) {
            gameState.gameHistory.pop();
            if (historyContainer.children.length >= 10) {
                historyContainer.removeChild(historyContainer.lastChild);
            }
        }

        gameState.gameHistory.unshift({ multiplier, isCrash });
        historyContainer.insertBefore(historyItem, historyContainer.firstChild);
    }

    // Generate a crash point with specific probabilities:
    // 30% chance between 1.00-1.50x
    // 50% chance between 1.50-3.00x
    // 20% chance between 3.00-8.00x
    function generateCrashPoint() {
        const random = Math.random();

        if (random < 0.3) {
            // 30% chance: 1.00-1.50x
            return 1.00 + (Math.random() * 0.5);
        } else if (random < 0.8) {
            // 50% chance: 1.50-3.00x
            return 1.50 + (Math.random() * 1.5);
        } else {
            // 20% chance: 3.00-8.00x
            return 3.00 + (Math.random() * 5);
        }
    }

    // Start game
    async function startGame() {
        try {
            const betAmount = parseFloat(betAmountInput.value);
            console.log('Starting crash game with bet amount:', betAmount);

            if (isNaN(betAmount) || betAmount <= 0) {
                showNotification('Please enter a valid bet amount', true);
                return;
            }

            // Disable bet button while processing to prevent double clicks
            betButton.disabled = true;
            gameStatusDisplay.textContent = 'PLACING BET...';
            console.log('Placing bet...');

            // Place bet using wallet.js
            const betSuccess = await placeBet(betAmount, 'crash');
            console.log('Bet placement result:', betSuccess);
            
            if (!betSuccess) {
                console.log('Bet placement failed, aborting game start');
                gameStatusDisplay.textContent = 'READY';
                betButton.disabled = false;
                return;
            }

            console.log('Bet placed successfully, setting up game state');
            gameState.betAmount = betAmount;

            // Generate crash point
            gameState.crashPoint = generateCrashPoint();
            console.log('Crash point:', gameState.crashPoint);

            // Clear any existing interval
            if (gameState.updateInterval) {
                clearInterval(gameState.updateInterval);
                gameState.updateInterval = null;
            }

            // Reset any previous game state
            gameState.currentMultiplier = 1.00;
            gameState.rocketPosition = 100; // Initial position
            gameState.isPlaying = true;
            
            // Update multiplier display immediately
            multiplierDisplay.textContent = `${gameState.currentMultiplier.toFixed(2)}x`;

            // Calculate max height based on viewport
            const gameDisplay = document.querySelector('.game-display');
            gameState.maxHeight = Math.min(gameDisplay.clientHeight - 150, gameState.maxRocketHeight);

            // Update UI
            betButton.disabled = true;
            cashoutButton.disabled = false;
            gameStatusDisplay.textContent = 'LAUNCHING';
            gameStatusDisplay.style.color = '#fff';
            
            // Make sure rocket is visible and reset to starting position
            rocketContainer.classList.remove('crash-animation');
            rocketContainer.style.transition = 'bottom 0.1s linear';
            rocketContainer.style.opacity = "1";
            rocketContainer.style.display = "block";
            rocketContainer.style.bottom = `${gameState.rocketPosition}px`;
            
            // Hide explosion effect
            explosion.style.opacity = '0';
            
            console.log('Game initialized, starting update interval');

            // Start updating the multiplier and rocket position
            gameState.updateInterval = setInterval(updateGame, 100);
            
            // Check game state to verify everything is initialized properly
            setTimeout(checkGameState, 500);
            
            // Also set a repeated check every second to ensure the game continues
            const gameCheckInterval = setInterval(() => {
                if (!gameState.isPlaying) {
                    clearInterval(gameCheckInterval);
                    return;
                }
                checkGameState();
            }, 1000);
            
            console.log('Game started successfully!');
        } catch (error) {
            console.error('Error in startGame:', error);
            showNotification('An error occurred when starting the game', true);
            gameStatusDisplay.textContent = 'READY';
            betButton.disabled = false;
            resetGame();
        }
    }

    // Update game state
    function updateGame() {
        // Log that the update function is being called
        console.log('Updating game state:', {
            currentMultiplier: gameState.currentMultiplier,
            rocketPosition: gameState.rocketPosition
        });

        if (!gameState.isPlaying) {
            console.log('updateGame called but game is not playing');
            return;
        }

        // Increase multiplier (controlled growth rate)
        const increase = 0.01 * (1 + (gameState.currentMultiplier - 1) * 0.05);
        gameState.currentMultiplier += increase;

        // Update multiplier display
        multiplierDisplay.textContent = `${gameState.currentMultiplier.toFixed(2)}x`;

        // Move the rocket upward based on multiplier, but with limits
        // Increased height increment for more visible movement
        const heightIncrease = 3 + (gameState.currentMultiplier - 1) * 0.6;
        gameState.rocketPosition += heightIncrease;

        // Limit the maximum height of the rocket
        const newPosition = Math.min(gameState.rocketPosition, gameState.maxHeight);
        
        // Make sure rocket is visible and animating properly
        rocketContainer.style.opacity = "1";
        rocketContainer.style.display = "block";
        rocketContainer.style.transition = 'bottom 0.1s linear';
        
        // Set new position and log it
        rocketContainer.style.bottom = `${newPosition}px`;
        console.log('New rocket position:', newPosition);

        // Check if game should crash
        if (gameState.currentMultiplier >= gameState.crashPoint) {
            console.log('Crash condition met, triggering crash');
            crashGame();
        }
    }

    // Cashout
    async function cashout() {
        try {
            if (!gameState.isPlaying) return;

            clearInterval(gameState.updateInterval);
            gameState.updateInterval = null;
            cashoutButton.disabled = true;
            gameStatusDisplay.textContent = 'CASHING OUT...';

            // Ensure multiplier is at least 1.00 when cashing out instantly
            if (gameState.currentMultiplier < 1.00) {
                gameState.currentMultiplier = 1.00;
            }

            // Calculate profit (winnings excluding the original bet)
            const profit = (gameState.betAmount * gameState.currentMultiplier) - gameState.betAmount;
            // Calculate total amount to return (original bet + profit)
            const totalAmount = gameState.betAmount * gameState.currentMultiplier;
            
            console.log('Cashout calculations:', {
                betAmount: gameState.betAmount,
                multiplier: gameState.currentMultiplier,
                profit: profit,
                totalAmount: totalAmount
            });
            
            // Add the TOTAL amount to wallet using wallet.js
            // This should be bet + profit, since the bet was removed earlier
            const cashoutSuccess = await addWinnings(totalAmount, 'crash', gameState.betAmount, gameState.currentMultiplier);
            
            // Update UI regardless of cashout success
            gameState.isPlaying = false;
            betButton.disabled = false;
            cashoutButton.disabled = true;
            
            if (cashoutSuccess) {
                gameStatusDisplay.textContent = 'CASHED OUT';
                gameStatusDisplay.style.color = '#00e701';
                
                // Update last win display with just the profit amount
                if (lastWinEl) {
                    lastWinEl.textContent = profit.toFixed(2);
                }
                
                // Show notification
                showNotification(`Cashed out at ${gameState.currentMultiplier.toFixed(2)}x! +$${profit.toFixed(2)}`);
                
                // Update history
                updateHistory(gameState.currentMultiplier, false);
            } else {
                gameStatusDisplay.textContent = 'CASHOUT FAILED';
                gameStatusDisplay.style.color = '#ff9900';
                
                // We'll still update history, but won't update the wallet
                updateHistory(gameState.currentMultiplier, false);
            }
            
            // Reset the game after a short delay
            setTimeout(resetGame, 1000);
        } catch (error) {
            console.error('Error in cashout:', error);
            showNotification('An error occurred during cashout', true);
            
            // Reset UI in case of error
            gameState.isPlaying = false;
            betButton.disabled = false;
            cashoutButton.disabled = true;
            gameStatusDisplay.textContent = 'READY';
            gameStatusDisplay.style.color = '#fff';
            
            // Make sure to reset the game
            resetGame();
        }
    }

    // Crash game
    function crashGame() {
        if (!gameState.isPlaying) return;
        
        clearInterval(gameState.updateInterval);
        gameState.updateInterval = null;
        gameState.isPlaying = false;
        
        // Show explosion effect
        explosion.style.opacity = 1;
        
        // Add crash animation to rocket
        rocketContainer.classList.add('crash-animation');
        
        // Update UI
        betButton.disabled = false;
        cashoutButton.disabled = true;
        gameStatusDisplay.textContent = 'CRASHED';
        gameStatusDisplay.style.color = '#ff3860';
        
        // Reset last win display to 0
        if (lastWinEl) {
            lastWinEl.textContent = "0.00";
        }
        
        // Show notification
        showNotification(`Crashed at ${gameState.currentMultiplier.toFixed(2)}x! -$${gameState.betAmount.toFixed(2)}`, true);
        
        // Update history
        updateHistory(gameState.currentMultiplier, true);
        
        // Reset after a delay
        setTimeout(resetGame, 2000);
    }

    // Reset game state
    function resetGame() {
        // Reset game state values
        gameState.currentMultiplier = 1.00;
        gameState.betAmount = 0;
        gameState.rocketPosition = 100; // Initial rocket position
        
        // Ensure game is not playing
        gameState.isPlaying = false;
        
        // Clear any lingering intervals
        if (gameState.updateInterval) {
            clearInterval(gameState.updateInterval);
            gameState.updateInterval = null;
        }

        // Update UI displays
        multiplierDisplay.textContent = '1.00x';
        gameStatusDisplay.textContent = 'READY';
        gameStatusDisplay.style.color = '#fff';
        
        // Reset rocket animation and position
        rocketContainer.classList.remove('crash-animation');
        
        // Ensure smooth transition for repositioning
        rocketContainer.style.transition = 'bottom 0.5s ease-out';
        
        // Reset rocket to initial position
        rocketContainer.style.bottom = '100px';
        rocketContainer.style.opacity = '1';
        rocketContainer.style.display = 'block';
        
        // Hide explosion effect
        explosion.style.opacity = '0';
        explosion.style.animation = 'none';
        
        // Re-enable buttons
        betButton.disabled = false;
        cashoutButton.disabled = true;
        
        // Re-enable animation after a short delay
        setTimeout(() => {
            explosion.style.animation = '';
        }, 100);
        
        // Log reset completion
        console.log('Game reset completed, rocket position:', rocketContainer.style.bottom);
    }

    // Handle window resize
    function handleResize() {
        const gameDisplay = document.querySelector('.game-display');
        gameState.maxHeight = Math.min(gameDisplay.clientHeight - 150, gameState.maxRocketHeight);

        // Reposition rocket if game is not in progress
        if (!gameState.isPlaying) {
            rocketContainer.style.bottom = '100px';
        }
    }

    // Event Listeners
    betButton.addEventListener('click', startGame);
    cashoutButton.addEventListener('click', cashout);
    window.addEventListener('resize', handleResize);

    // Initialize the game
    initStars();
    resetGame();
    handleResize();
    
    // Sync wallet display with the server
    if (typeof fetchCurrentBalance === 'function') {
        fetchCurrentBalance();
    }
});