<?php
/**
 * 用户个人资料页面
 */
// 加载系统
require_once __DIR__ . '/includes/common.php';

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
    <?php if (isset($error) && !isset($user)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?php echo htmlspecialchars($user['username']); ?> 的个人资料</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?php echo getHomeUrl(); ?>">首页</a></li>
                    <li class="breadcrumb-item active" aria-current="page">用户资料</li>
                </ol>
            </nav>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">基本信息</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <img src="<?php echo !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'https://kzwl.top/qq_avatar.php?qq=' . strtolower(trim($user['email'])); ?>" alt="<?php echo htmlspecialchars($user['username']); ?>" class="rounded-circle" style="width: 100px; height: 100px;">
                        </div>
                        
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>用户名</span>
                                <span><?php echo htmlspecialchars($user['username']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>角色</span>
                                <span>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="badge bg-danger">管理员</span>
                                    <?php elseif ($user['role'] === 'moderator'): ?>
                                        <span class="badge bg-warning">版主</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">普通用户</span>
                                    <?php endif; ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>注册时间</span>
                                <span><?php echo formatDateTime($user['created_at'], 'Y-m-d'); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>主题数</span>
                                <span><?php echo $topic_count; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>回复数</span>
                                <span><?php echo $post_count; ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">编辑个人资料</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            
                            <?php if (!empty($success)): ?>
                                <div class="alert alert-success"><?php echo $success; ?></div>
                            <?php endif; ?>
                            
                            <form method="post" action="<?php echo getUserProfileUrl($user_id); ?>">
                                <div class="mb-3">
                                    <label for="email" class="form-label">电子邮箱</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>

                                <h6>修改密码（如不修改请留空）</h6>
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">当前密码</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">新密码</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">确认新密码</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6">
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">保存修改</button>
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
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><a href="<?php echo getTopicUrl($topic['id'], null, $topic['title']); ?>"><?php echo htmlspecialchars($topic['title']); ?></a></h6>
                                        <div class="d-flex gap-2">
                                            <small><?php echo formatDateTime($topic['created_at']); ?></small>
                                            <?php if ($topic['user_id'] == $_SESSION['user_id'] ||$_SESSION['role'] == 'admin'): ?>
                                                <a href="delete.php?type=topic&id=<?php echo $topic['id']; ?>&redirect=user.php" 
                                                   class="text-danger confirm-action" 
                                                   data-confirm-message="确定要删除这个主题吗？这将删除所有相关回复。">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="mb-1"><?php echo mb_substr(strip_tags($topic['content']), 0, 100) . '...'; ?></p>
                                </div>
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
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><a href="<?php echo getTopicUrl($post['topic_id'], null, $post['topic_title']); ?>#post-<?php echo $post['id']; ?>">回复: <?php echo htmlspecialchars($post['topic_title']); ?></a></h6>
                                        <div class="d-flex gap-2">
                                            <small><?php echo formatDateTime($post['created_at']); ?></small>
                                            <?php if ($post['user_id'] == $_SESSION['user_id'] ||$_SESSION['role'] == 'admin'): ?>
                                                <a href="delete.php?type=post&id=<?php echo $post['id']; ?>&redirect=user.php" 
                                                   class="text-danger confirm-action" 
                                                   data-confirm-message="确定要删除这个回复吗？">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="mb-1"><?php echo mb_substr(strip_tags($post['content']), 0, 100) . '...'; ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">暂无回复</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 删除确认对话框
    const confirmButtons = document.querySelectorAll('.confirm-action');
    confirmButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const confirmMessage = this.getAttribute('data-confirm-message') || '确定要执行此操作吗？';
            
            if (confirm(confirmMessage)) {
                window.location.href = this.href;
            }
        });
    });
});
</script>
<?php
// 加载页面底部
include __DIR__ . '/templates/footer.php';
?>
