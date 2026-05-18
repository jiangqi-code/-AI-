<?php
// check_db_rules.php
$pdo = new PDO('mysql:host=localhost;dbname=medical_system;charset=utf8', 'medical_user', 'medical123');
$stmt = $pdo->query("SELECT rule_name, rule_logic, error_msg, is_active FROM audit_rules");
$rules = $stmt->fetchAll();

echo "<h3>数据库中的实际规则：</h3>";
foreach ($rules as $rule) {
    echo "<div style='border:1px solid blue; padding:10px; margin:10px; background:#f0f8ff;'>";
    echo "<strong>" . $rule['rule_name'] . "</strong> (" . ($rule['is_active'] ? '激活' : '未激活') . ")<br>";
    echo "规则逻辑: <code>" . htmlspecialchars($rule['rule_logic']) . "</code><br>";
    echo "错误信息: " . $rule['error_msg'] . "<br>";
    
    // 测试这个规则
    $testData = [
        'disease_type' => '肿瘤',
        'pathology_report' => null,
        'invoice_date' => '2025-11-04',
        'diagnosis_date' => '2025-11-12',
        'invoice_image' => 'test.jpg'
    ];
    
    extract($testData);
    
    try {
        $result = eval('return (' . $rule['rule_logic'] . ') ? true : false;');
        echo "测试结果: " . ($result ? '✅ 触发错误' : '❌ 未触发错误') . "<br>";
    } catch (Exception $e) {
        echo "测试结果: ❌ 执行错误 - " . $e->getMessage() . "<br>";
    }
    
    echo "</div>";
}
?>