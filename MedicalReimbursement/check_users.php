<?php
// check_users.php
$pdo = new PDO("mysql:host=localhost;dbname=medical_system;charset=utf8", 
               "medical_user", "medical123");

$stmt = $pdo->query("SELECT user_id, username, password, role FROM users");
$users = $stmt->fetchAll();

echo "<h3>系统用户列表</h3>";
foreach ($users as $user) {
    echo "<div style='border:1px solid #ccc; padding:10px; margin:5px;'>";
    echo "ID: " . $user['user_id'] . "<br>";
    echo "用户名: " . $user['username'] . "<br>";
    echo "角色: " . $user['role'] . "<br>";
    echo "密码哈希: " . $user['password'] . "<br>";
    
    // 测试密码验证
    $test = password_verify('auditor123', $user['password']);
    echo "密码验证(auditor123): " . ($test ? '✅ 正确' : '❌ 错误') . "<br>";
    echo "</div>";
}
?>