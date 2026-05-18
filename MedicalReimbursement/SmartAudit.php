<?php

class SmartAuditEngine {
    private $pdo;
    private $applicationId;
    
    public function __construct($applicationId) {
        $this->pdo = $this->getDBConnection();
        $this->applicationId = $applicationId;
    }
    
    private function getDBConnection() {
        try {
            return new PDO('mysql:host=localhost;dbname=medical_system;charset=utf8', 
                          'medical_user', 'medical123', 
                          [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch(PDOException $e) {
            die('数据库连接失败: ' . $e->getMessage());
        }
    }
    
    public function runAudit() {
        $errors = [];
        $data = $this->getApplicationData();
        
        if (empty($data)) {
            return [
                'score' => 0,
                'errors' => [['rule_name' => '系统错误', 'error_msg' => '未找到申请数据', 'type' => 'system']],
                'details' => ['total_rules' => 0, 'failed_rules' => 1, 'passed_rules' => 0]
            ];
        }
        
        $rules = $this->getActiveRules();
        
        // 调试：记录开始审核
        error_log("开始智能审核，申请ID: " . $this->applicationId);
        error_log("激活规则数量: " . count($rules));
        
        foreach ($rules as $rule) {
            $result = $this->executeRule($rule['rule_logic'], $data);
            if ($result === true) {
                $errors[] = [
                    'rule_name' => $rule['rule_name'],
                    'error_msg' => $rule['error_msg'],
                    'type' => $rule['rule_type']
                ];
                error_log("规则触发: " . $rule['rule_name']);
            }
        }
        
        $score = $this->calculateScore($errors);
        $this->saveAuditResult($score, $errors);
        
        error_log("审核完成，得分: " . $score . ", 错误数量: " . count($errors));
        
        return [
            'score' => $score,
            'errors' => $errors,
            'details' => [
                'total_rules' => count($rules),
                'failed_rules' => count($errors),
                'passed_rules' => count($rules) - count($errors)
            ]
        ];
    }
    
 private function getApplicationData() {
    $stmt = $this->pdo->prepare('SELECT * FROM reimbursement_applications WHERE application_id = ?');
    $stmt->execute([$this->applicationId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        error_log("错误：未找到申请数据，ID: " . $this->applicationId);
        return [];
    }
    
    // 处理附件路径
    $attachments = json_decode($data['attachment_paths'] ?? '[]', true);
    
    // 构建完整的数据数组，确保所有规则需要的字段都存在
    $processedData = [
        'disease_type' => $data['disease_type'] ?? null,
        'pathology_report' => !empty($attachments['pathology']) ? $attachments['pathology'] : null,
        'invoice_image' => !empty($attachments['invoice']) ? $attachments['invoice'] : null,
        'invoice_date' => $data['invoice_date'] ?? null,
        'diagnosis_date' => $data['diagnosis_date'] ?? null,
        'total_amount' => $data['total_amount'] ?? 0,
        'patient_name' => $data['patient_name'] ?? null,
        'details' => '[]' // 为金额匹配规则准备的字段
    ];
    
    // 详细的调试信息
    error_log("=== 审核数据详情 ===");
    error_log("申请ID: " . $this->applicationId);
    error_log("疾病类型: " . ($processedData['disease_type'] ?? 'null'));
    error_log("病理报告: " . ($processedData['pathology_report'] ?? 'null'));
    error_log("发票图片: " . ($processedData['invoice_image'] ?? 'null'));
    error_log("发票日期: " . ($processedData['invoice_date'] ?? 'null'));
    error_log("诊断日期: " . ($processedData['diagnosis_date'] ?? 'null'));
    error_log("总金额: " . ($processedData['total_amount'] ?? 'null'));
    error_log("====================");
    
    return $processedData;
}
    
    private function getActiveRules() {
        $stmt = $this->pdo->prepare('SELECT * FROM audit_rules WHERE is_active = 1 ORDER BY priority ASC');
        $stmt->execute();
        $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("获取到的激活规则:");
        foreach ($rules as $rule) {
            error_log("- " . $rule['rule_name'] . ": " . $rule['rule_logic']);
        }
        
        return $rules;
    }
    
   private function executeRule($ruleLogic, $data) {
    error_log("=== 开始执行规则 ===");
    error_log("规则逻辑: " . $ruleLogic);
    error_log("数据字段: " . implode(', ', array_keys($data)));
    
    // 记录每个字段的值
    foreach ($data as $key => $value) {
        error_log("  {$key}: " . (is_null($value) ? 'NULL' : $value));
    }
    
    try {
        extract($data, EXTR_SKIP);
        
        // 记录提取后的变量
        error_log("提取后的变量:");
        error_log("  disease_type: " . (isset($disease_type) ? $disease_type : '未定义'));
        error_log("  pathology_report: " . (isset($pathology_report) ? $pathology_report : '未定义'));
        error_log("  invoice_image: " . (isset($invoice_image) ? $invoice_image : '未定义'));
        
        $expression = 'return (' . $ruleLogic . ') ? true : false;';
        error_log("执行表达式: " . $expression);
        
        $result = eval($expression);
        error_log("规则执行结果: " . ($result ? 'TRUE (触发错误)' : 'FALSE (通过)'));
        
        return $result;
    } catch (Exception $e) {
        error_log('规则执行失败: ' . $e->getMessage());
        return false;
    }
}
    
    private function calculateScore($errors) {
        $baseScore = 100;
        $deductionMap = [
            'document' => 20, 
            'date' => 15, 
            'amount' => 10, 
            'logic' => 5
        ];
        
        foreach ($errors as $error) {
            $deduction = $deductionMap[$error['type']] ?? 5;
            $baseScore -= $deduction;
            error_log("扣分: " . $error['type'] . " -" . $deduction . "分");
        }
        
        $finalScore = max(0, $baseScore);
        error_log("最终得分: " . $finalScore);
        
        return $finalScore;
    }
    
    private function saveAuditResult($score, $errors) {
        try {
            $stmt = $this->pdo->prepare('UPDATE reimbursement_applications SET smart_score = ?, audit_result = ?, audit_status = ? WHERE application_id = ?');
            
            // 如果没有错误，状态为 auditing；如果有错误，状态保持为 draft
            $status = empty($errors) ? 'auditing' : 'draft';
            
            $stmt->execute([
                $score, 
                json_encode($errors, JSON_UNESCAPED_UNICODE), 
                $status,
                $this->applicationId
            ]);
            
            error_log("保存审核结果: 得分=" . $score . ", 状态=" . $status . ", 错误数=" . count($errors));
            
        } catch (Exception $e) {
            error_log("保存审核结果失败: " . $e->getMessage());
        }
    }
}