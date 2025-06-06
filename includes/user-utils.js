/**
 * User utilities for managing user data
 */

// Check if user is logged in
function isLoggedIn() {
    return document.getElementById('username-display') && 
           document.getElementById('username-display').innerText !== '';
}

// Get current wallet balance
function getWalletBalance() {
    const balanceElement = document.getElementById('wallet-display');
    if (balanceElement) {
        return parseFloat(balanceElement.innerText.replace(/,/g, ''));
    }
    return 0;
}

// Format currency
function formatCurrency(amount) {
    return parseFloat(amount).toFixed(2);
}

// Display notification
function showNotification(message, isError = false) {
    const notificationEl = document.getElementById('notification');
    if (!notificationEl) return;
    
    notificationEl.textContent = message;
    notificationEl.className = 'notification' + (isError ? ' error' : ' success');
    
    // Show notification
    notificationEl.style.opacity = '1';
    notificationEl.style.transform = 'translateY(0)';
    
    // Hide after 3 seconds
    setTimeout(() => {
        notificationEl.style.opacity = '0';
        notificationEl.style.transform = 'translateY(-20px)';
    }, 3000);
} 