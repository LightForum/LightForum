/**
 * PHP轻论坛 v3.0 JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // 回复功能
    initReplySystem();
    
    // 确认删除
    initConfirmDelete();
    
    // 表单验证
    initFormValidation();
    
    // 通知系统
    initNotifications();
});

/**
 * 初始化回复系统
 */
function initReplySystem() {
    const replyButtons = document.querySelectorAll('.reply-btn');
    const replyForm = document.getElementById('reply-form');
    const replyToInput = document.getElementById('reply_to');
    const replyToInfo = document.getElementById('reply-to-info');
    const replyUsername = document.getElementById('reply-username');
    const cancelReply = document.getElementById('cancel-reply');
    
    if (!replyForm) return;
    
    replyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const username = this.getAttribute('data-username');
            
            replyToInput.value = postId;
            replyUsername.textContent = username;
            replyToInfo.classList.remove('d-none');
            
            // 滚动到回复表单
            replyForm.scrollIntoView({ behavior: 'smooth' });
            
            // 聚焦到文本框
            document.getElementById('content').focus();
        });
    });
    
    if (cancelReply) {
        cancelReply.addEventListener('click', function() {
            replyToInput.value = '';
            replyToInfo.classList.add('d-none');
        });
    }
}

/**
 * 初始化确认删除
 */
function initConfirmDelete() {
    const confirmDelete = document.querySelectorAll('.confirm-delete');
    
    confirmDelete.forEach(function(element) {
        element.addEventListener('click', function(e) {
            if (!confirm('确定要删除吗？此操作不可恢复！')) {
                e.preventDefault();
            }
        });
    });
}

/**
 * 初始化表单验证
 */
function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
}

/**
 * 初始化通知系统
 */
function initNotifications() {
    // 显示通知
    window.showNotification = function(message, type = 'info') {
        const notificationContainer = document.getElementById('notification-container');
        
        if (!notificationContainer) {
            const container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'notification';
            document.body.appendChild(container);
        }
        
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show`;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        document.getElementById('notification-container').appendChild(notification);
        
        // 自动关闭
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 150);
        }, 5000);
    };
}

/**
 * 格式化日期时间
 */
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString();
}

/**
 * 滚动到指定元素
 */
function scrollToElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
}

/**
 * 复制文本到剪贴板
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('复制成功', 'success');
    }).catch(err => {
        showNotification('复制失败: ' + err, 'danger');
    });
}

