<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=medical_system;charset=utf8", 
                       "medical_user", "medical123");
        
        $stmt = $pdo->prepare("SELECT user_id, username, password, role FROM users WHERE username = ? AND role IN ('auditor', 'admin')");
        $stmt->execute([$_POST['username']]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($_POST['password'], $user['password'])) {
            $_SESSION['auditor_id'] = $user['user_id'];
            $_SESSION['auditor_name'] = $user['username'];
            $_SESSION['auditor_role'] = $user['role'];
            
            header("Location: auditor_dashboard.php");
            exit;
        } else {
            $error = "用户名或密码错误，或无权访问后台";
        }
    } catch (Exception $e) {
        $error = "登录失败: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>审核员登录</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* 关键：添加背景图片和半透明遮罩 */
        body {
            background-image: url('微信图片_20251112183638.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            margin: 0;
            padding: 20px 0;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.85);
            z-index: -1;
        }

        .login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 35px 30px;
            border: 1px solid #f0f4f6;
        }

        /* 其他样式保持一致 */
        .login-title {
            color: #1d3557;
            font-weight: 600;
            margin-bottom: 25px;
            font-size: 24px;
            border-bottom: 1px solid #e8f4f8;
            padding-bottom: 15px;
        }
        .btn-login {
            background: #38b2ac;
            border: none;
            padding: 11px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s ease-in-out;
            color: white !important;
        }
        .btn-login:hover {
            background: #319795;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(56, 178, 172, 0.2);
        }
        .alert {
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            margin-bottom: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-sm-8">
                <div class="login-card">
                    <div class="text-center">
                        <h2 class="login-title">📋 报销审核后台</h2>
                    </div>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">用户名</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">密码</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-login w-100">登录后台</button>
                    </form>
                    <div class="text-center mt-3">
                        <a href="login.php">返回用户登录</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>