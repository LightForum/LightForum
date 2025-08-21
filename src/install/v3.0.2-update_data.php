<?php
/**
 * 数据库更新工具
 * 用于添加新字段和导入默认数据
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 加载配置和函数
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// 加载默认数据文件
require_once __DIR__ . '/v3.0.2-default_data.php';

// 获取数据库连接
$db = Database::getInstance();
$prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';

// 开始输出结果
echo '<!DOCTYPE html>';
echo '<html>';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<title>数据库更新工具</title>';
echo '<style>body{font-family:Arial;margin:20px;}.success{color:green;}.error{color:red;}</style>';
echo '</head>';
echo '<body>';
echo '<h1>数据库更新工具</h1>';

// 执行更新操作
try {
    // 步骤1: 添加密码重置字段到用户表
    echo '<h3>1. 添加密码重置字段</h3>';
    
    // 检查字段是否已存在
    $columns = $db->fetchAll("SHOW COLUMNS FROM `{$prefix}users`");
    $columnNames = array_column($columns, 'Field');
    
    $fieldsAdded = false;
    
    if (!in_array('reset_token', $columnNames)) {
        $db->execute("ALTER TABLE `{$prefix}users` ADD COLUMN `reset_token` varchar(255) DEFAULT NULL");
        echo '<p class="success">已添加 reset_token 字段</p>';
        $fieldsAdded = true;
    } else {
        echo '<p>reset_token 字段已存在</p>';
    }
    
    if (!in_array('reset_expires', $columnNames)) {
        $db->execute("ALTER TABLE `{$prefix}users` ADD COLUMN `reset_expires` datetime DEFAULT NULL");
        echo '<p class="success">已添加 reset_expires 字段</p>';
        $fieldsAdded = true;
    } else {
        echo '<p>reset_expires 字段已存在</p>';
    }
    
    if (!$fieldsAdded) {
        echo '<p>所有密码重置字段已存在，无需更新</p>';
    }
    
    // 导入默认设置项
    echo '<h3>2. 导入默认设置项</h3>';
    
    // 获取默认数据
    $defaultData = getDefaultData($prefix);
    
    // 确保设置表数据存在
    if (isset($defaultData["{$prefix}settings"])) {
        $settingsAdded = 0;
        
        foreach ($defaultData["{$prefix}settings"] as $setting) {
            // 检查设置是否已存在
            $exists = $db->fetch(
                "SELECT * FROM `{$prefix}settings` WHERE `setting_key` = :setting_key",
                ['setting_key' => $setting['setting_key']]
            );
            
            if (!$exists) {
                $db->insert(
                    "{$prefix}settings",
                    [
                        'setting_key' => $setting['setting_key'],
                        'setting_value' => $setting['setting_value'],
                        'setting_type' => $setting['setting_type'],
                        'description' => $setting['description'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );
                echo '<p class="success">已添加设置: ' . $setting['setting_key'] . '</p>';
                $settingsAdded++;
            }
        }
        
        if ($settingsAdded === 0) {
            echo '<p>所有设置已存在，无需更新</p>';
        } else {
            echo '<p class="success">成功添加 ' . $settingsAdded . ' 个设置项</p>';
        }
    } else {
        echo '<p class="error">无法获取默认设置数据</p>';
    }
    
    echo '<h3 class="success">更新完成！</h3>';
    echo '<p>请删除此文件以确保安全性。</p>';
    
} catch (Exception $e) {
    echo '<p class="error">更新过程中发生错误: ' . $e->getMessage() . '</p>';
}

echo '</body>';
echo '</html>';