<?php
/**
 * 分类详情页面
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

// 获取分类ID
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($category_id <= 0) {
    header('Location: categories.php');
    exit;
}

// 获取页码
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

// 每页显示的主题数
$topics_per_page = (int)getSetting('topics_per_page', 20);

// 获取分类信息和主题列表
try {
    $db = Database::getInstance();
    $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
    
    // 获取分类信息
    $category = $db->fetch(
        "SELECT * FROM `{$prefix}categories` WHERE `id` = :id",
        ['id' => $category_id]
    );
    
    if (!$category) {
        header('Location: categories.php');
        exit;
    }
    
    // 获取主题总数
    $total_topics = $db->fetchColumn(
        "SELECT COUNT(*) FROM `{$prefix}topics` WHERE `category_id` = :category_id AND `status` = 'published'",
        ['category_id' => $category_id]
    );
    
    // 计算总页数
    $total_pages = ceil($total_topics / $topics_per_page);
    
    // 获取当前页的主题列表
    $offset = ($page - 1) * $topics_per_page;
    
    $topics = $db->fetchAll(
        "SELECT t.*, u.username, 
        (SELECT COUNT(*) FROM `{$prefix}posts` WHERE `topic_id` = t.id AND `status` = 'published') as reply_count,
        (SELECT u2.username FROM `{$prefix}users` u2 WHERE u2.id = t.last_post_user_id) as last_post_username
        FROM `{$prefix}topics` t 
        JOIN `{$prefix}users` u ON t.user_id = u.id 
        WHERE t.category_id = :category_id AND t.status = 'published' 
        ORDER BY t.is_sticky DESC, t.last_post_time DESC, t.created_at DESC 
        LIMIT :offset, :limit",
        [
            'category_id' => $category_id,
            'offset' => $offset,
            'limit' => $topics_per_page
        ]
    );
    
} catch (Exception $e) {
    $error = '加载分类信息失败: ' . $e->getMessage();
}

// 设置页面标题
$page_title = isset($category) ? $category['title'] : '分类详情';

// 加载页面头部
include __DIR__ . '/templates/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo htmlspecialchars($category['title']); ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                <li class="breadcrumb-item"><a href="categories.php">分类列表</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($category['title']); ?></li>
            </ol>
        </nav>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-body">
                <p class="mb-0"><?php echo htmlspecialchars($category['description']); ?></p>
            </div>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <span class="text-muted">共 <?php echo $total_topics; ?> 个主题</span>
            </div>
            <div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="new_topic.php?category_id=<?php echo $category_id; ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> 发布新主题
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-primary">登录后发布主题</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <div class="row">
                    <div class="col-md-6">主题</div>
                    <div class="col-md-2 text-center">回复 / 浏览</div>
                    <div class="col-md-4">最后回复</div>
                </div>
            </div>
            <div class="list-group list-group-flush">
                <?php if (count($topics) > 0): ?>
                    <?php foreach ($topics as $topic): ?>
                        <div class="list-group-item <?php echo $topic['is_sticky'] ? 'bg-light' : ''; ?>">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <?php if ($topic['is_sticky']): ?>
                                            <span class="badge bg-danger me-2">置顶</span>
                                        <?php endif; ?>
                                        <?php if ($topic['is_locked']): ?>
                                            <span class="badge bg-secondary me-2">锁定</span>
                                        <?php endif; ?>
                                        <div>
                                            <h5 class="mb-1"><a href="topic.php?id=<?php echo $topic['id']; ?>"><?php echo htmlspecialchars($topic['title']); ?></a></h5>
                                            <div class="small text-muted">
                                                由 <?php echo htmlspecialchars($topic['username']); ?> 发表于 <?php echo formatDateTime($topic['created_at']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 text-center">
                                    <div><?php echo $topic['reply_count']; ?> / <?php echo $topic['view_count']; ?></div>
                                </div>
                                <div class="col-md-4">
                                    <?php if ($topic['last_post_time']): ?>
                                        <div class="small">
                                            <div><?php echo htmlspecialchars($topic['last_post_username']); ?></div>
                                            <div class="text-muted"><?php echo formatDateTime($topic['last_post_time']); ?></div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">暂无回复</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="list-group-item">
                        <p class="text-center text-muted">暂无主题</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="mt-4">
                <?php echo generatePagination($page, $total_pages, 'category.php?id=' . $category_id . '&page=%d'); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
// 加载页面底部
include __DIR__ . '/templates/footer.php';
?>

