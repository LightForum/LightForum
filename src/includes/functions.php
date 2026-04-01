<?php

/**
 * 公共函数库
 * 避免使用MySQL保留字，确保SQL语句安全
 */

/**
 * 获取带前缀的表名
 */
function getTableName($name)
{
    $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
    return $prefix . $name;
}

/**
 * 获取系统设置
 */
function getSetting($key, $default = null)
{
    static $settings = null;

    if ($settings === null) {
        try {
            $db = Database::getInstance();
            $result = $db->fetchAll("SELECT `setting_key`, `setting_value` FROM `" . getTableName('settings') . "`");
            $settings = [];
            foreach ($result as $row) {
                $settings[$row['setting_key']] = htmlspecialchars($row['setting_value']);
            }
        } catch (Exception $e) {
            $settings = [];
        }
    }

    return $settings[$key] ?? $default;
}

/**
 * 设置系统设置
 */
function setSetting($key, $value)
{
    try {
        $db = Database::getInstance();

        $exists = $db->fetchColumn("SELECT COUNT(*) FROM `" . getTableName('settings') . "` WHERE `setting_key` = :key", ['key' => $key]);

        if ($exists) {
            return $db->update(getTableName('settings'), ['setting_value' => $value], "`setting_key` = :key", ['key' => $key]);
        } else {
            return $db->insert(getTableName('settings'), [
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
function logAction($action, $target_type = null, $target_id = null, $details = null)
{
    try {
        $db = Database::getInstance();

        $log_data = [
            'user_id' => $_SESSION['user_id'] ?? null,
            'action' => $action,
            'target_type' => $target_type,
            'target_id' => $target_id,
            'details' => $details ? json_encode($details) : null,
            'ip_address' => getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return $db->insert(getTableName('logs'), $log_data);
    } catch (Exception $e) {
        error_log('日志写入失败: ' . $e->getMessage());
        return false;
    }
}

/**
 * 获取客户端IP
 */
function getClientIp()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
}

/**
 * 生成CSRF令牌
 */
function generateCsrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 验证CSRF令牌
 */
function validateCsrfToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 安全过滤输入
 */
function safeInput($input)
{
    if (is_array($input)) {
        return array_map('safeInput', $input);
    }
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * 格式化日期时间
 */
function formatDateTime($datetime, $format = 'Y-m-d H:i:s')
{
    $timestamp = strtotime($datetime);
    return date($format, $timestamp);
}

/**
 * 获取所有启用的友链列表
 */
function getActiveLinks()
{
    try {
        $db = Database::getInstance();
        return $db->fetchAll("SELECT * FROM `" . getTableName('links') . "` WHERE `status` = 1 ORDER BY `sort_order` ASC, `id` ASC");
    } catch (Exception $e) {
        error_log('获取友链列表失败: ' . $e->getMessage());
        return [];
    }
}

/**
 * 检查用户是否为管理员
 */
function isAdmin()
{
    return ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * 检查用户是否为版主
 */
function isModerator()
{
    return in_array($_SESSION['role'] ?? '', ['moderator', 'admin']);
}
