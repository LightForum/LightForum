<?php
/**
 * 系统日志页面
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

// 处理日志操作
switch ($action) {
    case 'clear':
        // 清空日志
        try {
            // 清空日志表
            $db->execute("TRUNCATE TABLE `{$prefix}logs`");
            
            // 记录操作日志
            logAdminAction('clear_logs', 'system', 0);
            
            header('Location: logs.php?success=' . urlencode('系统日志已清空'));
            exit;
        } catch (Exception $e) {
            header('Location: logs.php?error=' . urlencode('清空系统日志失败: ' . $e->getMessage()));
            exit;
        }
        break;
        
    default:
        // 日志列表
        // 获取页码
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }
        
        // 每页显示的日志数
        $logs_per_page = 50;
        
        // 获取搜索参数
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
        
        try {
            // 构建查询条件
            $conditions = [];
            $params = [];
            
            if (!empty($search)) {
                $conditions[] = '(l.action LIKE :search OR l.details LIKE :search)';
                $params['search'] = '%' . $search . '%';
            }
            
            if (!empty($type)) {
                $conditions[] = 'l.type = :type';
                $params['type'] = $type;
            }
            
            if ($user_id > 0) {
                $conditions[] = 'l.user_id = :user_id';
                $params['user_id'] = $user_id;
            }
            
            if (!empty($start_date)) {
                $conditions[] = 'l.created_at >= :start_date';
                $params['start_date'] = $start_date . ' 00:00:00';
            }
            
            if (!empty($end_date)) {
                $conditions[] = 'l.created_at <= :end_date';
                $params['end_date'] = $end_date . ' 23:59:59';
            }
            
            $where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // 获取日志总数
            $total_logs = $db->fetchColumn(
                "SELECT COUNT(*) FROM `{$prefix}logs` l {$where_clause}",
                $params
            );
            
            // 计算总页数
            $total_pages = ceil($total_logs / $logs_per_page);
            
            // 确保页码不超过总页数
            if ($page > $total_pages && $total_pages > 0) {
                $page = $total_pages;
            }
            
            // 计算偏移量
            $offset = ($page - 1) * $logs_per_page;
            
            // 获取日志列表
            $logs = $db->fetchAll(
                "SELECT l.*, u.username 
                FROM `{$prefix}logs` l 
                LEFT JOIN `{$prefix}users` u ON l.user_id = u.id 
                {$where_clause} 
                ORDER BY l.id DESC 
                LIMIT :offset, :limit",
                array_merge($params, ['offset' => $offset, 'limit' => $logs_per_page])
            );
            
            // 获取日志类型列表
            $log_types = $db->fetchAll(
                "SELECT DISTINCT `type` FROM `{$prefix}logs` ORDER BY `type` ASC"
            );
        } catch (Exception $e) {
            $error = '获取系统日志失败: ' . $e->getMessage();
        }
        
        // 获取成功或错误消息
        if (isset($_GET['success'])) {
            $success = $_GET['success'];
        }
        
        if (isset($_GET['error'])) {
            $error = $_GET['error'];
        }
        
        // 设置页面标题
        $page_title = '系统日志';
        
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
                        <h1 class="h2">系统日志</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <a href="logs.php?action=clear" class="btn btn-sm btn-outline-danger confirm-action" data-confirm-message="确定要清空所有系统日志吗？此操作不可恢复！">清空日志</a>
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
                            <form method="get" action="logs.php" class="row g-3">
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="search" placeholder="搜索操作或详情" value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <select class="form-select" name="type">
                                        <option value="">所有类型</option>
                                        <?php if (isset($log_types)): ?>
                                            <?php foreach ($log_types as $log_type): ?>
                                                <option value="<?php echo $log_type['type']; ?>" <?php echo $type === $log_type['type'] ? 'selected' : ''; ?>>
                                                    <?php echo $log_type['type']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <input type="number" class="form-control" name="user_id" placeholder="用户ID" value="<?php echo $user_id > 0 ? $user_id : ''; ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <input type="date" class="form-control" name="start_date" placeholder="开始日期" value="<?php echo $start_date; ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <input type="date" class="form-control" name="end_date" placeholder="结束日期" value="<?php echo $end_date; ?>">
                                </div>
                                
                                <div class="col-md-1">
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
                                    <th>类型</th>
                                    <th>操作</th>
                                    <th>目标ID</th>
                                    <th>用户</th>
                                    <th>IP地址</th>
                                    <th>详情</th>
                                    <th>时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($logs) && count($logs) > 0): ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?php echo $log['id']; ?></td>
                                            <td><?php echo htmlspecialchars($log['type']); ?></td>
                                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                                            <td><?php echo $log['target_id']; ?></td>
                                            <td>
                                                <?php if ($log['user_id'] > 0): ?>
                                                    <?php echo htmlspecialchars($log['username'] ?? '未知用户'); ?> (<?php echo $log['user_id']; ?>)
                                                <?php else: ?>
                                                    系统
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                            <td>
                                                <?php if (!empty($log['details'])): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-info view-details" data-bs-toggle="modal" data-bs-target="#detailsModal" data-details="<?php echo htmlspecialchars($log['details']); ?>">
                                                        查看详情
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">无</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDateTime($log['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">没有找到日志记录</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (isset($total_pages) && $total_pages > 1): ?>
                        <div class="mt-4">
                            <?php
                            // 构建分页URL
                            $pagination_url = 'logs.php?';
                            if (!empty($search)) {
                                $pagination_url .= 'search=' . urlencode($search) . '&';
                            }
                            if (!empty($type)) {
                                $pagination_url .= 'type=' . urlencode($type) . '&';
                            }
                            if ($user_id > 0) {
                                $pagination_url .= 'user_id=' . $user_id . '&';
                            }
                            if (!empty($start_date)) {
                                $pagination_url .= 'start_date=' . urlencode($start_date) . '&';
                            }
                            if (!empty($end_date)) {
                                $pagination_url .= 'end_date=' . urlencode($end_date) . '&';
                            }
                            $pagination_url .= 'page=%d';
                            
                            echo generateAdminPagination($page, $total_pages, $pagination_url);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- 详情模态框 -->
                    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="detailsModalLabel">日志详情</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <pre id="detailsContent" class="bg-light p-3" style="max-height: 400px; overflow-y: auto;"></pre>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        
        <script>
            // 详情模态框
            document.addEventListener('DOMContentLoaded', function() {
                const viewDetailsButtons = document.querySelectorAll('.view-details');
                viewDetailsButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const details = this.getAttribute('data-details');
                        try {
                            // 尝试解析JSON
                            const detailsObj = JSON.parse(details);
                            document.getElementById('detailsContent').textContent = JSON.stringify(detailsObj, null, 2);
                        } catch (e) {
                            // 如果不是JSON，直接显示
                            document.getElementById('detailsContent').textContent = details;
                        }
                    });
                });
            });
        </script>
        
        <?php
        // 加载页面底部
        include __DIR__ . '/templates/admin_footer.php';
        break;
}
?>

