<?php
// Get current file path to enable conditional styles
$current_file = $_SERVER['SCRIPT_NAME'];

// Only execute this file if it's being accessed directly
if (basename($current_file) === 'fix-mobile.php') {
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

    // Initialize variables
    $error = '';
    $success = '';
    $amount = '';

    // Get the amount from session if exists
    if (isset($_SESSION['add_money_amount'])) {
        $amount = $_SESSION['add_money_amount'];
    }

    // Process UTR submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_utr'])) {
        $amount = floatval($_POST['amount'] ?? 0);
        $utr = trim($_POST['utr_number'] ?? '');
        
        if (empty($utr)) {
            $error = 'Please enter the UTR number from your payment.';
        } else {
            try {
                // Check if a payment request with this UTR already exists
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM payment_requests WHERE user_id = ? AND utr_number = ?");
                $checkStmt->execute([$_SESSION['user_id'], $utr]);
                $exists = $checkStmt->fetchColumn() > 0;
                
                if ($exists) {
                    $error = 'A payment request with this UTR number already exists.';
                } else {
                    // Insert payment request into database
                    $stmt = $pdo->prepare("INSERT INTO payment_requests (user_id, amount, utr_number) VALUES (?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $amount, $utr]);
                    
                    // Set success message in session and redirect to home
                    $_SESSION['payment_success'] = 'Your payment request has been submitted and is pending approval. Once approved, the amount will be added to your wallet.';
                    unset($_SESSION['add_money_amount']);
                    
                    header('Location: /');
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'An error occurred. Please try again.';
                error_log("Error in fix-mobile.php: " . $e->getMessage());
            }
        }
    }

    // Get pending payment requests for user
    $pendingRequests = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM payment_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$_SESSION['user_id']]);
        $pendingRequests = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching payment requests in fix-mobile.php: " . $e->getMessage());
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Submit Payment - WinXtream</title>
        <link rel="stylesheet" href="Home-Page/style.css">
        <link rel="stylesheet" href="responsive.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <style>
            body {
                background-color: #121212;
                color: #fff;
                font-family: Arial, sans-serif;
            }
            .container {
                max-width: 600px;
                margin: 40px auto;
                padding: 20px;
                background-color: #222;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
            .form-group {
                margin-bottom: 20px;
            }
            label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
            }
            input[type="text"], input[type="number"] {
                width: 100%;
                padding: 12px;
                border: 1px solid #444;
                border-radius: 4px;
                background-color: #333;
                color: #fff;
                font-size: 16px;
            }
            .btn {
                display: block;
                width: 100%;
                padding: 12px 20px;
                background-color: #4CAF50;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
                text-align: center;
            }
            .error {
                color: #ff6b6b;
                margin-bottom: 15px;
                padding: 10px;
                background-color: rgba(255, 107, 107, 0.1);
                border-radius: 4px;
            }
            .success {
                color: #4CAF50;
                margin-bottom: 15px;
                padding: 10px;
                background-color: rgba(76, 175, 80, 0.1);
                border-radius: 4px;
            }
            .back-btn {
                display: inline-block;
                margin-bottom: 20px;
                color: #4CAF50;
                text-decoration: none;
            }
            .payment-history {
                margin-top: 30px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
            }
            th, td {
                padding: 10px;
                text-align: left;
                border-bottom: 1px solid #444;
            }
            th {
                background-color: #333;
            }
            .pending { color: #ffc107; }
            .approved { color: #4CAF50; }
            .rejected { color: #ff6b6b; }
        </style>
    </head>
    <body>
        <div class="container">
            <a href="/" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Home</a>
            
            <h1>Submit Payment Details</h1>
            <p>Current Balance: $<?php echo number_format($balance, 2); ?></p>

            <?php if (!empty($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="amount">Amount to Add ($):</label>
                    <input type="number" id="amount" name="amount" value="<?php echo $amount; ?>" min="1" step="0.01" required <?php echo !empty($amount) ? 'readonly' : ''; ?>>
                    <small>This is the amount you've transferred via UPI/bank transfer.</small>
                </div>
                <div class="form-group">
                    <label for="utr_number">Enter UTR/Reference Number:</label>
                    <input type="text" id="utr_number" name="utr_number" placeholder="e.g., UTR123456789" required>
                    <small>You can find this in your payment confirmation.</small>
                </div>
                <button type="submit" name="submit_utr" class="btn">Submit Payment</button>
            </form>

            <!-- Recent Payment History -->
            <div class="payment-history">
                <h2>Recent Payment Requests</h2>
                
                <?php if (empty($pendingRequests)): ?>
                    <p>No recent payment requests.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>UTR</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingRequests as $request): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                    <td>$<?php echo number_format($request['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($request['utr_number']); ?></td>
                                    <td class="<?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
    <?php endif; ?>
            </div>
        </div>
    </body>
    </html>
<?php
}
?> 