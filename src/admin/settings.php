<?php
/**
 * 系统设置页面
 */

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

// 加载配置和函数
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/admin_functions.php';

// 检查是否已登录且是管理员
checkAdminAccess();

// 处理表单提交
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = $_POST['site_name'] ?? '';
    $site_title = $_POST['site_title'] ?? '';
    $site_description = $_POST['site_description'] ?? '';
    $allow_registration = isset($_POST['allow_registration']) ? '1' : '0';
    $topics_per_page = (int)$_POST['topics_per_page'];
    $posts_per_page = (int)$_POST['posts_per_page'];
    $password_reset_expires = (int)$_POST['password_reset_expires'];
    $account_activation_expires = (int)$_POST['account_activation_expires'];
    
    // 验证输入
    if (empty($site_name)) {
        $error = '站点名称不能为空';
    } elseif (empty($site_title)) {
        $error = '首页标题不能为空';
    } else if ($topics_per_page < 1 || $topics_per_page > 100) {
        $error = '每页主题数必须在1-100之间';
    } else if ($posts_per_page < 1 || $posts_per_page > 100) {
        $error = '每页回复数必须在1-100之间';
    } else {
        try {
            // 更新设置
            setSetting('site_name', $site_name);
            setSetting('site_title', $site_title);
            setSetting('site_description', $site_description);
            setSetting('allow_registration', $allow_registration);
            setSetting('topics_per_page', $topics_per_page);
            setSetting('posts_per_page', $posts_per_page);
            setSetting('password_reset_expires', $password_reset_expires);
            setSetting('account_activation_expires', $account_activation_expires);
            
            // 记录操作日志
            logAdminAction('update_settings');
            
            $success = '设置已更新';
        } catch (Exception $e) {
            $error = '更新设置失败: ' . $e->getMessage();
        }
    }
}

// 获取当前设置
$site_name = getSetting('site_name', '');
$site_title = getSetting('site_title', '');
$site_description = getSetting('site_description', '');
$allow_registration = getSetting('allow_registration', '1');
$topics_per_page = (int)getSetting('topics_per_page', 20);
$posts_per_page = (int)getSetting('posts_per_page', 15);
$password_reset_expires = (int)getSetting('password_reset_expires', 60);
$account_activation_expires = (int)getSetting('account_activation_expires', 24);

// 设置页面标题
$page_title = '系统设置';

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
                <h1 class="h2">系统设置</h1>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="post" action="settings.php">
                        <div class="mb-3">
                            <label for="site_name" class="form-label">站点名称</label>
                            <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="site_title" class="form-label">首页标题</label>
                            <input type="text" class="form-control" id="site_title" name="site_title" value="<?php echo htmlspecialchars($site_title); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="site_description" class="form-label">站点描述</label>
                            <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($site_description); ?></textarea>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="allow_registration" name="allow_registration" <?php echo $allow_registration === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="allow_registration">允许新用户注册</label>
                        </div>
                        
                        <div class="mb-3">
                            <label for="topics_per_page" class="form-label">每页显示的主题数</label>
                            <input type="number" class="form-control" id="topics_per_page" name="topics_per_page" value="<?php echo $topics_per_page; ?>" min="1" max="100" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="posts_per_page" class="form-label">每页显示的回复数</label>
                            <input type="number" class="form-control" id="posts_per_page" name="posts_per_page" value="<?php echo $posts_per_page; ?>" min="1" max="100" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_reset_expires" class="form-label">密码重置链接有效期</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="password_reset_expires" name="password_reset_expires" 
                                       value="<?php echo htmlspecialchars(getSetting('password_reset_expires', 60)); ?>" required>
                                <span class="input-group-text">分钟</span>
                            </div>
                            <div class="form-text">密码重置链接的有效时间</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="account_activation_expires" class="form-label">账户激活链接有效期</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="account_activation_expires" name="account_activation_expires" 
                                       value="<?php echo htmlspecialchars(getSetting('account_activation_expires', 24)); ?>" required>
                                <span class="input-group-text">小时</span>
                            </div>
                            <div class="form-text">账户激活链接的有效时间</div>
                        </div>
                        <button type="submit" class="btn btn-primary">保存设置</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
// 加载页面底部
include __DIR__ . '/templates/admin_footer.php';
?>

