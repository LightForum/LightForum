<?php
/**
 * 公共函数库 - 完全重构版
 * 避免使用MySQL保留字，确保SQL语句安全
 */

/**
 * 获取系统设置
 */
function getSetting($key, $default = null) {
    static $settings = null;
    
    if ($settings === null) {
        try {
            $db = Database::getInstance();
            $result = $db->fetchAll("SELECT `setting_key`, `setting_value` FROM `forum_settings`");
            $settings = [];
            foreach ($result as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            $settings = [];
        }
    }
    
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * 设置系统设置
 */
function setSetting($key, $value) {
    try {
        $db = Database::getInstance();
        
        // 检查设置是否存在
        $exists = $db->fetchColumn("SELECT COUNT(*) FROM `forum_settings` WHERE `setting_key` = :key", ['key' => $key]);
        
        if ($exists) {
            return $db->update('forum_settings', ['setting_value' => $value], "`setting_key` = :key", ['key' => $key]);
        } else {
            return $db->insert('forum_settings', [
                'setting_key' => $key,
                'setting_value' => $value,
                'setting_type' => 'string',
                'description' => ''
            ]);
        }
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 记录操作日志
 */
function logAction($action, $target_type = null, $target_id = null, $details = null) {
    try {
        $db = Database::getInstance();
        
        $log_data = [
            'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
            'action' => $action,
            'target_type' => $target_type,
            'target_id' => $target_id,
            'details' => $details ? json_encode($details) : null,
            'ip_address' => getClientIp(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $db->insert('forum_logs', $log_data);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 获取客户端IP
 */
function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    return $ip;
}

/**
 * 生成CSRF令牌
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 验证CSRF令牌
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 安全过滤输入
 */
function safeInput($input) {
    if (is_array($input)) {
        return array_map('safeInput', $input);
    }
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * 格式化日期时间
 */
function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
    $timestamp = strtotime($datetime);
    return date($format, $timestamp);
}

/**
 * 生成分页HTML
 */
function generatePagination($current_page, $total_pages, $url_pattern) {
    $html = '<div class="pagination">';
    
    // 上一页
    if ($current_page > 1) {
        $html .= '<a href="' . sprintf($url_pattern, $current_page - 1) . '" class="page-link">&laquo; 上一页</a>';
    } else {
        $html .= '<span class="page-link disabled">&laquo; 上一页</span>';
    }
    
    // 页码
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    if ($start > 1) {
        $html .= '<a href="' . sprintf($url_pattern, 1) . '" class="page-link">1</a>';
        if ($start > 2) {
            $html .= '<span class="page-link dots">...</span>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current_page) {
            $html .= '<span class="page-link active">' . $i . '</span>';
        } else {
            $html .= '<a href="' . sprintf($url_pattern, $i) . '" class="page-link">' . $i . '</a>';
        }
    }
    
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $html .= '<span class="page-link dots">...</span>';
        }
        $html .= '<a href="' . sprintf($url_pattern, $total_pages) . '" class="page-link">' . $total_pages . '</a>';
    }
    
    // 下一页
    if ($current_page < $total_pages) {
        $html .= '<a href="' . sprintf($url_pattern, $current_page + 1) . '" class="page-link">下一页 &raquo;</a>';
    } else {
        $html .= '<span class="page-link disabled">下一页 &raquo;</span>';
    }
    
    $html .= '</div>';
    
    return $html;
}
?>

