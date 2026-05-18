<?php
session_start();

// 检查Cookie自动登录
if (isset($_COOKIE['user_id']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $_COOKIE['user_id'];
    $_SESSION['username'] = $_COOKIE['username'];
    $_SESSION['user_role'] = $_COOKIE['user_role'] ?? 'patient';
    $_SESSION['welcome_back'] = true;
    
    // 根据角色跳转
    if (in_array($_SESSION['user_role'], ['auditor', 'admin'])) {
        header("Location: auditor_dashboard.php");
    } else {
        header("Location: apply.php");
    }
    exit;
}

// 已登录状态处理
if (isset($_SESSION['user_id'])) {
    $already_logged_in = true;
}

if (isset($_POST['login'])) {
    try {
        // 验证码检查
        if (empty($_POST['captcha'])) {
            throw new Exception("请输入验证码！");
        }
        
        if (!isset($_SESSION['captcha']) || empty($_SESSION['captcha'])) {
            throw new Exception("验证码已过期，请刷新页面！");
        }
        
        if ($_POST['captcha'] !== $_SESSION['captcha']) {
            throw new Exception("验证码错误！");
        }

        unset($_SESSION['captcha']);

        $pdo = new PDO("mysql:host=localhost;dbname=medical_system;charset=utf8", 
                       "medical_user", "medical123", 
                       [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        $stmt = $pdo->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$_POST['username']]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($_POST['password'], $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            if (isset($_POST['remember'])) {
                setcookie('user_id', $user['user_id'], time() + 86400 * 7, '/');
                setcookie('username', $user['username'], time() + 86400 * 7, '/');
                setcookie('user_role', $user['role'], time() + 86400 * 7, '/');
            }
            
            $_SESSION['login_success'] = true;
            
            if (in_array($user['role'], ['auditor', 'admin'])) {
                header("Location: auditor_dashboard.php");
            } else {
                header("Location: apply.php");
            }
            exit;
        } else {
            $error = "用户名或密码错误";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>智慧医疗报销系统</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* 关键修改：添加背景图片并设置透明度 */
        body {
            /* 替换为你的图片路径，这里用示例图 */
            background-image: url('微信图片_20251112183638.png');
            background-size: cover; /* 图片铺满屏幕 */
            background-position: center; /* 图片居中 */
            background-attachment: fixed; /* 固定背景不滚动 */
            position: relative; /* 用于叠加半透明遮罩 */
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: "Microsoft YaHei", "Helvetica Neue", Arial, sans-serif;
            padding: 20px 0;
            margin: 0;
        }
        
        /* 半透明遮罩层（用于降低图片透明度，不影响内容） */
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.85); /* 白色遮罩，透明度0.85 */
            z-index: -1; /* 放在内容下方 */
        }

        .login-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 35px 30px;
            border: 1px solid #f0f4f6;
            position: relative; /* 确保卡片在遮罩上方 */
        }

        /* 以下样式保持不变 */
        .login-title {
            color: #1d3557;
            font-weight: 600;
            margin-bottom: 25px;
            font-size: 24px;
            border-bottom: 1px solid #e8f4f8;
            padding-bottom: 15px;
        }
        .form-floating {
            margin-bottom: 18px;
        }
        .form-floating .form-control {
            border-radius: 8px;
            border: 1px solid #d1e7dd;
            padding: 12px 15px;
        }
        .form-floating label {
            color: #4a5568;
            padding-left: 15px;
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
        .medical-icon {
            font-size: 50px;
            color: #38b2ac;
            margin-bottom: 15px;
        }
        .captcha-img {
            border-radius: 8px;
            border: 1px solid #d1e7dd;
            height: 44px;
            object-fit: cover;
        }
        .welcome-message {
            background: #f5fafe;
            border: 1px solid #e8f4f8;
            border-radius: 8px;
            padding: 25px 15px;
            margin-bottom: 15px;
        }
        .role-badge {
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 12px;
            margin-left: 6px;
            font-weight: 500;
        }
        .form-label {
            color: #4a5568;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .input-group {
            border-radius: 8px;
            overflow: hidden;
        }
        .input-group .form-control {
            border-right: none;
        }
        .form-check-label {
            color: #4a5568;
            font-size: 14px;
        }
        .text-muted {
            color: #718096 !important;
            font-size: 13px;
            line-height: 1.6;
        }
        .alert {
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            margin-bottom: 18px;
        }
        .alert i {
            margin-right: 6px;
        }
        .btn-outline-secondary {
            border-color: #d1e7dd;
            color: #4a5568;
            border-radius: 8px;
        }
        .btn-outline-secondary:hover {
            background: #f5fafe;
            border-color: #38b2ac;
            color: #38b2ac;
        }
        .btn-warning {
            background: #ed8936;
            border-color: #ed8936;
            color: white;
            border-radius: 8px;
        }
        .btn-warning:hover {
            background: #dd6b20;
            border-color: #dd6b20;
            color: white;
        }
        .btn-success {
            background: #48bb78;
            border-color: #48bb78;
            color: white;
            border-radius: 8px;
        }
        .btn-success:hover {
            background: #38a169;
            border-color: #38a169;
            color: white;
        }
        .text-center mt-4 p a {
            color: #38b2ac;
        }
        .text-center mt-4 p a:hover {
            color: #319795;
            text-decoration: underline !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-sm-8">
                <div class="login-card">
                    <div class="text-center">
                        <i class="fas fa-clinic-medical medical-icon"></i>
                        <h2 class="login-title">智慧医疗报销系统</h2>
                    </div>
                    <?php if (isset($_SESSION['logout_message'])): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle"></i> <?= $_SESSION['logout_message'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['logout_message']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['login_success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> 登录成功！欢迎回来
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['login_success']); ?>
                    <?php endif; ?>
                
                    
                    <?php if (isset($already_logged_in)): ?>
                        <div class="welcome-message text-center">
                            <i class="fas fa-check-circle text-success fa-2x mb-3"></i>
                            <h4>欢迎回来，<?= htmlspecialchars($_SESSION['username']) ?>！
                                <span class="badge role-badge bg-<?= 
                                    $_SESSION['user_role'] == 'auditor' ? 'warning' : 
                                    ($_SESSION['user_role'] == 'admin' ? 'danger' : 'primary')
                                ?>">
                                    <?= 
                                        $_SESSION['user_role'] == 'auditor' ? '审核员' : 
                                        ($_SESSION['user_role'] == 'admin' ? '管理员' : '用户')
                                    ?>
                                </span>
                            </h4>
                            <p class="mb-4 text-muted">您已经登录系统，可直接进入对应功能页面</p>
                            <div class="d-grid gap-2">
                                <?php if (in_array($_SESSION['user_role'], ['auditor', 'admin'])): ?>
                                    <a href="auditor_dashboard.php" class="btn btn-warning">
                                        <i class="fas fa-tachometer-alt me-1"></i> 进入后台管理
                                    </a>
                                <?php else: ?>
                                    <a href="apply.php" class="btn btn-success">
                                        <i class="fas fa-file-medical-alt me-1"></i> 进入报销系统
                                    </a>
                                <?php endif; ?>
                                <a href="logout.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-sign-out-alt me-1"></i> 退出登录
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="form-floating">
                                <input type="text" name="username" class="form-control" placeholder="用户名" required>
                                <label><i class="fas fa-user me-1"></i> 用户名</label>
                            </div>
                            <div class="form-floating">
                                <input type="password" name="password" class="form-control" placeholder="密码" required>
                                <label><i class="fas fa-lock me-1"></i> 密码</label>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">验证码</label>
                                <div class="input-group">
                                    <input type="text" name="captcha" class="form-control" required placeholder="请输入验证码">
                                    <img src="captcha.php" onclick="this.src='captcha.php?'+Math.random()" 
                                         class="captcha-img" style="cursor:pointer;" title="点击刷新验证码">
                                </div>
                            </div>
                            
                            <div class="mb-4 form-check">
                                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">7天内免登录</label>
                            </div>
                            
                            <button type="submit" name="login" class="btn btn-login w-100">
                                <i class="fas fa-sign-in-alt me-1"></i> 立即登录
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="text-muted mb-2">
                                <i class="fas fa-info-circle"></i> 测试账号参考：
                                <br>
                                <strong>test_patient</strong> / <strong>password</strong> (普通用户)
                                <br>
                                <strong>auditor1</strong> / <strong>auditor123</strong> (审核员)
                            </p>
                            <p class="mt-2">
                                <a href="register.php" class="text-decoration-none">还没有账号？立即注册</a>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>