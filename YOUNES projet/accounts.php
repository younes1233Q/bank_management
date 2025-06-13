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
        $account->account_holder = trim($_POST['account_holder']);
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
    } elseif (isset($_POST['delete_account'])) {
        $account_id = $_POST['account_id'];
        if ($account->deleteAccount($account_id)) {
            $message = 'Account deleted successfully!';
            $accounts = $account->getAccountByUserId($_SESSION['user_id']);
        } else {
            $message = 'Failed to delete account.';
        }
    } elseif (isset($_POST['transfer'])) {
        $from_account = $account->getAccountByNumber($_POST['from_account']);
        $to_account = $account->getAccountByNumber($_POST['to_account']);
        $amount = floatval($_POST['amount']);
        if ($from_account && $to_account && $amount > 0 && $from_account['id'] != $to_account['id']) {
            if ($transaction->transfer($from_account['id'], $to_account['id'], $amount)) {
                $message = 'Transfer successful!';
                $accounts = $account->getAccountByUserId($_SESSION['user_id']);
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
    <title>My Accounts - Bank Management System</title>
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
        .account-card {
            background: #fff;
            color: #222;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(44, 62, 80, 0.08);
            border: 1.5px solid #e3e6f0;
        }
        .account-card .card-title {
            color: #2b3674;
            font-weight: 700;
        }
        .account-card .card-text {
            color: #4e5d78;
        }
        .input-group .form-control {
            border-radius: 8px 0 0 8px;
            border: 1.5px solid #e3e6f0;
        }
        .btn-deposit {
            background: #2b3674;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            margin-left: 8px;
            transition: background 0.2s;
        }
        .btn-deposit:hover {
            background: #4e73df;
        }
        .btn-withdraw {
            background: #f44336;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            margin-left: 8px;
            transition: background 0.2s;
        }
        .btn-withdraw:hover {
            background: #c62828;
        }
        .btn-transfer {
            background: linear-gradient(90deg, #36d1c4 0%, #5b86e5 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 8px;
            transition: background 0.2s;
        }
        .btn-transfer:hover {
            background: linear-gradient(90deg, #5b86e5 0%, #36d1c4 100%);
        }
        .btn-delete {
            background: #e74c3c;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-delete:hover {
            background: #c0392b;
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
                <a class="nav-link active" href="accounts.php">
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
                <h2>My Accounts</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAccountModal">
                    <i class="fas fa-plus me-2"></i>Create Account
                </button>
            </div>
            <?php if ($message): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <div class="row">
                <?php if (empty($accounts)): ?>
                    <div class="col-12 text-center py-4">
                        <i class="fas fa-university fa-3x mb-3 text-muted"></i>
                        <h4>No Accounts Found</h4>
                        <p>Create your first account to start banking!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($accounts as $acc): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
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
                                    <form method="POST" class="mb-2 d-flex align-items-center">
                                        <input type="hidden" name="account_id" value="<?php echo $acc['id']; ?>">
                                        <input type="number" step="0.01" class="form-control" name="amount" placeholder="Amount" required style="max-width: 120px;">
                                        <button type="submit" name="deposit" class="btn btn-deposit ms-2">Deposit</button>
                                        <button type="submit" name="withdraw" class="btn btn-withdraw ms-2">Withdraw</button>
                                    </form>
                                    <button type="button" class="btn btn-transfer w-100 mb-2" data-bs-toggle="modal" data-bs-target="#transferModal" onclick="setFromAccount('<?php echo htmlspecialchars($acc['account_number']); ?>')">
                                        <i class="fas fa-exchange-alt me-2"></i>Transfer
                                    </button>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this account? This action cannot be undone.');">
                                        <input type="hidden" name="account_id" value="<?php echo $acc['id']; ?>">
                                        <button type="submit" name="delete_account" class="btn btn-delete w-100"><i class="fas fa-trash me-2"></i>Delete Account</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
                    <div class="mb-3">
                        <label for="account_holder" class="form-label">Account Holder Name</label>
                        <input type="text" class="form-control" id="account_holder" name="account_holder" placeholder="Enter account holder's name" required>
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
<!-- Transfer Modal -->
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transfer Money</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="from_account" class="form-label">From Account</label>
                        <select class="form-select" id="from_account" name="from_account" required>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?php echo htmlspecialchars($acc['account_number']); ?>">
                                    <?php echo htmlspecialchars($acc['account_holder'] ?? $user_data['full_name']); ?> (<?php echo htmlspecialchars($acc['account_number']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="to_account" class="form-label">To Account</label>
                        <select class="form-select" id="to_account" name="to_account" required>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?php echo htmlspecialchars($acc['account_number']); ?>">
                                    <?php echo htmlspecialchars($acc['account_holder'] ?? $user_data['full_name']); ?> (<?php echo htmlspecialchars($acc['account_number']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="transfer_amount" class="form-label">Amount</label>
                        <input type="number" step="0.01" class="form-control" id="transfer_amount" name="amount" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="transfer" class="btn btn-warning">Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function setFromAccount(accountNumber) {
    document.getElementById('from_account').value = accountNumber;
}
</script>
</body>
</html> 