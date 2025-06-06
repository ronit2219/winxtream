document.addEventListener('DOMContentLoaded', () => {
    console.log("Document loaded, initializing game...");
    
    // Game state
    let walletBalance = 1000;  // Default balance
    let currentBet = 0;
    let betNumber = 50;
    let timerValue = 15;
    let canBet = true;
    let timerInterval = null;
    let gameHistory = [];

    // Multiplier lookup table based on the provided data
    const multiplierTable = {
        1: 8.00, 2: 7.92, 3: 7.85, 4: 7.77, 5: 7.70,
        6: 7.62, 7: 7.55, 8: 7.47, 9: 7.39, 10: 7.32,
        11: 7.24, 12: 7.17, 13: 7.09, 14: 7.02, 15: 6.94,
        16: 6.86, 17: 6.79, 18: 6.71, 19: 6.64, 20: 6.56,
        21: 6.48, 22: 6.41, 23: 6.33, 24: 6.26, 25: 6.18,
        26: 6.11, 27: 6.03, 28: 5.95, 29: 5.88, 30: 5.80,
        31: 5.73, 32: 5.65, 33: 5.58, 34: 5.50, 35: 5.42,
        36: 5.35, 37: 5.27, 38: 5.20, 39: 5.12, 40: 5.05,
        41: 4.97, 42: 4.89, 43: 4.82, 44: 4.74, 45: 4.67,
        46: 4.59, 47: 4.51, 48: 4.44, 49: 4.36, 50: 4.29,
        51: 4.21, 52: 4.14, 53: 4.06, 54: 3.98, 55: 3.91,
        56: 3.83, 57: 3.76, 58: 3.68, 59: 3.61, 60: 3.53,
        61: 3.45, 62: 3.38, 63: 3.30, 64: 3.23, 65: 3.15,
        66: 3.08, 67: 3.00, 68: 2.92, 69: 2.85, 70: 2.77,
        71: 2.70, 72: 2.62, 73: 2.55, 74: 2.47, 75: 2.39,
        76: 2.32, 77: 2.24, 78: 2.17, 79: 2.09, 80: 2.01,
        81: 1.94, 82: 1.86, 83: 1.79, 84: 1.71, 85: 1.64,
        86: 1.56, 87: 1.48, 88: 1.41, 89: 1.33, 90: 1.26,
        91: 1.18, 92: 1.11, 93: 1.03
    };
    
    // Validate the multiplier table
    console.log("Validating multiplier table...");
    console.log("Multiplier for 1:", multiplierTable[1]);
    console.log("Multiplier for 50:", multiplierTable[50]);
    console.log("Multiplier for 93:", multiplierTable[93]);

    // Simple function to get DOM elements with fallbacks
    function getElement(id) {
        if (!id) {
            console.warn('No element ID provided to getElement function');
            return null;
        }
        const element = document.getElementById(id);
        if (!element) {
            console.warn(`Element with id '${id}' not found!`);
            return null;
        }
        return element;
    }

    // Get wallet element - support both HTML and PHP versions
    function getWalletElement() {
        return document.getElementById('wallet-balance') || document.getElementById('wallet-display');
    }

    // Show simple notification
    function showNotification(message, isError = false) {
        alert(message);
    }

    // Generate number grid
    function generateNumberGrid() {
        const grid = getElement('number-grid');
        if (!grid) return;
        
        grid.innerHTML = '';
        for (let number = 1; number <= 100; number++) {
            const numberBox = document.createElement('div');
            numberBox.className = 'number-box';
            numberBox.textContent = number;
            numberBox.id = `number-${number}`;
            grid.appendChild(numberBox);
        }
    }

    // Get multiplier for the selected number
    function getMultiplier(num) {
        // Make sure num is a valid integer
        num = parseInt(num);
        
        // Add more logging to help debug
        console.log("Getting multiplier for:", num, "Value:", multiplierTable[num] || 1.03);
        
        // Ensure we return a valid multiplier
        return multiplierTable[num] || 1.03;
    }

    // Update game history
    function updateHistory(number, won) {
        const historyList = getElement('history-list');
        if (!historyList) return;

        if (gameHistory.length >= 10) {
            gameHistory.shift();
        }

        gameHistory.push({
            number: number,
            won: won
        });

        historyList.innerHTML = '';
        gameHistory.forEach(item => {
            const historyItem = document.createElement('div');
            historyItem.className = `history-item ${item.won ? 'win' : 'lose'}`;
            historyItem.textContent = item.number;
            historyList.appendChild(historyItem);
        });
    }

    // Start the timer
    function startTimer() {
        console.log("Starting timer...");
        timerValue = 15;
        const timerElement = getElement('timer');
        
        if (timerElement) {
            timerElement.textContent = timerValue;
        }
        
        if (timerInterval) {
            clearInterval(timerInterval);
        }
        
        timerInterval = setInterval(() => {
            timerValue--;
            console.log("Timer tick:", timerValue);
            
            if (timerElement) {
                timerElement.textContent = timerValue;
            }

            if (timerValue <= 0) {
                clearInterval(timerInterval);
                generateRandomNumber();
            }

            // Disable betting in the last 5 seconds
            if (timerValue <= 5) {
                canBet = false;
                const betBtn = getElement('bet-button');
                if (betBtn) {
                    betBtn.disabled = true;
                }
            }
        }, 1000);
    }

    // Generate a random number
    function generateRandomNumber() {
        console.log("Generating random number...");
        const randomNumber = Math.floor(Math.random() * 100) + 1;
        console.log("Number drawn:", randomNumber);

        // Find the number box and highlight it
        const numberBox = getElement(`number-${randomNumber}`);
        if (numberBox) {
            numberBox.classList.add('highlight');
        }

        // Process any active bets
        processBet(randomNumber);

        // After 2 seconds, reset and start next round
        setTimeout(() => {
            if (numberBox) {
                numberBox.classList.remove('highlight');
            }

            // Reset for next round
            canBet = true;
            const betBtn = getElement('bet-button');
            if (betBtn) {
                betBtn.disabled = false;
            }
            startTimer();
        }, 2000);
    }

    // Process the bet
    async function processBet(drawnNumber) {
        if (currentBet <= 0) return;
        
        try {
            // Define resultContainer at the very beginning before any references
            console.log("Looking for result container element");
            const resultContainer = document.getElementById('result-container');
            console.log("Result container found:", resultContainer);
            
            const won = drawnNumber < betNumber;
            const multiplier = getMultiplier(betNumber);

            if (won) {
                // Calculate winning amount correctly
                const totalAmount = Math.floor(currentBet * multiplier);
                const winAmount = totalAmount - currentBet;
                
                // Try to use wallet.js if available
                if (typeof addWinnings === 'function') {
                    try {
                        // Pass the total amount (bet + winnings) to wallet.js
                        await addWinnings(totalAmount, 'roulette', currentBet, multiplier);
                    } catch (e) {
                        console.error("Error using addWinnings function:", e);
                        // Fallback to basic wallet in case of error
                        walletBalance += totalAmount;
                        const walletEl = getWalletElement();
                        if (walletEl) {
                            walletEl.textContent = walletBalance;
                        }
                    }
                } else {
                    console.log("addWinnings function not found, using fallback");
                    // Fallback to basic wallet - add total amount
                    walletBalance += totalAmount;
                    const walletEl = getWalletElement();
                    if (walletEl) {
                        walletEl.textContent = walletBalance;
                    }
                }
                
                // Update last win display (this should show only the profit part)
                const lastWinEl = getElement('last-win');
                if (lastWinEl) {
                    lastWinEl.textContent = winAmount;
                }
                
                // Show result
                if (resultContainer) {
                    resultContainer.innerHTML = `
                        <div class="win">Number drawn: ${drawnNumber}. You won $${winAmount}!</div>
                    `;
                    resultContainer.classList.add('show');
                } else {
                    console.error("Could not find result container element to show win message");
                }
                
                alert(`You won $${winAmount}!`);
            } else {
                // Show losing result
                if (resultContainer) {
                    resultContainer.innerHTML = `
                        <div class="lose">Number drawn: ${drawnNumber}. You lost $${currentBet}.</div>
                    `;
                    resultContainer.classList.add('show');
                } else {
                    console.error("Could not find result container element to show loss message");
                }
                
                // Reset last win to 0
                const lastWinEl = getElement('last-win');
                if (lastWinEl) {
                    lastWinEl.textContent = "0";
                }
                
                alert(`You lost $${currentBet}.`);
            }

            // Add to history
            updateHistory(drawnNumber, won);
            
            // Reset bet state
            currentBet = 0;
            
            const betInfoEl = getElement('bet-info');
            if (betInfoEl) {
                betInfoEl.textContent = 'Choose a bet amount and number to begin.';
            }
            
            // Hide result after 5 seconds - only if resultContainer exists
            if (resultContainer && resultContainer.classList && resultContainer.classList.contains('show')) {
                setTimeout(() => {
                    resultContainer.classList.remove('show');
                }, 5000);
            } else {
                console.log("Result container not available for hiding after timeout");
            }
        } catch (error) {
            console.error('Error in processBet:', error);
            alert('An error occurred processing the bet');
            
            // Reset bet state
            currentBet = 0;
        }
    }

    // Initialize the game
    function init() {
        console.log('Starting roulette game initialization...');
        
        // Set up the initial wallet
        try {
            // Try to use wallet.js if available
            if (typeof fetchCurrentBalance === 'function') {
                fetchCurrentBalance();
            } else {
                // Fallback to basic wallet
                const walletEl = getWalletElement();
                if (walletEl) {
                    if (!walletEl.textContent || walletEl.textContent.trim() === '') {
                        walletEl.textContent = walletBalance;
                    }
                }
            }
        } catch (e) {
            console.error("Error setting wallet:", e);
        }

        // Generate grid
        generateNumberGrid();

        // Set up slider
        const slider = getElement('number-slider');
        const sliderValue = getElement('slider-value');
        const multiplierValue = getElement('multiplier-value');
        
        if (slider && sliderValue) {
            console.log("Initializing slider with value:", slider.value);
            
            // Set initial values
            sliderValue.textContent = slider.value;
            betNumber = parseInt(slider.value);
            
            if (multiplierValue) {
                const initialMult = getMultiplier(betNumber);
                console.log("Initial multiplier:", initialMult);
                multiplierValue.textContent = initialMult.toFixed(2);
            }
            
            // Add event listeners
            slider.addEventListener('input', function() {
                console.log("Slider value changed to:", this.value);
                betNumber = parseInt(this.value);
                if (sliderValue) {
                    sliderValue.textContent = betNumber;
                }
                if (multiplierValue) {
                    const mult = getMultiplier(betNumber);
                    console.log("Setting multiplier display to:", mult.toFixed(2));
                    multiplierValue.textContent = mult.toFixed(2);
                }
            });
            
            // Extra events to ensure it works on all devices
            slider.addEventListener('change', function() {
                console.log("Slider change event:", this.value);
                betNumber = parseInt(this.value);
                if (sliderValue) {
                    sliderValue.textContent = betNumber;
                }
                if (multiplierValue) {
                    const mult = getMultiplier(betNumber);
                    console.log("Setting multiplier display to:", mult.toFixed(2));
                    multiplierValue.textContent = mult.toFixed(2);
                }
            });
            
            // Manually trigger an input event to ensure initialization
            const event = new Event('input');
            slider.dispatchEvent(event);
        } else {
            console.error("Slider elements not found - slider:", slider, "sliderValue:", sliderValue);
        }

        // Set up bet button
        const betBtn = getElement('bet-button');
        const betInput = getElement('bet-amount');
        
        if (betBtn && betInput) {
            betBtn.addEventListener('click', async function() {
                if (!canBet) {
                    alert('Betting closed for this round!');
                    return;
                }

                const betAmount = parseInt(betInput.value);
                if (isNaN(betAmount) || betAmount <= 0) {
                    alert('Please enter a valid bet amount.');
                    return;
                }

                // Try to use wallet.js for placing bets if available
                let betSuccess = false;
                
                if (typeof placeBet === 'function') {
                    betSuccess = await placeBet(betAmount, 'roulette');
                    if (!betSuccess) {
                        return; // wallet.js will handle error notifications
                    }
                } else {
                    // Fallback to basic wallet handling
                    if (betAmount > walletBalance) {
                        alert('Insufficient funds in your wallet.');
                        return;
                    }
                    
                    // Deduct the bet from wallet
                    walletBalance -= betAmount;
                    const walletEl = getWalletElement();
                    if (walletEl) {
                        walletEl.textContent = walletBalance;
                    }
                    betSuccess = true;
                }
                
                if (betSuccess) {
                    currentBet = betAmount;
                    
                    // Update bet info
                    const betInfoEl = getElement('bet-info');
                    if (betInfoEl) {
                        betInfoEl.textContent = `Bet $${betAmount} that the number will be less than ${betNumber}`;
                    }
                    
                    // Clear input
                    betInput.value = '';
                }
            });
        }

        // Start timer to begin the game
        console.log("About to start timer...");
        startTimer();
        console.log("Timer should be started now");
    }

    // Start game
    console.log("Starting game initialization...");
    init();
});