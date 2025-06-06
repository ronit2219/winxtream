<?php
require_once 'includes/auth_functions.php';
require_once 'includes/db_connect.php';

// Check if user is logged in, redirect to login if not
if (!isLoggedIn()) {
    header('Location: /Home-Page/login.php');
    exit;
}

// Get current user's data
$user = getCurrentUser(true);
$balance = $_SESSION['balance'] ?? 0;

// Debug POST data
error_log("POST data: " . print_r($_POST, true));
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);

// Create payment_requests table if it doesn't exist
try {
    $sql = "CREATE TABLE IF NOT EXISTS payment_requests (
        request_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        utr_number VARCHAR(50) NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
} catch (PDOException $e) {
    error_log("Error creating payment_requests table: " . $e->getMessage());
}

// At the beginning of the file, initialize variables
$error = '';
$success = '';
$amount = '';
$showQR = false;

// Check if there's a session variable for amount to maintain it during page reloads
if (isset($_SESSION['add_money_amount']) && !isset($_POST['submit_amount'])) {
    $amount = $_SESSION['add_money_amount'];
    $showQR = true;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Processing POST request");
    
    if (isset($_POST['submit_amount'])) {
        error_log("Amount form submitted");
        // Step 1: User submitted amount
        $amount = floatval($_POST['amount'] ?? 0);
        error_log("Amount value: " . $amount);
        
        if ($amount <= 0) {
            $error = 'Please enter a valid amount greater than zero.';
            error_log("Invalid amount: " . $amount);
        } else {
            // Store amount in session to maintain it during page reloads
            $_SESSION['add_money_amount'] = $amount;
            // Show QR code for payment
            $showQR = true;
            error_log("Showing QR code for amount: " . $amount);
        }
    } else if (isset($_POST['submit_utr'])) {
        error_log("UTR form submitted");
        // Step 2: User submitted UTR after payment
        $amount = floatval($_POST['amount'] ?? 0);
        $utr = trim($_POST['utr_number'] ?? '');
        error_log("UTR form data - Amount: " . $amount . ", UTR: " . $utr);
        
        if (empty($utr)) {
            $error = 'Please enter the UTR number from your payment.';
            $showQR = true;
            error_log("Empty UTR number");
        } else {
            try {
                // Check if a payment request with this UTR already exists
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM payment_requests WHERE user_id = ? AND utr_number = ?");
                $checkStmt->execute([$_SESSION['user_id'], $utr]);
                $exists = $checkStmt->fetchColumn() > 0;
                
                if ($exists) {
                    $error = 'A payment request with this UTR number already exists. Please check your payment history below.';
                    $showQR = false;
                } else {
                    try {
                        // Insert payment request into database
                        $stmt = $pdo->prepare("INSERT INTO payment_requests (user_id, amount, utr_number) VALUES (?, ?, ?)");
                        $stmt->execute([$_SESSION['user_id'], $amount, $utr]);
                        
                        // Set success message in session to display it on the home page
                        $_SESSION['payment_success'] = 'Your payment request has been submitted and is pending approval. Once approved, the amount will be added to your wallet.';
                        
                        // Clear the add_money session variable
                        unset($_SESSION['add_money_amount']);
                        
                        // Redirect to home page after successful submission
                        header('Location: /');
                        exit;
                    } catch (PDOException $insertError) {
                        $error = 'There was a problem processing your payment request. Please try again.';
                        error_log("Error inserting payment request: " . $insertError->getMessage());
                        $showQR = true;
                    }
                }
            } catch (PDOException $e) {
                $error = 'An error occurred. Please try again.';
                error_log("Error submitting payment request: " . $e->getMessage());
                $showQR = true;
            }
        }
    }
}

// After successful payment request submission, clear the session variable
if (!empty($success)) {
    unset($_SESSION['add_money_amount']);
}

// Get pending payment requests
$pendingRequests = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM payment_requests WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $pendingRequests = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching payment requests: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Money to Wallet - WinXtream</title>
    <link rel="stylesheet" href="Home-Page/style.css">
    <link rel="stylesheet" href="responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background-color: #222;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .add-money-form {
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="number"], input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #444;
            border-radius: 4px;
            background-color: #333;
            color: #fff;
            font-size: 16px;
        }
        .btn {
            display: inline-block;
            padding: 12px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .error {
            color: #ff6b6b;
            margin-bottom: 15px;
        }
        .success {
            color: #4CAF50;
            margin-bottom: 15px;
        }
        .qr-section {
            text-align: center;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid #444;
            border-radius: 8px;
            background-color: #333;
        }
        .qr-code {
            max-width: 200px;
            margin: 0 auto;
            display: block;
        }
        .payment-instructions {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            background-color: #444;
            line-height: 1.6;
        }
        .payment-instructions ol {
            padding-left: 20px;
        }
        .payment-history {
            margin-top: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #444;
        }
        th {
            background-color: #333;
        }
        .pending {
            color: #ffc107;
        }
        .approved {
            color: #4CAF50;
        }
        .rejected {
            color: #ff6b6b;
        }
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            color: #4CAF50;
            text-decoration: none;
        }
        .back-btn:hover {
            text-decoration: underline;
        }
    </style>
    <script>
        // Add some debugging
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded');
            
            // Log form submissions
            const amountForm = document.getElementById('amount-form');
            if (amountForm) {
                console.log('Amount form found');
                amountForm.addEventListener('submit', function() {
                    console.log('Amount form submitted');
                });
            }
            
            const utrForm = document.getElementById('utr-form');
            if (utrForm) {
                console.log('UTR form found');
                utrForm.addEventListener('submit', function() {
                    console.log('UTR form submitted');
                });
            }
        });
    </script>
</head>
<body>
    <div class="container">
        <a href="/" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Home</a>
        
        <h1>Add Money to Wallet</h1>
        <p>Current Balance: $<?php echo number_format($balance, 2); ?></p>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!$showQR && empty($success)): ?>
            <!-- Step 1: Enter amount -->
            <div class="add-money-form">
                <form method="post">
                    <div class="form-group">
                        <label for="amount">Enter Amount to Add ($):</label>
                        <input type="number" id="amount" name="amount" min="1" step="0.01" required>
                    </div>
                    <button type="submit" name="submit_amount" class="btn">Continue</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($showQR): ?>
            <!-- Step 2: Show QR code and get UTR -->
            <div class="qr-section">
                <h2>Make Payment</h2>
                <p>Please scan the QR code below or use the account details to transfer $<?php echo number_format($amount, 2); ?></p>
                
                <img src="/Home-Page/Asset/QR.jpg" alt="Payment QR Code" class="qr-code">
                
                <div class="payment-instructions">
                    <h3>Payment Instructions:</h3>
                    <ol>
                        <li>Scan the QR code using any UPI payment app</li>
                        <li>Enter the exact amount: $<?php echo number_format($amount, 2); ?></li>
                        <li>Complete the payment</li>
                        <li>Note down the UTR/Reference number from your payment confirmation</li>
                        <li>Enter the UTR number below and submit to complete the process</li>
                    </ol>
                </div>
                
                <form method="post">
                    <input type="hidden" name="amount" value="<?php echo $amount; ?>">
                    <div class="form-group">
                        <label for="utr_number">Enter UTR/Reference Number:</label>
                        <input type="text" id="utr_number" name="utr_number" placeholder="e.g., UTR123456789" required>
                    </div>
                    <button type="submit" name="submit_utr" class="btn">Submit Payment</button>
                </form>
                
                <div style="margin-top: 20px; text-align: center;">
                    <p>Having trouble with this page? <a href="/fix-mobile.php" style="color: #4CAF50;">Try our mobile-friendly version</a></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Payment History -->
        <div class="payment-history">
            <h2>Payment History</h2>
            
            <?php if (empty($pendingRequests)): ?>
                <p>No payment requests found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>UTR Number</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingRequests as $request): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></td>
                                <td>$<?php echo number_format($request['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($request['utr_number']); ?></td>
                                <td class="<?php echo $request['status']; ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 