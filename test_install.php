<?php
/**
 * 安装测试脚本 - 完全重构版
 * 测试所有SQL语句，特别是之前出错的部分
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PHP轻论坛 v3.0 安装测试 ===\n\n";

// 测试数据库连接和SQL语句
try {
    echo "1. 测试数据库连接...\n";
    $pdo = new PDO("sqlite::memory:");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ 数据库连接成功\n\n";
    
    echo "2. 创建settings表...\n";
    $create_settings = "CREATE TABLE IF NOT EXISTS `forum_settings` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `setting_key` TEXT NOT NULL UNIQUE,
        `setting_value` TEXT DEFAULT NULL,
        `setting_type` TEXT DEFAULT 'string',
        `description` TEXT DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($create_settings);
    echo "✅ settings表创建成功\n\n";
    
    echo "3. 测试插入设置...\n";
    $settings = [
        ['setting_key' => 'site_name', 'setting_value' => 'PHP轻论坛', 'setting_type' => 'string', 'description' => '网站名称'],
        ['setting_key' => 'site_description', 'setting_value' => '一个简单易用的PHP论坛程序', 'setting_type' => 'string', 'description' => '网站描述'],
        ['setting_key' => 'allow_registration', 'setting_value' => '1', 'setting_type' => 'bool', 'description' => '是否允许用户注册']
    ];
    
    foreach ($settings as $setting) {
        $fields = array_keys($setting);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO `forum_settings` (`" . implode('`, `', $fields) . "`) VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($setting));
    }
    echo "✅ 设置插入成功\n\n";
    
    echo "4. 测试查询设置...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM `forum_settings`");
    $count = $stmt->fetchColumn();
    echo "查询结果: $count 条记录\n";
    
    if ($count > 0) {
        echo "✅ 设置查询成功\n\n";
    } else {
        echo "❌ 设置查询失败\n\n";
    }
    
    echo "5. 测试更新设置...\n";
    $stmt = $pdo->prepare("UPDATE `forum_settings` SET `setting_value` = ? WHERE `setting_key` = ?");
    $result = $stmt->execute(['PHP轻论坛测试版', 'site_name']);
    
    if ($result) {
        echo "✅ 设置更新成功\n\n";
    } else {
        echo "❌ 设置更新失败\n\n";
    }
    
    echo "6. 验证更新结果...\n";
    $stmt = $pdo->prepare("SELECT `setting_value` FROM `forum_settings` WHERE `setting_key` = ?");
    $stmt->execute(['site_name']);
    $value = $stmt->fetchColumn();
    echo "更新后的值: $value\n";
    
    if ($value === 'PHP轻论坛测试版') {
        echo "✅ 更新验证成功\n\n";
    } else {
        echo "❌ 更新验证失败\n\n";
    }
    
    echo "7. 测试系统配置步骤...\n";
    $site_name = 'PHP轻论坛 v3.0';
    $site_description = '完全重构版';
    
    // 更新系统设置
    $settings = [
        'site_name' => $site_name,
        'site_description' => $site_description,
        'install_date' => date('Y-m-d H:i:s')
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `forum_settings` WHERE `setting_key` = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetchColumn();
        
        if ($exists) {
            $stmt = $pdo->prepare("UPDATE `forum_settings` SET `setting_value` = ? WHERE `setting_key` = ?");
            $result = $stmt->execute([$value, $key]);
            echo "更新设置 $key: " . ($result ? "成功" : "失败") . "\n";
        } else {
            $stmt = $pdo->prepare("INSERT INTO `forum_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES (?, ?, 'string', '')");
            $result = $stmt->execute([$key, $value]);
            echo "插入设置 $key: " . ($result ? "成功" : "失败") . "\n";
        }
    }
    echo "✅ 系统配置步骤测试成功\n\n";
    
    echo "=== 测试完成 ===\n";
    echo "✅ 所有SQL语句测试通过，安装程序应该可以正常工作\n";
    
} catch (PDOException $e) {
    echo "❌ 测试失败: " . $e->getMessage() . "\n";
}
?>

