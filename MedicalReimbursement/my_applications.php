<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$pdo = new PDO("mysql:host=localhost;dbname=medical_system;charset=utf8", 
               "medical_user", "medical123", 
               [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$stmt = $pdo->prepare("SELECT * FROM reimbursement_applications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>我的报销申请</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
        }
        .navbar {
            background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
        }
        .application-card {
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .application-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 14px;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        .empty-state {
            text-align: center;
            padding: 60px;
            color: #6c757d;
        }
        .score-indicator {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        .reject-reason {
            background: #fff5f5;
            border-left: 4px solid #e53e3e;
            padding: 10px 15px;
            margin-top: 10px;
            border-radius: 5px;
            font-size: 14px;
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
        <a class="navbar-brand" href="apply.php"><i class="fas fa-hospital"></i> 智慧医疗报销</a>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text text-white me-3">
                <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username'] ?? '用户') ?>
                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['auditor', 'admin'])): ?>
                    <span class="badge bg-warning ms-1"><?= $_SESSION['user_role'] == 'auditor' ? '审核员' : '管理员' ?></span>
                <?php endif; ?>
            </span>
            
            <!-- 如果是审核员或管理员，显示后台管理入口 -->
            <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['auditor', 'admin'])): ?>
                <a class="nav-link text-white" href="auditor_dashboard.php">
                    <i class="fas fa-cog"></i> 后台管理
                </a>
            <?php endif; ?>
            
            <a class="nav-link text-white" href="apply.php">
                <i class="fas fa-plus-circle"></i> 新建申请
            </a>
            <a class="nav-link text-white" href="logout.php" onclick="return confirm('确定要退出登录吗？')">
                <i class="fas fa-sign-out-alt"></i> 退出
            </a>
        </div>
    </div>
</nav>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-list"></i> 我的报销申请</h2>
            <a href="apply.php" class="btn btn-success btn-lg">
                <i class="fas fa-plus-circle"></i> 新建申请
            </a>
        </div>
        
        <?php if (empty($apps)): ?>
            <div class="card empty-state">
                <i class="fas fa-inbox" style="font-size: 80px; color: #dee2e6; margin-bottom: 20px;"></i>
                <h4>暂无报销申请</h4>
                <p class="text-muted">点击上方按钮创建您的第一个申请</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($apps as $app): 
                    $statusClass = '';
                    $statusText = '';
                    switch ($app['audit_status']) {
                        case 'draft': $statusClass = 'bg-secondary'; $statusText = '草稿'; break;
                        case 'auditing': $statusClass = 'bg-primary'; $statusText = '审核中'; break;
                        case 'passed': $statusClass = 'bg-success'; $statusText = '已通过'; break;
                        case 'rejected': $statusClass = 'bg-danger'; $statusText = '已驳回'; break;
                    }
                    $scoreColor = $app['smart_score'] >= 80 ? 'success' : ($app['smart_score'] >= 60 ? 'warning' : 'danger');
                ?>
                <div class="col-md-6 mb-4">
                    <div class="card application-card h-100">
                        <div class="card-body d-flex">
                            <div class="score-indicator bg-<?= $scoreColor ?> me-3">
                                <?= $app['smart_score'] ?>
                            </div>
                            <div class="flex-grow-1">
                               <h5 class="card-title"><?= htmlspecialchars($app['patient_name']) ?></h5>
<p class="mb-1"><small>身份证号：<?= htmlspecialchars($app['patient_idcard'] ?? '') ?></small></p>
<p class="mb-1"><small>就诊医院：<?= htmlspecialchars($app['hospital_name'] ?? '') ?></small></p>
<p class="card-text text-muted mb-2">
    <i class="fas fa-disease"></i> <?= htmlspecialchars($app['disease_type']) ?> | 
    <i class="fas fa-yen-sign"></i> ¥<?= number_format($app['total_amount'], 2) ?>
</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="status-badge <?= $statusClass ?> text-white">
                                        <?= $statusText ?>
                                        <?php if ($app['audit_status'] == 'rejected' && !empty($app['auditor_notes'])): ?>
                                            <i class="fas fa-exclamation-circle ms-1" title="有驳回原因"></i>
                                        <?php endif; ?>
                                    </span>
                                    <small class="text-muted"><?= date('Y-m-d', strtotime($app['created_at'])) ?></small>
                                </div>
                                
                                <!-- 显示驳回原因 -->
                                <?php if ($app['audit_status'] == 'rejected' && !empty($app['auditor_notes'])): ?>
                                    <div class="reject-reason mt-2">
                                        <strong><i class="fas fa-times-circle text-danger"></i> 驳回原因：</strong>
                                        <?php
                                        // 提取驳回原因（如果包含【驳回原因】标签）
                                        $notes = $app['auditor_notes'];
                                        if (strpos($notes, '【驳回原因】') !== false) {
                                            $parts = explode('【补充说明】', $notes);
                                            $rejectReason = str_replace('【驳回原因】', '', $parts[0]);
                                            echo nl2br(htmlspecialchars(trim($rejectReason)));
                                        } else {
                                            echo nl2br(htmlspecialchars($notes));
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- 操作按钮区域 -->
                                <div class="d-flex justify-content-between mt-2">
                                    <a href="audit_result.php?id=<?= $app['application_id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> 查看详情
                                    </a>
                                    <?php if ($app['audit_status'] == 'draft'): ?>
                                        <div>
                                            <a href="edit.php?id=<?= $app['application_id'] ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> 编辑
                                            </a>
                                            <a href="delete.php?id=<?= $app['application_id'] ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('确定删除吗？')">
                                                <i class="fas fa-trash"></i> 删除
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
                        我可以帮您解答：<br>
                        • 申请进度查询<br>
                        • 报销状态说明<br>
                        • 驳回原因分析<br>
                        • 重新申请指导<br>
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
                    user_id: <?php echo $_SESSION['user_id']; ?>
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