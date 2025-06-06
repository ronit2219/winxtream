document.getElementById('login-form').addEventListener('submit', function (e) {
    e.preventDefault();

    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    const errorElement = document.getElementById('login-error');

    // Get users from local storage
    const users = JSON.parse(localStorage.getItem('users')) || [];

    // Find user
    const user = users.find(u => u.username === username && u.password === password);

    if (user) {
        // Store logged in user info
        localStorage.setItem('currentUser', JSON.stringify({
            username: user.username,
            balance: user.balance || 1000
        }));

        // Redirect to home page
        window.location.href = "index.html";
    } 
    else {
        // Show error
        errorElement.style.display = 'block';
    }
});