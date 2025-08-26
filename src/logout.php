<?php
/**
 * 退出登录页面 - 支持伪静态URL
 */
// 加载配置和函数
require_once __DIR__ . '/includes/common.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// 记录退出登录日志
if (isset($_SESSION['user_id'])) {
    logAction('logout');
}

// 清除会话
$_SESSION = [];

// 如果使用了会话cookie，清除它
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 销毁会话
session_destroy();

// 清除记住我的cookie
setcookie('remember_token', '', time() - 3600, '/', '', false, true);

// 重定向到首页
header('Location: ' . getHomeUrl());
exit;
?>
