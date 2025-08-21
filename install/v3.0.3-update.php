<?php
/**
 * 自动导入友链管理表结构
 * 访问此文件将自动创建友链表
 */
 
// 加载配置和函数
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// 初始化变量
$success = false;
$message = '';

try {
    // 获取数据库连接
    $db = Database::getInstance();
    $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
    
    // 检查友链表是否已存在
    $tableExists = $db->fetch(
        "SHOW TABLES LIKE '{$prefix}links'"
    );
    
    if ($tableExists) {
        $message = '友链表已存在，无需创建';
    } else {
        // 创建友链表的SQL语句（不含Logo字段）
        $sql = "
            CREATE TABLE `{$prefix}links` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL COMMENT '友链名称',
                `url` varchar(255) NOT NULL COMMENT '友链URL',
                `description` varchar(255) DEFAULT NULL COMMENT '友链描述',
                `sort_order` int(11) DEFAULT 0 COMMENT '排序顺序，数字越小越靠前',
                `status` tinyint(1) DEFAULT 1 COMMENT '状态：1=启用，0=禁用',
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `name` (`name`),
                KEY `status` (`status`),
                KEY `sort_order` (`sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='友情链接表';
        ";
        
        // 执行SQL语句
        $db->execute($sql);
        
        $success = true;
        $message = '友链表创建成功！现在您可以<a href="/../admin/links.php">管理友链</a>了。';
    }
} catch (Exception $e) {
    $message = '创建友链表失败: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>友链管理系统升级</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 50px;
        }
        .result-container {
            max-width: 600px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="result-container">
        <div class="card">
            <div class="card-header">
                <h3>友链管理系统升级结果</h3>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <h4 class="alert-heading">升级成功！</h4>
                        <p><?php echo $message; ?></p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger" role="alert">
                        <h4 class="alert-heading">升级失败</h4>
                        <p><?php echo $message; ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info" role="alert">
                    <p>为了安全起见，建议您在完成升级后删除此文件。</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>