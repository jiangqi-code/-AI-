<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// 测试数据库连接和用户
try {
    $pdo = new PDO("mysql:host=localhost;dbname=medical_system;charset=utf8", 
                   "medical_user", "medical123", 
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "<h3>🔍 登录诊断信息</h3>";
    
    // 检查用户表
    $stmt = $pdo->query("SELECT user_id, username, password, role FROM users WHERE username = 'test_patient'");
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<div class='alert alert-success'>✅ 用户存在: {$user['username']}</div>";
        echo "<p><strong>数据库中的密码哈希:</strong><br><code>{$user['password']}</code></p>";
        
        // 测试密码验证
        $testPassword = 'password';
        $isValid = password_verify($testPassword, $user['password']);
        echo "<p><strong>密码 'password' 验证结果:</strong> " . ($isValid ? "✅ 正确" : "❌ 错误") . "</p>";
        
        // 显示Session信息
        echo "<p><strong>当前Session ID:</strong> " . session_id() . "</p>";
        echo "<p><strong>Session保存路径:</strong> " . session_save_path() . "</p>";
        
        // 登录测试按钮
        echo "<hr><form method='POST'><button type='submit' name='test_login' class='btn btn-primary'>测试登录</button></form>";
        
        if (isset($_POST['test_login'])) {
            $_SESSION['user_id'] = $user['user_id'];
            echo "<p class='mt-2'>✅ 已手动设置Session！<a href='apply.php'>进入系统</a></p>";
        }
        
    } else {
        echo "<div class='alert alert-danger'>❌ 用户 'test_patient' 不存在</div>";
        echo "<p>请运行 <code>php init_db.php</code> 初始化数据库</p>";
    }
    
} catch (PDOException $e) {
    die("<div class='alert alert-danger'>❌ 数据库连接失败: " . $e->getMessage() . "</div>");
}
?>
<!DOCTYPE html>
<html>
<head><title>登录诊断</title><link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet"></head>
<body><div class="container mt-4"><?php /* 上面代码会在这里输出 */ ?></div></body>
</html>