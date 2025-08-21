<?php
/**
 * 删除主题或回复处理页面
 */

// 启动会话
session_start();

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 检查是否已安装
if (!file_exists(__DIR__ . '/config/config.php')) {
    header('Location: install/index.php');
    exit;
}

// 检查是否已登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 加载配置和函数
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// 获取操作类型和ID
$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';

if (empty($type) || $id <= 0) {
    header('Location: ' . $redirect);
    exit;
}

try {
    $db = Database::getInstance();
    $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
    
    // 根据类型获取对应的数据
    if ($type === 'topic') {
        $item = $db->fetch(
            "SELECT * FROM `{$prefix}topics` WHERE `id` = :id",
            ['id' => $id]
        );
        
        if (!$item) {
            header('Location: ' . $redirect);
            exit;
        }
        
        // 检查权限
        if ($item['user_id'] != $_SESSION['user_id'] && !isAdmin()) {
            header('Location: topic.php?id=' . $id);
            exit;
        }
        
        // 删除主题下的所有回复
        $db->delete("{$prefix}posts", 'topic_id = :topic_id', ['topic_id' => $id]);
        
        // 删除主题
        $db->delete("{$prefix}topics", 'id = :id', ['id' => $id]);
        
        // 记录日志
        logAction('delete_topic', 'topic', $id);
        
        // 重定向到分类页面
        header('Location: category.php?id=' . $item['category_id']);
        exit;
    } 
    else if ($type === 'post') {
        $item = $db->fetch(
            "SELECT p.*, t.category_id 
             FROM `{$prefix}posts` p 
             JOIN `{$prefix}topics` t ON p.topic_id = t.id 
             WHERE p.id = :id",
            ['id' => $id]
        );
        
        if (!$item) {
            header('Location: ' . $redirect);
            exit;
        }
        
        // 检查权限
        if ($item['user_id'] != $_SESSION['user_id'] && !isAdmin()) {
            header('Location: topic.php?id=' . $item['topic_id']);
            exit;
        }
        
        // 删除回复
        $db->delete("{$prefix}posts", 'id = :id', ['id' => $id]);
        
        // 记录日志
        logAction('delete_post', 'post', $id);
        
        // 重定向到主题页面
        header('Location: topic.php?id=' . $item['topic_id']);
        exit;
    }
    else {
        // 未知类型
        header('Location: ' . $redirect);
        exit;
    }
    
} catch (Exception $e) {
    // 记录错误日志
    error_log('删除失败: ' . $e->getMessage());
    header('Location: ' . $redirect);
    exit;
}