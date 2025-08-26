<?php
/**
 * 分类页面 - 支持伪静态URL
 */

// 加载配置和函数
require_once __DIR__ . '/includes/common.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// 获取分类ID
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($category_id <= 0) {
    header('Location: ' . getHomeUrl());
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
        header('Location: ' . getHomeUrl());
        exit;
    }
    
    // 获取该分类下的主题总数
    $total_topics = $db->fetchColumn(
        "SELECT COUNT(*) FROM `{$prefix}topics` WHERE `category_id` = :category_id AND `status` = 'published'",
        ['category_id' => $category_id]
    );
    
    // 计算总页数
    $total_pages = ceil($total_topics / $topics_per_page);
    
    // 获取当前页的主题列表
    $offset = ($page - 1) * $topics_per_page;
    
    $topics = $db->fetchAll(
        "SELECT t.*, u.username, lu.username as last_post_username
        FROM `{$prefix}topics` t 
        JOIN `{$prefix}users` u ON t.user_id = u.id 
        LEFT JOIN `{$prefix}users` lu ON t.last_post_user_id = lu.id
        WHERE t.category_id = :category_id AND t.status = 'published' 
        ORDER BY t.is_sticky DESC, t.last_post_time DESC 
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
$page_title = isset($category) ? $category['title'] . ($page > 1 ? ' - 第' . $page . '页' : '') : '分类详情';

// 加载页面头部
include __DIR__ . '/templates/header.php';
?>

<div class="container mt-4">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?php echo htmlspecialchars($category['title']); ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?php echo getHomeUrl(); ?>">首页</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo getCategoriesUrl(); ?>">分类列表</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($category['title']); ?></li>
                </ol>
            </nav>
        </div>
        
        <?php if (!empty($category['description'])): ?>
            <div class="alert alert-info mb-4">
                <?php echo nl2br(htmlspecialchars($category['description'])); ?>
            </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <span class="text-muted">共 <?php echo $total_topics; ?> 个主题</span>
            </div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?php echo getNewTopicUrl($category_id); ?>" class="btn btn-primary">发布新主题</a>
            <?php endif; ?>
        </div>
        
        <?php if (empty($topics)): ?>
            <div class="alert alert-info">该分类下暂无主题</div>
        <?php else: ?>
            <div class="list-group mb-4">
                <?php foreach ($topics as $topic): ?>
                    <a href="<?php echo getTopicUrl($topic['id'], null, $topic['title']); ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">
                                <?php if ($topic['is_sticky']): ?>
                                    <span class="badge bg-danger me-1">置顶</span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($topic['title']); ?>
                            </h5>
                            <small><?php echo formatDateTime($topic['created_at'], 'Y-m-d'); ?></small>
                        </div>
                        <div class="d-flex w-100 justify-content-between">
                            <div>
                                <small class="text-muted">
                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($topic['username']); ?>
                                </small>
                            </div>
                            <div>
                                <small class="text-muted">
                                    <i class="bi bi-eye"></i> <?php echo $topic['view_count']; ?>
                                    <?php if ($topic['last_post_time'] && $topic['last_post_time'] != $topic['created_at']): ?>
                                        <span class="ms-2">最后回复: <?php echo htmlspecialchars($topic['last_post_username']); ?> (<?php echo formatDateTime($topic['last_post_time'], 'm-d H:i'); ?>)</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="mb-4">
                    <?php 
                        $pagination_url = getPaginationUrlPattern('category.php', ['id' => $category_id]);
                        echo generatePagination($page, $total_pages, $pagination_url); 
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
// 加载页面底部
include __DIR__ . '/templates/footer.php';
?>
