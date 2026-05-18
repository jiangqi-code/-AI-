<?php
session_start();
require_once 'SmartAudit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("非法请求");
}

// 定义允许的文件类型和MIME类型
$allowedFileTypes = [
    'invoice' => [
        'extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
        'mime_types' => ['image/jpeg', 'image/png', 'application/pdf'],
        'max_size' => 5 * 1024 * 1024 // 5MB
    ],
    'pathology' => [
        'extensions' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],
        'mime_types' => ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'max_size' => 10 * 1024 * 1024 // 10MB
    ]
];

/**
 * 验证上传的文件
 * @param array $file $_FILES中的文件信息
 * @param string $type 文件类型（invoice/pathology）
 * @return array 验证结果
 */
function validateUploadedFile($file, $type) {
    global $allowedFileTypes;
    
    // 检查文件是否存在
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => '文件上传失败'];
    }
    
    $rules = $allowedFileTypes[$type];
    
    // 检查文件大小
    if ($file['size'] > $rules['max_size']) {
        $maxSizeMB = $rules['max_size'] / (1024 * 1024);
        return ['valid' => false, 'error' => "文件大小超过限制，最大允许 {$maxSizeMB}MB"];
    }
    
    // 获取文件扩展名
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $rules['extensions'])) {
        return ['valid' => false, 'error' => '不支持的文件类型，仅支持：' . implode(', ', $rules['extensions'])];
    }
    
    // 验证MIME类型
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $rules['mime_types'])) {
        return ['valid' => false, 'error' => '文件类型验证失败，请上传有效的文件'];
    }
    
    return ['valid' => true];
}

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'medical_system');
define('DB_USER', 'medical_user');
define('DB_PASS', 'medical123');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", 
                   DB_USER, DB_PASS, 
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // 创建上传目录
    $uploadDir = "uploads/" . date('Ymd') . "/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // 处理文件上传
    $attachments = [];
    $uploadErrors = [];
    
    // 处理发票图片上传
    if (!empty($_FILES['invoice_image']['name'])) {
        $validation = validateUploadedFile($_FILES['invoice_image'], 'invoice');
        if (!$validation['valid']) {
            $uploadErrors[] = "发票图片: " . $validation['error'];
        } else {
            $fileName = uniqid() . '_' . basename($_FILES['invoice_image']['name']);
            if (move_uploaded_file($_FILES['invoice_image']['tmp_name'], $uploadDir . $fileName)) {
                $attachments['invoice'] = $uploadDir . $fileName;
            } else {
                $uploadErrors[] = "发票图片上传失败，请重试";
            }
        }
    }
    
    // 处理病理报告上传
    if (!empty($_FILES['pathology_report']['name'])) {
        $validation = validateUploadedFile($_FILES['pathology_report'], 'pathology');
        if (!$validation['valid']) {
            $uploadErrors[] = "病理报告: " . $validation['error'];
        } else {
            $fileName = uniqid() . '_' . basename($_FILES['pathology_report']['name']);
            if (move_uploaded_file($_FILES['pathology_report']['tmp_name'], $uploadDir . $fileName)) {
                $attachments['pathology'] = $uploadDir . $fileName;
            } else {
                $uploadErrors[] = "病理报告上传失败，请重试";
            }
        }
    }
    
    // 如果有上传错误，显示错误信息
    if (!empty($uploadErrors)) {
        throw new Exception(implode("<br>", $uploadErrors));
    }
    
    // 插入申请表（含新字段）
    $stmt = $pdo->prepare("INSERT INTO reimbursement_applications 
                           (user_id, patient_idcard, patient_name, hospital_name, disease_type, total_amount, invoice_date, diagnosis_date, attachment_paths) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['user_id'] ?? 0,
        $_POST['patient_idcard'] ?? '',
        $_POST['patient_name'] ?? '',
        $_POST['hospital_name'] ?? '',
        $_POST['disease_type'] ?? '',
        $_POST['total_amount'] ?? 0,
        $_POST['invoice_date'] ?? null,
        $_POST['diagnosis_date'] ?? null,
        json_encode($attachments, JSON_UNESCAPED_UNICODE)
    ]);
    
    // 智能审核
    $applicationId = $pdo->lastInsertId();
    $auditEngine = new SmartAuditEngine($applicationId);
    $auditEngine->runAudit();
    
    header("Location: audit_result.php?id=" . $applicationId);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    echo "<!DOCTYPE html><html><head><title>错误</title></head><body>";
    echo "<div style='padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h2>❌ 提交失败</h2>";
    echo "<p><strong>错误信息：</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<hr><p><a href='apply.php'>返回申请表</a></p>";
    echo "</div></body></html>";
    exit;
}
?>