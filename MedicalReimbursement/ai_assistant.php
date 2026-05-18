<?php
// ai_assistant.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => '未登录']);
    exit;
}

// Coze API 配置
$config = [
    'bot_id' => '7571718321171660850',
    'token'  => 'pat_S2B5PjR9oAJC9aOczvjwEsHbClbqrDbgwSbHFp98pr8ddfU6qBRg2RGDoAhqDXBZ',
    'api_base' => 'https://api.coze.cn',
];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $userMessage = trim($input['message'] ?? '');
    $userId = $_SESSION['user_id'];

    if (empty($userMessage)) {
        throw new Exception('消息不能为空');
    }

    // 使用流式API
    $body = [
        'bot_id' => $config['bot_id'],
        'user_id' => (string)$userId,
        'stream' => true,
        'additional_messages' => [
            [
                'role' => 'user',
                'content' => $userMessage,
                'content_type' => 'text',
            ]
        ],
    ];

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $config['token'],
        'Accept: text/event-stream'
    ];

    $post_data = json_encode($body);
    
    $context_options = [
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers) . "\r\n" .
                       "Content-Length: " . strlen($post_data) . "\r\n",
            'content' => $post_data,
            'timeout' => 30,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ]
    ];

    $context = stream_context_create($context_options);
    $response = file_get_contents($config['api_base'] . '/v3/chat', false, $context);

    if ($response === false) {
        throw new Exception('AI服务暂时不可用，请稍后重试');
    }

    // 解析流式响应，提取AI回复
    $lines = explode("\n", $response);
    $aiReply = '';
    
    foreach ($lines as $line) {
        if (strpos($line, 'data:') === 0) {
            $data = trim(substr($line, 5));
            if ($data === '[DONE]') {
                break;
            }
            
            $jsonData = json_decode($data, true);
            // 只提取AI的answer类型消息
            if ($jsonData && 
                isset($jsonData['type']) && 
                $jsonData['type'] === 'answer' && 
                isset($jsonData['content'])) {
                $aiReply .= $jsonData['content'];
            }
        }
    }

    if (empty($aiReply)) {
        // 如果流式API没有返回内容，使用智能回复
        $aiReply = getSmartResponse($userMessage);
    }
    
    echo json_encode([
        'success' => true,
        'response' => $aiReply
    ]);

} catch (Exception $e) {
    // 记录错误日志
    error_log("AI Assistant Error: " . $e->getMessage());
    
    // 使用智能回复作为后备
    $smartResponse = getSmartResponse($userMessage ?? '');
    echo json_encode([
        'success' => true,
        'response' => $smartResponse
    ]);
}

function getSmartResponse($message) {
    $responses = [
        '怎么填写报销申请' => "📝 **报销申请填写指南**\n\n1. **患者基本信息**\n   • 身份证号：18位真实证件号码\n   • 患者姓名：与身份证完全一致\n   • 就诊医院：填写完整官方名称\n\n2. **医疗信息**\n   • 疾病类型：选择正确分类\n   • 诊断日期：实际就诊时间\n   • 发票日期：不能早于诊断日期\n\n3. **费用信息**\n   • 总金额：精确到分（如：1234.56）\n   • 发票照片：清晰、完整、盖章可见\n   • 病理报告：仅肿瘤病种需要\n\n💡 **提示**：系统会自动验证日期逻辑和身份证格式",
        
        '需要哪些材料' => "📋 **报销材料清单**\n\n✅ **必需材料**：\n• 患者身份证信息\n• 医疗费用发票\n• 疾病诊断证明\n\n✅ **补充材料（按病种）**：\n• 🏥 肿瘤：病理检测报告\n• ⚕️ 手术：手术记录单\n• 🏥 住院：出院小结\n• 💊 慢性病：长期治疗证明\n\n📌 请根据实际就诊情况准备相应材料",
        
        '报销比例' => "💰 **报销比例参考**\n\n• 普通门诊：70%-80%\n• 慢性疾病：80%-90%\n• 肿瘤治疗：85%-95%\n• 手术治疗：75%-85%\n• 住院治疗：80%-90%\n\n*注：具体比例以当地医保政策和实际审核为准*",
        
        '审核时间' => "⏰ **审核时间说明**\n\n• 标准审核：3-7个工作日\n• 加急处理：1-3个工作日\n• 进度查询：在\"我的申请\"页面查看实时状态\n• 结果通知：短信+系统通知\n\n审核通过后会及时通知您",
        
        '发票要求' => "🧾 **发票具体要求**\n\n✅ 正规医疗发票\n✅ 信息清晰可辨\n✅ 医院盖章完整\n✅ 日期在有效期内\n✅ 金额与填写一致\n\n请上传清晰的照片或扫描件，确保所有信息可见",
        
        '驳回原因' => "❌ **常见驳回原因**\n\n1. **材料不全**：缺少必要证明材料\n2. **信息错误**：身份证号、姓名等信息不匹配\n3. **发票问题**：发票不清晰、过期或无效\n4. **日期逻辑**：发票日期早于诊断日期\n5. **金额问题**：金额填写错误或与发票不符\n\n请根据驳回原因修正后重新提交",
        
        '身份证' => "🆔 **身份证填写要求**\n\n• 必须是18位真实有效的身份证号码\n• 需与患者姓名完全一致\n• 格式示例：110101199001011234\n• 系统会自动验证格式正确性",
        
        '医院' => "🏥 **就诊医院填写**\n\n请填写完整的医院官方名称，例如：\n• XX市第一人民医院\n• XX大学附属医院\n• XX省肿瘤医院\n\n避免使用简称或俗称",
        
        '日期' => "📅 **日期填写说明**\n\n• 诊断日期：实际就诊确诊时间\n• 发票日期：医疗费用发生时间\n• 重要规则：发票日期不能早于诊断日期\n• 请按实际时间准确填写",
        
        '金额' => "💰 **费用金额填写**\n\n• 填写发票上的总金额\n• 精确到小数点后两位\n• 示例：1234.50元\n• 确保与发票金额一致",
        
        '肿瘤' => "🎗️ **肿瘤病种报销**\n\n除常规材料外，肿瘤病种需要：\n• 病理检测报告（必需）\n• 影像学检查报告\n• 治疗方案说明\n\n请确保上传完整的诊断证明材料",
        
        '默认' => "🤖 **报销智能助手**\n\n您好！我是您的专属报销助手，可以帮您：\n\n📝 **申请指导** - 详细填写流程说明\n📋 **材料咨询** - 完整材料清单指导\n⏰ **流程说明** - 审核时间进度说明\n💰 **政策解读** - 报销比例政策解答\n❌ **问题分析** - 驳回原因详细分析\n\n请具体描述您的问题，我会为您提供专业指导！"
    ];

    $message = strtolower(trim($message));
    
    // 精确关键词匹配
    foreach ($responses as $key => $value) {
        if (strpos($message, strtolower($key)) !== false) {
            return str_replace('\n', "\n", $value);
        }
    }
    
    return str_replace('\n', "\n", $responses['默认']);
}
?>