            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-5 py-3 border-top" style="background-color: #f8f9fa;">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0 text-muted">
                        <small>&copy; <?php echo date('Y'); ?> Lilayi Park School. All rights reserved.</small>
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0 text-muted">
                        <small>Powered by School Management System v1.0</small>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button id="scrollToTop" class="btn btn-primary" style="display: none; position: fixed; bottom: 30px; right: 30px; z-index: 9999; width: 50px; height: 50px; border-radius: 50%; background-color: #2d5016; border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.3); transition: all 0.3s ease; opacity: 0.9;" title="Back to Top">
        <i class="bi bi-arrow-up" style="font-size: 1.5rem; color: white;"></i>
    </button>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
    <!-- app.js disabled - setFormLoading was disabling form fields before POST submission -->
    <!-- <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script> -->
    
    <!-- Scroll to Top Functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get the button
            const scrollToTopBtn = document.getElementById('scrollToTop');
            
            if (!scrollToTopBtn) return;
            
            // Show button when user scrolls down 200px from the top
            window.addEventListener('scroll', function() {
                if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                    scrollToTopBtn.style.display = 'block';
                } else {
                    scrollToTopBtn.style.display = 'none';
                }
            });
            
            // Scroll to top when button is clicked
            scrollToTopBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            
            // Hover effect
            scrollToTopBtn.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#5cb85c';
                this.style.transform = 'scale(1.1)';
                this.style.opacity = '1';
            });
            
            scrollToTopBtn.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '#2d5016';
                this.style.transform = 'scale(1)';
                this.style.opacity = '0.9';
            });
        });
    </script>
</body>
</html>
