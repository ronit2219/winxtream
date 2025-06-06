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

/* Mobile Responsive Styles */
@media (max-width: 920px) {
    .nav-container {
        flex-direction: column;
        align-items: stretch;
        padding: 10px;
    }
    
    .nav-logo {
        margin-bottom: 10px;
        text-align: center;
    }
    
    .nav-links {
        margin: 5px 0;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .nav-links a {
        margin: 3px;
    }
    
    .user-info, .wallet-info, .auth-buttons {
        margin-top: 10px;
        justify-content: center;
    }
    
    .wallet-info {
        margin-right: 0;
    }
    
    /* Fix add/withdraw money buttons in mobile view */
    .add-money-btn, .withdraw-money-btn {
        width: 28px !important;
        height: 28px !important;
        margin: 0 3px !important;
    }
    
    .add-money-btn svg, .withdraw-money-btn svg {
        width: 16px !important;
        height: 16px !important;
    }
    
    .wallet-balance {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
}

@media (max-width: 480px) {
    .nav-links a {
        padding: 6px 10px;
        font-size: 0.9rem;
    }
    
    /* Further improvements for smaller screens */
    .add-money-btn, .withdraw-money-btn {
        width: 32px !important;
        height: 32px !important;
        margin: 0 5px !important;
    }
    
    .add-money-btn svg, .withdraw-money-btn svg {
        width: 18px !important;
        height: 18px !important;
    }
}

/* Force navbar background color across all pages */
.navbar {
    background-color: #152431 !important;
}
</style>

<!-- Mobile-specific fixes for add/withdraw money buttons -->
<style>
@media (max-width: 768px) {
    /* Ensure buttons are larger and more visible on mobile */
    .add-money-btn, .withdraw-money-btn {
        width: 36px !important;
        height: 36px !important;
        margin: 0 5px !important;
    }
    
    /* Make icons more visible */
    .add-money-btn svg, .withdraw-money-btn svg {
        width: 20px !important;
        height: 20px !important;
    }
    
    /* Better spacing for wallet section */
    .wallet-balance {
        padding: 8px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
}
</style>

<!-- Fix for double scrollbar in mobile view -->
<style>
@media (max-width: 768px) {
    /* Fix double scrollbar issue */
    html, body {
        overflow-x: hidden !important;
        position: relative !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    
    body {
        overflow-y: auto !important;
    }
    
    /* Fix any container that might be causing overflow */
    .game-container, .main-container, main, .container {
        max-width: 100% !important;
        overflow-x: hidden !important;
        box-sizing: border-box !important;
    }
    
    /* Ensure the main navbar container doesn't cause overflow */
    .navbar {
        max-width: 100% !important;
        overflow: hidden !important;
    }
    
    /* Prevent horizontal overflow in wallet section */
    .wallet-section {
        flex-wrap: wrap !important;
        justify-content: center !important;
    }
}
</style> 