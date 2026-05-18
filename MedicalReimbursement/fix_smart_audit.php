<?php
// fix_smart_audit.php
// 直接修复 SmartAudit.php 文件

$filePath = 'SmartAudit.php';
$content = file_get_contents($filePath);

// 替换 getApplicationData 方法
$oldMethod = 'private function getApplicationData() {
    $stmt = $this->pdo->prepare(\'SELECT * FROM reimbursement_applications WHERE application_id = ?\');
    $stmt->execute([$this->applicationId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        error_log("错误：未找到申请数据，ID: " . $this->applicationId);
        return [];
    }
    
    // 处理附件路径
    $attachments = json_decode($data[\'attachment_paths\'] ?? \'[]\', true);
    
    // 确保文件字段正确设置
    $data[\'invoice_image\'] = !empty($attachments[\'invoice\']) ? $attachments[\'invoice\'] : null;
    $data[\'pathology_report\'] = !empty($attachments[\'pathology\']) ? $attachments[\'pathology\'] : null;
    $data[\'details\'] = $attachments[\'details\'] ?? \'[]\';
    
    // 调试信息
    error_log("审核数据详情:");
    error_log("- 疾病类型: " . ($data[\'disease_type\'] ?? \'空\'));
    error_log("- 病理报告: " . ($data[\'pathology_report\'] ?? \'空\'));
    error_log("- 发票图片: " . ($data[\'invoice_image\'] ?? \'空\'));
    error_log("- 发票日期: " . ($data[\'invoice_date\'] ?? \'空\'));
    error_log("- 诊断日期: " . ($data[\'diagnosis_date\'] ?? \'空\'));
    
    return $data;
}';

$newMethod = 'private function getApplicationData() {
    $stmt = $this->pdo->prepare(\'SELECT * FROM reimbursement_applications WHERE application_id = ?\');
    $stmt->execute([$this->applicationId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        error_log("错误：未找到申请数据，ID: " . $this->applicationId);
        return [];
    }
    
    // 处理附件路径
    $attachments = json_decode($data[\'attachment_paths\'] ?? \'[]\', true);
    
    // 确保所有需要的字段都存在
    $processedData = [
        \'disease_type\' => $data[\'disease_type\'] ?? null,
        \'pathology_report\' => !empty($attachments[\'pathology\']) ? $attachments[\'pathology\'] : null,
        \'invoice_image\' => !empty($attachments[\'invoice\']) ? $attachments[\'invoice\'] : null,
        \'invoice_date\' => $data[\'invoice_date\'] ?? null,
        \'diagnosis_date\' => $data[\'diagnosis_date\'] ?? null,
        \'total_amount\' => $data[\'total_amount\'] ?? 0,
        \'patient_name\' => $data[\'patient_name\'] ?? null
    ];
    
    // 调试信息
    error_log("审核数据详情:");
    foreach ($processedData as $key => $value) {
        error_log("- {$key}: " . (is_null($value) ? \'null\' : $value));
    }
    
    return $processedData;
}';

$newContent = str_replace($oldMethod, $newMethod, $content);

if (file_put_contents($filePath, $newContent)) {
    echo "✅ SmartAudit.php 修复成功！现在肿瘤规则应该能正常工作了。";
} else {
    echo "❌ 修复失败，请手动修改文件。";
}
?>