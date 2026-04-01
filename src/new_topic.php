<?php
/**
 * 新主题页面 - 支持伪静态URL
 */
// 加载系统
require_once __DIR__ . '/includes/common.php';

// 检查是否已登录
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = getNewTopicUrl();
    header('Location: ' . getLoginUrl());
    exit;
}

// 获取预选分类ID
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

try {
    $db = Database::getInstance();
    $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
    
    // 获取所有分类
    $categories = $db->fetchAll("SELECT * FROM `{$prefix}categories` ORDER BY `sort_order` ASC");
    
} catch (Exception $e) {
    $error = '加载分类列表失败: ' . $e->getMessage();
}

// 处理表单提交
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    
    // 验证输入
    if (empty($title) || empty($content) || $category_id <= 0) {
        $error = '请填写所有必填字段';
    } else if (strlen($title) < 5 || strlen($title) > 100) {
        $error = '标题长度必须在5-100个字符之间';
    } else if (strlen($content) < 10) {
        $error = '内容长度必须至少为10个字符';
    } else {
        try {
            // 检查分类是否存在
            $category = $db->fetch(
                "SELECT * FROM `{$prefix}categories` WHERE `id` = :id",
                ['id' => $category_id]
            );
            
            if (!$category) {
                $error = '所选分类不存在';
            } else {
                // 创建主题
                $db->insert("{$prefix}topics", [
                    'category_id' => $category_id,
                    'user_id' => $_SESSION['user_id'],
                    'title' => $title,
                    'content' => $content,
                    'status' => 'published',
                    'is_sticky' => 0,
                    'is_locked' => 0,
                    'view_count' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'last_post_id' => null,
                    'last_post_user_id' => $_SESSION['user_id'],
                    'last_post_time' => date('Y-m-d H:i:s')
                ]);
                
                $topic_id = $db->lastInsertId();
                
                // 记录创建主题日志
                logAction('create_topic', 'topic', $topic_id);
                
                $success = '主题创建成功';
                
                // 重定向到主题页面
                header('Location: ' . getTopicUrl($topic_id, null, $title));
                exit;
            }
        } catch (Exception $e) {
            $error = '创建主题失败: ' . $e->getMessage();
        }
    }
}

// 设置页面标题
$page_title = '发布新主题';

// 加载页面头部
include __DIR__ . '/templates/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>发布新主题</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo getHomeUrl(); ?>">首页</a></li>
                <li class="breadcrumb-item active" aria-current="page">发布新主题</li>
            </ol>
        </nav>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">发布新主题</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo getNewTopicUrl(); ?>">
                <div class="mb-3">
                    <label for="category_id" class="form-label">选择分类</label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        <option value="">-- 请选择分类 --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="title" class="form-label">主题标题</label>
                    <input type="text" class="form-control" id="title" name="title" required minlength="5" maxlength="100" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    <div class="form-text">标题长度必须在5-100个字符之间</div>
                </div>
                
                <div class="mb-3">
                    <label for="content" class="form-label">主题内容</label>
                    <textarea class="form-control" id="content" name="content" rows="10" required minlength="10"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                    <div class="form-text">内容长度必须至少为10个字符</div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">发布主题</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- ice -->
<script type="text/JavaScript" src="./assets/src/iceEditor.js"></script>
<!-- 编辑器脚本 -->
<script>
 //自定义编辑器菜单
ice.editor("content",function(e){
	this.uploadUrl = "assets/src/upload/php-upload.php";
	this.pasteText = false;
	this.screenshot = true;
	this.screenshotUpload = true;
	this.menu = [
		'backColor', 'fontSize', 'foreColor', 'bold', 'italic', 'underline', 'strikeThrough', 'line', 'justifyLeft',
		'justifyCenter', 'justifyRight', 'indent', 'outdent', 'line', 'insertOrderedList', 'insertUnorderedList', 'line', 'createLink', 'unlink', 'line', 'hr', 'face', 'table', 'files', 'music', 'video', 'insertImage',
		'removeFormat', 'paste', 'line', 'code'
	];
	this.create();
// 	this.setValue('Hi,My name is iceui。');
})
</script>
<?php
// 加载页面底部
include __DIR__ . '/templates/footer.php';
?>
