<?php
/**
 * 数据库结构定义 - 完全重构版
 * 避免使用MySQL保留字，确保SQL语句安全
 */

/**
 * 获取数据库表结构
 * @param string $prefix 表前缀
 * @return array 表结构SQL语句
 */
function getDatabaseSchema($prefix = 'forum_') {
    return [
        // 用户表
        "{$prefix}users" => "CREATE TABLE IF NOT EXISTS `{$prefix}users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `password` varchar(255) NOT NULL,
            `email` varchar(100) NOT NULL,
            `avatar` varchar(255) DEFAULT NULL,
            `role` enum('user','moderator','admin') DEFAULT 'user',
            `status` enum('active','inactive','banned') DEFAULT 'active',
            `last_login` datetime DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `updated_ip` varchar(255) DEFAULT NULL,
            `reset_token` varchar(255) DEFAULT NULL,
            `reset_expires` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // 分类表
        "{$prefix}categories" => "CREATE TABLE IF NOT EXISTS `{$prefix}categories` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `parent_id` int(11) DEFAULT NULL,
            `title` varchar(100) NOT NULL,
            `description` text DEFAULT NULL,
            `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `sort_order` int(11) DEFAULT 0,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `slug` (`slug`),
            KEY `parent_id` (`parent_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // 主题表
        "{$prefix}topics" => "CREATE TABLE IF NOT EXISTS `{$prefix}topics` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `category_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `title` varchar(255) NOT NULL,
            `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `content` text NOT NULL,
            `status` enum('published','draft','hidden') DEFAULT 'published',
            `is_sticky` tinyint(1) DEFAULT 0,
            `is_locked` tinyint(1) DEFAULT 0,
            `view_count` int(11) DEFAULT 0,
            `last_post_id` int(11) DEFAULT NULL,
            `last_post_user_id` int(11) DEFAULT NULL,
            `last_post_time` datetime DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `slug` (`slug`),
            KEY `category_id` (`category_id`),
            KEY `user_id` (`user_id`),
            KEY `last_post_user_id` (`last_post_user_id`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // 回复表
        "{$prefix}posts" => "CREATE TABLE IF NOT EXISTS `{$prefix}posts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `topic_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `content` text NOT NULL,
            `status` enum('published','hidden') DEFAULT 'published',
            `reply_to` int(11) DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `topic_id` (`topic_id`),
            KEY `user_id` (`user_id`),
            KEY `reply_to` (`reply_to`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // 设置表 - 避免使用保留字
        "{$prefix}settings" => "CREATE TABLE IF NOT EXISTS `{$prefix}settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `setting_key` varchar(100) NOT NULL,
            `setting_value` text DEFAULT NULL,
            `setting_type` enum('string','int','bool','json') DEFAULT 'string',
            `description` varchar(255) DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `setting_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // 日志表
        "{$prefix}logs" => "CREATE TABLE IF NOT EXISTS `{$prefix}logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL,
            `action` varchar(50) NOT NULL,
            `target_type` varchar(50) DEFAULT NULL,
            `target_id` int(11) DEFAULT NULL,
            `details` text DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` varchar(255) DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `action` (`action`),
            KEY `target_type` (`target_type`),
            KEY `target_id` (`target_id`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // 友链表
        "{$prefix}links" => "CREATE TABLE `{$prefix}links` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
}
?>

