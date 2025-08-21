<?php
/**
 * 用户个人资料页面
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

// 加载配置和函数
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// 检查是否已登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 获取用户ID
$user_id = $_GET['id'] ?? $_SESSION['user_id'];

// 获取用户信息
try {
    $db = Database::getInstance();
    $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
    
    $user = $db->fetch(
        "SELECT * FROM `{$prefix}users` WHERE `id` = :id",
        ['id' => $user_id]
    );
    
    if (!$user) {
        header('Location: index.php');
        exit;
    }
    
    // 获取用户统计信息
    $topic_count = $db->fetchColumn(
        "SELECT COUNT(*) FROM `{$prefix}topics` WHERE `user_id` = :user_id AND `status` = 'published'",
        ['user_id' => $user_id]
    );
    
    $post_count = $db->fetchColumn(
        "SELECT COUNT(*) FROM `{$prefix}posts` WHERE `user_id` = :user_id AND `status` = 'published'",
        ['user_id' => $user_id]
    );
    
    // 获取用户最近的主题
    $recent_topics = $db->fetchAll(
        "SELECT * FROM `{$prefix}topics` WHERE `user_id` = :user_id AND `status` = 'published' ORDER BY `created_at` DESC LIMIT 5",
        ['user_id' => $user_id]
    );
    
    // 获取用户最近的回复
    $recent_posts = $db->fetchAll(
        "SELECT p.*, t.title as topic_title FROM `{$prefix}posts` p 
        JOIN `{$prefix}topics` t ON p.topic_id = t.id 
        WHERE p.user_id = :user_id AND p.status = 'published' 
        ORDER BY p.created_at DESC LIMIT 5",
        ['user_id' => $user_id]
    );
    
} catch (Exception $e) {
    $error = '加载用户信息失败: ' . $e->getMessage();
}

// 处理个人资料更新
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id == $_SESSION['user_id']) {
    $email = $_POST['email'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 验证输入
    if (empty($email)) {
        $error = '请填写电子邮件地址';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入有效的电子邮件地址';
    } else if (!empty($new_password) && strlen($new_password) < 6) {
        $error = '新密码长度必须至少为6个字符';
    } else if (!empty($new_password) && $new_password !== $confirm_password) {
        $error = '两次输入的新密码不一致';
    } else if (!empty($new_password) && empty($current_password)) {
        $error = '请输入当前密码';
    } else if (!empty($new_password) && !password_verify($current_password, $user['password'])) {
        $error = '当前密码错误';
    } else {
        try {
            // 检查邮箱是否已被其他用户使用
            if ($email !== $user['email']) {
                $exists = $db->fetchColumn(
                    "SELECT COUNT(*) FROM `{$prefix}users` WHERE `email` = :email AND `id` != :id",
                    ['email' => $email, 'id' => $user_id]
                );
                
                if ($exists) {
                    $error = '电子邮件地址已被其他用户使用';
                }
            }
            
            if (empty($error)) {
                // 更新用户信息
                $update_data = ['email' => $email];
                
                if (!empty($new_password)) {
                    $update_data['password'] = password_hash($new_password, PASSWORD_DEFAULT);
                }
                
                $db->update(
                    "{$prefix}users",
                    $update_data,
                    "`id` = :id",
                    ['id' => $user_id]
                );
                
                // 记录更新日志
                logAction('update_profile');
                
                $success = '个人资料已更新';
                
                // 重新获取用户信息
                $user = $db->fetch(
                    "SELECT * FROM `{$prefix}users` WHERE `id` = :id",
                    ['id' => $user_id]
                );
            }
        } catch (Exception $e) {
            $error = '更新个人资料失败: ' . $e->getMessage();
        }
    }
}

// 设置页面标题
$page_title = '用户资料: ' . $user['username'];

// 加载页面头部
include __DIR__ . '/templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>用户资料</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="<?php echo htmlspecialchars($user['username']); ?>" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px;">
                        <?php else: ?>
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto" style="width: 150px; height: 150px; font-size: 64px;">
                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h5><?php echo htmlspecialchars($user['username']); ?></h5>
                    <p class="text-muted">
                        <?php if ($user['role'] === 'admin'): ?>
                            <span class="badge bg-danger">管理员</span>
                        <?php elseif ($user['role'] === 'moderator'): ?>
                            <span class="badge bg-warning">版主</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">会员</span>
                        <?php endif; ?>
                    </p>
                    
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            注册时间
                            <span><?php echo formatDateTime($user['created_at'], 'Y-m-d'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            最后登录
                            <span><?php echo $user['last_login'] ? formatDateTime($user['last_login'], 'Y-m-d') : '从未'; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            主题数
                            <span class="badge bg-primary rounded-pill"><?php echo $topic_count; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            回复数
                            <span class="badge bg-primary rounded-pill"><?php echo $post_count; ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <?php if ($user_id == $_SESSION['user_id']): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>编辑个人资料</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="profile.php">
                            <div class="mb-3">
                                <label for="username" class="form-label">用户名</label>
                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                <div class="form-text">用户名无法更改</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">电子邮件</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <hr>
                            
                            <h6>更改密码</h6>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">当前密码</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                                <div class="form-text">仅在需要更改密码时填写</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">新密码</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">确认新密码</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">保存更改</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>最近的主题</h5>
                </div>
                <div class="card-body">
                    <?php if (count($recent_topics) > 0): ?>
                        <div class="list-group">
                            <?php foreach ($recent_topics as $topic): ?>
                                <a href="topic.php?id=<?php echo $topic['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($topic['title']); ?></h6>
                                        <small><?php echo formatDateTime($topic['created_at']); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo mb_substr(strip_tags($topic['content']), 0, 100) . '...'; ?></p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">暂无主题</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>最近的回复</h5>
                </div>
                <div class="card-body">
                    <?php if (count($recent_posts) > 0): ?>
                        <div class="list-group">
                            <?php foreach ($recent_posts as $post): ?>
                                <a href="topic.php?id=<?php echo $post['topic_id']; ?>#post-<?php echo $post['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">回复: <?php echo htmlspecialchars($post['topic_title']); ?></h6>
                                        <small><?php echo formatDateTime($post['created_at']); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo mb_substr(strip_tags($post['content']), 0, 100) . '...'; ?></p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">暂无回复</p>
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

