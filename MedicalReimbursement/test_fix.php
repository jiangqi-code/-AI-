<?php
// test_fix.php
session_start();
require_once 'SmartAudit.php';

// 获取最近的一个申请ID
$pdo = new PDO('mysql:host=localhost;dbname=medical_system;charset=utf8', 'medical_user', 'medical123');
$stmt = $pdo->query("SELECT application_id FROM reimbursement_applications ORDER BY created_at DESC LIMIT 1");
$lastApp = $stmt->fetch();

if ($lastApp) {
    $appId = $lastApp['application_id'];
    echo "<h3>测试申请ID: {$appId}</h3>";
    
    $audit = new SmartAuditEngine($appId);
    $result = $audit->runAudit();
    
    echo "<h4>审核结果:</h4>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    // 检查服务器错误日志中的调试信息
    echo "<p>请查看服务器错误日志中的详细调试信息</p>";
} else {
    echo "<p>没有找到申请记录，请先提交一个测试申请</p>";
    echo '<a href="apply.php">去提交申请</a>';
}
?>