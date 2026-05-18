<?php
// fix_database_structure.php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=medical_system;charset=utf8", 
                   "medical_user", "medical123");
    
    echo "开始修复数据库结构...<br>";
    
    // 1. 检查并添加缺失的字段到报销申请表
    $checkColumns = $pdo->query("DESCRIBE reimbursement_applications")->fetchAll();
    $existingColumns = array_column($checkColumns, 'Field');
    
    $columnsToAdd = [
        'auditor_notes' => "ALTER TABLE reimbursement_applications ADD COLUMN auditor_notes TEXT NULL",
        'reviewed_at' => "ALTER TABLE reimbursement_applications ADD COLUMN reviewed_at TIMESTAMP NULL",
        'reviewed_by' => "ALTER TABLE reimbursement_applications ADD COLUMN reviewed_by INT NULL"
    ];
    
    foreach ($columnsToAdd as $column => $sql) {
        if (!in_array($column, $existingColumns)) {
            $pdo->exec($sql);
            echo "✅ 添加字段 {$column} 成功<br>";
        } else {
            echo "⏩ 字段 {$column} 已存在，跳过<br>";
        }
    }
    
    // 2. 检查并创建审核日志表
    $tables = $pdo->query("SHOW TABLES LIKE 'audit_logs'")->fetchAll();
    if (empty($tables)) {
        $sql = "CREATE TABLE audit_logs (
            log_id INT PRIMARY KEY AUTO_INCREMENT,
            application_id INT NOT NULL,
            auditor_id INT NOT NULL,
            action VARCHAR(20) NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        echo "✅ 创建审核日志表成功<br>";
    } else {
        echo "⏩ 审核日志表已存在，跳过<br>";
    }
    
    echo "<hr><h3>🎉 数据库修复完成！</h3>";
    echo "<p>现在可以正常使用后台审核功能了</p>";
    echo "<a href='admin_login.php' class='btn btn-success'>进入后台登录</a>";
    
} catch (Exception $e) {
    echo "❌ 修复失败: " . $e->getMessage();
}
?>