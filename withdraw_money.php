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

// Create withdrawal_requests table if it doesn't exist
try {
    $sql = "CREATE TABLE IF NOT EXISTS withdrawal_requests (
        request_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        upi_id VARCHAR(100) NOT NULL,
        utr_number VARCHAR(50) NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_note TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        processed_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
} catch (PDOException $e) {
    error_log("Error creating withdrawal_requests table: " . $e->getMessage());
}

// Initialize variables
$error = '';
$success = '';
$showForm = true;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_withdrawal'])) {
    $amount = floatval($_POST['amount'] ?? 0);
    $upi_id = trim($_POST['upi_id'] ?? '');
    
    // Validate amount and UPI ID
    if ($amount <= 0) {
        $error = 'Please enter a valid amount greater than zero.';
    } elseif ($amount > $balance) {
        $error = 'Insufficient balance for this withdrawal.';
    } elseif (empty($upi_id)) {
        $error = 'Please enter your UPI ID.';
    } else {
        try {
            // Insert withdrawal request
            $stmt = $pdo->prepare("INSERT INTO withdrawal_requests (user_id, amount, upi_id) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $amount, $upi_id]);
            
            $success = 'Your withdrawal request has been submitted and is pending approval.';
            $showForm = false;
            
        } catch (PDOException $e) {
            $error = 'There was a problem processing your withdrawal request. Please try again.';
            error_log("Error submitting withdrawal request: " . $e->getMessage());
        }
    }
}

// Get withdrawal history
$withdrawalRequests = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM withdrawal_requests WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $withdrawalRequests = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching withdrawal requests: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw Money - WinXtream</title>
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
        .withdraw-form {
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
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            color: #4CAF50;
            text-decoration: none;
        }
        .back-btn:hover {
            text-decoration: underline;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
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
        
        <h1>Withdraw Money</h1>
        <p>Current Balance: $<?php echo number_format($balance, 2); ?></p>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($showForm): ?>
            <div class="withdraw-form">
                <form method="post">
                    <div class="form-group">
                        <label for="amount">Enter Amount to Withdraw ($):</label>
                        <input type="number" id="amount" name="amount" min="1" max="<?php echo $balance; ?>" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="upi_id">Enter Your UPI ID:</label>
                        <input type="text" id="upi_id" name="upi_id" placeholder="example@upi" required>
                    </div>
                    <button type="submit" name="submit_withdrawal" class="btn">Submit Withdrawal Request</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Withdrawal History -->
        <div class="withdrawal-history">
            <h2>Withdrawal History</h2>
            
            <?php if (empty($withdrawalRequests)): ?>
                <p>No withdrawal requests found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>UPI ID</th>
                            <th>Status</th>
                            <th>UTR Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($withdrawalRequests as $request): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></td>
                                <td>$<?php echo number_format($request['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($request['upi_id']); ?></td>
                                <td class="<?php echo $request['status']; ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </td>
                                <td><?php echo $request['utr_number'] ? htmlspecialchars($request['utr_number']) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 