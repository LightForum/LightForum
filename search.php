<?php
/**
 * 搜索页面
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

// 获取搜索参数
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'topics';
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

// 获取页码
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

// 每页显示的结果数
$items_per_page = 20;

// 搜索结果
$results = [];
$total_items = 0;
$total_pages = 1;

// 获取分类列表
try {
    $db = Database::getInstance();
    $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
    
    // 获取所有分类
    $categories = $db->fetchAll("SELECT * FROM `{$prefix}categories` ORDER BY `sort_order` ASC");
    
    // 执行搜索
    if (!empty($keyword)) {
        // 记录搜索日志
        logAction('search', 'search', 0, ['keyword' => $keyword, 'type' => $type, 'category_id' => $category_id]);
        
        // 计算偏移量
        $offset = ($page - 1) * $items_per_page;
        
        // 准备搜索条件
        $search_keyword = '%' . $keyword . '%';
        $params = ['keyword1' => $search_keyword, 'keyword2' => $search_keyword];
        
        $category_condition = '';
        if ($category_id > 0) {
            $category_condition = 'AND t.category_id = :category_id';
            $params['category_id'] = $category_id;
        }
        
        if ($type === 'topics') {
            // 搜索主题
            $total_items = $db->fetchColumn(
                "SELECT COUNT(*) FROM `{$prefix}topics` t 
                WHERE (t.title LIKE :keyword1 OR t.content LIKE :keyword2) 
                AND t.status = 'published' $category_condition",
                $params
            );
            
            $results = $db->fetchAll(
                "SELECT t.*, u.username, c.title as category_title, c.id as category_id,
                (SELECT COUNT(*) FROM `{$prefix}posts` WHERE `topic_id` = t.id AND `status` = 'published') as reply_count
                FROM `{$prefix}topics` t 
                JOIN `{$prefix}users` u ON t.user_id = u.id 
                JOIN `{$prefix}categories` c ON t.category_id = c.id 
                WHERE (t.title LIKE :keyword1 OR t.content LIKE :keyword2) 
                AND t.status = 'published' $category_condition
                ORDER BY t.created_at DESC 
                LIMIT :offset, :limit",
                array_merge($params, ['offset' => $offset, 'limit' => $items_per_page])
            );
        } else {
            // 搜索回复
            $total_items = $db->fetchColumn(
                "SELECT COUNT(*) FROM `{$prefix}posts` p 
                JOIN `{$prefix}topics` t ON p.topic_id = t.id 
                WHERE p.content LIKE :keyword1 
                AND p.status = 'published' $category_condition",
                $params
            );
            
            $results = $db->fetchAll(
                "SELECT p.*, t.title as topic_title, t.id as topic_id, u.username, 
                c.title as category_title, c.id as category_id
                FROM `{$prefix}posts` p 
                JOIN `{$prefix}topics` t ON p.topic_id = t.id 
                JOIN `{$prefix}users` u ON p.user_id = u.id 
                JOIN `{$prefix}categories` c ON t.category_id = c.id 
                WHERE p.content LIKE :keyword1 
                AND p.status = 'published' $category_condition
                ORDER BY p.created_at DESC 
                LIMIT :offset, :limit",
                array_merge($params, ['offset' => $offset, 'limit' => $items_per_page])
            );
        }
        
        // 计算总页数
        $total_pages = ceil($total_items / $items_per_page);
    }
    
} catch (Exception $e) {
    $error = '搜索失败: ' . $e->getMessage();
}

// 设置页面标题
$page_title = '搜索' . (!empty($keyword) ? ': ' . $keyword : '');

// 加载页面头部
include __DIR__ . '/templates/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>搜索</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                <li class="breadcrumb-item active" aria-current="page">搜索</li>
            </ol>
        </nav>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="search.php" class="row g-3">
                <div class="col-md-6">
                    <label for="keyword" class="form-label">关键词</label>
                    <input type="text" class="form-control" id="keyword" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" required>
                </div>
                
                <div class="col-md-3">
                    <label for="type" class="form-label">搜索范围</label>
                    <select class="form-select" id="type" name="type">
                        <option value="topics" <?php echo $type === 'topics' ? 'selected' : ''; ?>>主题</option>
                        <option value="posts" <?php echo $type === 'posts' ? 'selected' : ''; ?>>回复</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="category_id" class="form-label">分类</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="0">所有分类</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">搜索</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php elseif (!empty($keyword)): ?>
        <div class="card">
            <div class="card-header">
                <h5>搜索结果: <?php echo htmlspecialchars($keyword); ?></h5>
                <p class="mb-0">共找到 <?php echo $total_items; ?> 条结果</p>
            </div>
            <div class="list-group list-group-flush">
                <?php if (count($results) > 0): ?>
                    <?php if ($type === 'topics'): ?>
                        <?php foreach ($results as $topic): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-1">
                                        <a href="topic.php?id=<?php echo $topic['id']; ?>"><?php echo highlightKeyword($topic['title'], $keyword); ?></a>
                                    </h5>
                                    <small>
                                        <span class="badge bg-primary rounded-pill"><?php echo $topic['reply_count']; ?> 回复</span>
                                    </small>
                                </div>
                                <p class="mb-1"><?php echo highlightKeyword(mb_substr(strip_tags($topic['content']), 0, 200) . '...', $keyword); ?></p>
                                <small class="text-muted">
                                    分类: <a href="category.php?id=<?php echo $topic['category_id']; ?>"><?php echo htmlspecialchars($topic['category_title']); ?></a> | 
                                    作者: <?php echo htmlspecialchars($topic['username']); ?> | 
                                    发表于: <?php echo formatDateTime($topic['created_at']); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($results as $post): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-1">
                                        <a href="topic.php?id=<?php echo $post['topic_id']; ?>#post-<?php echo $post['id']; ?>">回复: <?php echo htmlspecialchars($post['topic_title']); ?></a>
                                    </h5>
                                </div>
                                <p class="mb-1"><?php echo highlightKeyword(mb_substr(strip_tags($post['content']), 0, 200) . '...', $keyword); ?></p>
                                <small class="text-muted">
                                    分类: <a href="category.php?id=<?php echo $post['category_id']; ?>"><?php echo htmlspecialchars($post['category_title']); ?></a> | 
                                    作者: <?php echo htmlspecialchars($post['username']); ?> | 
                                    发表于: <?php echo formatDateTime($post['created_at']); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="list-group-item">
                        <p class="text-center text-muted">没有找到匹配的结果</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="mt-4">
                <?php echo generatePagination($page, $total_pages, 'search.php?keyword=' . urlencode($keyword) . '&type=' . $type . '&category_id=' . $category_id . '&page=%d'); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
// 加载页面底部
include __DIR__ . '/templates/footer.php';

/**
 * 高亮关键词
 */
function highlightKeyword($text, $keyword) {
    if (empty($keyword)) {
        return htmlspecialchars($text);
    }
    
    $text = htmlspecialchars($text);
    $keyword = htmlspecialchars($keyword);
    
    return preg_replace('/(' . preg_quote($keyword, '/') . ')/i', '<mark>$1</mark>', $text);
}
?>

