<?php
session_start();
require_once '../config/database.php';
require_once '../models/Admin.php';
require_once '../models/User.php';
require_once '../models/Account.php';
require_once '../models/Transaction.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$admin = new Admin($db);
$user = new User($db);
$account = new Account($db);
$transaction = new Transaction($db);

$admin_data = $admin->getAdminById($_SESSION['admin_id']);

// Get statistics
$total_users = $user->getTotalUsers();
$total_accounts = $account->getTotalAccounts();
$total_transactions = $transaction->getTotalTransactions();
$total_balance = $account->getTotalBalance();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_admin']) && $_SESSION['admin_role'] === 'super_admin') {
        $admin->username = $_POST['username'];
        $admin->password = $_POST['password'];
        $admin->email = $_POST['email'];
        $admin->full_name = $_POST['full_name'];
        $admin->role = $_POST['role'];

        if ($admin->create()) {
            $message = 'Admin created successfully!';
        } else {
            $message = 'Failed to create admin.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bank Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #e74c3c;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            color: white;
            padding: 20px;
        }

        .sidebar .nav-link {
            color: white;
            padding: 10px 20px;
            margin: 5px 0;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link.active {
            background-color: var(--accent-color);
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
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
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
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--accent-color), #c0392b);
            border: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="text-center mb-4">
                    <i class="fas fa-user-tie fa-3x mb-3"></i>
                    <h4>Admin Panel</h4>
                    <p><?php echo htmlspecialchars($admin_data['full_name']); ?></p>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users me-2"></i>Users
                    </a>
                    <a class="nav-link" href="accounts.php">
                        <i class="fas fa-university me-2"></i>Accounts
                    </a>
                    <a class="nav-link" href="transactions.php">
                        <i class="fas fa-exchange-alt me-2"></i>Transactions
                    </a>
                    <?php if ($_SESSION['admin_role'] === 'super_admin'): ?>
                    <a class="nav-link" href="admins.php">
                        <i class="fas fa-user-shield me-2"></i>Admins
                    </a>
                    <?php endif; ?>
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog me-2"></i>Settings
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
                        <span class="text-muted">Welcome, <?php echo htmlspecialchars($admin_data['full_name']); ?></span>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Users</h6>
                                        <h3><?php echo $total_users; ?></h3>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Accounts</h6>
                                        <h3><?php echo $total_accounts; ?></h3>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-university"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Transactions</h6>
                                        <h3><?php echo $total_transactions; ?></h3>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-exchange-alt"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Balance</h6>
                                        <h3>$<?php echo number_format($total_balance, 2); ?></h3>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-dollar-sign"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="card-title mb-0">Recent Activity</h5>
                            </div>
                            <div class="card-body">
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
                                            <?php
                                            $recent_transactions = $transaction->getRecentTransactions(5);
                                            foreach ($recent_transactions as $trans):
                                            ?>
                                            <tr>
                                                <td><?php echo date('Y-m-d H:i', strtotime($trans['created_at'])); ?></td>
                                                <td><?php echo ucfirst($trans['type']); ?></td>
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 