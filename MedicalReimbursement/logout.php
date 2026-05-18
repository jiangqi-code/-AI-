<?php
session_start();

// 设置退出消息
$_SESSION['logout_message'] = "您已成功退出登录";

// 清除Cookie
setcookie('user_id', '', time() - 3600, '/');
setcookie('username', '', time() - 3600, '/');

// 销毁session
session_destroy();

// 跳转到登录页
header("Location: login.php");
exit;
?>