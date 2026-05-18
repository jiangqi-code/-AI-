<?php
// test_rules_simple.php
$testData = [
    'disease_type' => '肿瘤',
    'pathology_report' => null,
    'invoice_date' => '2025-11-04',
    'diagnosis_date' => '2025-11-12',
    'invoice_image' => 'test.jpg'
];

extract($testData);

$rules = [
    '肿瘤规则' => '$disease_type == "肿瘤" && empty($pathology_report)',
    '日期规则' => 'strtotime($invoice_date) < strtotime($diagnosis_date)',
    '发票规则' => 'empty($invoice_image)'
];

echo "<h3>规则测试结果：</h3>";
foreach ($rules as $name => $logic) {
    try {
        $result = eval('return (' . $logic . ') ? true : false;');
        echo $name . ": " . ($result ? '✅ 触发错误' : '❌ 未触发错误') . "<br>";
    } catch (Exception $e) {
        echo $name . ": ❌ 执行错误 - " . $e->getMessage() . "<br>";
    }
}

echo "<h3>测试数据：</h3>";
echo "<pre>";
print_r($testData);
echo "</pre>";
?>