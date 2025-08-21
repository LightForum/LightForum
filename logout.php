<?php
/**
 * 用户退出登录
 */

// 启动会话
session_start();

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 加载配置和函数
if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/includes/database.php';
    require_once __DIR__ . '/includes/functions.php';
    
    // 记录退出日志
    if (isset($_SESSION['user_id'])) {
        try {
            logAction('logout');
        } catch (Exception $e) {
            // 忽略日志错误
        }
    }
}

// 清除会话数据
$_SESSION = [];

// 如果使用了会话cookie，清除它
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 销毁会话
session_destroy();

// 清除记住我的cookie
setcookie('remember_token', '', time() - 3600, '/');

// 重定向到首页
header('Location: index.php');
exit;
?>

