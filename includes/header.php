<?php
// Header file for all pages
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TransitOps - Smart Transport Operations Platform</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --primary-light: #818CF8;
            --primary-gradient: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            --sidebar-bg: #0F172A;
            --sidebar-hover: rgba(255,255,255,0.06);
            --sidebar-active: rgba(79,70,229,0.2);
            --header-bg: #0F172A;
            --header-text: #FFFFFF;
            --header-border: rgba(255,255,255,0.06);
            --footer-bg: #0F172A;
            --footer-text: rgba(255,255,255,0.7);
            --card-shadow: 0 4px 20px rgba(0,0,0,0.06);
            --sidebar-width: 260px;
            --header-height: 70px;
            --radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #F1F5F9;
            overflow-x: hidden;
        }

        /* ============================================
           LOADING SCREEN
        ============================================ */
        #loading-screen {
            position: fixed;
            inset: 0;
            background: var(--primary-gradient);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.8s ease, visibility 0.8s ease;
        }
        #loading-screen.hidden {
            opacity: 0;
            visibility: hidden;
        }
        .loader-spinner {
            width: 56px;
            height: 56px;
            border: 4px solid rgba(255,255,255,0.15);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loader-text {
            color: #fff;
            margin-top: 20px;
            font-size: 1rem;
            letter-spacing: 4px;
            text-transform: uppercase;
            font-weight: 300;
        }

        /* ============================================
           SIDEBAR - DARK THEME
        ============================================ */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            color: #fff;
            z-index: 1001;
            transition: transform 0.3s ease;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-track { background: transparent; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 2px; }

        .sidebar-brand {
            padding: 1.5rem 1.5rem 1.2rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar-brand .brand-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-gradient);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: #fff;
        }
        .sidebar-brand span {
            font-weight: 800;
            font-size: 1.3rem;
            letter-spacing: -0.5px;
            color: #fff;
        }
        .sidebar-brand span.highlight {
            color: var(--primary-light);
        }

        .sidebar-user {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar-user .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            color: #fff;
            flex-shrink: 0;
        }
        .sidebar-user .user-info h6 {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 600;
            color: #fff;
        }
        .sidebar-user .user-info small {
            font-size: 0.7rem;
            opacity: 0.5;
            display: block;
        }

        .sidebar-nav {
            flex: 1;
            padding: 1rem 0.8rem;
        }
        .sidebar-nav .nav-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: rgba(255,255,255,0.25);
            padding: 0.5rem 1rem;
            font-weight: 600;
        }
        .sidebar-nav .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.6rem 1rem;
            border-radius: 8px;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            margin-bottom: 2px;
        }
        .sidebar-nav .nav-item:hover {
            background: var(--sidebar-hover);
            color: #fff;
        }
        .sidebar-nav .nav-item.active {
            background: var(--sidebar-active);
            color: #fff;
        }
        .sidebar-nav .nav-item.active i {
            color: var(--primary-light);
        }
        .sidebar-nav .nav-item i {
            width: 20px;
            font-size: 1rem;
            color: rgba(255,255,255,0.35);
            transition: var(--transition);
        }
        .sidebar-nav .nav-item:hover i {
            color: #fff;
        }
        .sidebar-nav .nav-item .badge-nav {
            margin-left: auto;
            background: var(--primary);
            color: #fff;
            font-size: 0.6rem;
            padding: 0.15rem 0.6rem;
            border-radius: 50px;
        }

        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.06);
        }
        .sidebar-footer .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            color: rgba(255,255,255,0.4);
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.85rem;
        }
        .sidebar-footer .nav-item:hover {
            background: var(--sidebar-hover);
            color: #fff;
        }
        .sidebar-footer .nav-item i {
            width: 20px;
            color: rgba(255,255,255,0.3);
        }

        /* ============================================
           MAIN CONTENT
        ============================================ */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: #F1F5F9;
        }

        /* ============================================
           HEADER - DARK THEME (MATCHING SIDEBAR)
        ============================================ */
        .header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: var(--header-bg);
            padding: 0 2rem;
            height: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--header-border);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .header-left .toggle-sidebar {
            display: none;
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--header-text);
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 8px;
            transition: var(--transition);
        }
        .header-left .toggle-sidebar:hover {
            background: rgba(255,255,255,0.08);
            color: var(--primary-light);
        }
        .header-left .page-title h4 {
            font-weight: 700;
            font-size: 1.2rem;
            margin: 0;
            color: var(--header-text);
        }
        .header-left .page-title small {
            color: rgba(255,255,255,0.5);
            font-size: 0.8rem;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .header-right .search-box {
            position: relative;
            margin-right: 4px;
        }
        .header-right .search-box input {
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 50px;
            font-size: 0.85rem;
            background: rgba(255,255,255,0.06);
            transition: var(--transition);
            width: 220px;
            font-family: 'Inter', sans-serif;
            color: var(--header-text);
        }
        .header-right .search-box input::placeholder {
            color: rgba(255,255,255,0.4);
        }
        .header-right .search-box input:focus {
            border-color: var(--primary-light);
            outline: none;
            box-shadow: 0 0 0 4px rgba(79,70,229,0.15);
            background: rgba(255,255,255,0.1);
            width: 280px;
        }
        .header-right .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.3);
            font-size: 0.9rem;
        }

        .header-right .header-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: transparent;
            color: rgba(255,255,255,0.6);
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            position: relative;
            cursor: pointer;
        }
        .header-right .header-btn:hover {
            background: rgba(255,255,255,0.08);
            color: var(--header-text);
        }
        .header-right .header-btn .badge-dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #EF4444;
            border: 2px solid var(--header-bg);
        }

        .header-right .user-dropdown {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.3rem 0.8rem 0.3rem 0.3rem;
            border-radius: 50px;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.06);
        }
        .header-right .user-dropdown:hover {
            background: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.12);
        }
        .header-right .user-dropdown .avatar-sm {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            color: #fff;
        }
        .header-right .user-dropdown .user-text {
            display: block;
        }
        .header-right .user-dropdown .user-text h6 {
            margin: 0;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--header-text);
            line-height: 1.2;
        }
        .header-right .user-dropdown .user-text small {
            font-size: 0.7rem;
            color: rgba(255,255,255,0.5);
        }
        .header-right .user-dropdown .dropdown-arrow {
            color: rgba(255,255,255,0.3);
            font-size: 0.7rem;
            margin-left: 2px;
        }

        /* Dropdown Menu */
        .dropdown-menu-custom {
            border: none;
            border-radius: var(--radius);
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 0.5rem;
            min-width: 200px;
            background: #1E293B;
        }
        .dropdown-menu-custom .dropdown-item {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.7);
            transition: var(--transition);
        }
        .dropdown-menu-custom .dropdown-item:hover {
            background: rgba(255,255,255,0.06);
            color: #fff;
        }
        .dropdown-menu-custom .dropdown-item i {
            width: 20px;
            color: rgba(255,255,255,0.4);
        }
        .dropdown-menu-custom .dropdown-item:hover i {
            color: #fff;
        }
        .dropdown-menu-custom .dropdown-divider {
            border-color: rgba(255,255,255,0.06);
            margin: 0.3rem 0;
        }
        .dropdown-menu-custom .dropdown-item.text-danger:hover {
            background: rgba(239,68,68,0.1);
            color: #EF4444;
        }
        .dropdown-menu-custom .dropdown-item.text-danger:hover i {
            color: #EF4444;
        }

        /* ============================================
           FOOTER - DARK THEME
        ============================================ */
        .footer-dashboard {
            background: var(--footer-bg);
            padding: 1.2rem 2rem;
            border-top: 1px solid rgba(255,255,255,0.06);
            margin-top: auto;
        }
        .footer-dashboard .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .footer-dashboard .footer-content p {
            margin: 0;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.5);
        }
        .footer-dashboard .footer-content p strong {
            color: rgba(255,255,255,0.8);
        }
        .footer-dashboard .footer-content .footer-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .footer-dashboard .footer-content .footer-links a {
            color: rgba(255,255,255,0.4);
            text-decoration: none;
            font-size: 0.8rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .footer-dashboard .footer-content .footer-links a:hover {
            color: rgba(255,255,255,0.8);
        }
        .footer-dashboard .footer-content .footer-links .heart-link {
            color: #EF4444;
        }
        .footer-dashboard .footer-content .footer-links .heart-link:hover {
            color: #EF4444;
            transform: scale(1.1);
        }

        /* ============================================
           SIDEBAR OVERLAY
        ============================================ */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
        }
        .sidebar-overlay.active {
            display: block;
        }

        /* ============================================
           RESPONSIVE
        ============================================ */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .header-left .toggle-sidebar {
                display: block;
            }
            .header-right .search-box input {
                width: 140px;
            }
            .header-right .search-box input:focus {
                width: 180px;
            }
            .header-right .user-dropdown .user-text {
                display: none;
            }
            .sidebar-overlay.active {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 0 1rem;
            }
            .header-right .search-box input {
                width: 100px;
            }
            .header-right .search-box input:focus {
                width: 140px;
            }
            .header-right .header-btn.hide-mobile {
                display: none;
            }
            .footer-dashboard {
                padding: 1rem;
            }
            .footer-dashboard .footer-content {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 576px) {
            .header-left .page-title h4 {
                font-size: 1rem;
            }
            .header-left .page-title small {
                display: none;
            }
            .header-right .search-box input {
                width: 80px;
                padding: 0.4rem 0.6rem 0.4rem 2rem;
                font-size: 0.75rem;
            }
            .header-right .search-box input:focus {
                width: 120px;
            }
            .header-right .user-dropdown {
                padding: 0.2rem 0.4rem 0.2rem 0.2rem;
            }
            .header-right .user-dropdown .avatar-sm {
                width: 30px;
                height: 30px;
                font-size: 0.7rem;
            }
        }

        /* ============================================
           PAGE CONTENT
        ============================================ */
        .page-content {
            padding: 2rem;
            flex: 1;
        }

        @media (max-width: 768px) {
            .page-content {
                padding: 1rem;
            }
        }

        /* ============================================
           NOTIFICATION DROPDOWN STYLING
        ============================================ */
        .notification-dropdown {
            border: none;
            border-radius: var(--radius);
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 0;
            min-width: 340px;
            background: #1E293B;
        }
        .notification-dropdown .notif-header {
            padding: 1rem 1.2rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notification-dropdown .notif-header h6 {
            margin: 0;
            font-weight: 600;
            color: #fff;
            font-size: 0.9rem;
        }
        .notification-dropdown .notif-header a {
            color: var(--primary-light);
            font-size: 0.75rem;
            text-decoration: none;
        }
        .notification-dropdown .notif-item {
            padding: 0.8rem 1.2rem;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            display: flex;
            gap: 12px;
            transition: var(--transition);
            cursor: pointer;
        }
        .notification-dropdown .notif-item:hover {
            background: rgba(255,255,255,0.04);
        }
        .notification-dropdown .notif-item .notif-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.9rem;
        }
        .notification-dropdown .notif-item .notif-icon.blue { background: rgba(79,70,229,0.15); color: var(--primary-light); }
        .notification-dropdown .notif-item .notif-icon.green { background: rgba(16,185,129,0.15); color: #10B981; }
        .notification-dropdown .notif-item .notif-icon.red { background: rgba(239,68,68,0.15); color: #EF4444; }
        .notification-dropdown .notif-item .notif-text h6 {
            margin: 0;
            font-size: 0.85rem;
            font-weight: 500;
            color: rgba(255,255,255,0.9);
        }
        .notification-dropdown .notif-item .notif-text p {
            margin: 0;
            font-size: 0.75rem;
            color: rgba(255,255,255,0.4);
        }
        .notification-dropdown .notif-footer {
            padding: 0.8rem 1.2rem;
            text-align: center;
        }
        .notification-dropdown .notif-footer a {
            color: var(--primary-light);
            font-size: 0.8rem;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div id="loading-screen">
        <div class="loader-spinner"></div>
        <div class="loader-text">TransitOps</div>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="fas fa-truck-fast"></i>
            </div>
            <span>Transit<span class="highlight">Ops</span></span>
        </div>

        <div class="sidebar-user">
            <div class="avatar"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 2)); ?></div>
            <div class="user-info">
                <h6><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></h6>
                <small><?php echo str_replace('_', ' ', $_SESSION['role'] ?? 'Guest'); ?></small>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-label">Main Menu</div>
            <a href="../dashboard/index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], 'dashboard') !== false ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>

            <?php if (in_array($_SESSION['role'] ?? '', ['Fleet_Manager', 'Safety_Officer'])): ?>
                <a href="../vehicles/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], 'vehicles') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-truck"></i> Vehicles
                </a>
            <?php endif; ?>

            <?php if (in_array($_SESSION['role'] ?? '', ['Fleet_Manager', 'Safety_Officer'])): ?>
                <a href="../drivers/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], 'drivers') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Drivers
                </a>
            <?php endif; ?>

            <?php if (in_array($_SESSION['role'] ?? '', ['Fleet_Manager', 'Driver'])): ?>
                <a href="../trips/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], 'trips') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-route"></i> Trips
                    <span class="badge-nav">New</span>
                </a>
            <?php endif; ?>

            <?php if (in_array($_SESSION['role'] ?? '', ['Fleet_Manager', 'Safety_Officer'])): ?>
                <a href="../maintenance/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], 'maintenance') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-tools"></i> Maintenance
                </a>
            <?php endif; ?>

            <?php if (in_array($_SESSION['role'] ?? '', ['Fleet_Manager', 'Financial_Analyst'])): ?>
                <a href="../expenses/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], 'expenses') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-coins"></i> Expenses
                </a>
            <?php endif; ?>

            <?php if (in_array($_SESSION['role'] ?? '', ['Fleet_Manager', 'Financial_Analyst'])): ?>
                <a href="../reports/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], 'reports') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            <?php endif; ?>

            <div class="nav-label mt-3">Account</div>
            <a href="../profile/index.php" class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], 'profile') !== false ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i> Profile
            </a>
            <a href="../auth/logout.php" class="nav-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="nav-item" style="cursor:default;color:rgba(255,255,255,0.2);font-size:0.7rem;">
                <i class="fas fa-version"></i> v2.0.0
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header - DARK THEME -->
        <header class="header">
            <div class="header-left">
                <button class="toggle-sidebar" id="toggleSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="page-title">
                    <h4 id="pageTitle"><?php echo $page_title ?? 'Dashboard'; ?></h4>
                    <small id="pageSubtitle"><?php echo $page_subtitle ?? 'Overview of your operations'; ?></small>
                </div>
            </div>

            <div class="header-right">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search..." id="globalSearch">
                </div>
                
                <!-- Notification Button with Dropdown -->
                <div class="dropdown">
                    <button class="header-btn hide-mobile" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <span class="badge-dot"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-custom notification-dropdown dropdown-menu-end">
                        <div class="notif-header">
                            <h6>Notifications</h6>
                            <a href="#">Mark all read</a>
                        </div>
                        <div class="notif-item">
                            <div class="notif-icon blue"><i class="fas fa-tools"></i></div>
                            <div class="notif-text">
                                <h6>Maintenance Alert</h6>
                                <p>Vehicle VAN-001 due for service</p>
                            </div>
                        </div>
                        <div class="notif-item">
                            <div class="notif-icon red"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="notif-text">
                                <h6>License Expiring</h6>
                                <p>Driver Mike Johnson's license expires in 5 days</p>
                            </div>
                        </div>
                        <div class="notif-item">
                            <div class="notif-icon green"><i class="fas fa-check-circle"></i></div>
                            <div class="notif-text">
                                <h6>Trip Completed</h6>
                                <p>Trip TRP-20240101-0001 completed</p>
                            </div>
                        </div>
                        <div class="notif-footer">
                            <a href="#">View all notifications</a>
                        </div>
                    </ul>
                </div>
                
                <button class="header-btn hide-mobile" title="Help">
                    <i class="fas fa-question-circle"></i>
                </button>

                <!-- User Dropdown -->
                <div class="dropdown">
                    <a href="#" class="user-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="avatar-sm"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 2)); ?></div>
                        <div class="user-text">
                            <h6><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></h6>
                            <small><?php echo str_replace('_', ' ', $_SESSION['role'] ?? 'Guest'); ?></small>
                        </div>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-custom dropdown-menu-end">
                        <li><a class="dropdown-item" href="../profile/index.php">
                            <i class="fas fa-user-circle"></i> Profile
                        </a></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-cog"></i> Settings
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="page-content">