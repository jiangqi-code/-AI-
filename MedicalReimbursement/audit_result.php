<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/* ----------- 数据库连接 ----------- */
define('DB_HOST', 'localhost');
define('DB_NAME', 'medical_system');
define('DB_USER', 'medical_user');
define('DB_PASS', 'medical123');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die('数据库连接失败: ' . $e->getMessage());
}

/* ----------- 取申请数据 ----------- */
$appId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($appId <= 0) die('无权访问此申请');

$stmt = $pdo->prepare(
    "SELECT ra.*, u.username
       FROM reimbursement_applications ra
       LEFT JOIN users u ON ra.user_id = u.user_id
      WHERE ra.application_id = ?"
);
$stmt->execute([$appId]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$app) die('无权访问此申请');

$score  = (int)$app['smart_score'];
$errors = json_decode($app['audit_result'] ?? '[]', true) ?: [];
?>


<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>智能审核报告</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
        }
        .navbar {
            background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
        }
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            font-weight: bold;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .score-excellent { background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); }
        .score-warning { background: linear-gradient(135deg, #ffb347 0%, #ffcc33 100%); }
        .score-danger { background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%); }
        .audit-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .audit-card:hover {
            transform: translateY(-5px);
        }
        .alert-modern {
            border-radius: 10px;
            border-left: 5px solid;
        }
        .header-gradient {
            background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 20px;
            margin: -20px -20px 20px;
        }

        /* AI助手样式 */
        .ai-assistant {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }
        .ai-floating-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(45deg, #4facfe, #00f2fe);
            border: none;
            color: white;
            font-size: 24px;
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .ai-floating-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(79, 172, 254, 0.6);
        }
        .ai-chat-container {
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 380px;
            height: 500px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: none;
            flex-direction: column;
            overflow: hidden;
        }
        .ai-chat-header {
            background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .ai-chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        .ai-message {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }
        .ai-message.bot .message-content {
            background: white;
            border-radius: 15px 15px 15px 0;
            padding: 12px 15px;
            margin-left: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-width: 80%;
        }
        .ai-message.user {
            justify-content: flex-end;
        }
        .ai-message.user .message-content {
            background: #4facfe;
            color: white;
            border-radius: 15px 15px 0 15px;
            padding: 12px 15px;
            margin-right: 10px;
            max-width: 80%;
        }
        .ai-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #4facfe;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .ai-input-area {
            padding: 15px;
            border-top: 1px solid #dee2e6;
            background: white;
        }
        .ai-typing {
            padding: 10px 15px;
            font-style: italic;
            color: #6c757d;
            display: none;
            font-size: 14px;
        }
        .close-chat {
            background: none;
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .close-chat:hover {
            background: rgba(255,255,255,0.2);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="fas fa-hospital"></i> 智慧医疗报销</a>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text text-white me-3">
                <i class="fas fa-user"></i> 欢迎，<?= htmlspecialchars($_SESSION['username'] ?? '用户') ?>
            </span>
            <a class="nav-link text-white" href="my_applications.php">
                <i class="fas fa-list"></i> 我的申请
            </a>
            <a class="nav-link text-white" href="logout.php" onclick="return confirm('确定要退出登录吗？')">
                <i class="fas fa-sign-out-alt"></i> 退出
            </a>
        </div>
    </div>
</nav>
    
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- 评分仪表盘 -->
                <div class="card audit-card mb-4">
                    <div class="header-gradient text-center">
                        <h3 class="mb-0"><i class="fas fa-clipboard-check"></i> 智能审核报销报告</h3>
                    </div>
                    <div class="card-body text-center">
                        <div class="score-circle <?= $app['smart_score'] >= 80 ? 'score-excellent' : ($app['smart_score'] >= 60 ? 'score-warning' : 'score-danger') ?>">
                            <?= $app['smart_score'] ?>
                        </div>
                        <h4 class="mt-3">
                            <?php if ($app['smart_score'] >= 80): ?>
                                <i class="fas fa-check-circle text-success"></i> 材料齐全，预计1个工作日通过！
                            <?php elseif ($app['smart_score'] >= 60): ?>
                                <i class="fas fa-exclamation-triangle text-warning"></i> 存在小问题，需补充材料
                            <?php else: ?>
                                <i class="fas fa-times-circle text-danger"></i> 材料缺失严重，请按提示修改
                            <?php endif; ?>
                        </h4>
                        <p class="text-muted">申请ID：#<?= $app['application_id'] ?></p>
                    </div>
                </div>
                
                <!-- 问题清单 -->
                <?php if (!empty($auditResult)): ?>
                <div class="card audit-card border-danger mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-circle"></i> 发现 <?= count($auditResult) ?> 个问题需要修正</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($auditResult as $error): ?>
                            <div class="list-group-item list-group-item-action flex-column align-items-start">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">
                                        <span class="badge bg-primary"><?= ucfirst($error['type']) ?></span>
                                        <?= htmlspecialchars($error['rule_name']) ?>
                                    </h6>
                                </div>
                                <p class="mb-1 text-danger"><i class="fas fa-times-circle"></i> <?= htmlspecialchars($error['error_msg']) ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-success alert-modern mb-4">
                    <h4><i class="fas fa-check-circle"></i> 恭喜！</h4>
                    <p>报销已成功提交，您的申请已提交至人工审核队列。</p>
                </div>
                <?php endif; ?>
                
                <!-- 详细申请信息 -->
              <!-- ========== 替换原"申请详情"卡片内容 ========== -->
<div class="card audit-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-info-circle"></i> 申请详情</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>患者身份证号：</strong><?= htmlspecialchars($app['patient_idcard'] ?? '') ?></p>
                <p><strong>患者姓名：</strong><?= htmlspecialchars($app['patient_name'] ?? '') ?></p>
                <p><strong>就诊医院：</strong><?= htmlspecialchars($app['hospital_name'] ?? '') ?></p>
                <p><strong>疾病类型：</strong><?= htmlspecialchars($app['disease_type']) ?></p>
                <p><strong>提交用户：</strong><?= htmlspecialchars($app['username'] ?? '未知用户') ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>诊断日期：</strong><?= $app['diagnosis_date'] ?></p>
                <p><strong>发票日期：</strong><?= $app['invoice_date'] ?></p>
                <p><strong>提交时间：</strong><?= $app['created_at'] ?></p>
            </div>
        </div>
        <p><strong>费用金额：</strong><span class="text-success" style="font-size: 20px;">¥<?= number_format($app['total_amount'], 2) ?></span></p>

        <!-- 显示审核备注和驳回原因 -->
        <?php if (!empty($app['auditor_notes'])): ?>
            <div class="mt-3 p-3 bg-light rounded">
                <h6><i class="fas fa-comment"></i> 审核意见：</h6>
                <p class="mb-0"><?= nl2br(htmlspecialchars($app['auditor_notes'])) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
                
                <!-- 操作按钮 -->
                <div class="text-center">
                    <a href="apply.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus-circle"></i> 重新申请
                    </a>
                    <a href="my_applications.php" class="btn btn-info btn-lg ms-3">
                        <i class="fas fa-list"></i> 查看我的申请
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- AI助手浮标和聊天框 -->
    <div class="ai-assistant">
        <button class="ai-floating-btn" onclick="toggleChat()">
            <i class="fas fa-robot"></i>
        </button>
        <div class="ai-chat-container" id="aiChatContainer">
            <div class="ai-chat-header">
                <span><i class="fas fa-robot"></i> 报销智能助手</span>
                <button class="close-chat" onclick="toggleChat()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="ai-chat-messages" id="aiChatMessages">
                <div class="ai-message bot">
                    <div class="ai-avatar">AI</div>
                    <div class="message-content">
                        <strong>您好！我是报销智能助手</strong><br>
                        我可以帮您：<br>
                        • 解读审核报告<br>
                        • 分析评分原因<br>
                        • 指导问题修正<br>
                        • 解答审核疑问<br>
                        请问有什么可以帮您？
                    </div>
                </div>
            </div>
            <div class="ai-typing" id="aiTyping">
                <i class="fas fa-ellipsis-h"></i> AI正在思考中...
            </div>
            <div class="ai-input-area">
                <div class="input-group">
                    <input type="text" class="form-control" id="aiInput" placeholder="输入您的问题..." onkeypress="handleKeyPress(event)">
                    <button class="btn btn-primary" onclick="sendMessage()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // AI助手功能
        let isChatOpen = false;

        function toggleChat() {
            const chatContainer = document.getElementById('aiChatContainer');
            isChatOpen = !isChatOpen;
            chatContainer.style.display = isChatOpen ? 'flex' : 'none';
        }

        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }

        function sendMessage() {
            const input = document.getElementById('aiInput');
            const message = input.value.trim();
            
            if (!message) return;

            // 添加用户消息
            addMessage(message, 'user');
            input.value = '';

            // 显示正在输入
            showTyping();

            // 发送到后端处理
            fetch('ai_assistant.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: message,
                    user_id: <?php echo $_SESSION['user_id']; ?>,
                    application_id: <?php echo $appId; ?> // 传递当前申请ID
                })
            })
            .then(response => response.json())
            .then(data => {
                hideTyping();
                if (data.success) {
                    addMessage(data.response, 'bot');
                } else {
                    addMessage('抱歉，我暂时无法回答这个问题。请稍后重试。', 'bot');
                }
            })
            .catch(error => {
                hideTyping();
                addMessage('网络连接错误，请检查网络后重试。', 'bot');
                console.error('Error:', error);
            });
        }

        function addMessage(content, sender) {
            const messagesContainer = document.getElementById('aiChatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `ai-message ${sender}`;
            
            if (sender === 'bot') {
                messageDiv.innerHTML = `
                    <div class="ai-avatar">AI</div>
                    <div class="message-content">${content}</div>
                `;
            } else {
                messageDiv.innerHTML = `
                    <div class="message-content">${content}</div>
                `;
            }
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function showTyping() {
            document.getElementById('aiTyping').style.display = 'block';
        }

        function hideTyping() {
            document.getElementById('aiTyping').style.display = 'none';
        }
    </script>
</body>
</html>