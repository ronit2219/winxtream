document.getElementById('signup-form').addEventListener('submit', function (e) {
    e.preventDefault();

    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm-password').value;

    const usernameError = document.getElementById('username-error');
    const passwordError = document.getElementById('password-error');

    // Reset errors
    usernameError.style.display = 'none';
    passwordError.style.display = 'none';

    // Check if passwords match
    if (password !== confirmPassword) {
        passwordError.style.display = 'block';
        return;
    }

    // Get existing users
    let users = JSON.parse(localStorage.getItem('users')) || [];

    // Check if username already exists
    if (users.some(user => user.username === username)) {
        usernameError.style.display = 'block';
        return;
    }

    // Add new user
    users.push({
        username,
        email,
        password,
        balance: 1000
    });

    // Save to local storage
    localStorage.setItem('users', JSON.stringify(users));

    // Set current user
    localStorage.setItem('currentUser', JSON.stringify({
        username,
        balance: 1000
    }));

    // Redirect to home page
    window.location.href = "index.html";
});