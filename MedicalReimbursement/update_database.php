<?php
// update_database.php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=medical_system;charset=utf8", 
                   "medical_user", "medical123");
    
    echo "开始更新数据库结构...<br>";
    
    // 1. 为报销申请表添加审核相关字段
    $sql1 = "ALTER TABLE reimbursement_applications 
             ADD COLUMN auditor_notes TEXT NULL AFTER audit_result,
             ADD COLUMN reviewed_at TIMESTAMP NULL AFTER auditor_notes,
             ADD COLUMN reviewed_by INT NULL AFTER reviewed_at";
    
    $pdo->exec($sql1);
    echo "✅ 添加审核字段成功<br>";
    
    // 2. 创建审核日志表
    $sql2 = "CREATE TABLE IF NOT EXISTS audit_logs (
        log_id INT PRIMARY KEY AUTO_INCREMENT,
        application_id INT NOT NULL,
        auditor_id INT NOT NULL,
        action VARCHAR(20) NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (application_id) REFERENCES reimbursement_applications(application_id),
        FOREIGN KEY (auditor_id) REFERENCES users(user_id)
    )";
    
    $pdo->exec($sql2);
    echo "✅ 创建审核日志表成功<br>";
    
    echo "<hr><h3>🎉 数据库更新完成！</h3>";
    echo "<p>现在可以正常使用后台审核功能了</p>";
    echo "<a href='admin_login.php'>进入后台</a>";
    
} catch (Exception $e) {
    echo "❌ 更新失败: " . $e->getMessage();
}
?>