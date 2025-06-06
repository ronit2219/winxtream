/**
 * User utilities for managing user data in local storage
 */

// Get current user data from local storage
function getCurrentUser() {
    return JSON.parse(localStorage.getItem('currentUser'));
}

// Check if user is logged in
function isLoggedIn() {
    return localStorage.getItem('currentUser') !== null;
}

// Update user balance
function updateUserBalance(newBalance) {
    const currentUser = getCurrentUser();
    if (!currentUser) return false;
    
    currentUser.balance = parseFloat(newBalance);
    
    // Update currentUser in localStorage
    localStorage.setItem('currentUser', JSON.stringify(currentUser));
    
    // Update user in users array
    const users = JSON.parse(localStorage.getItem('users')) || [];
    const userIndex = users.findIndex(user => user.username === currentUser.username);
    
    if (userIndex !== -1) {
        users[userIndex].balance = currentUser.balance;
        localStorage.setItem('users', JSON.stringify(users));
    }
    
    return true;
}

// Add to user balance (positive for win, negative for loss)
function addToBalance(amount) {
    const currentUser = getCurrentUser();
    if (!currentUser) return false;
    
    const newBalance = parseFloat(currentUser.balance) + parseFloat(amount);
    return updateUserBalance(newBalance);
}

// Check if user has enough balance for a bet
function hasEnoughBalance(betAmount) {
    const currentUser = getCurrentUser();
    if (!currentUser) return false;
    
    return parseFloat(currentUser.balance) >= parseFloat(betAmount);
}

// Redirect to login if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        window.location.href = '/Home-Page/login.html';
        return false;
    }
    return true;
} 