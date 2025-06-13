<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Account.php';
require_once 'models/Transaction.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$account = new Account($db);
$transaction = new Transaction($db);

$user_data = $user->getUserById($_SESSION['user_id']);
$accounts = $account->getAccountByUserId($_SESSION['user_id']);

// Gather all transactions for all user's accounts
$all_transactions = [];
foreach ($accounts as $acc) {
    $acc_transactions = $transaction->getTransactionsByAccountId($acc['id']);
    foreach ($acc_transactions as $t) {
        $t['account_number'] = $acc['account_number'];
        $t['account_type'] = $acc['account_type'];
        $all_transactions[] = $t;
    }
}
// Sort all transactions by date descending
usort($all_transactions, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Transactions - Bank Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --dark-color: #2e59d9;
            --light-color: #f8f9fc;
        }
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background: linear-gradient(135deg, #233554 0%, #3a6ea5 100%);
            min-height: 100vh;
            color: #fff;
            padding: 20px;
        }
        .sidebar .nav-link {
            color: #fff;
            padding: 10px 20px;
            margin: 5px 0;
            border-radius: 8px;
            transition: background 0.2s, color 0.2s;
            font-weight: 500;
            font-size: 1.08rem;
        }
        .sidebar .nav-link:hover {
            background-color: #2d4379;
            color: #fff;
        }
        .sidebar .nav-link.active {
            background: linear-gradient(90deg, #36d1c4 0%, #5b86e5 100%);
            color: #fff;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.08);
        }
        .main-content {
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .table {
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
        }
        .table thead {
            background-color: var(--primary-color);
            color: white;
        }
        .badge {
            font-size: 0.95em;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar">
            <div class="text-center mb-4">
                <i class="fas fa-user-circle fa-3x mb-3"></i>
                <h4>Welcome</h4>
                <p><?php echo htmlspecialchars($user_data['full_name']); ?></p>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
                <a class="nav-link" href="accounts.php">
                    <i class="fas fa-university me-2"></i>My Accounts
                </a>
                <a class="nav-link active" href="transactions.php">
                    <i class="fas fa-exchange-alt me-2"></i>Transactions
                </a>
                <a class="nav-link" href="profile.php">
                    <i class="fas fa-user-cog me-2"></i>Profile
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </nav>
        </div>
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>All Transactions</h2>
            </div>
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Transaction History</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($all_transactions)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-exchange-alt fa-3x mb-3 text-muted"></i>
                            <h4>No Transactions Found</h4>
                            <p>Your transaction history will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Account</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_transactions as $trans): ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d H:i', strtotime($trans['created_at'])); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($trans['account_type']); ?> <br>
                                                <small>#<?php echo htmlspecialchars($trans['account_number']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    if ($trans['type'] === 'deposit') echo 'success';
                                                    elseif ($trans['type'] === 'withdrawal') echo 'danger';
                                                    elseif ($trans['type'] === 'transfer_in') echo 'info';
                                                    else echo 'warning';
                                                ?>">
                                                    <?php echo ucfirst($trans['type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($trans['description']); ?></td>
                                            <td>$<?php echo number_format($trans['amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    if (isset($trans['status'])) {
                                                        if ($trans['status'] === 'completed') echo 'success';
                                                        elseif ($trans['status'] === 'pending') echo 'warning';
                                                        else echo 'danger';
                                                    } else {
                                                        echo 'secondary';
                                                    }
                                                ?>">
                                                    <?php echo isset($trans['status']) ? ucfirst($trans['status']) : 'N/A'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 