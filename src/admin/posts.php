<?php
/**
 * 回复管理页面
 */

// 加载配置和函数
require_once __DIR__ . '/../includes/common.php';
require_once __DIR__ . '/includes/admin_functions.php';

// 检查是否已登录且是管理员
checkAdminAccess();

// 获取操作类型
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// 处理操作
$error = '';
$success = '';

// 获取数据库实例
$db = Database::getInstance();
$prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';

// 处理回复操作
switch ($action) {
    case 'edit':
        // 编辑回复
        $post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($post_id <= 0) {
            header('Location: posts.php');
            exit;
        }
        
        // 获取回复信息
        try {
            $post = $db->fetch(
                "SELECT p.*, u.username, t.title as topic_title 
                FROM `{$prefix}posts` p 
                JOIN `{$prefix}users` u ON p.user_id = u.id 
                JOIN `{$prefix}topics` t ON p.topic_id = t.id 
                WHERE p.id = :id",
                ['id' => $post_id]
            );
            
            if (!$post) {
                header('Location: posts.php');
                exit;
            }
        } catch (Exception $e) {
            $error = '获取回复信息失败: ' . $e->getMessage();
        }
        
        // 处理表单提交
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $content = $_POST['content'] ?? '';
            $status = $_POST['status'] ?? 'published';
            
            // 验证输入
            if (empty($content)) {
                $error = '回复内容不能为空';
            } else {
                try {
                    // 更新回复信息
                    $db->update("{$prefix}posts", [
                        'content' => $content,
                        'status' => $status,
                        'updated_at' => date('Y-m-d H:i:s')
                    ], '`id` = :id', ['id' => $post_id]);
                    
                    // 记录操作日志
                    logAdminAction('edit_post', 'post', $post_id);
                    
                    $success = '回复信息更新成功';
                    
                    // 重新获取回复信息
                    $post = $db->fetch(
                        "SELECT p.*, u.username, t.title as topic_title 
                        FROM `{$prefix}posts` p 
                        JOIN `{$prefix}users` u ON p.user_id = u.id 
                        JOIN `{$prefix}topics` t ON p.topic_id = t.id 
                        WHERE p.id = :id",
                        ['id' => $post_id]
                    );
                } catch (Exception $e) {
                    $error = '更新回复信息失败: ' . $e->getMessage();
                }
            }
        }
        
        // 设置页面标题
        $page_title = '编辑回复';
        
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
                        <h1 class="h2">编辑回复</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <a href="posts.php" class="btn btn-sm btn-outline-secondary">返回回复列表</a>
                                <a href="../topic.php?id=<?php echo $post['topic_id']; ?>#post-<?php echo $post_id; ?>" class="btn btn-sm btn-outline-primary" target="_blank">查看回复</a>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>回复信息</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <strong>ID:</strong>
                                </div>
                                <div class="col-md-9">
                                    <?php echo $post['id']; ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <strong>所属主题:</strong>
                                </div>
                                <div class="col-md-9">
                                    <a href="../topic.php?id=<?php echo $post['topic_id']; ?>" target="_blank">
                                        <?php echo htmlspecialchars($post['topic_title']); ?>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <strong>作者:</strong>
                                </div>
                                <div class="col-md-9">
                                    <?php echo htmlspecialchars($post['username']); ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <strong>创建时间:</strong>
                                </div>
                                <div class="col-md-9">
                                    <?php echo formatDateTime($post['created_at']); ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <strong>最后更新:</strong>
                                </div>
                                <div class="col-md-9">
                                    <?php echo formatDateTime($post['updated_at']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <form method="post" action="posts.php?action=edit&id=<?php echo $post_id; ?>">
                                <div class="mb-3">
                                    <label for="content" class="form-label">回复内容</label>
                                    <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="status" class="form-label">状态</label>
                                    <select class="form-select" id="status" name="status">
                                        <?php foreach (getPostStatuses() as $key => $value): ?>
                                            <option value="<?php echo $key; ?>" <?php echo $post['status'] === $key ? 'selected' : ''; ?>>
                                                <?php echo $value; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">保存修改</button>
                            </form>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        
        <?php
        // 加载页面底部
        include __DIR__ . '/templates/admin_footer.php';
        break;
        
    case 'delete':
        // 删除回复
        $post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($post_id <= 0) {
            header('Location: posts.php');
            exit;
        }
        
        try {
            // 获取回复信息
            $post = $db->fetch(
                "SELECT * FROM `{$prefix}posts` WHERE `id` = :id",
                ['id' => $post_id]
            );
            
            if (!$post) {
                header('Location: posts.php');
                exit;
            }
            
            // 删除回复
            $db->delete("{$prefix}posts", '`id` = :id', ['id' => $post_id]);
            
            // 更新主题的最后回复信息
            updateTopicLastPost($post['topic_id']);
            
            // 记录操作日志
            logAdminAction('delete_post', 'post', $post_id, ['topic_id' => $post['topic_id']]);
            
            header('Location: posts.php?success=' . urlencode('回复删除成功'));
            exit;
        } catch (Exception $e) {
            header('Location: posts.php?error=' . urlencode('删除回复失败: ' . $e->getMessage()));
            exit;
        }
        break;
        
    default:
        // 回复列表
        // 获取页码
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }
        
        // 每页显示的回复数
        $posts_per_page = 20;
        
        // 获取搜索参数
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $topic_id = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : 0;
        $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        try {
            // 构建查询条件
            $conditions = [];
            $params = [];
            
            if (!empty($search)) {
                $conditions[] = 'p.content LIKE :search';
                $params['search'] = '%' . $search . '%';
            }
            
            if ($topic_id > 0) {
                $conditions[] = 'p.topic_id = :topic_id';
                $params['topic_id'] = $topic_id;
            }
            
            if ($user_id > 0) {
                $conditions[] = 'p.user_id = :user_id';
                $params['user_id'] = $user_id;
            }
            
            if (!empty($status)) {
                $conditions[] = 'p.status = :status';
                $params['status'] = $status;
            }
            
            $where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // 获取回复总数
            $total_posts = $db->fetchColumn(
                "SELECT COUNT(*) FROM `{$prefix}posts` p {$where_clause}",
                $params
            );
            
            // 计算总页数
            $total_pages = ceil($total_posts / $posts_per_page);
            
            // 确保页码不超过总页数
            if ($page > $total_pages && $total_pages > 0) {
                $page = $total_pages;
            }
            
            // 计算偏移量
            $offset = ($page - 1) * $posts_per_page;
            
            // 获取回复列表
            $posts = $db->fetchAll(
                "SELECT p.*, u.username, t.title as topic_title 
                FROM `{$prefix}posts` p 
                JOIN `{$prefix}users` u ON p.user_id = u.id 
                JOIN `{$prefix}topics` t ON p.topic_id = t.id 
                {$where_clause} 
                ORDER BY p.id DESC 
                LIMIT :offset, :limit",
                array_merge($params, ['offset' => $offset, 'limit' => $posts_per_page])
            );
        } catch (Exception $e) {
            $error = '获取回复列表失败: ' . $e->getMessage();
        }
        
        // 获取成功或错误消息
        if (isset($_GET['success'])) {
            $success = $_GET['success'];
        }
        
        if (isset($_GET['error'])) {
            $error = $_GET['error'];
        }
        
        // 设置页面标题
        $page_title = '回复管理';
        
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
                        <h1 class="h2">回复管理</h1>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="get" action="posts.php" class="row g-3">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="search" placeholder="搜索回复内容" value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <input type="number" class="form-control" name="topic_id" placeholder="主题ID" value="<?php echo $topic_id > 0 ? $topic_id : ''; ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <input type="number" class="form-control" name="user_id" placeholder="用户ID" value="<?php echo $user_id > 0 ? $user_id : ''; ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <select class="form-select" name="status">
                                        <option value="">所有状态</option>
                                        <?php foreach (getPostStatuses() as $key => $value): ?>
                                            <option value="<?php echo $key; ?>" <?php echo $status === $key ? 'selected' : ''; ?>>
                                                <?php echo $value; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">搜索</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>内容</th>
                                    <th>主题</th>
                                    <th>作者</th>
                                    <th>状态</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($posts) && count($posts) > 0): ?>
                                    <?php foreach ($posts as $post): ?>
                                        <tr>
                                            <td><?php echo $post['id']; ?></td>
                                            <td><?php echo htmlspecialchars(mb_substr($post['content'], 0, 50)) . (mb_strlen($post['content']) > 50 ? '...' : ''); ?></td>
                                            <td>
                                                <a href="../topic.php?id=<?php echo $post['topic_id']; ?>" target="_blank">
                                                    <?php echo htmlspecialchars(mb_substr($post['topic_title'], 0, 20)) . (mb_strlen($post['topic_title']) > 20 ? '...' : ''); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($post['username']); ?></td>
                                            <td>
                                                <?php if ($post['status'] === 'published'): ?>
                                                    <span class="badge bg-success">已发布</span>
                                                <?php elseif ($post['status'] === 'hidden'): ?>
                                                    <span class="badge bg-secondary">已隐藏</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">已删除</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDateTime($post['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="posts.php?action=edit&id=<?php echo $post['id']; ?>" class="btn btn-outline-primary">编辑</a>
                                                    <a href="posts.php?action=delete&id=<?php echo $post['id']; ?>" class="btn btn-outline-danger confirm-delete">删除</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">没有找到回复</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (isset($total_pages) && $total_pages > 1): ?>
                        <div class="mt-4">
                            <?php
                            // 构建分页URL
                            $pagination_url = 'posts.php?';
                            if (!empty($search)) {
                                $pagination_url .= 'search=' . urlencode($search) . '&';
                            }
                            if ($topic_id > 0) {
                                $pagination_url .= 'topic_id=' . $topic_id . '&';
                            }
                            if ($user_id > 0) {
                                $pagination_url .= 'user_id=' . $user_id . '&';
                            }
                            if (!empty($status)) {
                                $pagination_url .= 'status=' . urlencode($status) . '&';
                            }
                            $pagination_url .= 'page=%d';
                            
                            echo generateAdminPagination($page, $total_pages, $pagination_url);
                            ?>
                        </div>
                    <?php endif; ?>
                </main>
            </div>
        </div>
        
        <?php
        // 加载页面底部
        include __DIR__ . '/templates/admin_footer.php';
        break;
}
?>

