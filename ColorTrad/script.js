document.addEventListener('DOMContentLoaded', () => {
    class ColorTrading {
        constructor() {
            this.walletEl = document.getElementById('wallet-display');
            this.timerEl = document.getElementById('timer');
            this.betInput = document.getElementById('betAmount');
            this.betButton = document.getElementById('betButton');
            this.resultEl = document.getElementById('result');
            this.lastRoundColorEl = document.getElementById('lastRoundColor');
            this.lastWinEl = document.getElementById('last-win');
            this.notificationEl = document.getElementById('notification');
            this.colorBtns = {
                green: document.getElementById('greenBtn'),
                red: document.getElementById('redBtn'),
                violet: document.getElementById('violetBtn')
            };

            this.timeLeft = 10;
            this.selectedColor = null;
            this.betAmount = 0;
            this.lastRoundColor = '-';
            this.timerInterval = null;

            this.initEventListeners();
            this.startTimer();
            
            // Initialize wallet display
            if (typeof fetchCurrentBalance === 'function') {
                fetchCurrentBalance();
            }
        }

        initEventListeners() {
            Object.entries(this.colorBtns).forEach(([color, btn]) => {
                btn.addEventListener('click', () => this.selectColor(color));
            });

            this.betButton.addEventListener('click', () => this.placeBet());
        }

        showNotification(message, isError = false) {
            if (!this.notificationEl) return;
            
            this.notificationEl.textContent = message;
            this.notificationEl.className = 'notification';
            this.notificationEl.classList.add(isError ? 'error' : 'success');
            this.notificationEl.style.display = 'block';
            
            setTimeout(() => {
                this.notificationEl.style.display = 'none';
            }, 3000);
        }

        selectColor(color) {
            // Remove active state from all buttons
            Object.values(this.colorBtns).forEach(btn => {
                btn.style.opacity = '0.5';
            });

            // Highlight selected color
            this.colorBtns[color].style.opacity = '1';
            this.selectedColor = color;
        }

        async placeBet() {
            try {
                const betAmount = parseInt(this.betInput.value);
                if (!this.selectedColor || isNaN(betAmount) || betAmount <= 0) {
                    this.resultEl.textContent = 'Invalid bet or color selection!';
                    this.resultEl.className = 'result lose';
                    this.showNotification('Please enter a valid bet amount and select a color', true);
                    return;
                }

                // Disable bet button to prevent multiple bets
                this.betButton.disabled = true;
                
                // Place bet using wallet.js
                const betPlaced = await placeBet(betAmount, 'colortrad');
                
                if (!betPlaced) {
                    this.betButton.disabled = false;
                    return; // placeBet will display appropriate error message
                }

                this.betAmount = betAmount;
                this.resultEl.textContent = `Bet placed: $${betAmount} on ${this.selectedColor}`;
                this.resultEl.className = 'result';
                this.showNotification(`Bet placed: $${betAmount} on ${this.selectedColor}`);
            } catch (error) {
                console.error('Error placing bet:', error);
                this.showNotification('An error occurred when placing the bet', true);
                this.betButton.disabled = false;
            }
        }

        startTimer() {
            // Clear any existing interval
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
            }
            
            this.timeLeft = 10;
            this.timerEl.textContent = this.timeLeft;
            
            this.timerInterval = setInterval(() => {
                this.timeLeft--;
                this.timerEl.textContent = this.timeLeft;

                if (this.timeLeft <= 0) {
                    this.revealResult();
                    this.resetGame();
                }
            }, 1000);
        }

        async revealResult() {
            try {
                const result = this.generateResult();

                // Update last round color
                this.lastRoundColor = result;
                this.lastRoundColorEl.textContent = result.toUpperCase();
                this.lastRoundColorEl.style.color = this.getColorCode(result);

                if (this.betAmount > 0) {
                    if (this.selectedColor === result) {
                        const multiplier = result === 'violet' ? 14 : 2;
                        // Calculate profit (winnings excluding the original bet)
                        const profit = this.betAmount * multiplier - this.betAmount;
                        // Calculate total amount to return (original bet + profit)
                        const totalAmount = this.betAmount * multiplier;
                        
                        console.log('Win calculations:', {
                            betAmount: this.betAmount,
                            multiplier: multiplier,
                            profit: profit,
                            totalAmount: totalAmount
                        });
                        
                        // Add total amount to wallet using wallet.js (bet + profit)
                        // This is the correct approach since the bet was removed earlier
                        const winSuccess = await addWinnings(totalAmount, 'colortrad', this.betAmount, multiplier);
                        
                        if (winSuccess) {
                            this.resultEl.textContent = `You won $${profit}!`;
                            this.resultEl.className = 'result win';
                            
                            // Update last win display with just the profit amount
                            if (this.lastWinEl) {
                                this.lastWinEl.textContent = profit;
                            }
                            this.showNotification(`You won $${profit}!`);
                        } else {
                            this.resultEl.textContent = 'Error updating balance. Please contact support.';
                            this.resultEl.className = 'result lose';
                            this.showNotification('Error updating winnings. Please check your balance.', true);
                        }
                    } else {
                        this.resultEl.textContent = `You lost $${this.betAmount}!`;
                        this.resultEl.className = 'result lose';
                        
                        // Reset last win to 0 when player loses
                        if (this.lastWinEl) {
                            this.lastWinEl.textContent = '0';
                        }
                        this.showNotification(`You lost $${this.betAmount}!`, true);
                    }
                }
            } catch (error) {
                console.error('Error in revealResult:', error);
                this.showNotification('An error occurred when processing the result', true);
            }
        }

        generateResult() {
            const randomNum = Math.random() * 20;
            if (randomNum < 10) return 'green';
            if (randomNum < 19) return 'red';
            return 'violet';
        }

        getColorCode(color) {
            const colorCodes = {
                green: '#10b981',
                red: '#ef4444',
                violet: '#8b5cf6'
            };
            return colorCodes[color];
        }

        resetGame() {
            this.timeLeft = 10;
            this.selectedColor = null;
            this.betAmount = 0;
            this.betButton.disabled = false;

            // Reset button colors
            Object.values(this.colorBtns).forEach(btn => {
                btn.style.opacity = '1';
            });
            
            // Start a new round
            this.startTimer();
        }
    }

    // Initialize the game
    new ColorTrading();
});document.addEventListener('DOMContentLoaded', () => {
    class ColorTrading {
        constructor() {
            this.walletEl = document.getElementById('wallet-display');
            this.timerEl = document.getElementById('timer');
            this.betInput = document.getElementById('betAmount');
            this.betButton = document.getElementById('betButton');
            this.resultEl = document.getElementById('result');
            this.lastRoundColorEl = document.getElementById('lastRoundColor');
            this.lastWinEl = document.getElementById('last-win');
            this.notificationEl = document.getElementById('notification');
            this.colorBtns = {
                green: document.getElementById('greenBtn'),
                red: document.getElementById('redBtn'),
                violet: document.getElementById('violetBtn')
            };

            this.timeLeft = 10;
            this.selectedColor = null;
            this.betAmount = 0;
            this.lastRoundColor = '-';
            this.timerInterval = null;

            this.initEventListeners();
            this.startTimer();
            
            // Initialize wallet display
            if (typeof fetchCurrentBalance === 'function') {
                fetchCurrentBalance();
            }
        }

        initEventListeners() {
            Object.entries(this.colorBtns).forEach(([color, btn]) => {
                btn.addEventListener('click', () => this.selectColor(color));
            });

            this.betButton.addEventListener('click', () => this.placeBet());
        }

        showNotification(message, isError = false) {
            if (!this.notificationEl) return;
            
            this.notificationEl.textContent = message;
            this.notificationEl.className = 'notification';
            this.notificationEl.classList.add(isError ? 'error' : 'success');
            this.notificationEl.style.display = 'block';
            
            setTimeout(() => {
                this.notificationEl.style.display = 'none';
            }, 3000);
        }

        selectColor(color) {
            // Remove active state from all buttons
            Object.values(this.colorBtns).forEach(btn => {
                btn.style.opacity = '0.5';
            });

            // Highlight selected color
            this.colorBtns[color].style.opacity = '1';
            this.selectedColor = color;
        }

        async placeBet() {
            try {
                const betAmount = parseInt(this.betInput.value);
                if (!this.selectedColor || isNaN(betAmount) || betAmount <= 0) {
                    this.resultEl.textContent = 'Invalid bet or color selection!';
                    this.resultEl.className = 'result lose';
                    this.showNotification('Please enter a valid bet amount and select a color', true);
                    return;
                }

                // Disable bet button to prevent multiple bets
                this.betButton.disabled = true;
                
                // Place bet using wallet.js
                const betPlaced = await placeBet(betAmount, 'colortrad');
                
                if (!betPlaced) {
                    this.betButton.disabled = false;
                    return; // placeBet will display appropriate error message
                }

                this.betAmount = betAmount;
                this.resultEl.textContent = `Bet placed: $${betAmount} on ${this.selectedColor}`;
                this.resultEl.className = 'result';
                this.showNotification(`Bet placed: $${betAmount} on ${this.selectedColor}`);
            } catch (error) {
                console.error('Error placing bet:', error);
                this.showNotification('An error occurred when placing the bet', true);
                this.betButton.disabled = false;
            }
        }

        startTimer() {
            // Clear any existing interval
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
            }
            
            this.timeLeft = 10;
            this.timerEl.textContent = this.timeLeft;
            
            this.timerInterval = setInterval(() => {
                this.timeLeft--;
                this.timerEl.textContent = this.timeLeft;

                if (this.timeLeft <= 0) {
                    this.revealResult();
                    this.resetGame();
                }
            }, 1000);
        }

        async revealResult() {
            try {
                const result = this.generateResult();

                // Update last round color
                this.lastRoundColor = result;
                this.lastRoundColorEl.textContent = result.toUpperCase();
                this.lastRoundColorEl.style.color = this.getColorCode(result);

                if (this.betAmount > 0) {
                    if (this.selectedColor === result) {
                        const multiplier = result === 'violet' ? 14 : 2;
                        // Calculate profit (winnings excluding the original bet)
                        const profit = this.betAmount * multiplier - this.betAmount;
                        // Calculate total amount to return (original bet + profit)
                        const totalAmount = this.betAmount * multiplier;
                        
                        console.log('Win calculations:', {
                            betAmount: this.betAmount,
                            multiplier: multiplier,
                            profit: profit,
                            totalAmount: totalAmount
                        });
                        
                        // Add total amount to wallet using wallet.js (bet + profit)
                        // This is the correct approach since the bet was removed earlier
                        const winSuccess = await addWinnings(totalAmount, 'colortrad', this.betAmount, multiplier);
                        
                        if (winSuccess) {
                            this.resultEl.textContent = `You won $${profit}!`;
                            this.resultEl.className = 'result win';
                            
                            // Update last win display with just the profit amount
                            if (this.lastWinEl) {
                                this.lastWinEl.textContent = profit;
                            }
                            this.showNotification(`You won $${profit}!`);
                        } else {
                            this.resultEl.textContent = 'Error updating balance. Please contact support.';
                            this.resultEl.className = 'result lose';
                            this.showNotification('Error updating winnings. Please check your balance.', true);
                        }
                    } else {
                        this.resultEl.textContent = `You lost $${this.betAmount}!`;
                        this.resultEl.className = 'result lose';
                        
                        // Reset last win to 0 when player loses
                        if (this.lastWinEl) {
                            this.lastWinEl.textContent = '0';
                        }
                        this.showNotification(`You lost $${this.betAmount}!`, true);
                    }
                }
            } catch (error) {
                console.error('Error in revealResult:', error);
                this.showNotification('An error occurred when processing the result', true);
            }
        }

        generateResult() {
            const randomNum = Math.random() * 20;
            if (randomNum < 10) return 'green';
            if (randomNum < 19) return 'red';
            return 'violet';
        }

        getColorCode(color) {
            const colorCodes = {
                green: '#10b981',
                red: '#ef4444',
                violet: '#8b5cf6'
            };
            return colorCodes[color];
        }

        resetGame() {
            this.timeLeft = 10;
            this.selectedColor = null;
            this.betAmount = 0;
            this.betButton.disabled = false;

            // Reset button colors
            Object.values(this.colorBtns).forEach(btn => {
                btn.style.opacity = '1';
            });
            
            // Start a new round
            this.startTimer();
        }
    }

    // Initialize the game
    new ColorTrading();
});