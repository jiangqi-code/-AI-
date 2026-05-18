<?php
// fix_tumor_rule.php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=medical_system;charset=utf8", 
                   "medical_user", "medical123");
    
    echo "<h3>修复肿瘤规则...</h3>";
    
    // 先检查规则是否存在
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_rules WHERE rule_name = ?");
    $stmt->execute(['肿瘤需病理报告']);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        // 更新现有规则
        $sql = "UPDATE audit_rules 
                SET rule_logic = '\$disease_type == \"肿瘤\" && empty(\$pathology_report)',
                    error_msg = '肿瘤病种必须上传病理报告'
                WHERE rule_name = '肿瘤需病理报告'";
        $pdo->exec($sql);
        echo "✅ 肿瘤规则更新成功！<br>";
    } else {
        // 插入新规则
        $sql = "INSERT INTO audit_rules (rule_name, rule_logic, error_msg, rule_type, priority)
                VALUES ('肿瘤需病理报告', 
                        '\$disease_type == \"肿瘤\" && empty(\$pathology_report)',
                        '肿瘤病种必须上传病理报告',
                        'document', 30)";
        $pdo->exec($sql);
        echo "✅ 肿瘤规则插入成功！<br>";
    }
    
    // 显示更新后的规则
    echo "<h3>更新后的肿瘤规则：</h3>";
    $stmt = $pdo->prepare("SELECT * FROM audit_rules WHERE rule_name = ?");
    $stmt->execute(['肿瘤需病理报告']);
    $rule = $stmt->fetch();
    
    if ($rule) {
        echo "<div style='border:1px solid green; padding:10px; background:#f0f8f0;'>";
        echo "<strong>规则名：</strong>" . $rule['rule_name'] . "<br>";
        echo "<strong>规则逻辑：</strong>" . htmlspecialchars($rule['rule_logic']) . "<br>";
        echo "<strong>错误信息：</strong>" . $rule['error_msg'] . "<br>";
        echo "<strong>类型：</strong>" . $rule['rule_type'] . "<br>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "❌ 错误：" . $e->getMessage();
}
?>