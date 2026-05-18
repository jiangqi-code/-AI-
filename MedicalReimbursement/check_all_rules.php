<?php
// check_all_rules.php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=medical_system;charset=utf8", 
                   "medical_user", "medical123");
    
    echo "<h3>所有审核规则：</h3>";
    $stmt = $pdo->query("SELECT * FROM audit_rules ORDER BY priority");
    $rules = $stmt->fetchAll();
    
    if (empty($rules)) {
        echo "❌ 没有找到任何规则！需要重新初始化。";
    } else {
        foreach ($rules as $rule) {
            echo "<div style='border:1px solid #ccc; padding:10px; margin:10px;'>";
            echo "<strong>ID：</strong>" . $rule['rule_id'] . "<br>";
            echo "<strong>规则名：</strong>" . $rule['rule_name'] . "<br>";
            echo "<strong>规则逻辑：</strong>" . htmlspecialchars($rule['rule_logic']) . "<br>";
            echo "<strong>错误信息：</strong>" . $rule['error_msg'] . "<br>";
            echo "<strong>类型：</strong>" . $rule['rule_type'] . "<br>";
            echo "<strong>优先级：</strong>" . $rule['priority'] . "<br>";
            echo "<strong>是否激活：</strong>" . ($rule['is_active'] ? '✅ 是' : '❌ 否') . "<br>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ 错误：" . $e->getMessage();
}
?>