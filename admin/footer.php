<?php
// Shared Admin Footer: admin/footer.php
// Production-ready shared footer content for the administrative panel
?>
    </div> <!-- Close Dashboard Container -->

    <!-- Global Image Lightbox Modal Viewer -->
    <div id="image-lightbox-modal" class="lightbox" role="dialog" aria-hidden="true">
        <span class="lightbox-close" id="lightbox-close-btn">&times;</span>
        <div class="lightbox-content-wrap">
            <img class="lightbox-content" id="lightbox-zoom-img" alt="Enlarged View">
            <div class="lightbox-caption" id="lightbox-zoom-caption"></div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../assets/js/app_v2.js"></script>
    <script src="../assets/js/admin_v2.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Setup simple close lightbox action
            const closeBtn = document.getElementById('lightbox-close-btn');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    const modal = document.getElementById('image-lightbox-modal');
                    if (modal) modal.style.display = "none";
                });
            }
        });
    </script>
</body>
</html>
