<?php
/**
 * 备份恢复页面
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

// 备份目录
$backup_dir = __DIR__ . '/../backups';

// 确保备份目录存在
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// 处理备份恢复操作
switch ($action) {
    case 'create':
        // 创建备份
        try {
            // 获取所有表
            $tables = $db->fetchAll("SHOW TABLES LIKE '{$prefix}%'");
            
            if (empty($tables)) {
                throw new Exception('没有找到需要备份的表');
            }
            
            // 创建备份文件名
            $backup_file = $backup_dir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
            
            // 打开备份文件
            $fp = fopen($backup_file, 'w');
            
            if (!$fp) {
                throw new Exception('无法创建备份文件');
            }
            
            // 写入备份文件头
            fwrite($fp, "-- PHP轻论坛数据库备份\n");
            fwrite($fp, "-- 创建时间: " . date('Y-m-d H:i:s') . "\n");
            fwrite($fp, "-- 版本: " . getSetting('forum_version', '1.0.0') . "\n");
            fwrite($fp, "-- -------------------------------------------------------\n\n");
            
            // 备份每个表
            foreach ($tables as $table) {
                $table_name = reset($table);
                
                // 写入表结构
                fwrite($fp, "-- 表结构: `{$table_name}`\n");
                
                $create_table = $db->fetch("SHOW CREATE TABLE `{$table_name}`");
                fwrite($fp, $create_table['Create Table'] . ";\n\n");
                
                // 获取表数据
                $rows = $db->fetchAll("SELECT * FROM `{$table_name}`");
                
                if (!empty($rows)) {
                    // 写入表数据
                    fwrite($fp, "-- 表数据: `{$table_name}`\n");
                    
                    foreach ($rows as $row) {
                        $columns = array_keys($row);
                        $values = array_values($row);
                        
                        // 转义值
                        foreach ($values as &$value) {
                            if ($value === null) {
                                $value = 'NULL';
                            } else {
                                $value = "'" . addslashes($value) . "'";
                            }
                        }
                        
                        fwrite($fp, "INSERT INTO `{$table_name}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n");
                    }
                    
                    fwrite($fp, "\n");
                }
            }
            
            // 关闭备份文件
            fclose($fp);
            
            // 记录操作日志
            logAdminAction('create_backup', 'system', 0, ['file' => basename($backup_file)]);
            
            $success = '数据库备份创建成功';
        } catch (Exception $e) {
            $error = '创建备份失败: ' . $e->getMessage();
        }
        
        // 重定向到备份列表
        header('Location: backup.php' . ($success ? '?success=' . urlencode($success) : ($error ? '?error=' . urlencode($error) : '')));
        exit;
        break;
        
    case 'restore':
        // 恢复备份
        $file = isset($_GET['file']) ? $_GET['file'] : '';
        
        if (empty($file)) {
            header('Location: backup.php?error=' . urlencode('未指定备份文件'));
            exit;
        }
        
        // 安全检查：确保文件名只包含允许的字符
        if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $file)) {
            header('Location: backup.php?error=' . urlencode('无效的备份文件名'));
            exit;
        }
        
        $backup_file = $backup_dir . '/' . $file;
        
        if (!file_exists($backup_file)) {
            header('Location: backup.php?error=' . urlencode('备份文件不存在'));
            exit;
        }
        
        try {
            // 读取备份文件
            $sql = file_get_contents($backup_file);
            
            if (empty($sql)) {
                throw new Exception('备份文件为空');
            }
            
            // 分割SQL语句
            $queries = explode(';', $sql);
            
            // 开始事务
            $db->beginTransaction();
            
            // 执行每个SQL语句
            foreach ($queries as $query) {
                $query = trim($query);
                
                if (!empty($query)) {
                    $db->execute($query);
                }
            }
            
            // 提交事务
            $db->commit();
            
            // 记录操作日志
            logAdminAction('restore_backup', 'system', 0, ['file' => $file]);
            
            $success = '数据库备份恢复成功';
        } catch (Exception $e) {
            // 回滚事务
            $db->rollBack();
            
            $error = '恢复备份失败: ' . $e->getMessage();
        }
        
        // 重定向到备份列表
        header('Location: backup.php' . ($success ? '?success=' . urlencode($success) : ($error ? '?error=' . urlencode($error) : '')));
        exit;
        break;
        
    case 'download':
        // 下载备份
        $file = isset($_GET['file']) ? $_GET['file'] : '';
        
        if (empty($file)) {
            header('Location: backup.php?error=' . urlencode('未指定备份文件'));
            exit;
        }
        
        // 安全检查：确保文件名只包含允许的字符
        if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $file)) {
            header('Location: backup.php?error=' . urlencode('无效的备份文件名'));
            exit;
        }
        
        $backup_file = $backup_dir . '/' . $file;
        
        if (!file_exists($backup_file)) {
            header('Location: backup.php?error=' . urlencode('备份文件不存在'));
            exit;
        }
        
        // 记录操作日志
        logAdminAction('download_backup', 'system', 0, ['file' => $file]);
        
        // 设置下载头
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($backup_file));
        
        // 输出文件内容
        readfile($backup_file);
        exit;
        break;
        
    case 'delete':
        // 删除备份
        $file = isset($_GET['file']) ? $_GET['file'] : '';
        
        if (empty($file)) {
            header('Location: backup.php?error=' . urlencode('未指定备份文件'));
            exit;
        }
        
        // 安全检查：确保文件名只包含允许的字符
        if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $file)) {
            header('Location: backup.php?error=' . urlencode('无效的备份文件名'));
            exit;
        }
        
        $backup_file = $backup_dir . '/' . $file;
        
        if (!file_exists($backup_file)) {
            header('Location: backup.php?error=' . urlencode('备份文件不存在'));
            exit;
        }
        
        try {
            // 删除备份文件
            if (!unlink($backup_file)) {
                throw new Exception('无法删除备份文件');
            }
            
            // 记录操作日志
            logAdminAction('delete_backup', 'system', 0, ['file' => $file]);
            
            $success = '备份文件删除成功';
        } catch (Exception $e) {
            $error = '删除备份文件失败: ' . $e->getMessage();
        }
        
        // 重定向到备份列表
        header('Location: backup.php' . ($success ? '?success=' . urlencode($success) : ($error ? '?error=' . urlencode($error) : '')));
        exit;
        break;
        
    default:
        // 备份列表
        try {
            // 获取备份文件列表
            $backup_files = [];
            
            if (is_dir($backup_dir)) {
                $files = scandir($backup_dir);
                
                foreach ($files as $file) {
                    if (preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $file)) {
                        $backup_files[] = [
                            'name' => $file,
                            'size' => filesize($backup_dir . '/' . $file),
                            'time' => filemtime($backup_dir . '/' . $file)
                        ];
                    }
                }
                
                // 按时间倒序排序
                usort($backup_files, function($a, $b) {
                    return $b['time'] - $a['time'];
                });
            }
        } catch (Exception $e) {
            $error = '获取备份文件列表失败: ' . $e->getMessage();
        }
        
        // 获取成功或错误消息
        if (isset($_GET['success'])) {
            $success = $_GET['success'];
        }
        
        if (isset($_GET['error'])) {
            $error = $_GET['error'];
        }
        
        // 设置页面标题
        $page_title = '备份恢复';
        
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
                        <h1 class="h2">备份恢复</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <a href="backup.php?action=create" class="btn btn-sm btn-outline-primary">创建备份</a>
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
                        <div class="card-header">
                            <h5>备份文件列表</h5>
                        </div>
                        <div class="card-body">
                            <?php if (isset($backup_files) && count($backup_files) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>文件名</th>
                                                <th>大小</th>
                                                <th>创建时间</th>
                                                <th>操作</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($backup_files as $file): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($file['name']); ?></td>
                                                    <td><?php echo formatFileSize($file['size']); ?></td>
                                                    <td><?php echo date('Y-m-d H:i:s', $file['time']); ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="backup.php?action=download&file=<?php echo urlencode($file['name']); ?>" class="btn btn-outline-primary">下载</a>
                                                            <a href="backup.php?action=restore&file=<?php echo urlencode($file['name']); ?>" class="btn btn-outline-warning confirm-action" data-confirm-message="确定要恢复此备份吗？当前数据将被覆盖！">恢复</a>
                                                            <a href="backup.php?action=delete&file=<?php echo urlencode($file['name']); ?>" class="btn btn-outline-danger confirm-action" data-confirm-message="确定要删除此备份吗？此操作不可恢复！">删除</a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">没有找到备份文件</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>备份说明</h5>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>备份文件包含数据库中所有表的结构和数据</li>
                                <li>恢复备份将覆盖当前数据库中的所有数据，请谨慎操作</li>
                                <li>建议定期创建备份，以防数据丢失</li>
                                <li>备份文件保存在 <code>backups</code> 目录下</li>
                            </ul>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        
        <?php
        // 加载页面底部
        include __DIR__ . '/templates/admin_footer.php';
        break;
}

/**
 * 格式化文件大小
 *
 * @param int $size 文件大小（字节）
 * @return string 格式化后的文件大小
 */
function formatFileSize($size) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    
    return round($size, 2) . ' ' . $units[$i];
}
?>

