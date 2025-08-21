<?php
/**
 * 用户登录页面
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

// 处理登录表单提交
$error = '';
$success = '';

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
                "SELECT * FROM `{$prefix}users` WHERE `username` = :username OR `email` = :email LIMIT 1",
                ['username' => $username, 'email' => $username]
            );
            
            if ($user && password_verify($password, $user['password'])) {
                // 登录成功
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // 更新最后登录时间
                $db->update(
                    "{$prefix}users",
                    ['last_login' => date('Y-m-d H:i:s')],
                    "`id` = :id",
                    ['id' => $user['id']]
                );
                
                // 记录登录日志
                logAction('login');
                
                // 如果选择了"记住我"，设置cookie
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + 30 * 24 * 60 * 60, '/');
                    
                    // 存储令牌到数据库
                    $db->update(
                        "{$prefix}users",
                        ['remember_token' => $token],
                        "`id` = :id",
                        ['id' => $user['id']]
                    );
                }
                
                // 重定向到首页
                header('Location: index.php');
                exit;
            } else {
                $error = '用户名或密码错误';
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
                    <h5>用户登录</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="login.php">
                        <div class="mb-3">
                            <label for="username" class="form-label">用户名或电子邮件</label>
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
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">还没有账号？<a href="register.php">立即注册</a></p>
                    <p class="mb-0 mt-2"><a href="forgot_password.php">忘记密码？</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 加载页面底部
include __DIR__ . '/templates/footer.php';
?>

