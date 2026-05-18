-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS medical_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE medical_system;

-- 用户表
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('patient', 'auditor', 'admin') DEFAULT 'patient',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 智能审核规则库（核心创新表）
CREATE TABLE audit_rules (
    rule_id INT PRIMARY KEY AUTO_INCREMENT,
    rule_name VARCHAR(100) NOT NULL,
    rule_logic TEXT NOT NULL,
    error_msg VARCHAR(255) NOT NULL,
    rule_type ENUM(
        'date',
        'amount',
        'document',
        'logic'
    ) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    priority INT DEFAULT 100
);

-- 报销申请表
CREATE TABLE reimbursement_applications (
    application_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    patient_name VARCHAR(50),
    disease_type VARCHAR(50),
    total_amount DECIMAL(10, 2),
    invoice_date DATE,
    diagnosis_date DATE,
    audit_status ENUM(
        'draft',
        'auditing',
        'passed',
        'rejected'
    ) DEFAULT 'draft',
    smart_score INT DEFAULT 0,
    audit_result TEXT,
    attachment_paths JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (user_id)
);

-- 插入测试用户（密码：password）
INSERT INTO
    users (username, password, role)
VALUES (
        'test_patient',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'patient'
    );

-- 初始化5条智能审核规则
INSERT INTO
    audit_rules (
        rule_name,
        rule_logic,
        error_msg,
        rule_type,
        priority
    )
VALUES (
        '发票完整性检查',
        'empty($data["invoice_image"])',
        '必须上传发票照片',
        'document',
        5
    ),
    (
        '发票日期逻辑检查',
        '$data["invoice_date"] < $data["diagnosis_date"]',
        '发票日期不能早于诊断日期',
        'date',
        10
    ),
    (
        '金额匹配检查',
        'abs($data["total_amount"] - array_sum(json_decode($data["details"], true))) > 0.01',
        '费用明细与总金额不符',
        'amount',
        20
    ),
    (
        '肿瘤需病理报告',
        '$data["disease_type"] == "肿瘤" && empty($data["pathology_report"])',
        '肿瘤病种需上传病理报告',
        'document',
        30
    ),
    (
        '金额合理性',
        '$data["total_amount"] > 50000',
        '金额超过5万元需额外人工审核',
        'logic',
        50
    );