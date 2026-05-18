<?php
// debug_complete.php
echo "<h2>智能审核系统完整调试</h2>";

// 1. 测试数据库连接和规则
echo "<h3>1. 数据库规则检查</h3>";
try {
    $pdo = new PDO('mysql:host=localhost;dbname=medical_system;charset=utf8', 'medical_user', 'medical123');
    echo "✅ 数据库连接成功<br>";
    
    // 检查规则表是否存在
    $tables = $pdo->query("SHOW TABLES LIKE 'audit_rules'")->fetchAll();
    if (empty($tables)) {
        echo "❌ audit_rules 表不存在<br>";
    } else {
        echo "✅ audit_rules 表存在<br>";
        
        // 检查规则数量
        $count = $pdo->query("SELECT COUNT(*) FROM audit_rules")->fetchColumn();
        echo "规则总数: " . $count . "<br>";
        
        // 显示所有规则
        $stmt = $pdo->query("SELECT * FROM audit_rules");
        $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rules)) {
            echo "❌ 没有找到任何规则<br>";
        } else {
            echo "<h4>所有规则详情：</h4>";
            foreach ($rules as $rule) {
                echo "<div style='border:2px solid " . ($rule['is_active'] ? 'green' : 'red') . "; padding:10px; margin:10px;'>";
                echo "<strong>规则名：</strong>" . $rule['rule_name'] . "<br>";
                echo "<strong>状态：</strong>" . ($rule['is_active'] ? '✅ 激活' : '❌ 未激活') . "<br>";
                echo "<strong>规则逻辑：</strong><code>" . htmlspecialchars($rule['rule_logic']) . "</code><br>";
                echo "<strong>错误信息：</strong>" . $rule['error_msg'] . "<br>";
                echo "<strong>类型：</strong>" . $rule['rule_type'] . "<br>";
                echo "<strong>优先级：</strong>" . $rule['priority'] . "<br>";
                echo "</div>";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ 数据库错误: " . $e->getMessage() . "<br>";
}

// 2. 测试规则执行
echo "<h3>2. 规则执行测试</h3>";
$testData = [
    'disease_type' => '肿瘤',
    'pathology_report' => null,
    'invoice_date' => '2025-11-04',
    'diagnosis_date' => '2025-11-12',
    'invoice_image' => 'test.jpg',
    'total_amount' => 1000
];

echo "<h4>测试数据：</h4>";
echo "<pre>";
print_r($testData);
echo "</pre>";

// 提取变量供规则使用
extract($testData);

// 测试常见的规则逻辑
$testRules = [
    '肿瘤规则1' => '$disease_type == "肿瘤" && empty($pathology_report)',
    '肿瘤规则2' => '$disease_type == \"肿瘤\" && empty($pathology_report)',
    '日期规则1' => 'strtotime($invoice_date) < strtotime($diagnosis_date)',
    '日期规则2' => 'strtotime($invoice_date) < strtotime($diagnosis_date)',
    '发票规则' => 'empty($invoice_image)'
];

echo "<h4>规则测试结果：</h4>";
foreach ($testRules as $name => $logic) {
    try {
        $result = eval('return (' . $logic . ') ? true : false;');
        echo $name . " (<code>" . htmlspecialchars($logic) . "</code>): " . 
             ($result ? '✅ 触发错误' : '❌ 未触发错误') . "<br>";
    } catch (Exception $e) {
        echo $name . ": ❌ 执行错误 - " . $e->getMessage() . "<br>";
    }
}

// 3. 检查申请表数据
echo "<h3>3. 申请表数据检查</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM reimbursement_applications");
    $appCount = $stmt->fetchColumn();
    echo "申请记录总数: " . $appCount . "<br>";
    
    if ($appCount > 0) {
        $stmt = $pdo->query("SELECT application_id, patient_name, disease_type, audit_status, smart_score FROM reimbursement_applications ORDER BY created_at DESC LIMIT 5");
        $apps = $stmt->fetchAll();
        
        echo "<h4>最近5条申请：</h4>";
        foreach ($apps as $app) {
            echo "<div style='border:1px solid #ccc; padding:10px; margin:5px;'>";
            echo "ID: " . $app['application_id'] . " | ";
            echo "患者: " . $app['patient_name'] . " | ";
            echo "疾病: " . $app['disease_type'] . " | ";
            echo "状态: " . $app['audit_status'] . " | ";
            echo "得分: " . $app['smart_score'];
            echo "</div>";
        }
    }
} catch (Exception $e) {
    echo "检查申请数据错误: " . $e->getMessage() . "<br>";
}

// 4. 测试SmartAudit类
echo "<h3>4. SmartAudit类测试</h3>";
if (file_exists('SmartAudit.php')) {
    require_once 'SmartAudit.php';
    
    // 获取最近的一个申请ID进行测试
    $stmt = $pdo->query("SELECT application_id FROM reimbursement_applications ORDER BY created_at DESC LIMIT 1");
    $lastApp = $stmt->fetch();
    
    if ($lastApp) {
        $appId = $lastApp['application_id'];
        echo "测试申请ID: " . $appId . "<br>";
        
        try {
            $audit = new SmartAuditEngine($appId);
            
            // 测试获取数据
            echo "<h4>获取申请数据：</h4>";
            $data = $audit->getApplicationData();
            echo "<pre>";
            print_r($data);
            echo "</pre>";
            
            // 测试获取规则
            echo "<h4>获取激活规则：</h4>";
            $rules = $audit->getActiveRules();
            echo "<pre>";
            print_r($rules);
            echo "</pre>";
            
            // 运行完整审核
            echo "<h4>运行完整审核：</h4>";
            $result = $audit->runAudit();
            echo "<pre>";
            print_r($result);
            echo "</pre>";
            
        } catch (Exception $e) {
            echo "SmartAudit错误: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "没有找到申请记录，请先提交一个测试申请<br>";
    }
} else {
    echo "❌ SmartAudit.php 文件不存在<br>";
}

echo "<hr><h3>下一步操作建议：</h3>";
echo "1. 如果规则不存在，运行 reinit_rules.php 重新初始化规则<br>";
echo "2. 如果没有申请记录，先提交一个测试申请<br>";
echo "3. 检查 SmartAudit.php 文件是否存在<br>";
?>