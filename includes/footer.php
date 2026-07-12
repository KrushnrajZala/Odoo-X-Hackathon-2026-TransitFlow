<?php
// Footer file for all pages
?>
    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6 text-md-start text-center">
                    <span class="text-muted">
                        &copy; <?php echo date('Y'); ?> <strong>TransitOps</strong> - Smart Transport Operations Platform
                    </span>
                </div>
                <div class="col-md-6 text-md-end text-center">
                    <span class="text-muted">
                        <i class="fas fa-code"></i> Built with <i class="fas fa-heart text-danger"></i> for Odoo Hackathon
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../../assets/js/main.js"></script>
    
    <!-- Hide loading screen -->
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.getElementById('loading-screen').classList.add('hidden');
            }, 500);
        });
    </script>
</body>
</html>