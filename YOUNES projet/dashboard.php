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

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_account'])) {
        $account->user_id = $_SESSION['user_id'];
        $account->account_type = $_POST['account_type'];
        
        if ($account->create()) {
            $message = 'Account created successfully!';
            $accounts = $account->getAccountByUserId($_SESSION['user_id']);
        } else {
            $message = 'Failed to create account.';
        }
    } elseif (isset($_POST['deposit'])) {
        $account->id = $_POST['account_id'];
        $amount = floatval($_POST['amount']);
        
        if ($amount > 0) {
            $transaction->account_id = $_POST['account_id'];
            $transaction->type = 'deposit';
            $transaction->amount = $amount;
            $transaction->description = 'Deposit';
            
            if ($transaction->create() && $account->updateBalance($amount)) {
                $message = 'Deposit successful!';
            } else {
                $message = 'Deposit failed.';
            }
        } else {
            $message = 'Invalid amount.';
        }
    } elseif (isset($_POST['withdraw'])) {
        $account->id = $_POST['account_id'];
        $amount = floatval($_POST['amount']);
        
        if ($amount > 0) {
            $transaction->account_id = $_POST['account_id'];
            $transaction->type = 'withdrawal';
            $transaction->amount = -$amount;
            $transaction->description = 'Withdrawal';
            
            if ($transaction->create() && $account->updateBalance(-$amount)) {
                $message = 'Withdrawal successful!';
            } else {
                $message = 'Withdrawal failed.';
            }
        } else {
            $message = 'Invalid amount.';
        }
    } elseif (isset($_POST['transfer'])) {
        $from_account = $account->getAccountByNumber($_POST['from_account']);
        $to_account = $account->getAccountByNumber($_POST['to_account']);
        $amount = floatval($_POST['amount']);
        
        if ($from_account && $to_account && $amount > 0) {
            if ($transaction->transfer($from_account['id'], $to_account['id'], $amount)) {
                $message = 'Transfer successful!';
            } else {
                $message = 'Transfer failed.';
            }
        } else {
            $message = 'Invalid transfer details.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Bank Management System</title>
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

        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
            color: white;
        }

        .stat-card .icon {
            font-size: 2rem;
            opacity: 0.8;
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

        .btn {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
            border: none;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--secondary-color), #17a673);
            border: none;
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            border: none;
        }

        .account-card {
            background: linear-gradient(135deg, #3a6ea5 0%, #233554 100%);
            color: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(44, 62, 80, 0.10);
            border: none;
        }

        .account-card .card-title {
            color: #fff;
            font-weight: 700;
        }

        .account-card .card-text {
            color: #e3e6f0;
        }

        .transaction-card {
            background: white;
        }

        .transaction-card .transaction-type {
            font-weight: 600;
        }

        .transaction-card .transaction-amount {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .transaction-card .transaction-date {
            color: #6c757d;
            font-size: 0.9rem;
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
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="accounts.php">
                        <i class="fas fa-university me-2"></i>My Accounts
                    </a>
                    <a class="nav-link" href="transactions.php">
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
                    <h2>Dashboard</h2>
                    <div>
                        <span class="text-muted">Welcome, <?php echo htmlspecialchars($user_data['full_name']); ?></span>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Account Summary -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Account Summary</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($accounts)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-university fa-3x mb-3 text-muted"></i>
                                        <h4>No Accounts Found</h4>
                                        <p>Create your first account to start banking!</p>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAccountModal">
                                            <i class="fas fa-plus me-2"></i>Create Account
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($accounts as $acc): ?>
                                            <div class="col-md-4 mb-4">
                                                <div class="card account-card">
                                                    <div class="card-body">
                                                        <h5 class="card-title">
                                                            <i class="fas fa-university me-2"></i>
                                                            <?php echo ucfirst($acc['account_type']); ?> Account
                                                        </h5>
                                                        <p class="card-text">
                                                            Account #: <?php echo htmlspecialchars($acc['account_number']); ?><br>
                                                            <strong>Account Holder:</strong> <?php echo htmlspecialchars($acc['account_holder'] ?? $user_data['full_name']); ?><br>
                                                            Balance: $<?php echo number_format($acc['balance'], 2); ?>
                                                        </p>
                                                        <div class="btn-group w-100">
                                                            <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#depositModal" data-account-id="<?php echo $acc['id']; ?>">
                                                                <i class="fas fa-plus me-2"></i>Deposit
                                                            </button>
                                                            <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#withdrawModal" data-account-id="<?php echo $acc['id']; ?>">
                                                                <i class="fas fa-minus me-2"></i>Withdraw
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Recent Transactions</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $recent_transactions = $transaction->getRecentTransactionsByUserId($_SESSION['user_id'], 5);
                                if (empty($recent_transactions)):
                                ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-exchange-alt fa-3x mb-3 text-muted"></i>
                                        <h4>No Transactions Yet</h4>
                                        <p>Your transaction history will appear here.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Description</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_transactions as $trans): ?>
                                                    <tr>
                                                        <td><?php echo date('Y-m-d H:i', strtotime($trans['created_at'])); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $trans['type'] === 'deposit' ? 'success' : ($trans['type'] === 'withdrawal' ? 'danger' : 'info'); ?>">
                                                                <?php echo ucfirst($trans['type']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($trans['description']); ?></td>
                                                        <td>$<?php echo number_format($trans['amount'], 2); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $trans['status'] === 'completed' ? 'success' : ($trans['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                                <?php echo ucfirst($trans['status']); ?>
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
        </div>
    </div>

    <!-- Create Account Modal -->
    <div class="modal fade" id="createAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="account_type" class="form-label">Account Type</label>
                            <select class="form-select" id="account_type" name="account_type" required>
                                <option value="savings">Savings</option>
                                <option value="checking">Checking</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_account" class="btn btn-primary">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Deposit Modal -->
    <div class="modal fade" id="depositModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Deposit Money</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="account_id" id="deposit_account_id">
                        <div class="mb-3">
                            <label for="deposit_amount" class="form-label">Amount</label>
                            <input type="number" step="0.01" class="form-control" id="deposit_amount" name="amount" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="deposit" class="btn btn-success">Deposit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Withdraw Modal -->
    <div class="modal fade" id="withdrawModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Withdraw Money</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="account_id" id="withdraw_account_id">
                        <div class="mb-3">
                            <label for="withdraw_amount" class="form-label">Amount</label>
                            <input type="number" step="0.01" class="form-control" id="withdraw_amount" name="amount" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="withdraw" class="btn btn-danger">Withdraw</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set account ID in modals
        document.querySelectorAll('[data-bs-target="#depositModal"]').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('deposit_account_id').value = this.dataset.accountId;
            });
        });

        document.querySelectorAll('[data-bs-target="#withdrawModal"]').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('withdraw_account_id').value = this.dataset.accountId;
            });
        });
    </script>
</body>
</html> 