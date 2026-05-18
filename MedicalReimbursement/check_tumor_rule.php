<?php
// check_tumor_rule.php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=medical_system;charset=utf8", 
                   "medical_user", "medical123");
    
    echo "<h3>检查肿瘤相关规则：</h3>";
    
    // 检查肿瘤规则
    $stmt = $pdo->prepare("SELECT * FROM audit_rules WHERE rule_name LIKE '%肿瘤%' OR rule_name LIKE '%病理%'");
    $stmt->execute();
    $rules = $stmt->fetchAll();
    
    if (empty($rules)) {
        echo "❌ 没有找到肿瘤相关规则！<br>";
    } else {
        foreach ($rules as $rule) {
            echo "<div style='border:1px solid blue; padding:10px; margin:10px;'>";
            echo "<strong>规则名：</strong>" . $rule['rule_name'] . "<br>";
            echo "<strong>规则逻辑：</strong>" . htmlspecialchars($rule['rule_logic']) . "<br>";
            echo "<strong>错误信息：</strong>" . $rule['error_msg'] . "<br>";
            echo "<strong>是否激活：</strong>" . ($rule['is_active'] ? '✅ 是' : '❌ 否') . "<br>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ 错误：" . $e->getMessage();
}
?>