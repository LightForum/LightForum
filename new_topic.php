<?php
/**
 * 发布新主题页面
 */

// 启动会话
session_start();

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 检查是否已安装
if (!file_exists(__DIR__ . '/config/config.php')) {
    header('Location: install/index.php');
    exit;
}

// 检查是否已登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 加载配置和函数
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// 获取分类ID
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

// 获取分类列表
try {
    $db = Database::getInstance();
    $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
    
    // 获取所有分类
    $categories = $db->fetchAll("SELECT * FROM `{$prefix}categories` ORDER BY `sort_order` ASC");
    
    // 如果指定了分类ID，检查该分类是否存在
    if ($category_id > 0) {
        $category_exists = false;
        foreach ($categories as $category) {
            if ($category['id'] == $category_id) {
                $category_exists = true;
                break;
            }
        }
        
        if (!$category_exists) {
            $category_id = 0;
        }
    }
    
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
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_post_time' => date('Y-m-d H:i:s'),
                    'last_post_user_id' => $_SESSION['user_id']
                ]);
                
                $topic_id = $db->lastInsertId();
                
                // 记录创建主题日志
                logAction('create_topic', 'topic', $topic_id);
                
                $success = '主题发布成功';
                
                // 重定向到主题页面
                header('Location: topic.php?id=' . $topic_id);
                exit;
            }
        } catch (Exception $e) {
            $error = '发布主题失败: ' . $e->getMessage();
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
                <li class="breadcrumb-item"><a href="index.php">首页</a></li>
                <li class="breadcrumb-item"><a href="categories.php">分类列表</a></li>
                <?php if ($category_id > 0): ?>
                    <?php foreach ($categories as $category): ?>
                        <?php if ($category['id'] == $category_id): ?>
                            <li class="breadcrumb-item"><a href="category.php?id=<?php echo $category_id; ?>"><?php echo htmlspecialchars($category['title']); ?></a></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page">发布新主题</li>
            </ol>
        </nav>
    </div>
    
    <div class="card">
            <div class="card-body">
                <form method="post" action="new_topic.php">
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
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required minlength="3" maxlength="100">
                        <div class="form-text">标题长度在3-100个字符之间</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">主题内容</label>
                        <textarea class="form-control rich-editor" id="content" name="content" rows="10" required minlength="10"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                        <div class="form-text">内容长度至少10个字符</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">发布主题</button>
                    <a href="index.php" class="btn btn-secondary">取消</a>
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

