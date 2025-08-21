<?php
/**
 * 重置密码页面
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

// 获取令牌
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: forgot_password.php');
    exit;
}

// 验证令牌
try {
    $db = Database::getInstance();
    $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
    
    $user = $db->fetch(
        "SELECT * FROM `{$prefix}users` WHERE `reset_token` = :token AND `reset_expires` > NOW() LIMIT 1",
        ['token' => $token]
    );
    
    if (!$user) {
        $invalid_token = true;
        $error = '无效或已过期的重置链接';
    } else {
        $invalid_token = false;
    }
} catch (Exception $e) {
    $invalid_token = true;
    $error = '验证重置链接失败: ' . $e->getMessage();
}

// 处理表单提交
$error = $error ?? '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$invalid_token) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 验证输入
    if (empty($password) || empty($confirm_password)) {
        $error = '请填写所有必填字段';
    } else if (strlen($password) < 6) {
        $error = '密码长度必须至少为6个字符';
    } else if ($password !== $confirm_password) {
        $error = '两次输入的密码不一致';
    } else {
        try {
            // 更新密码
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $db->update(
                "{$prefix}users",
                [
                    'password' => $password_hash,
                    'reset_token' => null,
                    'reset_expires' => null
                ],
                "`id` = :id",
                ['id' => $user['id']]
            );
            
            // 记录日志
            logAction('reset_password', 'user', $user['id']);
            
            $success = '密码已成功重置。现在您可以使用新密码登录了。';
        } catch (Exception $e) {
            $error = '重置密码失败: ' . $e->getMessage();
        }
    }
}

// 设置页面标题
$page_title = '重置密码';

// 加载页面头部
include __DIR__ . '/templates/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>重置密码</h5>
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
                    <?php elseif (!$invalid_token): ?>
                        <form method="post" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>">
                            <div class="mb-3">
                                <label for="password" class="form-label">新密码</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                <div class="form-text">密码长度必须至少为6个字符</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">确认新密码</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">重置密码</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center">
                            <a href="forgot_password.php" class="btn btn-primary">重新请求密码重置</a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0"><a href="login.php">返回登录</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 加载页面底部
include __DIR__ . '/templates/footer.php';
?>

