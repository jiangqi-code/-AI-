<?php
// update_rules.php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=medical_system;charset=utf8", 
                   "medical_user", "medical123", 
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // 更新规则
    $sql = "UPDATE audit_rules 
            SET rule_logic = 'strtotime(\$data[\\\"invoice_date\\\"]) < strtotime(\$data[\\\"diagnosis_date\\\"])',
                error_msg = '发票日期不能早于诊断日期',
                rule_type = 'date',
                priority = 10
            WHERE rule_name = '发票日期逻辑检查'";
    
    $pdo->exec($sql);
    echo "✅ 审核规则更新成功！<br>";
    
    // 检查规则是否更新成功
    $stmt = $pdo->query("SELECT rule_name, rule_logic, error_msg FROM audit_rules WHERE rule_name = '发票日期逻辑检查'");
    $rule = $stmt->fetch();
    
    echo "当前规则：<br>";
    echo "规则名：" . $rule['rule_name'] . "<br>";
    echo "规则逻辑：" . $rule['rule_logic'] . "<br>";
    echo "错误信息：" . $rule['error_msg'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ 错误：" . $e->getMessage();
}
?>