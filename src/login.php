<?php
/**
 * 登录页面 - 支持伪静态URL
 */
// 加载配置和函数
require_once __DIR__ . '/includes/common.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// 检查是否已登录
if (isset($_SESSION['user_id'])) {
    header('Location: ' . getHomeUrl());
    exit;
}

// 处理登录表单提交
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // 验证输入
    if (empty($username) || empty($password)) {
        $error = '请填写用户名和密码';
    } else {
        try {
            $db = Database::getInstance();
            $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
            
            // 查询用户
            $user = $db->fetch(
                "SELECT * FROM `{$prefix}users` WHERE `username` = :username OR `email` = :email",
                [
                    'username' => $username,
                    'email' => $username
                ]
            );
            
            if (!$user || !password_verify($password, $user['password'])) {
                $error = '用户名或密码不正确';
            } else if ($user['status'] !== 'active') {
                $error = '该账号已被禁用';
            } else {
                // 登录成功
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // 记录登录日志
                logAction('login');
                
                // 更新最后登录时间和IP
                $db->update("{$prefix}users", [
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_ip' => getClientIp()
                ], '`id` = :id', ['id' => $user['id']]);
                
                // 如果勾选了"记住我"，设置Cookie
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + 30 * 24 * 60 * 60; // 30天
                    
                    // 存储令牌到数据库
                    $db->insert("{$prefix}user_tokens", [
                        'user_id' => $user['id'],
                        'token' => password_hash($token, PASSWORD_DEFAULT),
                        'expires_at' => date('Y-m-d H:i:s', $expires),
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    // 设置Cookie
                    setcookie('remember_token', $user['id'] . ':' . $token, $expires, '/', '', false, true);
                }
                
                // 重定向到首页或之前的页面
                $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : getHomeUrl();
                unset($_SESSION['redirect_after_login']);
                
                header('Location: ' . $redirect);
                exit;
            }
        } catch (Exception $e) {
            $error = '登录失败: ' . $e->getMessage();
        }
    }
}

// 设置页面标题
$page_title = '用户登录';

// 加载页面头部
include __DIR__ . '/templates/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">用户登录</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?php echo getLoginUrl(); ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">用户名或电子邮箱</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">密码</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">记住我</label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">登录</button>
                            <a href="<?php echo getForgotPasswordUrl(); ?>" class="btn btn-link">忘记密码？</a>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <div class="text-center">
                        <p>还没有账号？ <a href="<?php echo getRegisterUrl(); ?>">立即注册</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 加载页面底部
include __DIR__ . '/templates/footer.php';
?>
