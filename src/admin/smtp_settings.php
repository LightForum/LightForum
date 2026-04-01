<?php
/**
 * SMTP邮箱设置页面
 */

// 加载配置和函数
require_once __DIR__ . '/../includes/common.php';
require_once __DIR__ . '/../includes/smtp.php';
require_once __DIR__ . '/includes/admin_functions.php';

// 检查是否已登录且是管理员
checkAdminAccess();

// 获取数据库实例和表前缀
$db = Database::getInstance();
$prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';

// 获取当前SMTP设置
$smtpSettings = [];
try {
    $settings = $db->fetchAll("SELECT `setting_key`, `setting_value` FROM `{$prefix}settings` WHERE `setting_key` LIKE 'smtp_%'");
    foreach ($settings as $setting) {
        $smtpSettings[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (Exception $e) {
    $error = '加载设置失败: ' . $e->getMessage();
}

// 处理表单提交
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $smtpHost = $_POST['smtp_host'] ?? '';
    $smtpPort = $_POST['smtp_port'] ?? '';
    $smtpSecure = $_POST['smtp_secure'] ?? '';
    $smtpUsername = $_POST['smtp_username'] ?? '';
    $smtpPassword = $_POST['smtp_password'] ?? '';
    $smtpFrom = $_POST['smtp_from'] ?? '';
    $smtpFromName = $_POST['smtp_from_name'] ?? '';
    
    // 验证输入
    if (empty($smtpHost) || empty($smtpPort) || empty($smtpUsername) || empty($smtpFrom)) {
        $error = '请填写所有必填字段';
    } else {
        try {
            // 更新SMTP设置
            $settings = [
                ['setting_key' => 'smtp_host', 'setting_value' => $smtpHost],
                ['setting_key' => 'smtp_port', 'setting_value' => $smtpPort],
                ['setting_key' => 'smtp_secure', 'setting_value' => $smtpSecure],
                ['setting_key' => 'smtp_username', 'setting_value' => $smtpUsername],
                ['setting_key' => 'smtp_from', 'setting_value' => $smtpFrom],
                ['setting_key' => 'smtp_from_name', 'setting_value' => $smtpFromName]
            ];
            
            // **关键修改：密码不进行哈希处理，直接存储**
            if (!empty($smtpPassword)) {
                $settings[] = ['setting_key' => 'smtp_password', 'setting_value' => $smtpPassword]; // 原始密码
            }
            
            foreach ($settings as $setting) {
                $exists = $db->fetch(
                    "SELECT * FROM `{$prefix}settings` WHERE `setting_key` = :setting_key",
                    ['setting_key' => $setting['setting_key']]
                );
                
                if ($exists) {
                    $db->update(
                        "{$prefix}settings",
                        ['setting_value' => $setting['setting_value']],
                        'setting_key = :setting_key',
                        ['setting_key' => $setting['setting_key']]
                    );
                } else {
                    $db->insert(
                        "{$prefix}settings",
                        [
                            'setting_key' => $setting['setting_key'],
                            'setting_value' => $setting['setting_value'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );
                }
            }
            
            // 记录操作日志
            logAction('update_smtp_settings', 'system', 0);
            
            $success = 'SMTP设置更新成功';
            
        } catch (Exception $e) {
            $error = '更新设置失败: ' . $e->getMessage();
        }
    }
}

// 设置页面标题
$page_title = 'SMTP邮箱配置';

// 加载页面头部
include __DIR__ . '/templates/admin_header.php';
?>
<div class="container-fluid">
    <div class="row">
        <!-- 侧边栏 -->
        <?php include __DIR__ . '/templates/admin_sidebar.php'; ?>
        
        <!-- 主内容区 -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">邮箱配置</h1>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="post" action="smtp_settings.php">
                    <div class="mb-3">
                        <label for="smtp_host" class="form-label">SMTP服务器</label>
                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                               value="<?php echo htmlspecialchars($smtpSettings['smtp_host'] ?? ''); ?>" required>
                        <div class="form-text">例如：smtp.gmail.com</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="smtp_port" class="form-label">SMTP端口</label>
                        <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                               value="<?php echo htmlspecialchars($smtpSettings['smtp_port'] ?? '587'); ?>" required>
                        <div class="form-text">常见端口：587 (TLS), 465 (SSL), 25 (未加密)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="smtp_secure" class="form-label">加密方式</label>
                        <select class="form-select" id="smtp_secure" name="smtp_secure">
                            <option value="" <?php echo (isset($smtpSettings['smtp_secure']) && $smtpSettings['smtp_secure'] == '') ? 'selected' : ''; ?>>无</option>
                            <option value="tls" <?php echo (isset($smtpSettings['smtp_secure']) && $smtpSettings['smtp_secure'] == 'tls') ? 'selected' : ''; ?>>TLS</option>
                            <option value="ssl" <?php echo (isset($smtpSettings['smtp_secure']) && $smtpSettings['smtp_secure'] == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="smtp_username" class="form-label">邮箱账号</label>
                        <input type="email" class="form-control" id="smtp_username" name="smtp_username" 
                               value="<?php echo htmlspecialchars($smtpSettings['smtp_username'] ?? ''); ?>" required>
                        <div class="form-text">用于发送邮件的邮箱地址</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="smtp_password" class="form-label">邮箱密码</label>
                        <input type="password" class="form-control" id="smtp_password" name="smtp_password">
                        <div class="form-text">留空则保持现有密码不变</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="smtp_from" class="form-label">发件人邮箱</label>
                        <input type="email" class="form-control" id="smtp_from" name="smtp_from" 
                               value="<?php echo htmlspecialchars($smtpSettings['smtp_from'] ?? ''); ?>" required>
                        <div class="form-text">显示在收件人邮箱中的发件人地址</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="smtp_from_name" class="form-label">发件人名称</label>
                        <input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name" 
                               value="<?php echo htmlspecialchars($smtpSettings['smtp_from_name'] ?? ''); ?>">
                        <div class="form-text">显示在收件人邮箱中的发件人名称</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">保存设置</button>
                </form>
            </div>
        </main>
    </div>
</div>

<?php
// 加载页面底部
include __DIR__ . '/templates/admin_footer.php';
?>