<?php
// reset_auditor_password.php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=medical_system;charset=utf8", 
                   "medical_user", "medical123");
    
    // 使用正确的密码：auditor123
    $passwordHash = password_hash('auditor123', PASSWORD_DEFAULT);
    
    // 更新审核员密码
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username IN ('auditor1', 'auditor2')");
    $stmt->execute([$passwordHash]);
    
    echo "✅ 审核员密码重置成功！<br>";
    echo "用户名: auditor1 或 auditor2<br>";
    echo "密码: auditor123<br>";
    echo "<a href='admin_login.php'>去登录后台</a>";
    
} catch (Exception $e) {
    echo "❌ 重置失败: " . $e->getMessage();
}
?>