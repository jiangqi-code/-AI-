<?php
// edit.php（可编辑草稿状态记录）
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
    
    // 权限验证
    $stmt = $pdo->prepare("SELECT * FROM reimbursement_applications WHERE application_id = ? AND user_id = ?");
    $stmt->execute([$appId, $_SESSION['user_id']]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$app) {
        throw new Exception("无权编辑此记录");
    }
    
    // 只允许编辑草稿
    if ($app['audit_status'] !== 'draft') {
        throw new Exception("只有草稿状态可以编辑");
    }
    
} catch (Exception $e) {
    die("<div class='alert alert-danger'>" . $e->getMessage() . "</div>");
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>编辑申请</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4" style="max-width: 800px;">
    <h2>编辑报销申请</h2>
    <form method="POST" action="update.php?id=<?= $appId ?>">
        <div class="mb-3">
            <label>患者身份证号</label>
            <input type="text" name="patient_name" class="form-control" 
                   value="<?= htmlspecialchars($app['patient_name']) ?>" required>
        </div>
        <div class="mb-3">
            <label>疾病类型</label>
            <select name="disease_type" class="form-select" required>
                <option value="肿瘤" <?= $app['disease_type'] == '肿瘤' ? 'selected' : '' ?>>肿瘤</option>
                <option value="普通感冒" <?= $app['disease_type'] == '普通感冒' ? 'selected' : '' ?>>普通感冒</option>
                <option value="手术治疗" <?= $app['disease_type'] == '手术治疗' ? 'selected' : '' ?>>手术治疗</option>
            </select>
        </div>
        <div class="mb-3">
            <label>费用金额</label>
            <input type="number" name="total_amount" step="0.01" class="form-control" 
                   value="<?= $app['total_amount'] ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">保存修改</button>
        <a href="my_applications.php" class="btn btn-secondary">返回</a>
    </form>
</div>
</body>
</html>