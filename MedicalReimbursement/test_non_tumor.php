<?php
// test_non_tumor.php
session_start();
require_once 'SmartAudit.php';

// 测试非肿瘤疾病（普通感冒）不需要病理报告
$pdo = new PDO('mysql:host=localhost;dbname=medical_system;charset=utf8', 'medical_user', 'medical123');

// 插入一个测试申请（普通感冒，无病理报告）
$stmt = $pdo->prepare("INSERT INTO reimbursement_applications 
                      (user_id, patient_name, disease_type, total_amount, invoice_date, diagnosis_date, attachment_paths) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)");
$attachments = ['invoice' => 'test_invoice.jpg']; // 只有发票，没有病理报告

$stmt->execute([
    1, // 测试用户ID
    '测试患者-感冒',
    '普通感冒', // 非肿瘤疾病
    500,
    '2025-11-29',
    '2025-10-29',
    json_encode($attachments)
]);

$appId = $pdo->lastInsertId();

echo "<h3>测试非肿瘤疾病（普通感冒）</h3>";
echo "申请ID: {$appId}<br>";
echo "疾病类型: 普通感冒<br>";
echo "病理报告: 未上传<br><br>";

// 运行智能审核
$audit = new SmartAuditEngine($appId);
$result = $audit->runAudit();

echo "<h4>审核结果:</h4>";
echo "<pre>";
print_r($result);
echo "</pre>";

if ($result['score'] == 100 && empty($result['errors'])) {
    echo "<h3 style='color:green;'>✅ 验证成功！普通感冒不需要病理报告</h3>";
} else {
    echo "<h3 style='color:red;'>❌ 验证失败</h3>";
}
?>