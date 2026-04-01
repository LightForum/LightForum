<?php
/**
 * 分类管理页面
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

// 处理分类操作
switch ($action) {
    case 'add':
        // 添加分类
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
            
            // 验证输入
            if (empty($title)) {
                $error = '分类标题不能为空';
            } else {
                try {
                    // 检查分类标题是否已存在
                    $exists = $db->fetchColumn(
                        "SELECT COUNT(*) FROM `{$prefix}categories` WHERE `title` = :title",
                        ['title' => $title]
                    );
                    
                    if ($exists > 0) {
                        $error = '分类标题已存在';
                    } else {
                        // 创建分类
                        $db->insert("{$prefix}categories", [
                            'title' => $title,
                            'description' => $description,
                            'sort_order' => $sort_order,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                        
                        // 记录操作日志
                        logAdminAction('add_category', 'category', $db->lastInsertId());
                        
                        $success = '分类添加成功';
                        
                        // 重定向到分类列表
                        header('Location: categories.php?success=' . urlencode($success));
                        exit;
                    }
                } catch (Exception $e) {
                    $error = '添加分类失败: ' . $e->getMessage();
                }
            }
        }
        
        // 设置页面标题
        $page_title = '添加分类';
        
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
                        <h1 class="h2">添加分类</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <a href="categories.php" class="btn btn-sm btn-outline-secondary">返回分类列表</a>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-body">
                            <form method="post" action="categories.php?action=add">
                                <div class="mb-3">
                                    <label for="title" class="form-label">分类标题</label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">分类描述</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">排序顺序</label>
                                    <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0; ?>">
                                    <div class="form-text">数字越小排序越靠前</div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">添加分类</button>
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
        
    case 'edit':
        // 编辑分类
        $category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($category_id <= 0) {
            header('Location: categories.php');
            exit;
        }
        
        // 获取分类信息
        try {
            $category = $db->fetch(
                "SELECT * FROM `{$prefix}categories` WHERE `id` = :id",
                ['id' => $category_id]
            );
            
            if (!$category) {
                header('Location: categories.php');
                exit;
            }
        } catch (Exception $e) {
            $error = '获取分类信息失败: ' . $e->getMessage();
        }
        
        // 处理表单提交
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
            
            // 验证输入
            if (empty($title)) {
                $error = '分类标题不能为空';
            } else {
                try {
                    // 检查分类标题是否已存在
                    $exists = $db->fetchColumn(
                        "SELECT COUNT(*) FROM `{$prefix}categories` WHERE `title` = :title AND `id` != :id",
                        ['title' => $title, 'id' => $category_id]
                    );
                    
                    if ($exists > 0) {
                        $error = '分类标题已存在';
                    } else {
                        // 更新分类信息
                        $db->update("{$prefix}categories", [
                            'title' => $title,
                            'description' => $description,
                            'sort_order' => $sort_order,
                            'updated_at' => date('Y-m-d H:i:s')
                        ], '`id` = :id', ['id' => $category_id]);
                        
                        // 记录操作日志
                        logAdminAction('edit_category', 'category', $category_id);
                        
                        $success = '分类信息更新成功';
                        
                        // 重新获取分类信息
                        $category = $db->fetch(
                            "SELECT * FROM `{$prefix}categories` WHERE `id` = :id",
                            ['id' => $category_id]
                        );
                    }
                } catch (Exception $e) {
                    $error = '更新分类信息失败: ' . $e->getMessage();
                }
            }
        }
        
        // 设置页面标题
        $page_title = '编辑分类';
        
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
                        <h1 class="h2">编辑分类</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <a href="categories.php" class="btn btn-sm btn-outline-secondary">返回分类列表</a>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-body">
                            <form method="post" action="categories.php?action=edit&id=<?php echo $category_id; ?>">
                                <div class="mb-3">
                                    <label for="title" class="form-label">分类标题</label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($category['title']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">分类描述</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($category['description']); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">排序顺序</label>
                                    <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo (int)$category['sort_order']; ?>">
                                    <div class="form-text">数字越小排序越靠前</div>
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
        // 删除分类
        $category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($category_id <= 0) {
            header('Location: categories.php');
            exit;
        }
        
        try {
            // 获取分类信息
            $category = $db->fetch(
                "SELECT * FROM `{$prefix}categories` WHERE `id` = :id",
                ['id' => $category_id]
            );
            
            if (!$category) {
                header('Location: categories.php');
                exit;
            }
            
            // 检查分类是否有主题
            $topic_count = $db->fetchColumn(
                "SELECT COUNT(*) FROM `{$prefix}topics` WHERE `category_id` = :category_id",
                ['category_id' => $category_id]
            );
            
            if ($topic_count > 0) {
                header('Location: categories.php?error=' . urlencode('无法删除分类，该分类下还有主题'));
                exit;
            }
            
            // 删除分类
            $db->delete("{$prefix}categories", '`id` = :id', ['id' => $category_id]);
            
            // 记录操作日志
            logAdminAction('delete_category', 'category', $category_id, ['title' => $category['title']]);
            
            header('Location: categories.php?success=' . urlencode('分类删除成功'));
            exit;
        } catch (Exception $e) {
            header('Location: categories.php?error=' . urlencode('删除分类失败: ' . $e->getMessage()));
            exit;
        }
        break;
        
    default:
        // 分类列表
        try {
            // 获取分类列表
            $categories = $db->fetchAll(
                "SELECT c.*, 
                (SELECT COUNT(*) FROM `{$prefix}topics` WHERE `category_id` = c.id) as topic_count 
                FROM `{$prefix}categories` c 
                ORDER BY c.sort_order ASC, c.id ASC"
            );
        } catch (Exception $e) {
            $error = '获取分类列表失败: ' . $e->getMessage();
        }
        
        // 获取成功或错误消息
        if (isset($_GET['success'])) {
            $success = $_GET['success'];
        }
        
        if (isset($_GET['error'])) {
            $error = $_GET['error'];
        }
        
        // 设置页面标题
        $page_title = '分类管理';
        
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
                        <h1 class="h2">分类管理</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <a href="categories.php?action=add" class="btn btn-sm btn-outline-secondary">添加分类</a>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>标题</th>
                                    <th>描述</th>
                                    <th>主题数</th>
                                    <th>排序</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($categories) && count($categories) > 0): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?php echo $category['id']; ?></td>
                                            <td><?php echo htmlspecialchars($category['title']); ?></td>
                                            <td><?php echo htmlspecialchars(mb_substr($category['description'], 0, 50)) . (mb_strlen($category['description']) > 50 ? '...' : ''); ?></td>
                                            <td><?php echo $category['topic_count']; ?></td>
                                            <td><?php echo $category['sort_order']; ?></td>
                                            <td><?php echo formatDateTime($category['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="categories.php?action=edit&id=<?php echo $category['id']; ?>" class="btn btn-outline-primary">编辑</a>
                                                    <?php if ($category['topic_count'] == 0): ?>
                                                        <a href="categories.php?action=delete&id=<?php echo $category['id']; ?>" class="btn btn-outline-danger confirm-delete">删除</a>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-outline-danger" disabled title="无法删除，该分类下还有主题">删除</button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">没有找到分类</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </main>
            </div>
        </div>
        
        <?php
        // 加载页面底部
        include __DIR__ . '/templates/admin_footer.php';
        break;
}
?>

