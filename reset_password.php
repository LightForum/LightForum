<?php
/**
 * 重置密码页面
 */

// 启动会话
session_start();

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 检查是否已安装
if (!file_exists(__DIR__ . '/config/config.php')) {
    header('Location: install/index.php');
    exit;
}

// 检查是否已登录
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// 加载配置和函数
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/smtp.php';

// 获取重置令牌
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token)) {
    header('Location: login.php');
    exit;
}

try {
    $db = Database::getInstance();
    $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
    
    // 检查令牌是否有效
    $user = $db->fetch(
        "SELECT * FROM `{$prefix}users` 
         WHERE `reset_token` = :token 
         AND `reset_expires` > NOW()",
        ['token' => $token]
    );
    
    if (!$user) {
        $invalidToken = true;
    } else {
        $invalidToken = false;
    }
    
} catch (Exception $e) {
    $error = '验证令牌失败: ' . $e->getMessage();
    $invalidToken = true;
}

// 处理表单提交
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$invalidToken) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // 验证输入
    if (empty($password) || empty($confirmPassword)) {
        $error = '请填写所有字段';
    } else if (strlen($password) < 8) {
        $error = '密码长度必须至少为8个字符';
    } else if ($password !== $confirmPassword) {
        $error = '两次输入的密码不一致';
    } else {
        try {
            // 更新用户密码
            $db->update(
                "{$prefix}users",
                [
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'reset_token' => null,
                    'reset_expires' => null
                ],
                'id = :id',
                ['id' => $user['id']]
            );
            
            $success = '密码重置成功，请使用新密码登录。';
            
            // 记录日志
            logAction('reset_password', 'user', $user['id']);
            
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
                    <h5><?php echo $invalidToken ? '无效的重置链接' : '重置密码'; ?></h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <a href="login.php" class="btn btn-primary">登录</a>
                    <?php elseif ($invalidToken): ?>
                        <div class="alert alert-danger">
                            <p>您的重置链接无效或已过期。</p>
                            <p>请返回<a href="forgot_password.php">忘记密码</a>页面重新请求重置密码。</p>
                        </div>
                        <a href="forgot_password.php" class="btn btn-primary">重新请求</a>
                    <?php else: ?>
                        <form method="post" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>">
                            <div class="mb-3">
                                <label for="password" class="form-label">新密码</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                <div class="form-text">密码长度必须至少为8个字符</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">确认新密码</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <div class="form-text">请再次输入您的新密码</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">重置密码</button>
                            <a href="login.php" class="btn btn-secondary">取消</a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 加载页面底部
include __DIR__ . '/templates/footer.php';
?>