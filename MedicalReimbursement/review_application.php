<?php
session_start();
if (!isset($_SESSION['auditor_id'])) {
    header("Location: admin_login.php");
    exit;
}

$pdo = new PDO("mysql:host=localhost;dbname=medical_system;charset=utf8", 
               "medical_user", "medical123");

$appId = (int)$_GET['id'];
$stmt = $pdo->prepare("
    SELECT ra.*, u.username 
    FROM reimbursement_applications ra 
    LEFT JOIN users u ON ra.user_id = u.user_id 
    WHERE ra.application_id = ?
");
$stmt->execute([$appId]);
$app = $stmt->fetch();

if (!$app) {
    die("申请不存在");
}

$attachments = json_decode($app['attachment_paths'] ?? '[]', true);
$auditResult = json_decode($app['audit_result'] ?? '[]', true);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>审核申请 #<?= $appId ?></title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a href="auditor_dashboard.php" class="navbar-brand">← 返回工作台</a>
            <span class="navbar-text text-white">审核申请 #<?= $appId ?></span>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
              <!-- ==========  替换开始  ========== -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">📋 申请信息</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>患者身份证号：</strong><?= htmlspecialchars($app['patient_idcard'] ?? '') ?></p>
                <p><strong>患者姓名：</strong><?= htmlspecialchars($app['patient_name'] ?? '') ?></p>
                <p><strong>就诊医院：</strong><?= htmlspecialchars($app['hospital_name'] ?? '') ?></p>
                <p><strong>疾病类型：</strong><?= htmlspecialchars($app['disease_type']) ?></p>
                <p><strong>提交用户：</strong><?= htmlspecialchars($app['username']) ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>诊断日期：</strong><?= $app['diagnosis_date'] ?></p>
                <p><strong>发票日期：</strong><?= $app['invoice_date'] ?></p>
                <p><strong>提交时间：</strong><?= $app['created_at'] ?></p>
            </div>
        </div>
        <p><strong>费用金额：</strong><span class="h4 text-success">¥<?= number_format($app['total_amount'], 2) ?></span></p>
    </div>
</div>
<!-- ==========  替换结束  ========== -->

                <!-- 智能审核结果 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">🤖 智能审核结果</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="score-circle me-3" style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; font-weight: bold;">
                                <?= $app['smart_score'] ?>
                            </div>
                            <div>
                                <h5>智能评分：<?= $app['smart_score'] ?>分</h5>
                                <p class="text-muted mb-0">基于<?= count($auditResult) ?>个问题自动评估</p>
                            </div>
                        </div>

                        <?php if (!empty($auditResult)): ?>
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle"></i> 智能审核发现的问题：</h6>
                                <ul class="mb-0">
                                    <?php foreach ($auditResult as $error): ?>
                                        <li><?= htmlspecialchars($error['error_msg']) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> 智能审核未发现问题
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 附件预览 -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">📎 申请附件</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <h6>发票照片：</h6>
                                <?php if (!empty($attachments['invoice'])): ?>
                                    <img src="<?= $attachments['invoice'] ?>" class="img-fluid rounded border" style="max-height: 300px;" alt="发票照片">
                                    <div class="mt-2">
                                        <a href="<?= $attachments['invoice'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-external-link-alt"></i> 查看原图
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">未上传</p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <h6>病理报告：</h6>
                                <?php if (!empty($attachments['pathology'])): ?>
                                    <?php if (pathinfo($attachments['pathology'], PATHINFO_EXTENSION) === 'pdf'): ?>
                                        <div class="border rounded p-3 text-center">
                                            <i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>
                                            <p>PDF文件</p>
                                            <a href="<?= $attachments['pathology'] ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-external-link-alt"></i> 查看PDF
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <img src="<?= $attachments['pathology'] ?>" class="img-fluid rounded border" style="max-height: 300px;" alt="病理报告">
                                        <div class="mt-2">
                                            <a href="<?= $attachments['pathology'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-external-link-alt"></i> 查看原图
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-muted">未上传</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- 人工审核操作 -->
                <div class="card">
    <div class="card-header">
        <h5 class="mb-0">✅ 人工审核</h5>
    </div>
    <div class="card-body">
        <form action="process_review.php" method="POST">
            <input type="hidden" name="application_id" value="<?= $appId ?>">
            
            <div class="mb-3">
                <label class="form-label"><strong>审核结果</strong></label>
                <div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="final_decision" value="passed" id="pass" required>
                        <label class="form-check-label text-success" for="pass">
                            <i class="fas fa-check-circle"></i> 通过审核
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="final_decision" value="rejected" id="reject">
                        <label class="form-check-label text-danger" for="reject">
                            <i class="fas fa-times-circle"></i> 驳回申请
                        </label>
                    </div>
                </div>
            </div>

            <!-- 添加驳回原因字段 -->
            <div class="mb-3" id="rejectReason" style="display: none;">
                <label class="form-label"><strong>驳回原因</strong></label>
                <textarea name="reject_reason" class="form-control" rows="3" placeholder="请详细说明驳回原因（必填）"></textarea>
                <small class="text-muted">驳回申请时必须填写具体原因</small>
            </div>

            <div class="mb-3">
                <label class="form-label"><strong>审核备注</strong></label>
                <textarea name="auditor_notes" class="form-control" rows="4" placeholder="请输入审核意见（可选）"></textarea>
            </div>

            <button type="submit" class="btn btn-success w-100">
                <i class="fas fa-paper-plane"></i> 提交审核决定
            </button>
        </form>
    </div>
</div>

<script>
// 显示/隐藏驳回原因输入框
document.addEventListener('DOMContentLoaded', function() {
    const rejectRadio = document.getElementById('reject');
    const passRadio = document.getElementById('pass');
    const rejectReason = document.getElementById('rejectReason');
    
    function toggleRejectReason() {
        if (rejectRadio.checked) {
            rejectReason.style.display = 'block';
            rejectReason.querySelector('textarea').required = true;
        } else {
            rejectReason.style.display = 'none';
            rejectReason.querySelector('textarea').required = false;
        }
    }
    
    rejectRadio.addEventListener('change', toggleRejectReason);
    passRadio.addEventListener('change', toggleRejectReason);
    
    // 表单提交验证
    document.querySelector('form').addEventListener('submit', function(e) {
        if (rejectRadio.checked) {
            const reason = rejectReason.querySelector('textarea').value.trim();
            if (!reason) {
                e.preventDefault();
                alert('请填写驳回原因！');
                rejectReason.querySelector('textarea').focus();
            }
        }
    });
});
</script>
                <!-- 审核历史 -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">📝 审核状态</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>当前状态：</strong>
                            <span class="badge bg-<?= 
                                $app['audit_status'] == 'passed' ? 'success' : 
                                ($app['audit_status'] == 'rejected' ? 'danger' : 
                                ($app['audit_status'] == 'auditing' ? 'primary' : 'secondary')) 
                            ?>">
                                <?= $app['audit_status'] ?>
                            </span>
                        </p>
                        <p><strong>智能评分：</strong><?= $app['smart_score'] ?>分</p>
                        <p><strong>提交时间：</strong><?= date('Y-m-d H:i', strtotime($app['created_at'])) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>