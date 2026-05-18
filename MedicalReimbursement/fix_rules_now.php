<?php
// fix_rules_now.php
echo "<h2>立即修复审核规则</h2>";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=medical_system;charset=utf8', 'medical_user', 'medical123');
    
    // 清空现有规则（如果有的话）
    $pdo->exec("DELETE FROM audit_rules");
    echo "✅ 清空现有规则<br>";
    
    // 插入4个核心规则
    $rules = [
        ['发票完整性检查', 'empty($invoice_image)', '必须上传发票照片', 'document', 5, 1],
        ['发票日期逻辑检查', 'strtotime($invoice_date) < strtotime($diagnosis_date)', '发票日期不能早于诊断日期', 'date', 10, 1],
        ['肿瘤需病理报告', '$disease_type == "肿瘤" && empty($pathology_report)', '肿瘤病种必须上传病理报告', 'document', 30, 1],
        ['金额合理性', '$total_amount > 50000', '金额超过5万元需额外人工审核', 'logic', 50, 1]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO audit_rules (rule_name, rule_logic, error_msg, rule_type, priority, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    
    $count = 0;
    foreach ($rules as $rule) {
        $stmt->execute($rule);
        $count++;
        echo "✅ 插入规则: " . $rule[0] . "<br>";
    }
    
    echo "<h3 style='color:green;'>🎉 成功插入 {$count} 条审核规则！</h3>";
    
    // 验证规则
    echo "<h3>验证插入的规则：</h3>";
    $stmt = $pdo->query("SELECT rule_name, rule_logic, error_msg FROM audit_rules");
    $newRules = $stmt->fetchAll();
    
    foreach ($newRules as $rule) {
        echo "<div style='border:2px solid green; padding:10px; margin:10px; background:#f0f8f0;'>";
        echo "<strong>" . $rule['rule_name'] . "</strong><br>";
        echo "逻辑: <code>" . htmlspecialchars($rule['rule_logic']) . "</code><br>";
        echo "错误: " . $rule['error_msg'] . "<br>";
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<h3>现在可以测试智能审核了：</h3>";
    echo "1. 访问 <a href='apply.php'>apply.php</a> 提交新的申请<br>";
    echo "2. 选择'肿瘤'疾病但不传病理报告<br>";
    echo "3. 设置发票日期早于诊断日期<br>";
    echo "4. 查看审核结果！";
    
} catch (Exception $e) {
    echo "<h3 style='color:red;'>❌ 错误：" . $e->getMessage() . "</h3>";
}
?>