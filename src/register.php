<?php
/**
 * 注册页面 - 支持伪静态URL
 */
// 加载系统
require_once __DIR__ . '/includes/common.php';

// 检查是否已登录
if (isset($_SESSION['user_id'])) {
    header('Location: ' . getHomeUrl());
    exit;
}

// 检查是否允许注册
$allow_registration = getSetting('allow_registration', '1');
if ($allow_registration !== '1') {
    $error = '当前不允许注册新用户';
} else {
    $error = '';
}

// 处理注册表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $allow_registration === '1') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 验证输入
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = '请填写所有必填字段';
    } else if (strlen($username) < 3 || strlen($username) > 20) {
        $error = '用户名长度必须在3-20个字符之间';
    } else if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = '用户名只能包含字母、数字和下划线';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '电子邮箱格式不正确';
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
                $error = '该用户名已被使用';
            } else {
                // 检查邮箱是否已存在
                $exists = $db->fetchColumn(
                    "SELECT COUNT(*) FROM `{$prefix}users` WHERE `email` = :email",
                    ['email' => $email]
                );
                
                if ($exists) {
                    $error = '该电子邮箱已被使用';
                } else {
                    // 创建用户
                    $db->insert("{$prefix}users", [
                        'username' => $username,
                        'email' => $email,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'role' => 'user',
                        'status' => 'active',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    $user_id = $db->lastInsertId();
                    
                    // 记录注册日志
                    logAction('register', 'user', $user_id);
                    
                    // 自动登录
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = 'user';
                    
                    // 重定向到首页
                    header('Location: ' . getHomeUrl());
                    exit;
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
                    <h5 class="mb-0">用户注册</h5>
                </div>
                <div class="card-body">
                    <?php if ($allow_registration !== '1'): ?>
                        <div class="alert alert-warning">当前不允许注册新用户</div>
                    <?php else: ?>
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="<?php echo getRegisterUrl(); ?>">
                            <div class="mb-3">
                                <label for="username" class="form-label">用户名</label>
                                <input type="text" class="form-control" id="username" name="username" required minlength="3" maxlength="20" pattern="[a-zA-Z0-9_]+" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                <div class="form-text">用户名只能包含字母、数字和下划线，长度在3-20个字符之间</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">电子邮箱</label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">密码</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                <div class="form-text">密码长度必须至少为6个字符</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">确认密码</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">注册</button>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <div class="text-center">
                            <p>已有账号？ <a href="<?php echo getLoginUrl(); ?>">立即登录</a></p>
                        </div>
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
