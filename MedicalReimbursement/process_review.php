<?php
session_start();
if (!isset($_SESSION['auditor_id'])) {
    header("Location: admin_login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("非法请求");
}

$pdo = new PDO("mysql:host=localhost;dbname=medical_system;charset=utf8", 
               "medical_user", "medical123");

$appId = (int)$_POST['application_id'];
$decision = $_POST['final_decision'];
$notes = $_POST['auditor_notes'] ?? '';
$rejectReason = $_POST['reject_reason'] ?? ''; // 获取驳回原因

// 验证申请存在且状态正确
$stmt = $pdo->prepare("SELECT audit_status FROM reimbursement_applications WHERE application_id = ?");
$stmt->execute([$appId]);
$app = $stmt->fetch();

if (!$app || $app['audit_status'] !== 'auditing') {
    die("申请不存在或状态不正确");
}

// 如果是驳回，必须填写驳回原因
if ($decision === 'rejected' && empty($rejectReason)) {
    header("Location: review_application.php?id=" . $appId . "&error=驳回申请必须填写驳回原因");
    exit;
}

// 组合备注信息
$finalNotes = $notes;
if ($decision === 'rejected' && !empty($rejectReason)) {
    if (!empty($notes)) {
        $finalNotes = "【驳回原因】" . $rejectReason . "\n\n【补充说明】" . $notes;
    } else {
        $finalNotes = "【驳回原因】" . $rejectReason;
    }
}

// 更新申请状态
$stmt = $pdo->prepare("
    UPDATE reimbursement_applications 
    SET audit_status = ?, auditor_notes = ?, reviewed_at = NOW(), reviewed_by = ?
    WHERE application_id = ?
");
$stmt->execute([$decision, $finalNotes, $_SESSION['auditor_id'], $appId]);

// 记录审核日志
$stmt = $pdo->prepare("
    INSERT INTO audit_logs (application_id, auditor_id, action, notes, created_at) 
    VALUES (?, ?, ?, ?, NOW())
");
$stmt->execute([$appId, $_SESSION['auditor_id'], $decision, $finalNotes]);

header("Location: auditor_dashboard.php?success=审核完成");
exit;
?>