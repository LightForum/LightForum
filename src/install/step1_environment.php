<?php
/**
 * 安装步骤1：环境检测 - 完全重构版
 */

// 检查PHP版本
$php_version = phpversion();
$php_version_ok = version_compare($php_version, '7.4.0', '>=');

// 检查必要的PHP扩展
$extensions = [
    'pdo' => extension_loaded('pdo'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'mbstring' => extension_loaded('mbstring'),
    'json' => extension_loaded('json'),
    'gd' => extension_loaded('gd')
];

// 检查目录权限
$directories = [
    '../config' => is_dir(__DIR__ . '/../config') && is_writable(__DIR__ . '/../config'),
    '../upload' => is_dir(__DIR__ . '/../upload') && is_writable(__DIR__ . '/../upload')
];

// 检查是否所有条件都满足
$all_requirements_met = $php_version_ok && !in_array(false, $extensions) && !in_array(false, $directories);
?>

<h2>环境检测</h2>
<p class="text-muted">安装程序将检查您的服务器环境是否满足运行轻论坛的要求。</p>

<div class="card mb-4">
    <div class="card-header">
        <h5>PHP环境</h5>
    </div>
    <div class="card-body">
        <table class="table">
            <tr>
                <td>PHP版本</td>
                <td><?php echo $php_version; ?></td>
                <td>
                    <?php if ($php_version_ok): ?>
                        <span class="badge bg-success">通过</span>
                    <?php else: ?>
                        <span class="badge bg-danger">不满足</span>
                        <small class="text-danger">需要PHP 7.4.0或更高版本</small>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5>PHP扩展</h5>
    </div>
    <div class="card-body">
        <table class="table">
            <?php foreach ($extensions as $extension => $loaded): ?>
            <tr>
                <td><?php echo $extension; ?></td>
                <td>
                    <?php if ($loaded): ?>
                        <span class="badge bg-success">已安装</span>
                    <?php else: ?>
                        <span class="badge bg-danger">未安装</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5>目录权限</h5>
    </div>
    <div class="card-body">
        <table class="table">
            <?php foreach ($directories as $directory => $writable): ?>
            <tr>
                <td><?php echo $directory; ?></td>
                <td>
                    <?php if ($writable): ?>
                        <span class="badge bg-success">可写</span>
                    <?php else: ?>
                        <span class="badge bg-danger">不可写</span>
                        <small class="text-danger">请确保目录存在并且可写</small>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<div class="install-footer">
    <?php if ($all_requirements_met): ?>
        <form method="post" action="index.php?step=1">
            <button type="submit" class="btn btn-primary">下一步</button>
        </form>
    <?php else: ?>
        <div class="alert alert-warning">
            请解决上述问题后再继续安装。
        </div>
        <button type="button" class="btn btn-secondary" onclick="location.reload()">重新检测</button>
    <?php endif; ?>
</div>

