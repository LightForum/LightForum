<?php
/**
 * 友链管理页面
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

// 获取操作类型
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// 处理操作
$error = '';
$success = '';

// 获取数据库实例
$db = Database::getInstance();
$prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';

// 处理友链操作
switch ($action) {
    case 'add':
        // 添加友链
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $url = $_POST['url'] ?? '';
            $description = $_POST['description'] ?? '';
            $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
            $status = isset($_POST['status']) ? 1 : 0;
            
            // 验证输入
            if (empty($name) || empty($url)) {
                $error = '友链名称和URL不能为空';
            } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                $error = '请输入有效的URL地址';
            } else {
                try {
                    // 检查友链名称是否已存在
                    $exists = $db->fetchColumn(
                        "SELECT COUNT(*) FROM `{$prefix}links` WHERE `name` = :name",
                        ['name' => $name]
                    );
                    
                    if ($exists > 0) {
                        $error = '友链名称已存在';
                    } else {
                        // 创建友链
                        $db->insert("{$prefix}links", [
                            'name' => $name,
                            'url' => $url,
                            'description' => $description,
                            'sort_order' => $sort_order,
                            'status' => $status,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                        
                        // 记录操作日志
                        logAdminAction('add_link', 'link', $db->lastInsertId());
                        
                        $success = '友链添加成功';
                        
                        // 重定向到友链列表
                        header('Location: links.php?success=' . urlencode($success));
                        exit;
                    }
                } catch (Exception $e) {
                    $error = '添加友链失败: ' . $e->getMessage();
                }
            }
        }
        
        // 设置页面标题
        $page_title = '添加友链';
        
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
                        <h1 class="h2">添加友链</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <a href="links.php" class="btn btn-sm btn-outline-secondary">返回友链列表</a>
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
                            <form method="post" action="links.php?action=add">
                                <div class="mb-3">
                                    <label for="name" class="form-label">友链名称</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="url" class="form-label">友链URL</label>
                                    <input type="url" class="form-control" id="url" name="url" value="<?php echo isset($_POST['url']) ? htmlspecialchars($_POST['url']) : ''; ?>" required>
                                    <div class="form-text">请输入完整的URL，包括http://或https://</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">友链描述</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">排序顺序</label>
                                    <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0; ?>">
                                    <div class="form-text">数字越小排序越靠前</div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="status" name="status" checked>
                                        <label class="form-check-label" for="status">
                                            启用友链
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">添加友链</button>
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
        // 编辑友链
        $link_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($link_id <= 0) {
            header('Location: links.php');
            exit;
        }
        
        // 获取友链信息
        try {
            $link = $db->fetch(
                "SELECT * FROM `{$prefix}links` WHERE `id` = :id",
                ['id' => $link_id]
            );
            
            if (!$link) {
                header('Location: links.php');
                exit;
            }
        } catch (Exception $e) {
            $error = '获取友链信息失败: ' . $e->getMessage();
        }
        
        // 处理表单提交
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $url = $_POST['url'] ?? '';
            $description = $_POST['description'] ?? '';
            $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
            $status = isset($_POST['status']) ? 1 : 0;
            
            // 验证输入
            if (empty($name) || empty($url)) {
                $error = '友链名称和URL不能为空';
            } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                $error = '请输入有效的URL地址';
            } else {
                try {
                    // 检查友链名称是否已存在（排除当前ID）
                    $exists = $db->fetchColumn(
                        "SELECT COUNT(*) FROM `{$prefix}links` WHERE `name` = :name AND `id` != :id",
                        ['name' => $name, 'id' => $link_id]
                    );
                    
                    if ($exists > 0) {
                        $error = '友链名称已存在';
                    } else {
                        // 更新友链信息
                        $db->update("{$prefix}links", [
                            'name' => $name,
                            'url' => $url,
                            'description' => $description,
                            'sort_order' => $sort_order,
                            'status' => $status,
                            'updated_at' => date('Y-m-d H:i:s')
                        ], '`id` = :id', ['id' => $link_id]);
                        
                        // 记录操作日志
                        logAdminAction('edit_link', 'link', $link_id);
                        
                        $success = '友链信息更新成功';
                    }
                } catch (Exception $e) {
                    $error = '更新友链信息失败: ' . $e->getMessage();
                }
            }
        }
        
        // 设置页面标题
        $page_title = '编辑友链';
        
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
                        <h1 class="h2">编辑友链</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <a href="links.php" class="btn btn-sm btn-outline-secondary">返回友链列表</a>
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
                            <form method="post" action="links.php?action=edit&id=<?php echo $link_id; ?>">
                                <div class="mb-3">
                                    <label for="name" class="form-label">友链名称</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($link['name']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="url" class="form-label">友链URL</label>
                                    <input type="url" class="form-control" id="url" name="url" value="<?php echo htmlspecialchars($link['url']); ?>" required>
                                    <div class="form-text">请输入完整的URL，包括http://或https://</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">友链描述</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($link['description']); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">排序顺序</label>
                                    <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo (int)$link['sort_order']; ?>">
                                    <div class="form-text">数字越小排序越靠前</div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="status" name="status" <?php echo $link['status'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="status">
                                            启用友链
                                        </label>
                                    </div>
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
        // 删除友链
        $link_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($link_id <= 0) {
            header('Location: links.php');
            exit;
        }
        
        try {
            // 获取友链信息
            $link = $db->fetch(
                "SELECT * FROM `{$prefix}links` WHERE `id` = :id",
                ['id' => $link_id]
            );
            
            if (!$link) {
                header('Location: links.php');
                exit;
            }
            
            // 删除友链
            $db->delete("{$prefix}links", '`id` = :id', ['id' => $link_id]);
            
            // 记录操作日志
            logAdminAction('delete_link', 'link', $link_id, ['name' => $link['name']]);
            
            header('Location: links.php?success=' . urlencode('友链删除成功'));
            exit;
        } catch (Exception $e) {
            header('Location: links.php?error=' . urlencode('删除友链失败: ' . $e->getMessage()));
            exit;
        }
        break;
        
    default:
        // 友链列表
        try {
            // 获取友链列表
            $links = $db->fetchAll(
                "SELECT * FROM `{$prefix}links` ORDER BY `sort_order` ASC, `id` ASC"
            );
        } catch (Exception $e) {
            $error = '获取友链列表失败: ' . $e->getMessage();
        }
        
        // 获取成功或错误消息
        if (isset($_GET['success'])) {
            $success = $_GET['success'];
        }
        
        if (isset($_GET['error'])) {
            $error = $_GET['error'];
        }
        
        // 设置页面标题
        $page_title = '友链管理';
        
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
                        <h1 class="h2">友链管理</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <a href="links.php?action=add" class="btn btn-sm btn-outline-secondary">添加友链</a>
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
                                    <th>名称</th>
                                    <th>URL</th>
                                    <th>描述</th>
                                    <th>排序</th>
                                    <th>状态</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($links) && count($links) > 0): ?>
                                    <?php foreach ($links as $link): ?>
                                        <tr>
                                            <td><?php echo $link['id']; ?></td>
                                            <td><?php echo htmlspecialchars($link['name']); ?></td>
                                            <td><a href="<?php echo $link['url']; ?>" target="_blank"><?php echo htmlspecialchars($link['url']); ?></a></td>
                                            <td><?php echo htmlspecialchars(mb_substr($link['description'], 0, 50)) . (mb_strlen($link['description']) > 50 ? '...' : ''); ?></td>
                                            <td><?php echo $link['sort_order']; ?></td>
                                            <td>
                                                <?php if ($link['status']): ?>
                                                    <span class="badge badge-success">启用</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">禁用</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDateTime($link['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="links.php?action=edit&id=<?php echo $link['id']; ?>" class="btn btn-outline-primary">编辑</a>
                                                    <a href="links.php?action=delete&id=<?php echo $link['id']; ?>" class="btn btn-outline-danger confirm-delete">删除</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">没有找到友链</td>
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

<script>
// 确认删除提示
$(document).ready(function() {
    $('.confirm-delete').click(function(e) {
        if (!confirm('确定要删除这个友链吗？')) {
            e.preventDefault();
        }
    });
});
</script>