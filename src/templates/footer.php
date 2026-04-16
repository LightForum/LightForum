    </div><!-- .main-content -->

    <!-- 返回顶部按钮 -->
    <button class="btn btn-primary back-to-top">返回顶部</button>
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo getSetting('site_name', '轻论坛'); ?>. 保留所有权利。</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">Powered by <a href="https://github.com/LightForum/LightForum" target="_blank">LightForum</a></p>
                </div>
            </div>
        </div>
    </footer>
    <script>
        // 返回顶部功能
        const backToTopButton = document.querySelector('.back-to-top');
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopButton.style.display = 'block';
            } else {
                backToTopButton.style.display = 'none';
            }
        });
        backToTopButton.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>

    <script src="https://static.doucdn.org/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>

