<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user->username = $_POST['username'];
    $user->password = $_POST['password'];

    $result = $user->login();
    if ($result) {
        $_SESSION['user_id'] = $result['id'];
        $_SESSION['username'] = $result['username'];
        header('Location: dashboard.php');
        exit();
    } else {
        $message = 'Invalid username or password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - VoltBank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Montserrat', Arial, sans-serif;
            background: #1a1832;
        }
        .split-container {
            display: flex;
            min-height: 100vh;
        }
        .login-left {
            background: #fff;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 30px;
            min-width: 350px;
            box-shadow: 2px 0 20px rgba(0,0,0,0.05);
        }
        .login-logo {
            width: 60px;
            margin-bottom: 30px;
        }
        .login-title {
            font-weight: 700;
            font-size: 2rem;
            color: #3a2e7b;
            margin-bottom: 20px;
        }
        .login-form {
            width: 100%;
            max-width: 350px;
        }
        .form-label {
            font-weight: 600;
            color: #3a2e7b;
        }
        .form-control {
            border-radius: 8px;
            border: 1.5px solid #e0e0e0;
            margin-bottom: 18px;
            padding: 12px 14px;
            font-size: 1rem;
        }
        .btn-login {
            width: 100%;
            background: linear-gradient(90deg, #6a4cf6 0%, #a14eea 100%);
            color: #fff;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 1.1rem;
            margin-top: 10px;
            transition: background 0.2s;
        }
        .btn-login:hover {
            background: linear-gradient(90deg, #a14eea 0%, #6a4cf6 100%);
        }
        .register-link {
            margin-top: 18px;
            text-align: center;
        }
        .register-link a {
            color: #6a4cf6;
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        .login-warning {
            font-size: 0.85rem;
            color: #888;
            margin-top: 30px;
        }
        .login-right {
            flex: 1.5;
            background: linear-gradient(135deg, #2b1e5a 0%, #6a4cf6 60%, #a14eea 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 60px 60px 60px 80px;
            position: relative;
        }
        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }
        .welcome-subtitle {
            font-size: 1.2rem;
            opacity: 0.85;
            margin-bottom: 30px;
        }
        .brand-curve {
            position: absolute;
            right: 0;
            bottom: 0;
            width: 60%;
            max-width: 500px;
            opacity: 0.3;
        }
        @media (max-width: 900px) {
            .split-container { flex-direction: column; }
            .login-right { padding: 40px 20px; align-items: center; }
        }
        @media (max-width: 600px) {
            .split-container { flex-direction: column; }
            .login-left, .login-right { padding: 20px 10px; }
            .login-right { align-items: center; }
        }
    </style>
</head>
<body>
<div class="split-container">
    <div class="login-left">
        <div class="login-header d-flex align-items-center justify-content-center mb-4">
            <img src="Letter D Logo With Financial and Money Concept..jpg" alt="VoltBank Logo" class="login-logo me-3" style="width:60px; height:60px;">
            <div class="login-title" style="font-size:2.2rem; font-weight:700; color:#3a2e7b;">VoltBank</div>
        </div>
        <?php if ($message): ?>
            <div class="alert alert-danger w-100 text-center"><?php echo $message; ?></div>
        <?php endif; ?>
        <form class="login-form" method="POST">
            <label for="username" class="form-label">ID or Username</label>
            <input type="text" class="form-control" id="username" name="username" required autofocus>
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
            <button type="submit" class="btn btn-login">LOG IN</button>
        </form>
        <div class="register-link">
            <span>Don't have an account? <a href="register.php">Sign up</a></span>
        </div>
        <div class="login-warning">
            <span>Warning!<br>Do not share your account credentials with anyone.</span>
        </div>
    </div>
    <div class="login-right">
        <div>
            <div class="welcome-title">Welcome to <span style="color:#a14eea;">VoltBank</span></div>
            <div class="welcome-subtitle">Log in to access your account</div>
        </div>
        <!-- Decorative SVG or image for background branding -->
        <svg class="brand-curve" viewBox="0 0 600 400" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0 300 Q300 400 600 200 L600 400 L0 400 Z" fill="#fff" fill-opacity="0.1"/>
            <path d="M0 350 Q300 450 600 250" stroke="#fff" stroke-opacity="0.2" stroke-width="8"/>
        </svg>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 