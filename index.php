<?php
/**
 * 论坛首页 - 完全重构版
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

// 加载页面头部
include __DIR__ . '/templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>欢迎来到 <?php echo htmlspecialchars(getSetting('site_name', 'PHP轻论坛')); ?></h5>
                </div>
                <div class="card-body">
                    <p><?php echo htmlspecialchars(getSetting('site_description', '一个简单易用的PHP论坛程序')); ?></p>
                    
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="alert alert-info">
                            <p>您尚未登录。请 <a href="login.php">登录</a> 或 <a href="register.php">注册</a> 以参与讨论。</p>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-info">
                        季伯家族站点：<a href="https://ikunwl.com">iKun外链</a> | <a href="http://bbs.8w.gs">云梦社区</a> 欢迎宝子们光临~
                    </div>
                </div>
            </div>
            
            <?php
            try {
                $db = Database::getInstance();
                $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
                
                // 获取分类列表
                $categories = $db->fetchAll("SELECT * FROM `{$prefix}categories` ORDER BY `sort_order` ASC");
                
                foreach ($categories as $category) {
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
                        LIMIT 5",
                        ['category_id' => $category['id']]
                    );
                    
                    if (count($topics) > 0) {
                        echo '<div class="list-group">';
                        foreach ($topics as $topic) {
                            echo '<a href="topic.php?id=' . $topic['id'] . '" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">';
                            echo '<div>';
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
                }
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">加载论坛数据时出错: ' . $e->getMessage() . '</div>';
            }
            ?>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>论坛统计</h5>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $db = Database::getInstance();
                        $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
                        
                        $user_count = $db->fetchColumn("SELECT COUNT(*) FROM `{$prefix}users`");
                        $topic_count = $db->fetchColumn("SELECT COUNT(*) FROM `{$prefix}topics` WHERE `status` = 'published'");
                        $post_count = $db->fetchColumn("SELECT COUNT(*) FROM `{$prefix}posts` WHERE `status` = 'published'");
                        
                        echo '<ul class="list-group list-group-flush">';
                        echo '<li class="list-group-item d-flex justify-content-between align-items-center">用户数<span class="badge bg-primary rounded-pill">' . $user_count . '</span></li>';
                        echo '<li class="list-group-item d-flex justify-content-between align-items-center">主题数<span class="badge bg-primary rounded-pill">' . $topic_count . '</span></li>';
                        echo '<li class="list-group-item d-flex justify-content-between align-items-center">回复数<span class="badge bg-primary rounded-pill">' . $post_count . '</span></li>';
                        echo '</ul>';
                    } catch (Exception $e) {
                        echo '<p class="text-danger">加载统计数据时出错</p>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>最新用户</h5>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $db = Database::getInstance();
                        $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
                        
                        $latest_users = $db->fetchAll("SELECT `username`, `created_at` FROM `{$prefix}users` ORDER BY `created_at` DESC LIMIT 5");
                        
                        if (count($latest_users) > 0) {
                            echo '<ul class="list-group list-group-flush">';
                            foreach ($latest_users as $user) {
                                echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                echo htmlspecialchars($user['username']);
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
</div>

<?php
// 加载页面底部
include __DIR__ . '/templates/footer.php';
?>

