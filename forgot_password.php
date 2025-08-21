<?php
/**
 * 忘记密码页面
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

// 获取数据库连接和表前缀
$db = Database::getInstance();
$prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';

// 处理表单提交
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    // 验证输入
    if (empty($email)) {
        $error = '请输入您的邮箱地址';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入有效的邮箱地址';
    } else {
        try {
            // 检查邮箱是否存在
            $user = $db->fetch(
                "SELECT * FROM `{$prefix}users` WHERE `email` = :email",
                ['email' => $email]
            );
            
            if (!$user) {
                $error = '该邮箱地址未注册';
            } else {
                // 获取密码重置有效期（分钟）
                $resetExpiresMinutes = (int)getSetting('password_reset_expires', 60);
                
                // 生成重置令牌
                $token = bin2hex(random_bytes(32));
                $resetExpires = date('Y-m-d H:i:s', strtotime("+{$resetExpiresMinutes} minutes"));
                
                // 更新用户的重置令牌
                $db->update(
                    "{$prefix}users",
                    [
                        'reset_token' => $token,
                        'reset_expires' => $resetExpires
                    ],
                    'id = :id',
                    ['id' => $user['id']]
                );
                
                // 构建完整的重置密码链接
                $resetLink = getResetPasswordUrl($token);
                
                // 构建邮件内容
                $emailSubject = '重置您的密码';
                $emailMessage = '
                    <html>
                    <head>
                        <title>重置您的密码</title>
                    </head>
                    <body>
                        <p>您好，' . htmlspecialchars($user['username']) . '</p>
                        <p>您收到这封邮件是因为您请求重置您的账户密码。</p>
                        <p>请点击下面的链接重置您的密码：</p>
                        <p><a href="' . $resetLink . '">' . $resetLink . '</a></p>
                        <p>此链接将在 ' . $resetExpiresMinutes . ' 分钟后过期。</p>
                        <p>如果您没有请求重置密码，请忽略此邮件。</p>
                        <p>此致<br>论坛管理员</p>
                    </body>
                    </html>
                ';
                
                // 发送邮件
                if (sendEmail($email, $emailSubject, $emailMessage)) {
                    $success = '重置密码的链接已发送到您的邮箱，请检查您的邮件。';
                    
                    // 记录日志
                    logAction('request_password_reset', 'user', $user['id']);
                } else {
                    $error = '发送重置邮件失败，请稍后再试。';
                }
            }
        } catch (Exception $e) {
            $error = '处理请求失败: ' . $e->getMessage();
        }
    }
}

// 设置页面标题
$page_title = '忘记密码';

// 加载页面头部
include __DIR__ . '/templates/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>忘记密码</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (empty($success)): ?>
                        <form method="post" action="<?php echo getForgotPasswordUrl(); ?>">
                            <div class="mb-3">
                                <label for="email" class="form-label">邮箱地址</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="form-text">我们将发送重置密码的链接到这个邮箱</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">发送重置链接</button>
                            <a href="<?php echo getLoginUrl(); ?>" class="btn btn-secondary">返回登录</a>
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