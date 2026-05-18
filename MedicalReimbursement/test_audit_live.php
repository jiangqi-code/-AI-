<?php
// test_audit_live.php
echo "<h2>实时审核测试</h2>";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=medical_system;charset=utf8', 'medical_user', 'medical123');
    require_once 'SmartAudit.php';
    
    // 获取最新的申请ID
    $stmt = $pdo->query("SELECT application_id FROM reimbursement_applications ORDER BY created_at DESC LIMIT 1");
    $lastApp = $stmt->fetch();
    
    if ($lastApp) {
        $appId = $lastApp['application_id'];
        echo "<h3>测试申请ID: {$appId}</h3>";
        
        // 运行智能审核
        $audit = new SmartAuditEngine($appId);
        $result = $audit->runAudit();
        
        echo "<h3>审核结果：</h3>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
        
        echo "<h3>申请详情：</h3>";
        $stmt = $pdo->prepare("SELECT * FROM reimbursement_applications WHERE application_id = ?");
        $stmt->execute([$appId]);
        $app = $stmt->fetch();
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>字段</th><th>值</th></tr>";
        foreach ($app as $key => $value) {
            echo "<tr><td>{$key}</td><td>" . htmlspecialchars($value ?? '空') . "</td></tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color:red;'>请先提交一个测试申请！</p>";
        echo "<a href='apply.php' style='padding:10px; background:blue; color:white; text-decoration:none;'>去提交申请</a>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>错误: " . $e->getMessage() . "</p>";
}
?>