<?php
/**
 * 忘记密码页面
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

// 处理表单提交
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    // 验证输入
    if (empty($email)) {
        $error = '请填写电子邮件地址';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入有效的电子邮件地址';
    } else {
        try {
            $db = Database::getInstance();
            $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
            
            // 查询用户
            $user = $db->fetch(
                "SELECT * FROM `{$prefix}users` WHERE `email` = :email LIMIT 1",
                ['email' => $email]
            );
            
            if ($user) {
                // 生成重置令牌
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600); // 1小时后过期
                
                // 存储令牌到数据库
                $db->update(
                    "{$prefix}users",
                    [
                        'reset_token' => $token,
                        'reset_expires' => $expires
                    ],
                    "`id` = :id",
                    ['id' => $user['id']]
                );
                
                // 记录日志
                logAction('request_password_reset', 'user', $user['id']);
                
                // 在实际应用中，这里应该发送包含重置链接的电子邮件
                // 但在这个简化版本中，我们直接显示重置链接
                $reset_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/reset_password.php?token=' . $token;
                
                $success = '密码重置链接已生成。在实际应用中，这将通过电子邮件发送给您。<br><br>重置链接: <a href="' . $reset_url . '">' . $reset_url . '</a>';
            } else {
                // 为了安全，不要透露用户是否存在
                $success = '如果该电子邮件地址与一个有效账户关联，我们将向您发送密码重置说明。';
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
                    <?php else: ?>
                        <p>请输入您的电子邮件地址，我们将向您发送密码重置链接。</p>
                        
                        <form method="post" action="forgot_password.php">
                            <div class="mb-3">
                                <label for="email" class="form-label">电子邮件</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">发送重置链接</button>
                            </div>
                        </form>
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

