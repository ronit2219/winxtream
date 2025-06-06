/**
 * WinXtream Wallet Management
 * Common functions for handling wallet operations across all games
 */

// Wallet elements
function findWalletElement() {
    return document.getElementById('wallet-balance') || 
           document.getElementById('walletAmount') || 
           document.getElementById('wallet-display');
}

function findLastWinElement() {
    return document.getElementById('last-win');
}

let walletBalanceElement = findWalletElement();
let lastWinElement = findLastWinElement();

// Update wallet display with current balance
function updateWalletDisplay(amount) {
    // Always get the latest element reference in case DOM has changed
    walletBalanceElement = findWalletElement();
    
    if (!walletBalanceElement) {
        console.error('Wallet display element not found!');
        return;
    }
    
    if (typeof amount === 'number') {
        walletBalanceElement.textContent = amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        console.log('Wallet display updated to:', amount.toFixed(2));
    } else {
        console.error('Invalid amount type passed to updateWalletDisplay:', typeof amount);
    }
}

// Get current wallet balance
function getCurrentBalance() {
    // Always get the latest element reference in case DOM has changed
    walletBalanceElement = findWalletElement();
    
    if (!walletBalanceElement) {
        console.error('Wallet display element not found!');
        return 1000;
    }
    
    // Remove commas and convert to float
    const rawBalance = walletBalanceElement.textContent.replace(/,/g, '');
    return parseFloat(rawBalance);
}

// Check if user has enough balance for a bet
function hasEnoughBalance(betAmount) {
    const currentBalance = getCurrentBalance();
    console.log('Current Balance:', currentBalance, 'Bet Amount:', betAmount);
    return currentBalance >= betAmount;
}

// Fetch the current balance from the server
async function fetchCurrentBalance() {
    try {
        console.log('Fetching current balance from server...');
        
        // Add retry logic
        let maxRetries = 2;
        let retries = 0;
        let success = false;
        let balance = null;
        
        while (!success && retries < maxRetries) {
            try {
                retries++;
                
                // Get the website base URL
                const baseUrl = window.location.origin;
                
                // Debug - Show current location
                console.log('Current location:', window.location.pathname);
                
                // Construct absolute path with the domain
                const pathToAjax = baseUrl + '/includes/ajax_handler.php';
                
                console.log(`Using path for AJAX: ${pathToAjax}`);
                
                const response = await fetch(pathToAjax, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'get_balance'
                    })
                });
                
                if (!response.ok) {
                    console.error(`HTTP error! Status: ${response.status}`);
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                
                // Debug - Log the raw response text before parsing JSON
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                try {
                    // Parse the JSON response
                    const data = JSON.parse(responseText);
                    
                    if (data.success) {
                        // Update the wallet display
                        updateWalletDisplay(data.balance);
                        console.log("Balance synced from server: " + data.balance);
                        balance = data.balance;
                        success = true;
                    } else {
                        console.error('Failed to fetch balance:', data.error);
                        if (retries >= maxRetries) {
                            return getCurrentBalance();
                        }
                        // Wait a moment before retry
                        await new Promise(resolve => setTimeout(resolve, 500));
                    }
                } catch (jsonError) {
                    console.error('JSON parsing error:', jsonError);
                    console.error('Response text was:', responseText);
                    throw jsonError;
                }
            } catch (error) {
                console.error(`AJAX error when fetching balance (attempt ${retries}):`, error);
                if (retries >= maxRetries) {
                    return getCurrentBalance();
                }
                // Wait a moment before retry
                await new Promise(resolve => setTimeout(resolve, 500));
            }
        }
        
        return success ? balance : getCurrentBalance();
    } catch (error) {
        console.error('Error in fetchCurrentBalance:', error);
        return getCurrentBalance();
    }
}

// Place a bet (subtract from balance)
async function placeBet(amount, game) {
    if (!amount || isNaN(amount) || amount <= 0) {
        showNotification('Please enter a valid bet amount', 'error');
        return false;
    }
    
    try {
        // Always fetch the current balance from server before placing a bet
        const currentBalance = await fetchCurrentBalance();
        
        // Check if user has enough balance
        if (currentBalance < amount) {
            // Show a proper modal dialog for insufficient funds
            showInsufficientFundsModal(currentBalance, amount);
            return false;
        }
        
        // Calculate new balance
        const newBalance = currentBalance - amount;
        
        // Update display immediately for better UX
        updateWalletDisplay(newBalance);
        
        // Add retry logic
        let maxRetries = 2;
        let retries = 0;
        let success = false;
        let isFirstAttempt = true; // Flag to track if this is the first attempt
        
        while (!success && retries < maxRetries) {
            try {
                retries++;
                
                // Only deduct money from the database on the first attempt
                // On retry attempts, check if balance already reflects the deduction
                let changeAmount = -amount;
                let requestNewBalance = newBalance;
                
                if (!isFirstAttempt) {
                    // On retry, check current balance first to see if deduction already happened
                    const updatedBalance = await fetchCurrentBalance();
                    
                    if (Math.abs(updatedBalance - newBalance) < 0.01) {
                        // Balance has already been updated in the database, just verify/confirm
                        changeAmount = 0;
                        requestNewBalance = updatedBalance;
                        console.log('Balance already reflects bet deduction, sending confirmation only');
                    }
                }
                
                console.log('Placing bet with parameters:', {
                    game: game,
                    amount: amount,
                    new_balance: requestNewBalance,
                    change_amount: changeAmount,
                    retry_attempt: isFirstAttempt ? 0 : retries
                });
                
                // Get the website base URL
                const baseUrl = window.location.origin;
                
                // Construct absolute path with the domain
                const pathToAjax = baseUrl + '/includes/ajax_handler.php';
                
                console.log(`Using path for AJAX: ${pathToAjax}`);
                
                // Update balance in the database via AJAX
                const response = await fetch(pathToAjax, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'update_balance',
                        new_balance: requestNewBalance,
                        change_amount: changeAmount,
                        game: game || 'unknown',
                        bet_amount: amount,
                        multiplier: 1,
                        retry_attempt: isFirstAttempt ? 0 : retries
                    })
                });
                
                isFirstAttempt = false; // Mark that we've attempted at least once
                
                if (!response.ok) {
                    console.error(`HTTP error! Status: ${response.status}`);
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                
                // Debug - Log the raw response text before parsing JSON
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                try {
                    // Parse the JSON response
                    const data = JSON.parse(responseText);
                    
                    // Debug log the full response
                    console.log('Server response for bet placement:', data);
                    
                    if (data.success) {
                        console.log(`Bet placed: $${amount} - New balance: $${requestNewBalance}`);
                        // Set the wallet display to the confirmed balance
                        updateWalletDisplay(requestNewBalance);
                        success = true;
                    } else {
                        // If update failed and we've used all retries
                        console.error(`Failed to place bet (attempt ${retries}):`, data.error);
                        if (retries >= maxRetries) {
                            await fetchCurrentBalance(); // Refresh to correct balance
                            showNotification('Error placing bet: ' + data.error, 'error');
                            return false;
                        }
                        // Wait a moment before retry
                        await new Promise(resolve => setTimeout(resolve, 500));
                    }
                } catch (jsonError) {
                    console.error('JSON parsing error:', jsonError);
                    console.error('Response text was:', responseText);
                    throw jsonError;
                }
            } catch (error) {
                console.error(`AJAX error when placing bet (attempt ${retries}):`, error);
                if (retries >= maxRetries) {
                    await fetchCurrentBalance(); // Refresh to correct balance
                    showNotification('Network error when placing bet', 'error');
                    return false;
                }
                // Wait a moment before retry
                await new Promise(resolve => setTimeout(resolve, 500));
            }
        }
        
        // Add debug to confirm success or failure
        console.log('Final result of placeBet:', success ? 'SUCCESS' : 'FAILURE');
        return success;
    } catch (error) {
        console.error('Error in placeBet:', error);
        await fetchCurrentBalance(); // Refresh to correct balance
        showNotification('An error occurred while placing your bet', 'error');
        return false;
    }
}

// Show a modal dialog for insufficient funds
function showInsufficientFundsModal(currentBalance, attemptedBet) {
    // Check if modal already exists (avoid duplicates)
    let modal = document.getElementById('insufficient-funds-modal');
    
    // If modal doesn't exist, create it
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'insufficient-funds-modal';
        modal.className = 'modal-overlay';
        
        // Create modal content
        const modalContent = document.createElement('div');
        modalContent.className = 'modal-content';
        
        // Create header
        const header = document.createElement('div');
        header.className = 'modal-header';
        header.style.backgroundColor = '#ff3860';
        header.innerHTML = '<h3>Insufficient Funds</h3>';
        
        // Create body
        const body = document.createElement('div');
        body.className = 'modal-body';
        body.innerHTML = `
            <p>You don't have enough funds to place this bet.</p>
            <div class="funds-info">
                <div class="funds-row">
                    <span>Your Balance:</span>
                    <span>$${currentBalance.toFixed(2)}</span>
                </div>
                <div class="funds-row">
                    <span>Bet Amount:</span>
                    <span>$${attemptedBet.toFixed(2)}</span>
                </div>
                <div class="funds-row shortfall">
                    <span>Shortfall:</span>
                    <span>$${(attemptedBet - currentBalance).toFixed(2)}</span>
                </div>
            </div>
        `;
        
        // Create footer with OK button
        const footer = document.createElement('div');
        footer.className = 'modal-footer';
        const okButton = document.createElement('button');
        okButton.className = 'modal-btn';
        okButton.textContent = 'OK';
        okButton.onclick = function() {
            closeModal(modal);
        };
        footer.appendChild(okButton);
        
        // Assemble the modal
        modalContent.appendChild(header);
        modalContent.appendChild(body);
        modalContent.appendChild(footer);
        modal.appendChild(modalContent);
        
        // Add styles for the modal if not already added
        if (!document.getElementById('wallet-modal-styles')) {
            const style = document.createElement('style');
            style.id = 'wallet-modal-styles';
            style.textContent = `
                .modal-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background-color: rgba(0, 0, 0, 0.7);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 9999;
                }
                .modal-content {
                    background-color: #1a1a2e;
                    border-radius: 8px;
                    width: 90%;
                    max-width: 400px;
                    box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
                    border: 1px solid #333;
                    overflow: hidden;
                }
                .modal-header {
                    color: white;
                    padding: 15px;
                    text-align: center;
                }
                .modal-header h3 {
                    margin: 0;
                    font-size: 18px;
                }
                .modal-body {
                    padding: 20px;
                    color: #fff;
                }
                .modal-body p {
                    margin-top: 0;
                    text-align: center;
                }
                .funds-info {
                    background-color: rgba(0, 0, 0, 0.2);
                    border-radius: 5px;
                    padding: 12px;
                    margin-top: 15px;
                }
                .funds-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 8px;
                }
                .funds-row.shortfall {
                    margin-top: 15px;
                    padding-top: 8px;
                    border-top: 1px solid #444;
                    font-weight: bold;
                    color: #ff3860;
                }
                .modal-footer {
                    padding: 15px;
                    display: flex;
                    justify-content: center;
                }
                .modal-btn {
                    background-color: #0084ff;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    padding: 10px 24px;
                    font-size: 16px;
                    cursor: pointer;
                    transition: background-color 0.2s;
                }
                .modal-btn:hover {
                    background-color: #0073e6;
                }
            `;
            document.head.appendChild(style);
        }
        
        // Allow closing by clicking outside the modal content
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
        
        // Add to body
        document.body.appendChild(modal);
        
        // Add escape key listener
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal && document.body.contains(modal)) {
                closeModal(modal);
            }
        });
    } else {
        // If modal exists, update the values
        const balanceElem = modal.querySelector('.funds-row:nth-child(1) span:nth-child(2)');
        const betElem = modal.querySelector('.funds-row:nth-child(2) span:nth-child(2)');
        const shortfallElem = modal.querySelector('.shortfall span:nth-child(2)');
        
        if (balanceElem) balanceElem.textContent = `$${currentBalance.toFixed(2)}`;
        if (betElem) betElem.textContent = `$${attemptedBet.toFixed(2)}`;
        if (shortfallElem) shortfallElem.textContent = `$${(attemptedBet - currentBalance).toFixed(2)}`;
        
        // Make sure it's visible
        modal.style.display = 'flex';
    }
}

// Add winnings (add to balance)
async function addWinnings(amount, game, betAmount, multiplier) {
    if (!amount || isNaN(amount) || amount <= 0) {
        return false;
    }
    
    try {
        // Always fetch the current balance from server first
        const currentBalance = await fetchCurrentBalance();
        
        // Calculate new balance
        const newBalance = currentBalance + amount;
        
        // Update display immediately for better UX
        updateWalletDisplay(newBalance);
        
        // Add retry logic
        let maxRetries = 2;
        let retries = 0;
        let success = false;
        let isFirstAttempt = true; // Flag to track if this is the first attempt
        
        while (!success && retries < maxRetries) {
            try {
                retries++;
                
                // Only add money to the database on the first attempt
                // On retry attempts, check if balance already reflects the addition
                let changeAmount = amount;
                let requestNewBalance = newBalance;
                
                if (!isFirstAttempt) {
                    // On retry, check current balance first to see if addition already happened
                    const updatedBalance = await fetchCurrentBalance();
                    
                    if (Math.abs(updatedBalance - newBalance) < 0.01) {
                        // Balance has already been updated in the database, just verify/confirm
                        changeAmount = 0;
                        requestNewBalance = updatedBalance;
                        console.log('Balance already reflects winnings addition, sending confirmation only');
                    }
                }
                
                // Get the website base URL
                const baseUrl = window.location.origin;
                
                // Construct absolute path with the domain
                const pathToAjax = baseUrl + '/includes/ajax_handler.php';
                
                console.log(`Using path for AJAX: ${pathToAjax}`);
                
                // Update balance in the database via AJAX
                const response = await fetch(pathToAjax, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'update_balance',
                        new_balance: newBalance,
                        change_amount: amount,
                        game: game || 'unknown',
                        bet_amount: betAmount || 0,
                        multiplier: multiplier || 1,
                        retry_attempt: retries
                    })
                });
                
                isFirstAttempt = false; // Mark that we've attempted at least once
                
                if (!response.ok) {
                    console.error(`HTTP error! Status: ${response.status}`);
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                
                // Debug - Log the raw response text before parsing JSON
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                try {
                    // Parse the JSON response
                    const data = JSON.parse(responseText);
                    
                    if (data.success) {
                        console.log(`Winnings added: $${amount} - New balance: $${requestNewBalance}`);
                        
                        // Update last win display
                        lastWinElement = findLastWinElement();
                        if (lastWinElement) {
                            lastWinElement.textContent = amount.toFixed(2);
                        }
                        
                        showNotification(`You won $${amount.toFixed(2)}!`, 'success');
                        success = true;
                    } else {
                        // If update failed and we've used all retries
                        console.error(`Failed to add winnings (attempt ${retries}):`, data.error);
                        if (retries >= maxRetries) {
                            await fetchCurrentBalance(); // Refresh to correct balance
                            showNotification('Error adding winnings: ' + data.error, 'error');
                            return false;
                        }
                        // Wait a moment before retry
                        await new Promise(resolve => setTimeout(resolve, 500));
                    }
                } catch (jsonError) {
                    console.error('JSON parsing error:', jsonError);
                    console.error('Response text was:', responseText);
                    throw jsonError;
                }
            } catch (error) {
                console.error(`AJAX error when adding winnings (attempt ${retries}):`, error);
                if (retries >= maxRetries) {
                    await fetchCurrentBalance(); // Refresh to correct balance
                    showNotification('Network error when adding winnings', 'error');
                    return false;
                }
                // Wait a moment before retry
                await new Promise(resolve => setTimeout(resolve, 500));
            }
        }
        
        return success;
    } catch (error) {
        console.error('Error in addWinnings:', error);
        await fetchCurrentBalance(); // Refresh to correct balance
        showNotification('An error occurred while adding your winnings', 'error');
        return false;
    }
}

// Show notification (if game has a notification element)
function showNotification(message, type = 'info') {
    console.log('Notification:', message, type);
    
    // Try to find a notification element
    const notification = document.getElementById('notification');
    
    if (notification) {
        notification.textContent = message;
        notification.className = 'notification';
        notification.classList.add(type === 'error' ? 'error' : 
                                  type === 'success' ? 'success' : 'info');
        notification.style.display = 'block';
        
        // Automatically hide after 3 seconds
        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
    } else {
        // If no notification element exists, use a modal instead of alert
        if (type === 'error' || type === 'success') {
            showMessageModal(message, type);
        }
    }
}

// Show a general message modal
function showMessageModal(message, type = 'info') {
    // Check if modal already exists (avoid duplicates)
    let modal = document.getElementById('message-modal');
    
    // If modal doesn't exist, create it
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'message-modal';
        modal.className = 'modal-overlay';
        
        // Create modal content
        const modalContent = document.createElement('div');
        modalContent.className = 'modal-content';
        
        // Create header
        const header = document.createElement('div');
        header.className = 'modal-header';
        
        // Set header background color based on type
        if (type === 'error') {
            header.style.backgroundColor = '#ff3860';
            header.innerHTML = '<h3>Error</h3>';
        } else if (type === 'success') {
            header.style.backgroundColor = '#23d160';
            header.innerHTML = '<h3>Success</h3>';
        } else {
            header.style.backgroundColor = '#209cee';
            header.innerHTML = '<h3>Notice</h3>';
        }
        
        // Create body
        const body = document.createElement('div');
        body.className = 'modal-body';
        body.innerHTML = `<p>${message}</p>`;
        
        // Create footer with OK button
        const footer = document.createElement('div');
        footer.className = 'modal-footer';
        const okButton = document.createElement('button');
        okButton.className = 'modal-btn';
        okButton.textContent = 'OK';
        okButton.onclick = function() {
            closeModal(modal);
        };
        footer.appendChild(okButton);
        
        // Assemble the modal
        modalContent.appendChild(header);
        modalContent.appendChild(body);
        modalContent.appendChild(footer);
        modal.appendChild(modalContent);
        
        // Add styles for the modal if not already added
        if (!document.getElementById('wallet-modal-styles')) {
            const style = document.createElement('style');
            style.id = 'wallet-modal-styles';
            style.textContent = `
                .modal-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background-color: rgba(0, 0, 0, 0.7);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 9999;
                }
                .modal-content {
                    background-color: #1a1a2e;
                    border-radius: 8px;
                    width: 90%;
                    max-width: 400px;
                    box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
                    border: 1px solid #333;
                    overflow: hidden;
                }
                .modal-header {
                    color: white;
                    padding: 15px;
                    text-align: center;
                }
                .modal-header h3 {
                    margin: 0;
                    font-size: 18px;
                }
                .modal-body {
                    padding: 20px;
                    color: #fff;
                }
                .modal-body p {
                    margin: 0;
                    text-align: center;
                }
                .modal-footer {
                    padding: 15px;
                    display: flex;
                    justify-content: center;
                }
                .modal-btn {
                    background-color: #0084ff;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    padding: 10px 24px;
                    font-size: 16px;
                    cursor: pointer;
                    transition: background-color 0.2s;
                }
                .modal-btn:hover {
                    background-color: #0073e6;
                }
            `;
            document.head.appendChild(style);
        }
        
        // Allow closing by clicking outside the modal content
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
        
        // Add to body
        document.body.appendChild(modal);
        
        // Add escape key listener
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal && document.body.contains(modal)) {
                closeModal(modal);
            }
        });
    } else {
        // If modal exists, update the message
        const messageElem = modal.querySelector('.modal-body p');
        if (messageElem) messageElem.textContent = message;
        
        // Update header color and title based on type
        const header = modal.querySelector('.modal-header');
        const headerTitle = modal.querySelector('.modal-header h3');
        
        if (header && headerTitle) {
            if (type === 'error') {
                header.style.backgroundColor = '#ff3860';
                headerTitle.textContent = 'Error';
            } else if (type === 'success') {
                header.style.backgroundColor = '#23d160';
                headerTitle.textContent = 'Success';
            } else {
                header.style.backgroundColor = '#209cee';
                headerTitle.textContent = 'Notice';
            }
        }
        
        // Make sure it's visible
        modal.style.display = 'flex';
    }
}

// Helper function to close modals
function closeModal(modal) {
    if (modal && document.body.contains(modal)) {
        document.body.removeChild(modal);
    }
}

// Test AJAX connectivity
async function testAjaxConnection() {
    try {
        console.log('Testing AJAX connectivity...');
        const testEndpoint = '/includes/test_ajax.php';
        
        console.log(`Using test endpoint: ${testEndpoint}`);
        
        const response = await fetch(testEndpoint);
        
        if (!response.ok) {
            console.error(`HTTP error! Status: ${response.status}`);
            return false;
        }
        
        const responseText = await response.text();
        console.log('Raw test response:', responseText);
        
        try {
            const data = JSON.parse(responseText);
            console.log('Test endpoint returned:', data);
            return true;
        } catch (jsonError) {
            console.error('JSON parsing error in test:', jsonError);
            console.error('Response text was:', responseText);
            return false;
        }
    } catch (error) {
        console.error('Error testing AJAX connectivity:', error);
        return false;
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', async function() {
    console.log('Wallet.js initialized');
    
    // Find wallet elements
    walletBalanceElement = findWalletElement();
    lastWinElement = findLastWinElement();
    
    if (walletBalanceElement) {
        console.log('Wallet element found:', walletBalanceElement.id);
    } else {
        console.error('Wallet element not found!');
    }
    
    // Test AJAX connectivity before attempting to fetch balance
    const ajaxWorks = await testAjaxConnection();
    console.log('AJAX connectivity test result:', ajaxWorks);
    
    if (ajaxWorks) {
        // Fetch current balance from server on page load
        fetchCurrentBalance();
    } else {
        console.error('AJAX connectivity test failed - cannot connect to server');
        showNotification('Could not connect to server. Please refresh the page or contact support.', 'error');
    }
}); 