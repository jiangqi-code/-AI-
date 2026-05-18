<?php
// delete.php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

define('DB_HOST', 'localhost');
define('DB_NAME', 'medical_system');
define('DB_USER', 'medical_user');
define('DB_PASS', 'medical123');

if (isset($_GET['id'])) {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", 
                   DB_USER, DB_PASS, 
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $stmt = $pdo->prepare("DELETE FROM reimbursement_applications 
                           WHERE application_id = ? AND user_id = ? AND audit_status = 'draft'");
    $stmt->execute([(int)$_GET['id'], $_SESSION['user_id']]);
    
    header("Location: my_applications.php?msg=删除成功");
    exit;
}