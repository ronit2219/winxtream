// Function to check if user is logged in and update UI accordingly
document.addEventListener('DOMContentLoaded', function() {
    // Get UI elements
    const loginBtn = document.getElementById('login-btn');
    const signupBtn = document.getElementById('signup-btn');
    const userInfoDiv = document.getElementById('user-info');
    const usernameDisplay = document.getElementById('username-display');
    const logoutBtn = document.getElementById('logout-btn');
    const walletBalance = document.getElementById('wallet-balance');

    // Check if user is logged in
    function updateAuthUI() {
        const currentUser = JSON.parse(localStorage.getItem('currentUser'));
        
        if (currentUser) {
            // User is logged in, hide login/signup buttons and show user info
            loginBtn.style.display = 'none';
            signupBtn.style.display = 'none';
            userInfoDiv.style.display = 'flex';
            
            // Display username and update wallet balance
            usernameDisplay.textContent = currentUser.username;
            if (walletBalance) {
                walletBalance.textContent = currentUser.balance;
            }
        } else {
            // User is not logged in, show login/signup buttons and hide user info
            loginBtn.style.display = 'inline-block';
            signupBtn.style.display = 'inline-block';
            userInfoDiv.style.display = 'none';
        }
    }
    
    // Handle logout
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Clear user data from local storage
            localStorage.removeItem('currentUser');
            // Update UI
            updateAuthUI();
        });
    }

    // Initial UI update
    updateAuthUI();
});
