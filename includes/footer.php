<?php
// Footer file for all pages
?>
        </div>
        <!-- End Page Content -->

        <!-- Footer -->
        <footer class="footer-dashboard">
            <div class="footer-content">
                <p>
                    <i class="fas fa-copyright" style="font-size:0.7rem;"></i> <?php echo date('Y'); ?> <strong>TransitOps</strong> — Smart Transport Operations Platform
                </p>
                <div class="footer-links">
                    <a href="#"><i class="fas fa-life-ring"></i> Help</a>
                    <a href="#"><i class="fas fa-file-alt"></i> Docs</a>
                    <a href="#"><i class="fas fa-envelope"></i> Support</a>
                    <a href="#" class="heart-link"><i class="fas fa-heart"></i></a>
                </div>
            </div>
        </footer>
    </div>
    <!-- End Main Content -->

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../../assets/js/main.js"></script>
    
    <script>
        // ============================================
        // LOADING SCREEN
        // ============================================
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.getElementById('loading-screen').classList.add('hidden');
            }, 800);
        });

        // ============================================
        // SIDEBAR TOGGLE
        // ============================================
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        });

        document.getElementById('sidebarOverlay')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('active');
        });

        // Close sidebar on window resize (desktop)
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                document.getElementById('sidebar').classList.remove('open');
                document.getElementById('sidebarOverlay').classList.remove('active');
            }
        });

        // ============================================
        // SEARCH BOX
        // ============================================
        document.getElementById('globalSearch')?.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                var query = this.value.trim();
                if (query) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Search Results',
                        text: 'Searching for: "' + query + '"',
                        confirmButtonColor: '#4F46E5'
                    });
                }
            }
        });

        // ============================================
        // NOTIFICATION BUTTON
        // ============================================
        document.querySelector('.header-btn .fa-bell')?.closest('.header-btn')?.addEventListener('click', function() {
            Swal.fire({
                icon: 'info',
                title: 'Notifications',
                html: `
                    <div style="text-align:left;">
                        <div style="padding:8px 0;border-bottom:1px solid #F1F5F9;">
                            <strong>🔧 Maintenance Alert</strong><br>
                            <small style="color:#64748B;">Vehicle VAN-001 due for service</small>
                        </div>
                        <div style="padding:8px 0;border-bottom:1px solid #F1F5F9;">
                            <strong>⚠️ License Expiring</strong><br>
                            <small style="color:#64748B;">Driver Mike Johnson's license expires in 5 days</small>
                        </div>
                        <div style="padding:8px 0;">
                            <strong>✅ Trip Completed</strong><br>
                            <small style="color:#64748B;">Trip TRP-20240101-0001 completed</small>
                        </div>
                    </div>
                `,
                confirmButtonColor: '#4F46E5',
                confirmButtonText: 'View All'
            });
        });

        // ============================================
        // AUTO-HIDE ALERTS
        // ============================================
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);

        // ============================================
        // SIDEBAR NAVIGATION ACTIVE STATE
        // ============================================
        document.querySelectorAll('.sidebar-nav .nav-item').forEach(function(item) {
            item.addEventListener('click', function(e) {
                if (!this.getAttribute('href')) return;
                
                document.querySelectorAll('.sidebar-nav .nav-item').forEach(function(nav) {
                    nav.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>