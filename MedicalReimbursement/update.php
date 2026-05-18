<?php
// update.php（处理修改）
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

define('DB_HOST', 'localhost');
define('DB_NAME', 'medical_system');
define('DB_USER', 'medical_user');
define('DB_PASS', 'medical123');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", 
                   DB_USER, DB_PASS, 
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $appId = (int)$_GET['id'];
    
    $stmt = $pdo->prepare("UPDATE reimbursement_applications SET 
                           patient_name = ?, disease_type = ?, total_amount = ? 
                           WHERE application_id = ? AND user_id = ? AND audit_status = 'draft'");
    $stmt->execute([
        $_POST['patient_name'],
        $_POST['disease_type'],
        $_POST['total_amount'],
        $appId,
        $_SESSION['user_id']
    ]);
    
    header("Location: my_applications.php?msg=修改成功");
    exit;
    
} catch (Exception $e) {
    die("<div class='alert alert-danger'>修改失败: " . $e->getMessage() . "</div>");
}