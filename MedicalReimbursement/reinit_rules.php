<?php
// reinit_rules.php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=medical_system;charset=utf8", 
                   "medical_user", "medical123");
    
    echo "<h3>重新初始化审核规则...</h3>";
    
    // 清空现有规则
    $pdo->exec("DELETE FROM audit_rules");
    echo "✅ 清空现有规则<br>";
    
    // 插入新的规则（使用正确的引号）
    $rules = [
        ['发票完整性检查', 'empty($invoice_image)', '必须上传发票照片', 'document', 5],
        ['发票日期逻辑检查', 'strtotime($invoice_date) < strtotime($diagnosis_date)', '发票日期不能早于诊断日期', 'date', 10],
        ['肿瘤需病理报告', '$disease_type == \"肿瘤\" && empty($pathology_report)', '肿瘤病种必须上传病理报告', 'document', 30],
        ['金额合理性', '$total_amount > 50000', '金额超过5万元需额外人工审核', 'logic', 50]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO audit_rules (rule_name, rule_logic, error_msg, rule_type, priority) VALUES (?, ?, ?, ?, ?)");
    
    $count = 0;
    foreach ($rules as $rule) {
        $stmt->execute($rule);
        $count++;
        echo "✅ 插入规则: " . $rule[0] . "<br>";
    }
    
    echo "<h3>🎉 成功初始化 {$count} 条规则！</h3>";
    
    // 显示新规则
    echo "<h3>新的审核规则：</h3>";
    $stmt = $pdo->query("SELECT * FROM audit_rules ORDER BY priority");
    $newRules = $stmt->fetchAll();
    
    foreach ($newRules as $rule) {
        echo "<div style='border:1px solid green; padding:10px; margin:10px; background:#f0f8f0;'>";
        echo "<strong>规则名：</strong>" . $rule['rule_name'] . "<br>";
        echo "<strong>规则逻辑：</strong>" . htmlspecialchars($rule['rule_logic']) . "<br>";
        echo "<strong>错误信息：</strong>" . $rule['error_msg'] . "<br>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "❌ 错误：" . $e->getMessage();
}
?>