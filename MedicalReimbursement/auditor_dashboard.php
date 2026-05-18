<?php
session_start();
if (!isset($_SESSION['auditor_id'])) {
    header("Location: admin_login.php");
    exit;
}

/* ========== 数据库公共部分 ========== */
define('DB_HOST', 'localhost');
define('DB_NAME', 'medical_system');
define('DB_USER', 'medical_user');
define('DB_PASS', 'medical123');
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
                   DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    die("数据库错误: " . $e->getMessage());
}

/* ---------- 统计卡片（不变） ---------- */
$stats = $pdo->query("
    SELECT
        COUNT(*)                    AS total,
        SUM(audit_status = 'draft') AS draft,
        SUM(audit_status = 'auditing') AS auditing,
        SUM(audit_status = 'passed') AS passed,
        SUM(audit_status = 'rejected') AS rejected
    FROM reimbursement_applications
")->fetch();

/* ---------- 待审核列表（原逻辑） ---------- */
$pendingApps = $pdo->query("
    SELECT ra.*, u.username
    FROM reimbursement_applications ra
    LEFT JOIN users u ON ra.user_id = u.user_id
    WHERE ra.audit_status = 'auditing'
    ORDER BY ra.created_at DESC
")->fetchAll();

/* ---------- 全部申请 + 搜索 + 分页（新增） ---------- */
$where = '';
$params = [];
if (!empty($_GET['keyword'])) {
    $keyword = '%' . trim($_GET['keyword']) . '%';
    $where = " WHERE patient_name LIKE ? OR disease_type LIKE ?";
    $params = [$keyword, $keyword];
}
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM reimbursement_applications" . $where);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages   = ceil($totalRecords / $limit);

$allStmt = $pdo->prepare("
    SELECT ra.*, u.username
    FROM reimbursement_applications ra
    LEFT JOIN users u ON ra.user_id = u.user_id
    $where
    ORDER BY ra.created_at DESC
    LIMIT " . (int)$offset . ", " . (int)$limit);
$allStmt->execute($params);
$allApps = $allStmt->fetchAll();
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>审核工作台 - 医疗报销系统</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root{--accent:#4f46e5;}
        body{background:#f7f9fb;font-family:-apple-system,BlinkMacSystemFont,"Helvetica Neue","PingFang SC","Microsoft YaHei",sans-serif;}
        .navbar-brand{font-weight:700;color:var(--accent)!important;}
        .stat-card{border:none;border-radius:16px;background:#fff;box-shadow:0 4px 20px -4px rgba(0,0,0,.06);transition:.25s}
        .stat-card:hover{transform:translateY(-3px)}
        .search-box{background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.04)}
        .badge{font-size:.75rem;padding:.45rem .65rem;border-radius:99px}
    </style>
</head>
<body>
<!-- ===== 顶部导航（不变） ===== -->
<nav class="navbar navbar-light bg-white border-bottom sticky-top">
    <div class="container d-flex justify-content-between align-items-center py-2">
        <a class="navbar-brand" href="#"><i class="fa-solid fa-house-chimney-medical me-2"></i>医疗报销系统 - 审核工作台</a>
        <div>
            <span class="text-muted me-3"><i class="fa-solid fa-user me-1"></i><?= htmlspecialchars($_SESSION['auditor_name']) ?></span>
            <a href="admin_logout.php" class="btn btn-sm btn-outline-danger">退出</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <!-- ===== 统计卡片（不变） ===== -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card text-center p-3"><div class="text-muted small">总申请</div><div class="h4 mb-0"><?= $stats['total'] ?></div></div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card text-center p-3"><div class="text-muted small">待审核</div><div class="h4 mb-0 text-primary"><?= $stats['auditing'] ?></div></div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card text-center p-3"><div class="text-muted small">已通过</div><div class="h4 mb-0 text-success"><?= $stats['passed'] ?></div></div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card text-center p-3"><div class="text-muted small">已驳回</div><div class="h4 mb-0 text-danger"><?= $stats['rejected'] ?></div></div>
        </div>
    </div>

    <!-- ===== 标签页：原有“待审核” + 新增“全部申请” ===== -->
    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pending-tab" data-bs-toggle="pill" data-bs-target="#pending" type="button">待审核</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="all-tab" data-bs-toggle="pill" data-bs-target="#all" type="button">全部申请</button>
        </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">
        <!-- ———— 待审核（原页面逻辑） ———— -->
        <div class="tab-pane fade show active" id="pending" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><h6 class="mb-0">待审核申请 (<?= count($pendingApps) ?> 个)</h6></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>患者身份证号</th><th>疾病类型</th><th>金额</th><th>提交用户</th><th>提交时间</th><th>智能评分</th><th>操作</th></tr></thead>
                        <tbody>
                        <?php foreach ($pendingApps as $app): ?>
                            <tr>
                                <td><?= htmlspecialchars($app['patient_name']) ?></td>
                                <td><?= htmlspecialchars($app['disease_type']) ?></td>
                                <td>¥<?= number_format($app['total_amount'],2) ?></td>
                                <td><?= htmlspecialchars($app['username']) ?></td>
                                <td><?= date('m-d H:i',strtotime($app['created_at'])) ?></td>
                                <td><span class="badge bg-<?= $app['smart_score']>=80?'success':($app['smart_score']>=60?'warning':'danger') ?>"><?= $app['smart_score'] ?></span></td>
                                <td><a href="review_application.php?id=<?= $app['application_id'] ?>" class="btn btn-sm btn-primary">审核</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ———— 全部申请（新增） ———— -->
        <div class="tab-pane fade" id="all" role="tabpanel">
            <!-- 搜索 -->
            <div class="card shadow-sm mb-3">
                <div class="card-body search-box">
                    <form method="get" class="row g-2">
                        <div class="col-md-10">
                            <input type="text" name="keyword" class="form-control" placeholder="按患者身份证号或疾病类型搜索..." value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100"><i class="fa-solid fa-search me-1"></i>搜索</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- 列表 -->
            <div class="card shadow-sm">
                <div class="card-header bg-white"><h6 class="mb-0">全部申请列表（共 <?= $totalRecords ?> 条）</h6></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>患者身份证号</th><th>疾病类型</th><th>费用金额</th><th>申请日期</th><th>状态</th><th>操作</th></tr></thead>
                        <tbody>
                        <?php foreach ($allApps as $app):
                            $statusClass = match($app['audit_status']){'draft'=>'secondary','auditing'=>'primary','passed'=>'success','rejected'=>'danger'};
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($app['patient_name']) ?></td>
                                <td><?= htmlspecialchars($app['disease_type']) ?></td>
                                <td>¥<?= number_format($app['total_amount'],2) ?></td>
                                <td><?= date('Y-m-d',strtotime($app['created_at'])) ?></td>
                                <td><span class="badge bg-<?= $statusClass ?>"><?= $app['audit_status'] ?></span></td>
                                <td>
                                    <a href="audit_result.php?id=<?= $app['application_id'] ?>" class="btn btn-sm btn-info">查看</a>
                                    <?php if ($app['audit_status'] === 'auditing'): ?>
                                        <a href="review_application.php?id=<?= $app['application_id'] ?>" class="btn btn-sm btn-primary ms-1">审核</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- 分页 -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&keyword=<?= urlencode($_GET['keyword'] ?? '') ?>#all"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>