<?php
// register.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'medical_system');
define('DB_USER', 'medical_user');
define('DB_PASS', 'medical123');

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", 
                       DB_USER, DB_PASS, 
                       [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        // 1. 表单验证
        if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['confirm_password'])) {
            throw new Exception("所有字段不能为空！");
        }
        
        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("两次输入的密码不一致！");
        }
        
        if (strlen($_POST['password']) < 6) {
            throw new Exception("密码长度至少6位！");
        }
        
        // 2. 检查用户名重复
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$_POST['username']]);
        if ($stmt->fetch()) {
            throw new Exception("用户名已存在！");
        }
        
        // 3. 密码加密并插入
        $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'patient')");
        $stmt->execute([$_POST['username'], $passwordHash]);
        
        $success = "✅ 注册成功！请登录";
        
    } catch (Exception $e) {
        $error = "❌ " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>用户注册</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 40px;
            margin-top: 50px;
        }
    </style>
</head>
<body>
<div class="container" style="max-width: 500px;">
    <div class="form-container">
        <h3 class="text-center mb-4">用户注册</h3>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <p class="text-center"><a href="login.php">立即登录</a></p>
        <?php else: ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">用户名</label>
                    <input type="text" name="username" class="form-control" required 
                           placeholder="请输入用户名">
                </div>
                <div class="mb-3">
                    <label class="form-label">密码</label>
                    <input type="password" name="password" class="form-control" required 
                           placeholder="至少6位">
                </div>
                <div class="mb-3">
                    <label class="form-label">确认密码</label>
                    <input type="password" name="confirm_password" class="form-control" required 
                           placeholder="再次输入密码">
                </div>
                <button type="submit" class="btn btn-success w-100">注册</button>
            </form>
            <p class="mt-3 text-center">已有账号？<a href="login.php">去登录</a></p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>