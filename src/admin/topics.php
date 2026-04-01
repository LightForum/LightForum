<?php
/**
 * 主题管理页面
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

// 处理主题操作
switch ($action) {
    case 'edit':
        // 编辑主题
        $topic_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($topic_id <= 0) {
            header('Location: topics.php');
            exit;
        }
        
        // 获取主题信息
        try {
            $topic = $db->fetch(
                "SELECT t.*, u.username, c.title as category_title 
                FROM `{$prefix}topics` t 
                JOIN `{$prefix}users` u ON t.user_id = u.id 
                JOIN `{$prefix}categories` c ON t.category_id = c.id 
                WHERE t.id = :id",
                ['id' => $topic_id]
            );
            
            if (!$topic) {
                header('Location: topics.php');
                exit;
            }
            
            // 获取分类列表
            $categories = $db->fetchAll(
                "SELECT * FROM `{$prefix}categories` ORDER BY `sort_order` ASC, `id` ASC"
            );
        } catch (Exception $e) {
            $error = '获取主题信息失败: ' . $e->getMessage();
        }
        
        // 处理表单提交
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'] ?? '';
            $content = $_POST['content'] ?? '';
            $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
            $status = $_POST['status'] ?? 'published';
            
            // 验证输入
            if (empty($title) || empty($content) || $category_id <= 0) {
                $error = '请填写所有必填字段';
            } else {
                try {
                    // 检查分类是否存在
                    $category_exists = $db->fetchColumn(
                        "SELECT COUNT(*) FROM `{$prefix}categories` WHERE `id` = :id",
                        ['id' => $category_id]
                    );
                    
                    if ($category_exists == 0) {
                        $error = '所选分类不存在';
                    } else {
                        // 更新主题信息
                        $db->update("{$prefix}topics", [
                            'title' => $title,
                            'content' => $content,
                            'category_id' => $category_id,
                            'status' => $status,
                            'updated_at' => date('Y-m-d H:i:s')
                        ], '`id` = :id', ['id' => $topic_id]);
                        
                        // 记录操作日志
                        logAdminAction('edit_topic', 'topic', $topic_id);
                        
                        $success = '主题信息更新成功';
                        
                        // 重新获取主题信息
                        $topic = $db->fetch(
                            "SELECT t.*, u.username, c.title as category_title 
                            FROM `{$prefix}topics` t 
                            JOIN `{$prefix}users` u ON t.user_id = u.id 
                            JOIN `{$prefix}categories` c ON t.category_id = c.id 
                            WHERE t.id = :id",
                            ['id' => $topic_id]
                        );
                    }
                } catch (Exception $e) {
                    $error = '更新主题信息失败: ' . $e->getMessage();
                }
            }
        }
        
        // 设置页面标题
        $page_title = '编辑主题';
        
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
                        <h1 class="h2">编辑主题</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <a href="topics.php" class="btn btn-sm btn-outline-secondary">返回主题列表</a>
                                <a href="../topic.php?id=<?php echo $topic_id; ?>" class="btn btn-sm btn-outline-primary" target="_blank">查看主题</a>
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
                            <h5>主题信息</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <strong>ID:</strong>
                                </div>
                                <div class="col-md-9">
                                    <?php echo $topic['id']; ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <strong>作者:</strong>
                                </div>
                                <div class="col-md-9">
                                    <?php echo htmlspecialchars($topic['username']); ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <strong>创建时间:</strong>
                                </div>
                                <div class="col-md-9">
                                    <?php echo formatDateTime($topic['created_at']); ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <strong>最后更新:</strong>
                                </div>
                                <div class="col-md-9">
                                    <?php echo formatDateTime($topic['updated_at']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <form method="post" action="topics.php?action=edit&id=<?php echo $topic_id; ?>">
                                <div class="mb-3">
                                    <label for="title" class="form-label">主题标题</label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($topic['title']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">所属分类</label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">-- 请选择分类 --</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo $topic['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="content" class="form-label">主题内容</label>
                                    <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($topic['content']); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="status" class="form-label">状态</label>
                                    <select class="form-select" id="status" name="status">
                                        <?php foreach (getTopicStatuses() as $key => $value): ?>
                                            <option value="<?php echo $key; ?>" <?php echo $topic['status'] === $key ? 'selected' : ''; ?>>
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
        // 删除主题
        $topic_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($topic_id <= 0) {
            header('Location: topics.php');
            exit;
        }
        
        try {
            // 获取主题信息
            $topic = $db->fetch(
                "SELECT * FROM `{$prefix}topics` WHERE `id` = :id",
                ['id' => $topic_id]
            );
            
            if (!$topic) {
                header('Location: topics.php');
                exit;
            }
            
            // 删除主题相关的回复
            $db->delete("{$prefix}posts", '`topic_id` = :topic_id', ['topic_id' => $topic_id]);
            
            // 删除主题
            $db->delete("{$prefix}topics", '`id` = :id', ['id' => $topic_id]);
            
            // 记录操作日志
            logAdminAction('delete_topic', 'topic', $topic_id, ['title' => $topic['title']]);
            
            header('Location: topics.php?success=' . urlencode('主题删除成功'));
            exit;
        } catch (Exception $e) {
            header('Location: topics.php?error=' . urlencode('删除主题失败: ' . $e->getMessage()));
            exit;
        }
        break;
        
    default:
        // 主题列表
        // 获取页码
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }
        
        // 每页显示的主题数
        $topics_per_page = 20;
        
        // 获取搜索参数
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        try {
            // 获取分类列表
            $categories = $db->fetchAll(
                "SELECT * FROM `{$prefix}categories` ORDER BY `sort_order` ASC, `id` ASC"
            );
            
            // 构建查询条件
            $conditions = [];
            $params = [];
            
            if (!empty($search)) {
                $conditions[] = '(t.title LIKE :search OR t.content LIKE :search)';
                $params['search'] = '%' . $search . '%';
            }
            
            if ($category_id > 0) {
                $conditions[] = 't.category_id = :category_id';
                $params['category_id'] = $category_id;
            }
            
            if (!empty($status)) {
                $conditions[] = 't.status = :status';
                $params['status'] = $status;
            }
            
            $where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // 获取主题总数
            $total_topics = $db->fetchColumn(
                "SELECT COUNT(*) FROM `{$prefix}topics` t {$where_clause}",
                $params
            );
            
            // 计算总页数
            $total_pages = ceil($total_topics / $topics_per_page);
            
            // 确保页码不超过总页数
            if ($page > $total_pages && $total_pages > 0) {
                $page = $total_pages;
            }
            
            // 计算偏移量
            $offset = ($page - 1) * $topics_per_page;
            
            // 获取主题列表
            $topics = $db->fetchAll(
                "SELECT t.*, u.username, c.title as category_title,
                (SELECT COUNT(*) FROM `{$prefix}posts` WHERE `topic_id` = t.id) as reply_count
                FROM `{$prefix}topics` t 
                JOIN `{$prefix}users` u ON t.user_id = u.id 
                JOIN `{$prefix}categories` c ON t.category_id = c.id 
                {$where_clause} 
                ORDER BY t.id DESC 
                LIMIT :offset, :limit",
                array_merge($params, ['offset' => $offset, 'limit' => $topics_per_page])
            );
        } catch (Exception $e) {
            $error = '获取主题列表失败: ' . $e->getMessage();
        }
        
        // 获取成功或错误消息
        if (isset($_GET['success'])) {
            $success = $_GET['success'];
        }
        
        if (isset($_GET['error'])) {
            $error = $_GET['error'];
        }
        
        // 设置页面标题
        $page_title = '主题管理';
        
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
                        <h1 class="h2">主题管理</h1>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="get" action="topics.php" class="row g-3">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="search" placeholder="搜索主题标题或内容" value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                
                                <div class="col-md-3">
                                    <select class="form-select" name="category_id">
                                        <option value="0">所有分类</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <select class="form-select" name="status">
                                        <option value="">所有状态</option>
                                        <?php foreach (getTopicStatuses() as $key => $value): ?>
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
                                    <th>标题</th>
                                    <th>分类</th>
                                    <th>作者</th>
                                    <th>回复数</th>
                                    <th>状态</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($topics) && count($topics) > 0): ?>
                                    <?php foreach ($topics as $topic): ?>
                                        <tr>
                                            <td><?php echo $topic['id']; ?></td>
                                            <td>
                                                <a href="../topic.php?id=<?php echo $topic['id']; ?>" target="_blank">
                                                    <?php echo htmlspecialchars(mb_substr($topic['title'], 0, 30)) . (mb_strlen($topic['title']) > 30 ? '...' : ''); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($topic['category_title']); ?></td>
                                            <td><?php echo htmlspecialchars($topic['username']); ?></td>
                                            <td><?php echo $topic['reply_count']; ?></td>
                                            <td>
                                                <?php if ($topic['status'] === 'published'): ?>
                                                    <span class="badge bg-success">已发布</span>
                                                <?php elseif ($topic['status'] === 'draft'): ?>
                                                    <span class="badge bg-warning text-dark">草稿</span>
                                                <?php elseif ($topic['status'] === 'hidden'): ?>
                                                    <span class="badge bg-secondary">已隐藏</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">已删除</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDateTime($topic['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="topics.php?action=edit&id=<?php echo $topic['id']; ?>" class="btn btn-outline-primary">编辑</a>
                                                    <a href="topics.php?action=delete&id=<?php echo $topic['id']; ?>" class="btn btn-outline-danger confirm-delete">删除</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">没有找到主题</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (isset($total_pages) && $total_pages > 1): ?>
                        <div class="mt-4">
                            <?php
                            // 构建分页URL
                            $pagination_url = 'topics.php?';
                            if (!empty($search)) {
                                $pagination_url .= 'search=' . urlencode($search) . '&';
                            }
                            if ($category_id > 0) {
                                $pagination_url .= 'category_id=' . $category_id . '&';
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

