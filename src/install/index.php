<?php
/**
 * 安装向导主入口文件 - 完全重构版
 * 避免使用MySQL保留字，确保SQL语句安全
 */

// 启动会话
session_start();

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 定义安装步骤
$steps = [
    1 => '环境检测',
    2 => '数据库配置',
    3 => '系统设置',
    4 => '安装完成'
];

// 获取当前步骤
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($current_step < 1 || $current_step > count($steps)) {
    $current_step = 1;
}

// 检查是否已安装
if (file_exists(__DIR__ . '/../config/config.php') && $current_step != 4) {
    $installed = true;
    $warning = '警告：检测到论坛已经安装。继续安装将覆盖现有数据！';
} else {
    $installed = false;
    $warning = '';
}

// 处理表单提交
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($current_step) {
        case 1:
            // 环境检测通过，进入下一步
            header('Location: index.php?step=2');
            exit;
            
        case 2:
            // 处理数据库配置
            $db_host = $_POST['db_host'] ?? 'localhost';
            $db_name = $_POST['db_name'] ?? '';
            $db_user = $_POST['db_user'] ?? '';
            $db_pass = $_POST['db_pass'] ?? '';
            $db_prefix = $_POST['db_prefix'] ?? 'forum_';
            
            // 验证输入
            if (empty($db_host) || empty($db_name) || empty($db_user)) {
                $error = '请填写所有必填字段';
            } else {
                try {
                    // 保存数据库配置到会话
                    $_SESSION['db_host'] = $db_host;
                    $_SESSION['db_name'] = $db_name;
                    $_SESSION['db_user'] = $db_user;
                    $_SESSION['db_pass'] = $db_pass;
                    $_SESSION['db_prefix'] = $db_prefix;
                    
                    // 尝试连接数据库
                    $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // 检查数据库是否存在，不存在则创建
                    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db_name'");
                    if (!$stmt->fetch()) {
                        $pdo->exec("CREATE DATABASE `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    }
                    
                    // 选择数据库
                    $pdo->exec("USE `$db_name`");
                    
                    // 创建表结构
                    include_once 'database_schema.php';
                    $tables = getDatabaseSchema($db_prefix);
                    
                    foreach ($tables as $table_name => $sql) {
                        $pdo->exec($sql);
                    }
                    
                    // 插入默认数据
                    include_once 'default_data.php';
                    $default_data = getDefaultData($db_prefix);
                    
                    foreach ($default_data as $table => $rows) {
                        foreach ($rows as $row) {
                            $fields = array_keys($row);
                            $placeholders = array_fill(0, count($fields), '?');
                            
                            $sql = "INSERT INTO `$table` (`" . implode('`, `', $fields) . "`) VALUES (" . implode(', ', $placeholders) . ")";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute(array_values($row));
                        }
                    }
                    
                    // 生成配置文件内容
                    $config_content = "<?php\n";
                    $config_content .= "// 数据库配置\n";
                    $config_content .= "define('DB_HOST', '$db_host');\n";
                    $config_content .= "define('DB_NAME', '$db_name');\n";
                    $config_content .= "define('DB_USER', '$db_user');\n";
                    $config_content .= "define('DB_PASS', '$db_pass');\n";
                    $config_content .= "define('DB_PREFIX', '$db_prefix');\n";
                    $config_content .= "?>";
                    
                    // 保存配置文件内容到会话
                    $_SESSION['config_content'] = $config_content;
                    
                    // 进入下一步
                    header('Location: index.php?step=3');
                    exit;
                    
                } catch (PDOException $e) {
                    $error = '数据库连接失败: ' . $e->getMessage();
                }
            }
            break;
            
        case 3:
            // 处理系统设置
            $site_name = $_POST['site_name'] ?? '轻论坛';
            $site_description = $_POST['site_description'] ?? '一个简单易用的PHP论坛程序';
            $admin_username = $_POST['admin_username'] ?? '';
            $admin_password = $_POST['admin_password'] ?? '';
            $admin_email = $_POST['admin_email'] ?? '';
            
            // 验证输入
            if (empty($admin_username) || empty($admin_password) || empty($admin_email)) {
                $error = '请填写所有必填字段';
            } else if (strlen($admin_password) < 6) {
                $error = '管理员密码至少需要6个字符';
            } else if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
                $error = '请输入有效的电子邮件地址';
            } else {
                try {
                    // 连接数据库
                    $db_host = $_SESSION['db_host'];
                    $db_name = $_SESSION['db_name'];
                    $db_user = $_SESSION['db_user'];
                    $db_pass = $_SESSION['db_pass'];
                    $db_prefix = $_SESSION['db_prefix'];
                    
                    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // 更新系统设置
                    $settings = [
                        'site_name' => $site_name,
                        'site_description' => $site_description,
                        'install_date' => date('Y-m-d H:i:s')
                    ];
                    
                    foreach ($settings as $key => $value) {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$db_prefix}settings` WHERE `setting_key` = ?");
                        $stmt->execute([$key]);
                        $exists = $stmt->fetchColumn();
                        
                        if ($exists) {
                            $stmt = $pdo->prepare("UPDATE `{$db_prefix}settings` SET `setting_value` = ? WHERE `setting_key` = ?");
                            $stmt->execute([$value, $key]);
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO `{$db_prefix}settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES (?, ?, 'string', '')");
                            $stmt->execute([$key, $value]);
                        }
                    }
                    
                    // 创建管理员账户
                    $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("INSERT INTO `{$db_prefix}users` (`username`, `password`, `email`, `role`, `status`, `created_at`) VALUES (?, ?, ?, 'admin', 'active', NOW())");
                    $stmt->execute([$admin_username, $password_hash, $admin_email]);
                    
                    // 写入配置文件
                    $config_dir = __DIR__ . '/../config/';
                    if (!is_dir($config_dir)) {
                        mkdir($config_dir, 0755, true);
                    }
                    
                    $config_file = $config_dir . 'config.php';
                    file_put_contents($config_file, $_SESSION['config_content']);
                    
                    // 进入下一步
                    header('Location: index.php?step=4');
                    exit;
                    
                } catch (PDOException $e) {
                    $error = '配置失败: ' . $e->getMessage();
                } catch (Exception $e) {
                    $error = '配置失败: ' . $e->getMessage();
                }
            }
            break;
    }
}

// 页面头部
function showHeader($title) {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title; ?> - 轻论坛安装向导</title>
        <link href="https://static.doucdn.org/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background-color: #f8f9fa;
                padding-top: 2rem;
            }
            .install-container {
                max-width: 800px;
                margin: 0 auto;
                background-color: #fff;
                border-radius: 10px;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
                padding: 2rem;
            }
            .install-header {
                text-align: center;
                margin-bottom: 2rem;
                padding-bottom: 1rem;
                border-bottom: 1px solid #dee2e6;
            }
            .install-steps {
                display: flex;
                justify-content: space-between;
                margin-bottom: 2rem;
            }
            .step-item {
                flex: 1;
                text-align: center;
                padding: 0.5rem;
                position: relative;
            }
            .step-item:not(:last-child):after {
                content: '';
                position: absolute;
                top: 50%;
                right: -10px;
                width: 20px;
                height: 2px;
                background-color: #dee2e6;
            }
            .step-item.active {
                font-weight: bold;
                color: #007bff;
            }
            .step-item.completed {
                color: #28a745;
            }
            .install-content {
                margin-bottom: 2rem;
            }
            .install-footer {
                text-align: right;
                padding-top: 1rem;
                border-top: 1px solid #dee2e6;
            }
            .alert {
                margin-bottom: 1rem;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="install-container">
                <div class="install-header">
                    <h1>轻论坛安装向导</h1>
                    <p class="text-muted">简单易用的PHP论坛程序</p>
                </div>
    <?php
}

// 页面底部
function showFooter() {
    ?>
            </div>
        </div>
        <script src="https://static.doucdn.org/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}

// 显示步骤导航
function showSteps($steps, $current_step) {
    ?>
    <div class="install-steps">
        <?php foreach ($steps as $step_num => $step_name): ?>
            <div class="step-item <?php echo $step_num == $current_step ? 'active' : ($step_num < $current_step ? 'completed' : ''); ?>">
                <?php echo $step_num . '. ' . $step_name; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

// 显示错误信息
function showError($message) {
    if (!empty($message)) {
        echo '<div class="alert alert-danger">' . $message . '</div>';
    }
}

// 显示成功信息
function showSuccess($message) {
    if (!empty($message)) {
        echo '<div class="alert alert-success">' . $message . '</div>';
    }
}

// 显示警告信息
function showWarning($message) {
    if (!empty($message)) {
        echo '<div class="alert alert-warning">' . $message . '</div>';
    }
}

// 显示页面内容
showHeader($steps[$current_step]);
showSteps($steps, $current_step);
showError($error);
showSuccess($success);
showWarning($warning);
?>

<div class="install-content">
    <?php
    // 根据当前步骤显示不同内容
    switch ($current_step) {
        case 1:
            include 'step1_environment.php';
            break;
        case 2:
            include 'step2_database.php';
            break;
        case 3:
            include 'step3_config.php';
            break;
        case 4:
            include 'step4_complete.php';
            break;
    }
    ?>
</div>

<?php
showFooter();
?>

