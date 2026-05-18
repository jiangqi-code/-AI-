<?php
// quick_fix_tumor_rule.php
$pdo = new PDO('mysql:host=localhost;dbname=medical_system;charset=utf8', 'medical_user', 'medical123');

// 修复肿瘤规则逻辑
$sql = "UPDATE audit_rules 
        SET rule_logic = '\$disease_type == \"肿瘤\" && empty(\$pathology_report)',
            error_msg = '肿瘤病种必须上传病理报告'
        WHERE rule_name = '肿瘤需病理报告'";
$pdo->exec($sql);

echo "✅ 肿瘤规则已修复！现在规则使用变量语法而不是数组语法。";
?>