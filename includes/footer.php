<?php
// Shared View: includes/footer.php
// Production-ready footer template with lightbox modal markup and unified JS event listeners

$basePath = (defined('IN_ADMIN_DIR') ? '../' : '');
?>
    <!-- Footer -->
    <footer class="main-footer">
        <div class="container footer-content-wrap">
            <div class="footer-bottom" style="border-top: none; padding-top: 0;">
                <p>&copy; <?php echo date("Y"); ?> Palghar LIVE News. All Rights Reserved. | Developed by Digital Daddy</p>
                <p>Voice of the Public | <a href="<?php echo $basePath; ?>admin/index.php" style="color: rgba(255,255,255,0.4); text-decoration: underline;">Admin Portal</a></p>
            </div>
        </div>
    </footer>

    <!-- Global Image Lightbox Modal Viewer -->
    <div id="image-lightbox-modal" class="lightbox" role="dialog" aria-hidden="true">
        <span class="lightbox-close" id="lightbox-close-btn">&times;</span>
        <div class="lightbox-content-wrap">
            <img class="lightbox-content" id="lightbox-zoom-img" alt="Enlarged View">
            <div class="lightbox-caption" id="lightbox-zoom-caption"></div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?php echo $basePath; ?>assets/js/app_v2.js"></script>
    <script>
        // Separate event registration logic from HTML elements
        document.addEventListener('DOMContentLoaded', () => {
            // Theme toggle registration
            const themeBtn = document.getElementById('theme-toggle');
            if (themeBtn) {
                themeBtn.addEventListener('click', () => {
                    if (typeof toggleTheme === 'function') {
                        toggleTheme();
                    }
                });
            }

            // Mobile menu drawer registration
            const menuToggle = document.getElementById('mobile-menu-toggle');
            if (menuToggle) {
                menuToggle.addEventListener('click', () => {
                    const navMenu = document.getElementById('nav-links-menu');
                    if (navMenu) {
                        navMenu.classList.toggle('active');
                    }
                });
            }

            // Lightbox Modal Close binding
            const closeBtn = document.getElementById('lightbox-close-btn');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    const modal = document.getElementById('image-lightbox-modal');
                    if (modal) {
                        modal.style.display = "none";
                        modal.setAttribute('aria-hidden', 'true');
                    }
                });
            }

            // Lightbox click trigger delegate
            document.body.addEventListener('click', (e) => {
                const imgWrap = e.target.closest('.article-img-wrap img, .media-thumb img, .trending-item img, .section-static-banner img');
                if (imgWrap && !e.target.closest('.media-manager-item')) {
                    const modal = document.getElementById('image-lightbox-modal');
                    const modalImg = document.getElementById('lightbox-zoom-img');
                    const captionText = document.getElementById('lightbox-zoom-caption');
                    
                    if (modal && modalImg) {
                        modal.style.display = "flex";
                        modal.setAttribute('aria-hidden', 'false');
                        modalImg.src = imgWrap.src;
                        if (captionText) {
                            captionText.innerHTML = imgWrap.alt || "Palghar LIVE Image Preview";
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
