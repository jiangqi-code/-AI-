<?php
// check_rules_simple.php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=medical_system;charset=utf8', 'medical_user', 'medical123');
    $stmt = $pdo->query("SELECT rule_name, rule_logic, error_msg FROM audit_rules WHERE is_active = 1");
    $rules = $stmt->fetchAll();

    echo "<h3>当前激活的规则：</h3>";
    foreach ($rules as $rule) {
        echo "<div style='border:1px solid #ccc; padding:10px; margin:5px;'>";
        echo "<strong>" . $rule['rule_name'] . "</strong><br>";
        echo "逻辑: " . htmlspecialchars($rule['rule_logic']) . "<br>";
        echo "错误: " . $rule['error_msg'] . "<br>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "错误: " . $e->getMessage();
}
?>