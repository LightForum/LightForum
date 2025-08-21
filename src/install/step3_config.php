<?php
/**
 * 安装步骤3：系统设置 - 完全重构版
 * 避免使用MySQL保留字，确保SQL语句安全
 */

// 获取之前保存的系统设置（如果有）
$site_name = $_SESSION['site_name'] ?? 'PHP轻论坛';
$site_description = $_SESSION['site_description'] ?? '一个简单易用的PHP论坛程序';
$admin_username = $_SESSION['admin_username'] ?? '';
$admin_password = $_SESSION['admin_password'] ?? '';
$admin_email = $_SESSION['admin_email'] ?? '';
?>

<h2>系统设置</h2>
<p class="text-muted">请设置您的论坛基本信息和管理员账户。</p>

<form method="post" action="index.php?step=3" class="needs-validation" novalidate>
    <div class="card mb-4">
        <div class="card-header">
            <h5>论坛信息</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="site_name" class="form-label">论坛名称</label>
                <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="site_description" class="form-label">论坛描述</label>
                <textarea class="form-control" id="site_description" name="site_description" rows="2"><?php echo htmlspecialchars($site_description); ?></textarea>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5>管理员账户</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="admin_username" class="form-label">用户名</label>
                <input type="text" class="form-control" id="admin_username" name="admin_username" value="<?php echo htmlspecialchars($admin_username); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="admin_password" class="form-label">密码</label>
                <input type="password" class="form-control" id="admin_password" name="admin_password" value="<?php echo htmlspecialchars($admin_password); ?>" required minlength="6">
                <div class="form-text">密码至少需要6个字符</div>
            </div>
            
            <div class="mb-3">
                <label for="admin_email" class="form-label">电子邮件</label>
                <input type="email" class="form-control" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($admin_email); ?>" required>
            </div>
        </div>
    </div>
    
    <div class="install-footer">
        <a href="index.php?step=2" class="btn btn-secondary">上一步</a>
        <button type="submit" class="btn btn-primary">完成安装</button>
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

