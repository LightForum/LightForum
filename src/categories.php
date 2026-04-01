<?php
/**
 * 分类列表页面
 */

// 加载系统
require_once __DIR__ . '/includes/common.php';

// 获取分类列表
try {
    $db = Database::getInstance();
    $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
    
    // 获取所有分类
    $categories = $db->fetchAll("SELECT * FROM `{$prefix}categories` ORDER BY `sort_order` ASC");
    // 获取每个分类的主题数量
    foreach ($categories as &$_category) {
        $_category['topic_count'] = $db->fetchColumn(
            "SELECT COUNT(*) FROM `{$prefix}topics` WHERE `category_id` = :category_id AND `status` = 'published'",
            ['category_id' => $_category['id']]
        );
        
        // 获取最新主题
        $latest_topic = $db->fetch(
            "SELECT t.*, u.username FROM `{$prefix}topics` t 
            JOIN `{$prefix}users` u ON t.user_id = u.id 
            WHERE t.category_id = :category_id AND t.status = 'published' 
            ORDER BY t.created_at DESC LIMIT 1",
            ['category_id' => $_category['id']]
        );
        
        $_category['latest_topic'] = $latest_topic;
    }
    // print_r($categories);exit;
    
} catch (Exception $e) {
    $error = '加载分类列表失败: ' . $e->getMessage();
}

// 设置页面标题
$page_title = '分类列表';

// 加载页面头部
include __DIR__ . '/templates/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>分类列表</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                <li class="breadcrumb-item active" aria-current="page">分类列表</li>
            </ol>
        </nav>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php else: ?>
        <div class="card">
            <div class="card-header bg-primary text-white">
                <div class="row">
                    <div class="col-md-6">分类</div>
                    <div class="col-md-2 text-center">主题数</div>
                    <div class="col-md-4">最新主题</div>
                </div>
            </div>
            <div class="list-group list-group-flush">
                <?php if (count($categories) > 0): ?>
                    <?php foreach ($categories as $category): ?>
                        <div class="list-group-item">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5><a href="category.php?id=<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['title']); ?></a></h5>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($category['description']); ?></p>
                                </div>
                                <div class="col-md-2 text-center">
                                    <span class="badge bg-secondary"><?php echo $category['topic_count']; ?></span>
                                </div>
                                <div class="col-md-4">
                                    <?php if ($category['latest_topic']): ?>
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <a href="topic.php?id=<?php echo $category['latest_topic']['id']; ?>"><?php echo htmlspecialchars($category['latest_topic']['title']); ?></a>
                                                <div class="small text-muted">
                                                    由 <?php echo htmlspecialchars($category['latest_topic']['username']); ?> 发表于 <?php echo formatDateTime($category['latest_topic']['created_at']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">暂无主题</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="list-group-item">
                        <p class="text-center text-muted">暂无分类</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// 加载页面底部
include __DIR__ . '/templates/footer.php';
?>

