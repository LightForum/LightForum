<?php
/**
 * 首页 - 支持伪静态URL
 */
// 加载配置和函数
require_once __DIR__ . '/includes/common.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// 检查是否已登录
if (isset($_SESSION['user_id'])) {
    // 获取用户ID
    $user_id = $_GET['id'] ?? $_SESSION['user_id'];
    
    // 获取用户信息（包含email字段）
    try {
        $db = Database::getInstance();
        $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
        $user = $db->fetch(
            "SELECT id, username, email, avatar, created_at, updated_at 
             FROM `{$prefix}users` 
             WHERE `id` = :id",
            ['id' => $user_id]
        );
    } catch (Exception $e) {
        $error = '加载用户信息失败: ' . $e->getMessage();
    }
}

// 获取页码
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

// 每页显示的主题数
$topics_per_page = getSetting('topics_per_page', '12');

// 获取主题列表
try {
    $db = Database::getInstance();
    $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
    
    // 获取主题总数
    $total_topics = $db->fetchColumn(
        "SELECT COUNT(*) FROM `{$prefix}topics` WHERE `status` = 'published'"
    );
    
    // 计算总页数
    $total_pages = ceil($total_topics / $topics_per_page);
    
    // 获取当前页的主题列表，同时获取作者邮箱
    $offset = ($page - 1) * $topics_per_page;
    
    $topics = $db->fetchAll(
        "SELECT t.*, u.username, u.email as author_email, c.title as category_title, c.id as category_id,
                lu.username as last_post_username
        FROM `{$prefix}topics` t 
        JOIN `{$prefix}users` u ON t.user_id = u.id 
        JOIN `{$prefix}categories` c ON t.category_id = c.id 
        LEFT JOIN `{$prefix}users` lu ON t.last_post_user_id = lu.id
        WHERE t.status = 'published' 
        ORDER BY t.is_sticky DESC, t.last_post_time DESC 
        LIMIT :offset, :limit",
        [
            'offset' => $offset,
            'limit' => $topics_per_page
        ]
    );
    
    // 获取分类列表
    $categories = $db->fetchAll(
        "SELECT c.*, COUNT(t.id) as topic_count 
        FROM `{$prefix}categories` c 
        LEFT JOIN `{$prefix}topics` t ON c.id = t.category_id AND t.status = 'published'
        GROUP BY c.id 
        ORDER BY c.sort_order ASC"
    );
    
} catch (Exception $e) {
    $error = '加载主题列表失败: ' . $e->getMessage();
}

// 设置页面标题
$page_title = getSetting('site_title', 'PHP轻论坛') . ($page > 1 ? ' - 第' . $page . '页' : '');

// 加载页面头部
include __DIR__ . '/templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    欢迎来到 <?php echo htmlspecialchars(getSetting('site_name', 'PHP轻论坛')); ?>
                </div>
                <div class="card-body">
                    <p><?php echo htmlspecialchars(getSetting('site_description', '一个简单易用的PHP论坛程序')); ?></p>
                    
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="alert alert-info">
                            您尚未登录。请 <a href="login.php">登录</a> 或 <a href="register.php">注册</a> 以参与讨论。
                        </div>
                    <?php endif; ?>
                    <div class="alert alert-info">
                        系列站点：<a href="http://free.uiisc.org" target="_blank">免费空间</a> | <a href="http://doghost.cc/zh-cn/" target="_blank">狗狗主机</a> 欢迎宝子们光临~
                    </div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    最新主题
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?php echo getNewTopicUrl(); ?>" class="btn btn-primary">发布新主题</a>
                    <?php endif; ?>
                </div>
                <?php if (isset($error)): ?>
                    <div class="card-body"><?php echo $error; ?></div>
                <?php elseif (empty($topics)): ?>
                    <div class="card-body">暂无主题</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($topics as $topic): ?>
                            <a href="<?php echo getTopicUrl($topic['id'], null, $topic['title']); ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between newpost">
                                    <p class="mb-0">
                                        <?php if ($topic['is_sticky']): ?>
                                            <span class="badge bg-danger me-1">置顶</span>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($topic['title']); ?>
                                    </p>
                                    <small class="text-muted">
                                        <?php if ($topic['last_post_time'] && $topic['last_post_time'] != $topic['created_at']): ?>
                                            <span>最后回复: <?php echo htmlspecialchars($topic['last_post_username']); ?> (<?php echo formatDateTime($topic['last_post_time'], 'm-d H:i'); ?>)</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <style>
            .newpost{align-items: center;}
            @media only screen and (max-width: 768px) {
                .newpost{flex-direction: column;align-items: flex-start;}
            }
        </style>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">分类列表</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($categories as $category): ?>
                            <a href="<?php echo getCategoryUrl($category['id'], null, $category['title']); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($category['title']); ?>
                                <span class="badge bg-primary rounded-pill"><?php echo $category['topic_count']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">论坛统计</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            主题数
                            <span class="badge bg-primary rounded-pill"><?php echo $total_topics; ?></span>
                        </li>
                        <?php
                        $total_posts = $db->fetchColumn("SELECT COUNT(*) FROM `{$prefix}posts` WHERE `status` = 'published'");
                        $total_users = $db->fetchColumn("SELECT COUNT(*) FROM `{$prefix}users`");
                        $newest_user = $db->fetch("SELECT username, id FROM `{$prefix}users` ORDER BY created_at DESC LIMIT 1");
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            回复数
                            <span class="badge bg-primary rounded-pill"><?php echo $total_posts; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            用户数
                            <span class="badge bg-primary rounded-pill"><?php echo $total_users; ?></span>
                        </li>
                        <?php if ($newest_user): ?>
                            <li class="list-group-item">
                                欢迎最新会员: <a href="<?php echo getUserProfileUrl($newest_user['id'], $newest_user['username']); ?>"><?php echo htmlspecialchars($newest_user['username']); ?></a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header">
                    <h5>用户列表</h5>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $db = Database::getInstance();
                        $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
                        
                        $latest_users = $db->fetchAll("SELECT `username`, `id`, `created_at` FROM `{$prefix}users` ORDER BY `created_at` DESC LIMIT 6");
                        
                        if (count($latest_users) > 0) {
                            echo '<ul class="list-group">';
                            foreach ($latest_users as $user) {
                                echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                echo '<a href="' . getUserProfileUrl($user['id'], $user['username']) . '">' . htmlspecialchars($user['username']) . '</a>';
                                echo '<small class="text-muted">' . formatDateTime($user['created_at'], 'Y-m-d') . '</small>';
                                echo '</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<p class="text-muted">暂无用户</p>';
                        }
                    } catch (Exception $e) {
                        echo '<p class="text-danger">加载用户数据时出错</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <?php
            foreach ($categories as $category) {
                echo '<div class="col-md-4">';
                echo '<div class="card mb-4">';
                echo '<div class="card-header d-flex justify-content-between align-items-center">';
                echo '<h5>' . htmlspecialchars($category['title']) . '</h5>';
                echo '<a href="category.php?id=' . $category['id'] . '" class="btn btn-sm btn-primary">查看全部</a>';
                echo '</div>';
                
                echo '<div class="card-body">';
                echo '<p>' . htmlspecialchars($category['description']) . '</p>';
                
                // 获取该分类下的最新主题
                $topics = $db->fetchAll(
                    "SELECT t.*, u.username 
                    FROM `{$prefix}topics` t 
                    JOIN `{$prefix}users` u ON t.user_id = u.id 
                    WHERE t.category_id = :category_id AND t.status = 'published' 
                    ORDER BY t.is_sticky DESC, t.created_at DESC 
                    LIMIT 6",
                    ['category_id' => $category['id']]
                );
                
                if (count($topics) > 0) {
                    echo '<div class="list-group">';
                    foreach ($topics as $topic) {
                        echo '<a href="topic.php?id=' . $topic['id'] . '" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">';
                        echo '<div style="text-overflow: ellipsis;white-space: nowrap;overflow: hidden;">';
                        if ($topic['is_sticky']) {
                            echo '<span class="badge bg-danger me-2">置顶</span>';
                        }
                        echo htmlspecialchars($topic['title']);
                        echo '<small class="text-muted ms-2">by ' . htmlspecialchars($topic['username']) . '</small>';
                        echo '</div>';
                        echo '<span class="badge bg-primary rounded-pill">' . $topic['view_count'] . ' 浏览</span>';
                        echo '</a>';
                    }
                    echo '</div>';
                } else {
                    echo '<p class="text-muted">暂无主题</p>';
                }
                
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        ?>
    </div>
    <?php
    // 获取启用的友链列表
    $links = getActiveLinks();
    if (!empty($links)): ?>
    <div class="card">
        <div class="card-header">
            <h5>友情链接</h5>
        </div>
        <div class="card-body">
            <?php foreach ($links as $link): ?>
                <div class="links">
                    <a href="<?php echo $link['url']; ?>" target="_blank" class="d-block link-item">
                        <span class="badge bg-primary text-white"><?php echo htmlspecialchars($link['name']); ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <style>
            .links a {margin-right: 4px;float: left;}
        </style>
    </div>
    <?php endif; ?>
</div>
<?php
// 加载页面底部
include __DIR__ . '/templates/footer.php';
?>
