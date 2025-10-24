<?php
/**
 * 安装步骤4：安装完成 - 完全重构版
 */

// 获取安装信息
$db_name = $_SESSION['db_name'] ?? '';
$db_prefix = $_SESSION['db_prefix'] ?? 'forum_';
$site_name = $_SESSION['site_name'] ?? '轻论坛';

// 清除安装会话数据
$_SESSION = [];
?>

<div class="text-center mb-4">
    <div class="display-1 text-success mb-3">
        <i class="bi bi-check-circle-fill"></i>
        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
        </svg>
    </div>
    <h2>安装成功！</h2>
    <p class="lead">恭喜您，轻论坛已成功安装。</p>
</div>

<div class="alert alert-info">
    <h5>安装信息</h5>
    <ul class="mb-0">
        <li>论坛名称: <?php echo htmlspecialchars($site_name); ?></li>
        <li>数据库: <?php echo htmlspecialchars($db_name); ?></li>
        <li>表前缀: <?php echo htmlspecialchars($db_prefix); ?></li>
        <li>安装时间: <?php echo date('Y-m-d H:i:s'); ?></li>
    </ul>
</div>

<div class="alert alert-warning">
    <h5>安全提示</h5>
    <p>为了安全起见，请删除或重命名 <code>install</code> 目录。</p>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5>下一步</h5>
    </div>
    <div class="card-body">
        <p>您现在可以：</p>
        <ul>
            <li>访问论坛首页</li>
            <li>使用您创建的管理员账户登录</li>
            <li>开始创建分类和主题</li>
            <li>自定义论坛设置</li>
        </ul>
    </div>
</div>

<div class="install-footer text-center">
    <a href="../index.php" class="btn btn-primary">进入论坛</a>
    <a href="../admin/index.php" class="btn btn-secondary">管理后台</a>
</div>

