<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
?>
<?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['auditor', 'admin'])): ?>
    <a class="nav-link text-white" href="auditor_dashboard.php">
        <i class="fas fa-cog"></i> 后台管理
    </a>
<?php endif; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>智能报销申请</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: url("926077b6656c909c804c262e715c94a.png");
           
        }
        .navbar {
            background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 40px;
            margin-top: 30px;
        }
        .form-title {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
            font-size: 24px;
        }
        .form-label {
            font-weight: 600;
            color: #34495e;
        }
        .btn-submit {
            background: linear-gradient(45deg, #4facfe, #00f2fe);
            border: none;
            padding: 12px 40px;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 172, 254, 0.4);
        }
        .file-upload-wrap {
            border: 2px dashed #4facfe;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .file-upload-wrap:hover {
            background: rgba(79, 172, 254, 0.05);
        }
        .smart-tips {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
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

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="form-container">
                <h2 class="form-title"><i class="fas fa-file-medical"></i> 智能报销申请</h2>

                <div class="smart-tips">
                    <i class="fas fa-lightbulb"></i> <strong>智能提示：</strong>系统会自动检测表单逻辑，发票日期不能早于诊断日期
                </div>

                <form method="POST" action="submit_application.php" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">

                    <!-- 新增字段 -->
                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-user-injured"></i> 患者身份证号</label>
                        <input type="text" name="patient_idcard" class="form-control form-control-lg" required placeholder="请输入患者 18 位身份证号">
                    </div>
                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-user"></i> 患者姓名</label>
                        <input type="text" name="patient_name" class="form-control form-control-lg" required placeholder="请输入患者真实姓名">
                    </div>
                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-hospital"></i> 就诊医院</label>
                        <input type="text" name="hospital_name" class="form-control form-control-lg" required placeholder="请输入就诊医院全称">
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-disease"></i> 疾病类型</label>
                        <select name="disease_type" class="form-select form-select-lg" required>
                            <option value="">请选择疾病类型</option>
                            <option value="肿瘤">🏥 肿瘤</option>
                            <option value="普通感冒">🤧 普通感冒</option>
                            <option value="手术治疗">⚕️ 手术治疗</option>
                            <option value="慢性病">💊 慢性病</option>
                        </select>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-calendar-check"></i> 诊断日期</label>
                            <input type="date" name="diagnosis_date" class="form-control form-control-lg" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-file-invoice"></i> 发票日期</label>
                            <input type="date" name="invoice_date" class="form-control form-control-lg" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-yen-sign"></i> 费用总金额（元）</label>
                        <input type="number" name="total_amount" step="0.01" class="form-control form-control-lg" required placeholder="请输入精确金额，如：1234.50">
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-file-image"></i> 发票照片（必填）</label>
                        <div class="file-upload-wrap">
                            <input type="file" name="invoice_image" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                            <small class="text-muted">支持格式：JPG、PNG、PDF（最大5MB）</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-file-medical-alt"></i> 病理报告（肿瘤病种必填）</label>
                        <div class="file-upload-wrap">
                            <input type="file" name="pathology_report" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                            <small class="text-muted">支持格式：JPG、PNG、PDF、Word文档（最大10MB）</small>
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-submit text-white">
                            <i class="fas fa-paper-plane"></i> 提交并智能审核
                        </button>
                    </div>
                </form>
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
                    我可以帮您解答：<br>
                    • 报销类型分类问题<br>
                    • 材料准备指导<br>
                    • 流程问题咨询<br>
                    • 政策解读<br>
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
// 文件类型和大小限制
const fileLimits = {
    invoice_image: {
        maxSize: 5 * 1024 * 1024, // 5MB
        allowedTypes: ['image/jpeg', 'image/png', 'application/pdf'],
        allowedExtensions: ['.jpg', '.jpeg', '.png', '.pdf']
    },
    pathology_report: {
        maxSize: 10 * 1024 * 1024, // 10MB
        allowedTypes: ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        allowedExtensions: ['.jpg', '.jpeg', '.png', '.pdf', '.doc', '.docx']
    }
};

// 验证文件
function validateFile(input, fieldName) {
    const file = input.files[0];
    if (!file) return true;
    
    const limits = fileLimits[fieldName];
    
    // 检查文件大小
    if (file.size > limits.maxSize) {
        const maxSizeMB = limits.maxSize / (1024 * 1024);
        alert(`文件 "${file.name}" 大小超过限制，最大允许 ${maxSizeMB}MB`);
        input.value = '';
        return false;
    }
    
    // 检查文件扩展名
    const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
    if (!limits.allowedExtensions.includes(fileExtension)) {
        alert(`文件 "${file.name}" 格式不支持，仅支持：${limits.allowedExtensions.join(', ')}`);
        input.value = '';
        return false;
    }
    
    return true;
}

// 为文件输入添加验证
document.addEventListener('DOMContentLoaded', function() {
    const invoiceInput = document.querySelector('input[name="invoice_image"]');
    const pathologyInput = document.querySelector('input[name="pathology_report"]');
    
    if (invoiceInput) {
        invoiceInput.addEventListener('change', function() {
            validateFile(this, 'invoice_image');
        });
    }
    
    if (pathologyInput) {
        pathologyInput.addEventListener('change', function() {
            validateFile(this, 'pathology_report');
        });
    }
});

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

// 原有的表单验证逻辑
document.addEventListener('DOMContentLoaded', function () {
    /* ========== 身份证实时验证 ========== */
    const idCardInput = document.querySelector('input[name="patient_idcard"]');
    const idCardHint  = document.createElement('small');
    idCardHint.className = 'form-text text-muted';
    idCardHint.innerText = '请输入18位身份证号码';
    idCardInput.parentNode.appendChild(idCardHint);

    function validateIdCard(val) {
        return /^[1-9]\d{5}(18|19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[\dX]$/i.test(val);
    }

    idCardInput.addEventListener('input', function () {
        const val = this.value.trim();
        if (val.length === 0) {
            idCardHint.className = 'form-text text-muted';
            idCardHint.innerText = '请输入18位身份证号码';
            this.classList.remove('is-invalid', 'is-valid');
            return;
        }
        if (validateIdCard(val)) {
            idCardHint.className = 'form-text text-success';
            idCardHint.innerText = '✅ 身份证格式正确';
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else {
            idCardHint.className = 'form-text text-danger';
            idCardHint.innerText = '❌ 身份证格式错误';
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
        }
    });

    /* ========== 日期逻辑验证 ========== */
    const diagnosisDateInput = document.querySelector('input[name="diagnosis_date"]');
    const invoiceDateInput   = document.querySelector('input[name="invoice_date"]');
    const form               = document.querySelector('form');

    function validateDates() {
        const diagnosisDate = new Date(diagnosisDateInput.value);
        const invoiceDate   = new Date(invoiceDateInput.value);
        if (diagnosisDate && invoiceDate && invoiceDate < diagnosisDate) {
            alert('❌ 错误：发票日期不能早于诊断日期！');
            invoiceDateInput.focus();
            return false;
        }
        return true;
    }

    diagnosisDateInput.addEventListener('change', validateDates);
    invoiceDateInput.addEventListener('change', validateDates);

    // 提交前验证（身份证+日期）
    form.addEventListener('submit', function (e) {
        if (!validateDates() || !validateIdCard(idCardInput.value.trim())) {
            e.preventDefault();
            alert('请修正身份证或日期错误后再提交！');
            return false;
        }
    });
});
</script>
</body>
</html>