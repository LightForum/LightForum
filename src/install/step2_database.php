<?php
/**
 * 安装步骤2：数据库配置 - 完全重构版
 * 避免使用MySQL保留字，确保SQL语句安全
 */

// 获取之前保存的数据库配置（如果有）
$db_host = $_SESSION['db_host'] ?? 'localhost';
$db_name = $_SESSION['db_name'] ?? '';
$db_user = $_SESSION['db_user'] ?? '';
$db_pass = $_SESSION['db_pass'] ?? '';
$db_prefix = $_SESSION['db_prefix'] ?? 'forum_';
?>

<h2>数据库配置</h2>
<p class="text-muted">请填写您的MySQL数据库连接信息。如果数据库不存在，安装程序将尝试创建它。</p>

<form method="post" action="index.php?step=2" class="needs-validation" novalidate>
    <div class="mb-3">
        <label for="db_host" class="form-label">数据库主机</label>
        <input type="text" class="form-control" id="db_host" name="db_host" value="<?php echo htmlspecialchars($db_host); ?>" required>
        <div class="form-text">通常为localhost或127.0.0.1</div>
    </div>
    
    <div class="mb-3">
        <label for="db_name" class="form-label">数据库名称</label>
        <input type="text" class="form-control" id="db_name" name="db_name" value="<?php echo htmlspecialchars($db_name); ?>" required>
        <div class="form-text">如果不存在，安装程序将尝试创建</div>
    </div>
    
    <div class="mb-3">
        <label for="db_user" class="form-label">数据库用户名</label>
        <input type="text" class="form-control" id="db_user" name="db_user" value="<?php echo htmlspecialchars($db_user); ?>" required>
    </div>
    
    <div class="mb-3">
        <label for="db_pass" class="form-label">数据库密码</label>
        <input type="password" class="form-control" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($db_pass); ?>">
    </div>
    
    <div class="mb-3">
        <label for="db_prefix" class="form-label">表前缀</label>
        <input type="text" class="form-control" id="db_prefix" name="db_prefix" value="<?php echo htmlspecialchars($db_prefix); ?>">
        <div class="form-text">如果您在同一个数据库中安装多个论坛，可以使用不同的表前缀</div>
    </div>
    
    <div class="install-footer">
        <a href="index.php?step=1" class="btn btn-secondary">上一步</a>
        <button type="submit" class="btn btn-primary">下一步</button>
    </div>
</form>

<script>
// 表单验证
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

