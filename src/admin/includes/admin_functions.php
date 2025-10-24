<?php
/**
 * 管理后台函数库
 */

/**
 * 检查管理员访问权限
 */
function checkAdminAccess() {
    // 检查是否已登录
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }
    
    // 检查是否是管理员
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Location: ../index.php');
        exit;
    }
}

/**
 * 生成管理后台分页
 */
function generateAdminPagination($current_page, $total_pages, $url_pattern) {
    $pagination = '<nav aria-label="分页导航"><ul class="pagination">';
    
    // 上一页
    if ($current_page > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . sprintf($url_pattern, $current_page - 1) . '">上一页</a></li>';
    } else {
        $pagination .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">上一页</a></li>';
    }
    
    // 页码
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    if ($start_page > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . sprintf($url_pattern, 1) . '">1</a></li>';
        if ($start_page > 2) {
            $pagination .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">...</a></li>';
        }
    }
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            $pagination .= '<li class="page-item active" aria-current="page"><a class="page-link" href="#">' . $i . '</a></li>';
        } else {
            $pagination .= '<li class="page-item"><a class="page-link" href="' . sprintf($url_pattern, $i) . '">' . $i . '</a></li>';
        }
    }
    
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            $pagination .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">...</a></li>';
        }
        $pagination .= '<li class="page-item"><a class="page-link" href="' . sprintf($url_pattern, $total_pages) . '">' . $total_pages . '</a></li>';
    }
    
    // 下一页
    if ($current_page < $total_pages) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . sprintf($url_pattern, $current_page + 1) . '">下一页</a></li>';
    } else {
        $pagination .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">下一页</a></li>';
    }
    
    $pagination .= '</ul></nav>';
    
    return $pagination;
}

/**
 * 记录管理操作日志
 */
function logAdminAction($action, $target_type = '', $target_id = 0, $details = []) {
    try {
        $db = Database::getInstance();
        $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
        
        $db->insert("{$prefix}admin_logs", [
            'user_id' => $_SESSION['user_id'],
            'action' => $action,
            'target_type' => $target_type,
            'target_id' => $target_id,
            'details' => !empty($details) ? json_encode($details) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return true;
    } catch (Exception $e) {
        // 记录日志失败，但不影响主要功能
        error_log('记录管理操作日志失败: ' . $e->getMessage());
        return false;
    }
}

/**
 * 获取用户角色列表
 */
function getUserRoles() {
    return [
        'user' => '普通用户',
        'moderator' => '版主',
        'admin' => '管理员'
    ];
}

/**
 * 获取用户状态列表
 */
function getUserStatuses() {
    return [
        'active' => '正常',
        'banned' => '禁用',
        'pending' => '待验证'
    ];
}

/**
 * 获取主题状态列表
 */
function getTopicStatuses() {
    return [
        'published' => '已发布',
        'draft' => '草稿',
        'hidden' => '已隐藏',
        'deleted' => '已删除'
    ];
}

/**
 * 获取回复状态列表
 */
function getPostStatuses() {
    return [
        'published' => '已发布',
        'hidden' => '已隐藏',
        'deleted' => '已删除'
    ];
}

/**
 * 清理缓存
 */
function clearCache() {
    // 清理系统缓存
    $cache_dir = __DIR__ . '/../../cache';
    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
    
    // 清理会话缓存
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    
    return true;
}

/**
 * 备份数据库
 */
function backupDatabase() {
    try {
        $db = Database::getInstance();
        $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
        
        // 获取所有表
        $tables = $db->fetchAll("SHOW TABLES LIKE '{$prefix}%'");
        
        $backup = "-- 轻论坛数据库备份\n";
        $backup .= "-- 生成时间: " . date('Y-m-d H:i:s') . "\n";
        $backup .= "-- 版本: " . getSetting('forum_version', '3.0.0') . "\n\n";
        
        foreach ($tables as $table) {
            $table_name = reset($table);
            
            // 表结构
            $create_table = $db->fetch("SHOW CREATE TABLE `{$table_name}`");
            $backup .= "-- 表结构: {$table_name}\n";
            $backup .= $create_table['Create Table'] . ";\n\n";
            
            // 表数据
            $rows = $db->fetchAll("SELECT * FROM `{$table_name}`");
            if (count($rows) > 0) {
                $backup .= "-- 表数据: {$table_name}\n";
                $backup .= "INSERT INTO `{$table_name}` VALUES\n";
                
                $values = [];
                foreach ($rows as $row) {
                    $row_values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $row_values[] = 'NULL';
                        } else {
                            $row_values[] = "'" . addslashes($value) . "'";
                        }
                    }
                    $values[] = '(' . implode(', ', $row_values) . ')';
                }
                
                $backup .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        // 保存备份文件
        $backup_dir = __DIR__ . '/../../backups';
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        $backup_file = $backup_dir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
        file_put_contents($backup_file, $backup);
        
        return $backup_file;
    } catch (Exception $e) {
        error_log('备份数据库失败: ' . $e->getMessage());
        return false;
    }
}
?>

