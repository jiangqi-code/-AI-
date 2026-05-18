<?php
// test_all_rules.php
echo "<h3>测试所有审核规则</h3>";

// 测试数据：肿瘤但没有病理报告，发票日期错误
$testData = [
    'disease_type' => '肿瘤',
    'pathology_report' => null,
    'invoice_image' => 'test.jpg',
    'invoice_date' => '2025-11-04',
    'diagnosis_date' => '2025-11-12',
    'total_amount' => 1000
];

$rules = [
    [
        'name' => '发票完整性检查',
        'logic' => 'empty($invoice_image)',
        'should_fail' => false
    ],
    [
        'name' => '发票日期逻辑检查', 
        'logic' => 'strtotime($invoice_date) < strtotime($diagnosis_date)',
        'should_fail' => true
    ],
    [
        'name' => '肿瘤需病理报告',
        'logic' => '$disease_type == "肿瘤" && empty($pathology_report)',
        'should_fail' => true
    ],
    [
        'name' => '金额合理性',
        'logic' => '$total_amount > 50000', 
        'should_fail' => false
    ]
];

// 提取变量
extract($testData, EXTR_SKIP);

foreach ($rules as $rule) {
    echo "<h4>测试规则: {$rule['name']}</h4>";
    
    try {
        $result = eval('return (' . $rule['logic'] . ') ? true : false;');
        
        $expected = $rule['should_fail'] ? '应该报错' : '不应该报错';
        $actual = $result ? '实际报错' : '实际未报错';
        
        if (($rule['should_fail'] && $result) || (!$rule['should_fail'] && !$result)) {
            echo "✅ 通过: {$expected}, {$actual}<br>";
        } else {
            echo "❌ 失败: {$expected}, {$actual}<br>";
        }
    } catch (Exception $e) {
        echo "❌ 规则执行错误: " . $e->getMessage() . "<br>";
    }
    echo "<hr>";
}

echo "<h3>测试数据：</h3>";
echo "<pre>" . print_r($testData, true) . "</pre>";
?>