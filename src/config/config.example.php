<?php
/**
 * 配置文件示例
 * 安装程序会自动生成config.php文件
 */

// 数据库配置
define('DB_HOST', 'localhost');     // 数据库主机
define('DB_NAME', 'forum');         // 数据库名
define('DB_USER', 'root');          // 数据库用户名
define('DB_PASS', '');              // 数据库密码
define('DB_PORT', 3306);            // 数据库端口
define('DB_PREFIX', 'forum_');      // 数据库表前缀

// 安全配置
define('SECURITY_SALT', '随机生成的安全盐');  // 用于密码加密
define('SESSION_NAME', 'PHPSESSID');         // 会话名称

// 调试配置
define('DEBUG_MODE', false);  // 是否开启调试模式

// 时区配置
define('TIMEZONE', 'Asia/Shanghai');  // 默认时区

// 设置时区
date_default_timezone_set(TIMEZONE);
?>

