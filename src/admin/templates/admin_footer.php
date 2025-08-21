    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- 自定义脚本 -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 确认删除
            const confirmDelete = document.querySelectorAll('.confirm-delete');
            confirmDelete.forEach(function(element) {
                element.addEventListener('click', function(e) {
                    if (!confirm('确定要删除吗？此操作不可恢复！')) {
                        e.preventDefault();
                    }
                });
            });
            
            // 确认操作
            const confirmAction = document.querySelectorAll('.confirm-action');
            confirmAction.forEach(function(element) {
                element.addEventListener('click', function(e) {
                    const message = this.getAttribute('data-confirm-message') || '确定要执行此操作吗？';
                    if (!confirm(message)) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>

