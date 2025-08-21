<?php
/**
 * 主题详情页面
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

// 获取主题ID
$topic_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($topic_id <= 0) {
    header('Location: index.php');
    exit;
}

// 获取页码
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

// 每页显示的回复数
$posts_per_page = (int)getSetting('posts_per_page', 15);

// 获取主题信息和回复列表
try {
    $db = Database::getInstance();
    $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
    
    // 获取主题信息
    $topic = $db->fetch(
        "SELECT t.*, u.username, c.title as category_title, c.id as category_id 
        FROM `{$prefix}topics` t 
        JOIN `{$prefix}users` u ON t.user_id = u.id 
        JOIN `{$prefix}categories` c ON t.category_id = c.id 
        WHERE t.id = :id AND t.status = 'published'",
        ['id' => $topic_id]
    );
    
    if (!$topic) {
        header('Location: index.php');
        exit;
    }
    
    // 更新浏览次数
    $db->update(
        "{$prefix}topics",
        ['view_count' => $topic['view_count'] + 1],
        "`id` = :id",
        ['id' => $topic_id]
    );
    
    // 获取回复总数
    $total_posts = $db->fetchColumn(
        "SELECT COUNT(*) FROM `{$prefix}posts` WHERE `topic_id` = :topic_id AND `status` = 'published'",
        ['topic_id' => $topic_id]
    );
    
    // 计算总页数
    $total_pages = ceil(($total_posts + 1) / $posts_per_page); // +1 是因为主题内容也算一个帖子
    
    // 获取当前页的回复列表
    $offset = ($page - 1) * $posts_per_page;
    
    // 如果是第一页，则减去1，因为主题内容占用了一个位置
    if ($page == 1) {
        $limit = $posts_per_page - 1;
        $offset = 0;
    } else {
        $limit = $posts_per_page;
        $offset = $offset - 1; // 减去主题内容占用的位置
    }
    
    $posts = [];
    
    if ($limit > 0) {
        $posts = $db->fetchAll(
            "SELECT p.*, u.username, u.avatar, u.role, u.created_at as user_created_at 
            FROM `{$prefix}posts` p 
            JOIN `{$prefix}users` u ON p.user_id = u.id 
            WHERE p.topic_id = :topic_id AND p.status = 'published' 
            ORDER BY p.created_at ASC 
            LIMIT :offset, :limit",
            [
                'topic_id' => $topic_id,
                'offset' => $offset,
                'limit' => $limit
            ]
        );
    }
    
    // 处理回复表单提交
    $error = '';
    $success = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
        $content = $_POST['content'] ?? '';
        $reply_to = isset($_POST['reply_to']) ? (int)$_POST['reply_to'] : null;
        
        // 验证输入
        if (empty($content)) {
            $error = '请填写回复内容';
        } else if ($topic['is_locked']) {
            $error = '该主题已被锁定，无法回复';
        } else {
            try {
                // 创建回复
                $db->insert("{$prefix}posts", [
                    'topic_id' => $topic_id,
                    'user_id' => $_SESSION['user_id'],
                    'content' => $content,
                    'reply_to' => $reply_to,
                    'status' => 'published',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                $post_id = $db->lastInsertId();
                
                // 更新主题的最后回复信息
                $db->update(
                    "{$prefix}topics",
                    [
                        'last_post_id' => $post_id,
                        'last_post_user_id' => $_SESSION['user_id'],
                        'last_post_time' => date('Y-m-d H:i:s')
                    ],
                    "`id` = :id",
                    ['id' => $topic_id]
                );
                
                // 记录回复日志
                logAction('create_post', 'post', $post_id);
                
                $success = '回复成功';
                
                // 重定向到最后一页
                $new_total_posts = $total_posts + 1;
                $new_total_pages = ceil(($new_total_posts + 1) / $posts_per_page);
                
                header('Location: topic.php?id=' . $topic_id . '&page=' . $new_total_pages . '#post-' . $post_id);
                exit;
            } catch (Exception $e) {
                $error = '回复失败: ' . $e->getMessage();
            }
        }
    }
    
} catch (Exception $e) {
    $error = '加载主题信息失败: ' . $e->getMessage();
}

// 设置页面标题
$page_title = isset($topic) ? $topic['title'] : '主题详情';

// 加载页面头部
include __DIR__ . '/templates/header.php';
?>

<div class="container mt-4">
    <?php if (isset($error) && !isset($topic)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?php echo htmlspecialchars($topic['title']); ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                    <li class="breadcrumb-item"><a href="categories.php">分类列表</a></li>
                    <li class="breadcrumb-item"><a href="category.php?id=<?php echo $topic['category_id']; ?>"><?php echo htmlspecialchars($topic['category_title']); ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">主题详情</li>
                </ol>
            </nav>
        </div>
        
        <?php if ($page == 1): ?>
            <div class="card mb-4" id="post-topic">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?php echo htmlspecialchars($topic['username']); ?></strong>
                        <span class="text-muted ms-2"><?php echo formatDateTime($topic['created_at']); ?></span>
                    </div>
                    <div>
                        <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $topic['user_id'] || $_SESSION['role'] == 'admin')): ?>
                            <a href="edit_topic.php?id=<?php echo $topic_id; ?>" class="btn btn-sm btn-outline-primary">编辑</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="topic-content">
                        <?php echo nl2br(htmlspecialchars($topic['content'])); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $index => $post): ?>
                <div class="card mb-4" id="post-<?php echo $post['id']; ?>">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo htmlspecialchars($post['username']); ?></strong>
                            <?php if ($post['role'] === 'admin'): ?>
                                <span class="badge bg-danger ms-1">管理员</span>
                            <?php elseif ($post['role'] === 'moderator'): ?>
                                <span class="badge bg-warning ms-1">版主</span>
                            <?php endif; ?>
                            <span class="text-muted ms-2"><?php echo formatDateTime($post['created_at']); ?></span>
                        </div>
                        <div>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <button class="btn btn-sm btn-outline-secondary reply-btn" data-post-id="<?php echo $post['id']; ?>" data-username="<?php echo htmlspecialchars($post['username']); ?>">回复</button>
                                <?php if ($_SESSION['user_id'] == $post['user_id'] || $_SESSION['role'] == 'admin'): ?>
                                    <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary">编辑</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($post['reply_to']): ?>
                            <div class="alert alert-secondary">
                                <?php
                                $reply_to_post = $db->fetch(
                                    "SELECT p.content, u.username FROM `{$prefix}posts` p 
                                    JOIN `{$prefix}users` u ON p.user_id = u.id 
                                    WHERE p.id = :id",
                                    ['id' => $post['reply_to']]
                                );
                                
                                if ($reply_to_post):
                                ?>
                                    <div class="small">
                                        <strong>回复 <?php echo htmlspecialchars($reply_to_post['username']); ?>:</strong>
                                        <p class="mb-0"><?php echo mb_substr(strip_tags($reply_to_post['content']), 0, 100) . (mb_strlen($reply_to_post['content']) > 100 ? '...' : ''); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="post-content">
                            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if ($total_pages > 1): ?>
            <div class="mb-4">
                <?php echo generatePagination($page, $total_pages, 'topic.php?id=' . $topic_id . '&page=%d'); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['user_id']) && !$topic['is_locked']): ?>
            <div class="card">
                <div class="card-header">
                    <h5>发表回复</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="topic.php?id=<?php echo $topic_id; ?>" id="reply-form">
                        <input type="hidden" name="reply_to" id="reply_to" value="">
                        
                        <div id="reply-to-info" class="alert alert-info d-none">
                            回复给: <span id="reply-username"></span>
                            <button type="button" class="btn-close float-end" id="cancel-reply"></button>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">回复内容</label>
                            <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">提交回复</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($topic['is_locked']): ?>
            <div class="alert alert-warning">
                <i class="bi bi-lock-fill"></i> 该主题已被锁定，无法回复
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <a href="login.php">登录</a> 后才能回复
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 回复功能
    const replyButtons = document.querySelectorAll('.reply-btn');
    const replyForm = document.getElementById('reply-form');
    const replyToInput = document.getElementById('reply_to');
    const replyToInfo = document.getElementById('reply-to-info');
    const replyUsername = document.getElementById('reply-username');
    const cancelReply = document.getElementById('cancel-reply');
    
    replyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const username = this.getAttribute('data-username');
            
            replyToInput.value = postId;
            replyUsername.textContent = username;
            replyToInfo.classList.remove('d-none');
            
            // 滚动到回复表单
            replyForm.scrollIntoView({ behavior: 'smooth' });
            
            // 聚焦到文本框
            document.getElementById('content').focus();
        });
    });
    
    if (cancelReply) {
        cancelReply.addEventListener('click', function() {
            replyToInput.value = '';
            replyToInfo.classList.add('d-none');
        });
    }
});
</script>

<?php
// 加载页面底部
include __DIR__ . '/templates/footer.php';
?>

