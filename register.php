<?php
/**
 * 用户注册页面
 */

// 启动会话
session_start();

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 检查是否已登录
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// 检查是否已安装
if (!file_exists(__DIR__ . '/config/config.php')) {
    header('Location: install/index.php');
    exit;
}

// 加载配置和函数
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// 检查是否允许注册
$allow_registration = getSetting('allow_registration', '1');
if ($allow_registration !== '1') {
    $error = '注册功能已关闭';
    $disabled = true;
} else {
    $error = '';
    $disabled = false;
}

$success = '';

// 处理注册表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$disabled) {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 验证输入
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = '请填写所有必填字段';
    } else if (strlen($username) < 3 || strlen($username) > 20) {
        $error = '用户名长度必须在3-20个字符之间';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入有效的电子邮件地址';
    } else if (strlen($password) < 6) {
        $error = '密码长度必须至少为6个字符';
    } else if ($password !== $confirm_password) {
        $error = '两次输入的密码不一致';
    } else {
        try {
            $db = Database::getInstance();
            $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
            
            // 检查用户名是否已存在
            $exists = $db->fetchColumn(
                "SELECT COUNT(*) FROM `{$prefix}users` WHERE `username` = :username",
                ['username' => $username]
            );
            
            if ($exists) {
                $error = '用户名已被使用';
            } else {
                // 检查邮箱是否已存在
                $exists = $db->fetchColumn(
                    "SELECT COUNT(*) FROM `{$prefix}users` WHERE `email` = :email",
                    ['email' => $email]
                );
                
                if ($exists) {
                    $error = '电子邮件地址已被使用';
                } else {
                    // 创建用户
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    $db->insert("{$prefix}users", [
                        'username' => $username,
                        'email' => $email,
                        'password' => $password_hash,
                        'role' => 'user',
                        'status' => 'active',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    $user_id = $db->lastInsertId();
                    
                    // 记录注册日志
                    logAction('register', 'user', $user_id);
                    
                    $success = '注册成功！现在您可以登录了。';
                }
            }
        } catch (Exception $e) {
            $error = '注册失败: ' . $e->getMessage();
        }
    }
}

// 设置页面标题
$page_title = '用户注册';

// 加载页面头部
include __DIR__ . '/templates/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>用户注册</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <div class="text-center">
                            <a href="login.php" class="btn btn-primary">前往登录</a>
                        </div>
                    <?php else: ?>
                        <?php if (!$disabled): ?>
                            <form method="post" action="register.php">
                                <div class="mb-3">
                                    <label for="username" class="form-label">用户名</label>
                                    <input type="text" class="form-control" id="username" name="username" required minlength="3" maxlength="20">
                                    <div class="form-text">用户名长度必须在3-20个字符之间</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">电子邮件</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">密码</label>
                                    <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                    <div class="form-text">密码长度必须至少为6个字符</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">确认密码</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">注册</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">已有账号？<a href="login.php">立即登录</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 加载页面底部
include __DIR__ . '/templates/footer.php';
?>

