<?php
/**
 * 用户管理页面
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

// 处理用户操作
switch ($action) {
    case 'add':
        // 添加用户
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';
            $status = $_POST['status'] ?? 'active';
            
            // 验证输入
            if (empty($username) || empty($email) || empty($password)) {
                $error = '请填写所有必填字段';
            } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = '邮箱格式不正确';
            } else if (strlen($password) < 6) {
                $error = '密码长度必须至少为6个字符';
            } else {
                try {
                    // 检查用户名是否已存在
                    $exists = $db->fetchColumn(
                        "SELECT COUNT(*) FROM `{$prefix}users` WHERE `username` = :username",
                        ['username' => $username]
                    );
                    
                    if ($exists > 0) {
                        $error = '用户名已存在';
                    } else {
                        // 检查邮箱是否已存在
                        $exists = $db->fetchColumn(
                            "SELECT COUNT(*) FROM `{$prefix}users` WHERE `email` = :email",
                            ['email' => $email]
                        );
                        
                        if ($exists > 0) {
                            $error = '邮箱已存在';
                        } else {
                            // 创建用户
                            $db->insert("{$prefix}users", [
                                'username' => $username,
                                'email' => $email,
                                'password' => password_hash($password, PASSWORD_DEFAULT),
                                'role' => $role,
                                'status' => $status,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
                            
                            // 记录操作日志
                            logAdminAction('add_user', 'user', $db->lastInsertId());
                            
                            $success = '用户添加成功';
                            
                            // 重定向到用户列表
                            header('Location: users.php?success=' . urlencode($success));
                            exit;
                        }
                    }
                } catch (Exception $e) {
                    $error = '添加用户失败: ' . $e->getMessage();
                }
            }
        }
        
        // 设置页面标题
        $page_title = '添加用户';
        
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
                        <h1 class="h2">添加用户</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <a href="users.php" class="btn btn-sm btn-outline-secondary">返回用户列表</a>
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
                            <form method="post" action="users.php?action=add">
                                <div class="mb-3">
                                    <label for="username" class="form-label">用户名</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">邮箱</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">密码</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="role" class="form-label">角色</label>
                                    <select class="form-select" id="role" name="role">
                                        <?php foreach (getUserRoles() as $key => $value): ?>
                                            <option value="<?php echo $key; ?>" <?php echo (isset($_POST['role']) && $_POST['role'] === $key) ? 'selected' : ''; ?>>
                                                <?php echo $value; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="status" class="form-label">状态</label>
                                    <select class="form-select" id="status" name="status">
                                        <?php foreach (getUserStatuses() as $key => $value): ?>
                                            <option value="<?php echo $key; ?>" <?php echo (isset($_POST['status']) && $_POST['status'] === $key) ? 'selected' : ''; ?>>
                                                <?php echo $value; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">添加用户</button>
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
        // 编辑用户
        $user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($user_id <= 0) {
            header('Location: users.php');
            exit;
        }
        
        // 获取用户信息
        try {
            $user = $db->fetch(
                "SELECT * FROM `{$prefix}users` WHERE `id` = :id",
                ['id' => $user_id]
            );
            
            if (!$user) {
                header('Location: users.php');
                exit;
            }
        } catch (Exception $e) {
            $error = '获取用户信息失败: ' . $e->getMessage();
        }
        
        // 处理表单提交
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';
            $status = $_POST['status'] ?? 'active';
            
            // 验证输入
            if (empty($username) || empty($email)) {
                $error = '请填写所有必填字段';
            } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = '邮箱格式不正确';
            } else if (!empty($password) && strlen($password) < 6) {
                $error = '密码长度必须至少为6个字符';
            } else {
                try {
                    // 检查用户名是否已存在
                    $exists = $db->fetchColumn(
                        "SELECT COUNT(*) FROM `{$prefix}users` WHERE `username` = :username AND `id` != :id",
                        ['username' => $username, 'id' => $user_id]
                    );
                    
                    if ($exists > 0) {
                        $error = '用户名已存在';
                    } else {
                        // 检查邮箱是否已存在
                        $exists = $db->fetchColumn(
                            "SELECT COUNT(*) FROM `{$prefix}users` WHERE `email` = :email AND `id` != :id",
                            ['email' => $email, 'id' => $user_id]
                        );
                        
                        if ($exists > 0) {
                            $error = '邮箱已存在';
                        } else {
                            // 更新用户信息
                            $data = [
                                'username' => $username,
                                'email' => $email,
                                'role' => $role,
                                'status' => $status,
                                'updated_at' => date('Y-m-d H:i:s')
                            ];
                            
                            // 如果提供了新密码，则更新密码
                            if (!empty($password)) {
                                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
                            }
                            
                            $db->update("{$prefix}users", $data, '`id` = :id', ['id' => $user_id]);
                            
                            // 记录操作日志
                            logAdminAction('edit_user', 'user', $user_id);
                            
                            $success = '用户信息更新成功';
                            
                            // 重新获取用户信息
                            $user = $db->fetch(
                                "SELECT * FROM `{$prefix}users` WHERE `id` = :id",
                                ['id' => $user_id]
                            );
                        }
                    }
                } catch (Exception $e) {
                    $error = '更新用户信息失败: ' . $e->getMessage();
                }
            }
        }
        
        // 设置页面标题
        $page_title = '编辑用户';
        
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
                        <h1 class="h2">编辑用户</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <a href="users.php" class="btn btn-sm btn-outline-secondary">返回用户列表</a>
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
                            <form method="post" action="users.php?action=edit&id=<?php echo $user_id; ?>">
                                <div class="mb-3">
                                    <label for="username" class="form-label">用户名</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">邮箱</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">密码</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="留空表示不修改密码">
                                    <div class="form-text">如果不需要修改密码，请留空</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="role" class="form-label">角色</label>
                                    <select class="form-select" id="role" name="role">
                                        <?php foreach (getUserRoles() as $key => $value): ?>
                                            <option value="<?php echo $key; ?>" <?php echo $user['role'] === $key ? 'selected' : ''; ?>>
                                                <?php echo $value; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="status" class="form-label">状态</label>
                                    <select class="form-select" id="status" name="status">
                                        <?php foreach (getUserStatuses() as $key => $value): ?>
                                            <option value="<?php echo $key; ?>" <?php echo $user['status'] === $key ? 'selected' : ''; ?>>
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
        // 删除用户
        $user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($user_id <= 0) {
            header('Location: users.php');
            exit;
        }
        
        // 不能删除自己
        if ($user_id == $_SESSION['user_id']) {
            header('Location: users.php?error=' . urlencode('不能删除当前登录的用户'));
            exit;
        }
        
        try {
            // 获取用户信息
            $user = $db->fetch(
                "SELECT * FROM `{$prefix}users` WHERE `id` = :id",
                ['id' => $user_id]
            );
            
            if (!$user) {
                header('Location: users.php');
                exit;
            }
            
            // 删除用户
            $db->delete("{$prefix}users", '`id` = :id', ['id' => $user_id]);
            
            // 记录操作日志
            logAdminAction('delete_user', 'user', $user_id, ['username' => $user['username']]);
            
            header('Location: users.php?success=' . urlencode('用户删除成功'));
            exit;
        } catch (Exception $e) {
            header('Location: users.php?error=' . urlencode('删除用户失败: ' . $e->getMessage()));
            exit;
        }
        break;
        
    default:
        // 用户列表
        // 获取页码
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }
        
        // 每页显示的用户数
        $users_per_page = 20;
        
        // 获取搜索参数
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $role = isset($_GET['role']) ? $_GET['role'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        try {
            // 构建查询条件
            $conditions = [];
            $params = [];
            
            if (!empty($search)) {
                $conditions[] = '(`username` LIKE :search OR `email` LIKE :search)';
                $params['search'] = '%' . $search . '%';
            }
            
            if (!empty($role)) {
                $conditions[] = '`role` = :role';
                $params['role'] = $role;
            }
            
            if (!empty($status)) {
                $conditions[] = '`status` = :status';
                $params['status'] = $status;
            }
            
            $where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // 获取用户总数
            $total_users = $db->fetchColumn(
                "SELECT COUNT(*) FROM `{$prefix}users` {$where_clause}",
                $params
            );
            
            // 计算总页数
            $total_pages = ceil($total_users / $users_per_page);
            
            // 确保页码不超过总页数
            if ($page > $total_pages && $total_pages > 0) {
                $page = $total_pages;
            }
            
            // 计算偏移量
            $offset = ($page - 1) * $users_per_page;
            
            // 获取用户列表
            $users = $db->fetchAll(
                "SELECT * FROM `{$prefix}users` {$where_clause} ORDER BY `id` DESC LIMIT :offset, :limit",
                array_merge($params, ['offset' => $offset, 'limit' => $users_per_page])
            );
        } catch (Exception $e) {
            $error = '获取用户列表失败: ' . $e->getMessage();
        }
        
        // 获取成功或错误消息
        if (isset($_GET['success'])) {
            $success = $_GET['success'];
        }
        
        if (isset($_GET['error'])) {
            $error = $_GET['error'];
        }
        
        // 设置页面标题
        $page_title = '用户管理';
        
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
                        <h1 class="h2">用户管理</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <a href="users.php?action=add" class="btn btn-sm btn-outline-secondary">添加用户</a>
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
                        <div class="card-body">
                            <form method="get" action="users.php" class="row g-3">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="search" placeholder="搜索用户名或邮箱" value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                
                                <div class="col-md-3">
                                    <select class="form-select" name="role">
                                        <option value="">所有角色</option>
                                        <?php foreach (getUserRoles() as $key => $value): ?>
                                            <option value="<?php echo $key; ?>" <?php echo $role === $key ? 'selected' : ''; ?>>
                                                <?php echo $value; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <select class="form-select" name="status">
                                        <option value="">所有状态</option>
                                        <?php foreach (getUserStatuses() as $key => $value): ?>
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
                                    <th>用户名</th>
                                    <th>邮箱</th>
                                    <th>角色</th>
                                    <th>状态</th>
                                    <th>注册时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($users) && count($users) > 0): ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <?php if ($user['role'] === 'admin'): ?>
                                                    <span class="badge bg-danger">管理员</span>
                                                <?php elseif ($user['role'] === 'moderator'): ?>
                                                    <span class="badge bg-warning text-dark">版主</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">普通用户</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($user['status'] === 'active'): ?>
                                                    <span class="badge bg-success">正常</span>
                                                <?php elseif ($user['status'] === 'banned'): ?>
                                                    <span class="badge bg-danger">禁用</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">待验证</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDateTime($user['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-outline-primary">编辑</a>
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-outline-danger confirm-delete">删除</a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">没有找到用户</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (isset($total_pages) && $total_pages > 1): ?>
                        <div class="mt-4">
                            <?php
                            // 构建分页URL
                            $pagination_url = 'users.php?';
                            if (!empty($search)) {
                                $pagination_url .= 'search=' . urlencode($search) . '&';
                            }
                            if (!empty($role)) {
                                $pagination_url .= 'role=' . urlencode($role) . '&';
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

