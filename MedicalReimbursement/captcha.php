<?php
// captcha.php
session_start();

// 清除之前的输出缓冲区
if (ob_get_length()) ob_clean();

// 设置正确的header
header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 生成验证码
$code = rand(1000, 9999);
$_SESSION['captcha'] = (string)$code;

// 创建图像
$width = 120;
$height = 40;
$image = imagecreatetruecolor($width, $height);

// 设置颜色
$bgColor = imagecolorallocate($image, 255, 255, 255);
$textColor = imagecolorallocate($image, 0, 0, 0);
$noiseColor = imagecolorallocate($image, 200, 200, 200);

// 填充背景
imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

// 添加干扰线
for ($i = 0; $i < 5; $i++) {
    imageline($image, 
        rand(0, $width), rand(0, $height),
        rand(0, $width), rand(0, $height),
        $noiseColor
    );
}

// 添加干扰点
for ($i = 0; $i < 50; $i++) {
    imagesetpixel($image, rand(0, $width), rand(0, $height), $noiseColor);
}

// 写入验证码
imagestring($image, 5, 40, 12, $code, $textColor);

// 输出图像
imagepng($image);
imagedestroy($image);
exit;