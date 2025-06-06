<?php
// Get the current script name to highlight active nav item
$current_script = $_SERVER['SCRIPT_NAME'];
$is_home = $current_script === '/index.php';
$is_roulette = strpos($current_script, 'roulette') !== false;
$is_mines = strpos($current_script, 'mines') !== false;
$is_crash = strpos($current_script, 'crash-game') !== false;
$is_color_trading = strpos($current_script, 'ColorTrad') !== false;

// Include auth functions if not already included
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/auth_functions.php';
}

// Check login status using isLoggedIn function
$userIsLoggedIn = isLoggedIn();
?>
<nav class="navbar">
    <button class="mobile-menu-toggle" id="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="logo-section">
        <div class="logo navbar-brand"><img src="/Home-Page/Asset/WinXStream-removebg-preview.png" alt="WinXtream"></div>
        <div class="nav-links nav-menu" id="nav-menu">
            <a href="/index.php" <?php echo $is_home ? 'class="active"' : ''; ?>>Home</a>
            <a href="/roulette/roullete.php" <?php echo $is_roulette ? 'class="active"' : ''; ?>>Roulette</a>
            <a href="/mines/mines.php" <?php echo $is_mines ? 'class="active"' : ''; ?>>Mines</a>
            <a href="/crash-game/crash.php" <?php echo $is_crash ? 'class="active"' : ''; ?>>Crash</a>
            <a href="/ColorTrad/color-trade.php" <?php echo $is_color_trading ? 'class="active"' : ''; ?>>Color Trading</a>
        </div>
    </div>
    <div class="wallet-section">
        <div class="wallet-balance">
            <svg class="wallet-icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M12.136.326A1.5 1.5 0 0 1 14 1.78V3h.5A1.5 1.5 0 0 1 16 4.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-9a1.5 1.5 0 0 1 1.432-1.499L12.136.326zM5.562 3H13V1.78a.5.5 0 0 0-.621-.484L5.562 3zM1.5 4a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-13z"/>
            </svg>
            $<span id="wallet-display"><?php echo number_format($wallet_balance, 2); ?></span>
            <?php if ($userIsLoggedIn): ?>
                <a href="/add_money.php" id="add-money-btn" class="add-money-btn" title="Add Money">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/>
                    </svg>
                </a>
                <a href="/withdraw_money.php" id="withdraw-money-btn" class="withdraw-money-btn" title="Withdraw Money">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11H7v-2h10v2z"/>
                    </svg>
                </a>
            <?php endif; ?>
        </div>
        <?php if (isset($show_last_win) && $show_last_win): ?>
            <div class="wallet-balance">
                Last Win: $<span id="last-win">0</span>
            </div>
        <?php endif; ?>
        
        <?php if ($userIsLoggedIn): ?>
            <?php if (!isset($hideUsernameAndLogout) || !$hideUsernameAndLogout): ?>
                <div class="user-info" style="display: flex; align-items: center;">
                    <span id="username-display">
                        <?php 
                        if (isset($user) && isset($user['username'])) {
                            echo htmlspecialchars($user['username']);
                        } elseif (isset($_SESSION['username'])) {
                            echo htmlspecialchars($_SESSION['username']);
                        } else {
                            echo 'User';
                        }
                        ?>
                    </span>
                    <a href="/?logout=1" id="logout-btn" class="nav-button">Logout</a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="auth-buttons">
                <a href="/Home-Page/login.php" id="login-btn" class="nav-button">Login</a>
                <a href="/Home-Page/signup.php" id="signup-btn" class="nav-button signup-btn">Signup</a>
            </div>
        <?php endif; ?>
    </div>
</nav>

<style>
/* Navigation Bar Styles */
.navbar {
    background-color: #152431;
    color: white;
    width: 100%;
    padding: 10px 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    margin: 0;
    position: relative;
    z-index: 100; /* Ensure navbar is on top of other elements */
}

.add-money-btn, .withdraw-money-btn {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    margin-left: 5px;
    width: 20px;
    height: 20px;
    color: white;
    border-radius: 50%;
    text-decoration: none;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s;
}

.add-money-btn {
    background-color: #4CAF50;
}

.add-money-btn:hover {
    background-color: #45a049;
}

.add-money-btn svg {
    width: 14px;
    height: 14px;
    fill: white;
}

.withdraw-money-btn {
    background-color: #ff6b6b;
}

.withdraw-money-btn svg {
    width: 14px;
    height: 14px;
    fill: white;
}

.withdraw-money-btn:hover {
    background-color: #ff5252;
}

.nav-button {
    display: inline-block;
    padding: 8px 16px;
    margin-left: 10px;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.nav-button:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.signup-btn {
    background-color: #4CAF50;
}

.signup-btn:hover {
    background-color: #45a049;
}

#username-display {
    margin-right: 10px;
    color: white;
}

.auth-buttons {
    display: flex;
    align-items: center;
}

@media (max-width: 768px) {
    .auth-buttons {
        margin-top: 10px;
    }
    
    .nav-button {
        margin: 5px;
        text-align: center;
    }
}
</style> 
