<?php
/**
 * 管理后台首页
 */

// 启动会话
session_start();

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 检查是否已安装
if (!file_exists(__DIR__ . '/../config/config.php')) {
    header('Location: ../install/index.php');
    exit;
}

// 加载配置和函数
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/admin_functions.php';

// 检查是否已登录且是管理员
checkAdminAccess();

// 获取统计信息
try {
    $db = Database::getInstance();
    $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
    
    // 获取用户数量
    $user_count = $db->fetchColumn("SELECT COUNT(*) FROM `{$prefix}users`");
    
    // 获取主题数量
    $topic_count = $db->fetchColumn("SELECT COUNT(*) FROM `{$prefix}topics`");
    
    // 获取回复数量
    $post_count = $db->fetchColumn("SELECT COUNT(*) FROM `{$prefix}posts`");
    
    // 获取分类数量
    $category_count = $db->fetchColumn("SELECT COUNT(*) FROM `{$prefix}categories`");
    
    // 获取最近注册的用户
    $recent_users = $db->fetchAll(
        "SELECT * FROM `{$prefix}users` ORDER BY `created_at` DESC LIMIT 5"
    );
    
    // 获取最近的主题
    $recent_topics = $db->fetchAll(
        "SELECT t.*, u.username FROM `{$prefix}topics` t 
        JOIN `{$prefix}users` u ON t.user_id = u.id 
        ORDER BY t.created_at DESC LIMIT 5"
    );
    
    // 获取最近的回复
    $recent_posts = $db->fetchAll(
        "SELECT p.*, t.title as topic_title, u.username FROM `{$prefix}posts` p 
        JOIN `{$prefix}topics` t ON p.topic_id = t.id 
        JOIN `{$prefix}users` u ON p.user_id = u.id 
        ORDER BY p.created_at DESC LIMIT 5"
    );
    
    // 获取系统信息
    $system_info = [
        'php_version' => PHP_VERSION,
        'mysql_version' => $db->fetchColumn("SELECT VERSION()"),
        'forum_version' => getSetting('forum_version', '3.0.0'),
        'install_date' => getSetting('install_date', date('Y-m-d H:i:s')),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'server_os' => PHP_OS
    ];
    
} catch (Exception $e) {
    $error = '加载统计信息失败: ' . $e->getMessage();
}

// 设置页面标题
$page_title = '管理后台';

// 加载页面头部
include __DIR__ . '/templates/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- 侧边栏 -->
        <?php include __DIR__ . '/templates/admin_sidebar.php'; ?>
        
        <!-- 主内容区 -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">控制面板</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="../index.php" class="btn btn-sm btn-outline-secondary" target="_blank">访问前台</a>
                    </div>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php else: ?>
                <!-- 统计卡片 -->
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">用户数量</h6>
                                        <h2 class="card-text"><?php echo $user_count; ?></h2>
                                    </div>
                                    <i class="bi bi-people fs-1"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center">
                                <span>查看详情</span>
                                <a href="users.php" class="text-white"><i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">主题数量</h6>
                                        <h2 class="card-text"><?php echo $topic_count; ?></h2>
                                    </div>
                                    <i class="bi bi-file-text fs-1"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center">
                                <span>查看详情</span>
                                <a href="topics.php" class="text-white"><i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">回复数量</h6>
                                        <h2 class="card-text"><?php echo $post_count; ?></h2>
                                    </div>
                                    <i class="bi bi-chat-dots fs-1"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center">
                                <span>查看详情</span>
                                <a href="posts.php" class="text-white"><i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">分类数量</h6>
                                        <h2 class="card-text"><?php echo $category_count; ?></h2>
                                    </div>
                                    <i class="bi bi-folder fs-1"></i>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center">
                                <span>查看详情</span>
                                <a href="categories.php" class="text-white"><i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- 最近注册的用户 -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>最近注册的用户</h5>
                            </div>
                            <div class="list-group list-group-flush">
                                <?php if (count($recent_users) > 0): ?>
                                    <?php foreach ($recent_users as $user): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                    <?php if ($user['role'] === 'admin'): ?>
                                                        <span class="badge bg-danger ms-1">管理员</span>
                                                    <?php elseif ($user['role'] === 'moderator'): ?>
                                                        <span class="badge bg-warning ms-1">版主</span>
                                                    <?php endif; ?>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($user['email']); ?></div>
                                                </div>
                                                <div class="text-muted small">
                                                    <?php echo formatDateTime($user['created_at']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="list-group-item">
                                        <p class="text-center text-muted">暂无用户</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer text-end">
                                <a href="users.php" class="btn btn-sm btn-outline-primary">查看所有用户</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 最近的主题 -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>最近的主题</h5>
                            </div>
                            <div class="list-group list-group-flush">
                                <?php if (count($recent_topics) > 0): ?>
                                    <?php foreach ($recent_topics as $topic): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <a href="../topic.php?id=<?php echo $topic['id']; ?>" target="_blank"><?php echo htmlspecialchars($topic['title']); ?></a>
                                                    <div class="small text-muted">作者: <?php echo htmlspecialchars($topic['username']); ?></div>
                                                </div>
                                                <div class="text-muted small">
                                                    <?php echo formatDateTime($topic['created_at']); ?>
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
                            <div class="card-footer text-end">
                                <a href="topics.php" class="btn btn-sm btn-outline-primary">查看所有主题</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- 最近的回复 -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>最近的回复</h5>
                            </div>
                            <div class="list-group list-group-flush">
                                <?php if (count($recent_posts) > 0): ?>
                                    <?php foreach ($recent_posts as $post): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <a href="../topic.php?id=<?php echo $post['topic_id']; ?>#post-<?php echo $post['id']; ?>" target="_blank">回复: <?php echo htmlspecialchars($post['topic_title']); ?></a>
                                                    <div class="small text-muted">作者: <?php echo htmlspecialchars($post['username']); ?></div>
                                                </div>
                                                <div class="text-muted small">
                                                    <?php echo formatDateTime($post['created_at']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="list-group-item">
                                        <p class="text-center text-muted">暂无回复</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer text-end">
                                <a href="posts.php" class="btn btn-sm btn-outline-primary">查看所有回复</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 系统信息 -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>系统信息</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <tbody>
                                        <tr>
                                            <th>论坛版本</th>
                                            <td><?php echo htmlspecialchars($system_info['forum_version']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>安装日期</th>
                                            <td><?php echo formatDateTime($system_info['install_date']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>PHP版本</th>
                                            <td><?php echo htmlspecialchars($system_info['php_version']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>MySQL版本</th>
                                            <td><?php echo htmlspecialchars($system_info['mysql_version']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>服务器软件</th>
                                            <td><?php echo htmlspecialchars($system_info['server_software']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>服务器操作系统</th>
                                            <td><?php echo htmlspecialchars($system_info['server_os']); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php
// 加载页面底部
include __DIR__ . '/templates/admin_footer.php';
?>

