<?php
// 强制UTF-8编码
header('Content-Type: text/html; charset=utf-8');

try {
    // 修改1：使用medical_user连接（不是root）
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", "medical_user", "medical123", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    echo "✅ 数据库连接成功<br>";
    
    // 2. 使用数据库
    $pdo->exec("USE medical_system");
    
    // 3. 创建用户表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            user_id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('patient','auditor','admin') DEFAULT 'patient',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ 用户表创建成功<br>";
    
    // 4. 创建审核规则表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_rules (
            rule_id INT PRIMARY KEY AUTO_INCREMENT,
            rule_name VARCHAR(100) NOT NULL,
            rule_logic TEXT NOT NULL,
            error_msg VARCHAR(255) NOT NULL,
            rule_type ENUM('date','amount','document','logic') NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            priority INT DEFAULT 100
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ 审核规则表创建成功<br>";
    
    // 5. 创建申请表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reimbursement_applications (
            application_id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            patient_name VARCHAR(50),
            disease_type VARCHAR(50),
            total_amount DECIMAL(10,2),
            invoice_date DATE,
            diagnosis_date DATE,
            audit_status ENUM('draft','auditing','passed','rejected') DEFAULT 'draft',
            smart_score INT DEFAULT 0,
            audit_result TEXT,
            attachment_paths JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ 申请表创建成功<br>";
    
    // 6. 插入测试用户
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute(['test_patient', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient']);
    echo "✅ 测试用户插入成功：test_patient / password<br>";
    
    // 7. 插入审核规则
    $rules = [
        ['发票完整性检查', 'empty($data["invoice_image"])', '必须上传发票照片', 'document', 5],
        ['发票日期逻辑检查', '$data["invoice_date"] < $data["diagnosis_date"]', '发票日期不能早于诊断日期', 'date', 10],
        ['金额匹配检查', 'abs($data["total_amount"] - array_sum(json_decode($data["details"], true))) > 0.01', '费用明细与总金额不符', 'amount', 20],
        ['肿瘤需病理报告', '$data["disease_type"] == "肿瘤" && empty($data["pathology_report"])', '肿瘤病种需上传病理报告', 'document', 30],
        ['金额合理性', '$data["total_amount"] > 50000', '金额超过5万元需额外人工审核', 'logic', 50]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO audit_rules (rule_name, rule_logic, error_msg, rule_type, priority) VALUES (?, ?, ?, ?, ?)");
    foreach ($rules as $rule) {
        $stmt->execute($rule);
    }
    echo "✅ 审核规则插入成功（5条）<br>";
    
    echo "<hr><h2>🎉 数据库初始化全部完成！</h2>";
    echo "<p>现在可以用 <strong>test_patient / password</strong> 登录系统了</p>";
    
} catch (PDOException $e) {
    die("❌ 初始化失败: " . $e->getMessage() . "<br>请检查MySQL是否启动，用户名密码是否正确");
}
?>