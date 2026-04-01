<?php

/**
 * 公共模块
 */
header("Content-Type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai'); // 系统时区设置

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

// 公共：配置文件
require_once __DIR__ . '/../config/config.php';
// 公共：数据库
require_once __DIR__ . '/database.php';
// 公共：函数库
require_once __DIR__ . '/functions.php';
// 功能：加载URL助手函数
require_once __DIR__ . '/url_helper.php';
// 功能：分页
require_once __DIR__ . '/pagination_helper.php';
