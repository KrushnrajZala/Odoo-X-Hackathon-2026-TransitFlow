<?php
error_reporting(0);
session_start();

// Include database connection
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

// ============================================
// GET REAL STATISTICS FROM DATABASE
// ============================================

// 1. Total Vehicles
$totalVehicles = $db->query("SELECT COUNT(*) as count FROM vehicles")->fetch_assoc()['count'];

// 2. Active Drivers (Available or On Trip)
$activeDrivers = $db->query("SELECT COUNT(*) as count FROM drivers WHERE status IN ('Available', 'On_Trip')")->fetch_assoc()['count'];

// 3. Completed Trips
$completedTrips = $db->query("SELECT COUNT(*) as count FROM trips WHERE status = 'Completed'")->fetch_assoc()['count'];

// 4. Fleet Efficiency (Utilization)
$totalVehiclesCount = $db->query("SELECT COUNT(*) as count FROM vehicles")->fetch_assoc()['count'];
$activeVehicles = $db->query("SELECT COUNT(*) as count FROM vehicles WHERE status IN ('Available', 'On_Trip')")->fetch_assoc()['count'];
$fleetEfficiency = $totalVehiclesCount > 0 ? round(($activeVehicles / $totalVehiclesCount) * 100) : 0;

// 5. Total Drivers
$totalDrivers = $db->query("SELECT COUNT(*) as count FROM drivers")->fetch_assoc()['count'];

// 6. Total Trips
$totalTrips = $db->query("SELECT COUNT(*) as count FROM trips")->fetch_assoc()['count'];

// 7. Active Trips (Dispatched)
$activeTrips = $db->query("SELECT COUNT(*) as count FROM trips WHERE status = 'Dispatched'")->fetch_assoc()['count'];

// 8. Vehicles in Maintenance
$maintenanceVehicles = $db->query("SELECT COUNT(*) as count FROM vehicles WHERE status = 'In_Shop'")->fetch_assoc()['count'];

// 9. Available Vehicles
$availableVehicles = $db->query("SELECT COUNT(*) as count FROM vehicles WHERE status = 'Available'")->fetch_assoc()['count'];

// 10. Total Revenue (from completed trips - $100 per trip)
$totalRevenue = $completedTrips * 100;

// 11. Total Expenses
$totalExpenses = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses")->fetch_assoc()['total'];
$totalMaintenance = $db->query("SELECT COALESCE(SUM(cost), 0) as total FROM maintenance_logs")->fetch_assoc()['total'];
$totalOperationalCost = $totalExpenses + $totalMaintenance;

// 12. Profit
$totalProfit = $totalRevenue - $totalOperationalCost;

// 13. Total Acquisition Cost
$totalAcquisitionCost = $db->query("SELECT COALESCE(SUM(acquisition_cost), 0) as total FROM vehicles")->fetch_assoc()['total'];

// 14. Overall ROI
$overallROI = $totalAcquisitionCost > 0 ? round(($totalProfit / $totalAcquisitionCost) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TransitOps - Smart Transport Operations Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* ============================================
           CSS VARIABLES - ENHANCED COLOR PALETTE
        ============================================ */
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --primary-light: #818CF8;
            --primary-gradient: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            --primary-gradient-soft: linear-gradient(135deg, #EEF2FF 0%, #F5F3FF 100%);
            
            --secondary: #7C3AED;
            --secondary-light: #A78BFA;
            
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            
            --dark: #0F172A;
            --dark-secondary: #1E293B;
            --gray: #64748B;
            --gray-light: #94A3B8;
            --gray-lighter: #E2E8F0;
            --light: #F8FAFC;
            --white: #FFFFFF;
            
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-md: 0 10px 15px -3px rgba(0,0,0,0.1);
            --shadow-lg: 0 20px 25px -5px rgba(0,0,0,0.1);
            --shadow-xl: 0 25px 50px -12px rgba(0,0,0,0.25);
            --shadow-2xl: 0 35px 60px -15px rgba(79,70,229,0.2);
            
            --radius: 12px;
            --radius-lg: 20px;
            --radius-xl: 28px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--white);
            color: var(--dark);
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
           NAVBAR
        ============================================ */
        .navbar-custom {
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: 0.75rem 0;
            transition: var(--transition);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            border-bottom: 1px solid rgba(0,0,0,0.04);
        }
        .navbar-custom.scrolled {
            box-shadow: var(--shadow-md);
        }
        .navbar-brand {
            font-weight: 800;
            font-size: 1.4rem;
            color: var(--dark) !important;
            letter-spacing: -0.5px;
        }
        .navbar-brand .brand-icon {
            color: var(--primary);
        }
        .navbar-brand span {
            color: var(--primary);
        }
        .nav-link {
            font-weight: 500;
            color: var(--dark) !important;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: var(--transition);
            font-size: 0.9rem;
        }
        .nav-link:hover {
            background: rgba(79,70,229,0.08);
            color: var(--primary) !important;
        }
        .nav-link.btn-primary-nav {
            background: var(--primary-gradient);
            color: #fff !important;
            padding: 0.5rem 1.6rem !important;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(79,70,229,0.25);
        }
        .nav-link.btn-primary-nav:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79,70,229,0.35);
            color: #fff !important;
        }
        .nav-link.btn-outline-nav {
            border: 2px solid var(--primary);
            color: var(--primary) !important;
            padding: 0.5rem 1.6rem !important;
            border-radius: 50px;
            font-weight: 600;
            transition: var(--transition);
        }
        .nav-link.btn-outline-nav:hover {
            background: var(--primary-gradient);
            color: #fff !important;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79,70,229,0.3);
        }

        /* ============================================
           HERO SECTION - ENHANCED
        ============================================ */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 120px 0 80px;
            background: var(--primary-gradient-soft);
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -10%;
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(79,70,229,0.06) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(124,58,237,0.04) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(79,70,229,0.1);
            color: var(--primary);
            padding: 0.4rem 1.2rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            letter-spacing: 0.3px;
        }
        .hero-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--success);
            animation: pulse-dot 2s infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .hero-title {
            font-size: 4rem;
            font-weight: 900;
            line-height: 1.08;
            margin-bottom: 1.5rem;
            letter-spacing: -1.5px;
            color: var(--dark);
        }
        .hero-title .gradient-text {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-subtitle {
            font-size: 1.15rem;
            color: var(--gray);
            line-height: 1.8;
            max-width: 480px;
            margin-bottom: 2rem;
        }
        .hero-stats {
            display: flex;
            gap: 2.5rem;
            margin-top: 2.5rem;
        }
        .hero-stats .stat-item h3 {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.2rem;
        }
        .hero-stats .stat-item h3 .stat-number {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-stats .stat-item p {
            color: var(--gray);
            font-size: 0.85rem;
            margin: 0;
        }
        .btn-hero {
            padding: 0.85rem 2.2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition);
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-hero-primary {
            background: var(--primary-gradient);
            color: #fff;
            box-shadow: 0 8px 30px rgba(79,70,229,0.3);
        }
        .btn-hero-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(79,70,229,0.4);
            color: #fff;
        }
        .btn-hero-secondary {
            background: var(--white);
            color: var(--dark);
            border: 2px solid var(--gray-lighter);
        }
        .btn-hero-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }
        .btn-hero-white {
            background: var(--white);
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        .btn-hero-white:hover {
            background: var(--primary-gradient);
            color: #fff;
            border-color: transparent;
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(79,70,229,0.3);
        }
        .hero-image {
            position: relative;
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-16px); }
        }
        .hero-image .dashboard-mockup {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-2xl);
            padding: 1.5rem;
            border: 1px solid rgba(0,0,0,0.04);
        }
        .hero-image .dashboard-mockup img {
            width: 100%;
            border-radius: var(--radius);
        }
        .floating-card {
            position: absolute;
            background: var(--white);
            padding: 0.8rem 1.2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: float 4s ease-in-out infinite;
        }
        .floating-card.card-1 {
            top: -12px;
            right: -20px;
            animation-delay: 0s;
            border-left: 4px solid var(--success);
        }
        .floating-card.card-2 {
            bottom: 20px;
            left: -30px;
            animation-delay: 2s;
            border-left: 4px solid var(--primary);
        }
        .floating-card .icon-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: #fff;
        }
        .floating-card .icon-circle.green { background: var(--success); }
        .floating-card .icon-circle.blue { background: var(--primary); }
        .floating-card .text h6 { font-weight: 600; margin: 0; font-size: 0.85rem; color: var(--dark); }
        .floating-card .text p { margin: 0; font-size: 0.7rem; color: var(--gray); }

        /* ============================================
           FEATURES SECTION - ENHANCED
        ============================================ */
        .features {
            padding: 80px 0;
            background: var(--white);
        }
        .section-header {
            text-align: center;
            margin-bottom: 3.5rem;
        }
        .section-header .tag {
            display: inline-block;
            background: rgba(79,70,229,0.08);
            color: var(--primary);
            padding: 0.3rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.8rem;
        }
        .section-header h2 {
            font-size: 2.6rem;
            font-weight: 800;
            letter-spacing: -1px;
            color: var(--dark);
        }
        .section-header h2 .highlight {
            color: var(--primary);
        }
        .section-header p {
            color: var(--gray);
            font-size: 1.05rem;
            max-width: 560px;
            margin: 0 auto;
        }
        .feature-card {
            background: var(--white);
            padding: 2rem 1.8rem;
            border-radius: var(--radius);
            border: 1px solid var(--gray-lighter);
            transition: var(--transition);
            height: 100%;
            position: relative;
        }
        .feature-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-lg);
            transform: translateY(-6px);
        }
        .feature-card .icon-box {
            width: 52px;
            height: 52px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #fff;
            margin-bottom: 1rem;
            background: var(--primary-gradient);
        }
        .feature-card .icon-box.green { background: linear-gradient(135deg, #10B981, #059669); }
        .feature-card .icon-box.orange { background: linear-gradient(135deg, #F59E0B, #D97706); }
        .feature-card .icon-box.purple { background: linear-gradient(135deg, #7C3AED, #6D28D9); }
        .feature-card .icon-box.pink { background: linear-gradient(135deg, #EC4899, #DB2777); }
        .feature-card .icon-box.cyan { background: linear-gradient(135deg, #06B6D4, #0891B2); }
        .feature-card h5 {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--dark);
            margin-bottom: 0.6rem;
        }
        .feature-card p {
            color: var(--gray);
            font-size: 0.9rem;
            line-height: 1.7;
            margin: 0;
        }
        .feature-card .learn-more {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--primary);
            font-weight: 600;
            font-size: 0.85rem;
            margin-top: 1rem;
            text-decoration: none;
            transition: var(--transition);
        }
        .feature-card .learn-more:hover {
            gap: 12px;
            color: var(--primary-dark);
        }

        /* ============================================
           STATS SECTION - ENHANCED
        ============================================ */
        .stats-section {
            padding: 70px 0;
            background: var(--primary-gradient);
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .stats-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.03);
            border-radius: 50%;
        }
        .stats-section .stat-item h2 {
            font-size: 3rem;
            font-weight: 900;
            letter-spacing: -1px;
        }
        .stats-section .stat-item p {
            font-size: 1rem;
            opacity: 0.8;
            margin: 0;
        }

        /* ============================================
           TESTIMONIALS
        ============================================ */
        .testimonials {
            padding: 80px 0;
            background: var(--light);
        }
        .testimonial-card {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--radius);
            border: 1px solid var(--gray-lighter);
            transition: var(--transition);
            height: 100%;
            text-align: center;
        }
        .testimonial-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }
        .testimonial-card .quote-icon {
            font-size: 2.5rem;
            color: var(--primary);
            opacity: 0.2;
            margin-bottom: 0.5rem;
        }
        .testimonial-card p {
            font-style: italic;
            font-size: 0.95rem;
            color: var(--dark-secondary);
            line-height: 1.7;
        }
        .testimonial-card .avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
            margin: 1rem auto 0.5rem;
        }
        .testimonial-card h6 { font-weight: 600; margin: 0; color: var(--dark); }
        .testimonial-card small { color: var(--gray); }

        /* ============================================
           CTA SECTION - ENHANCED
        ============================================ */
        .cta-section {
            padding: 70px 0;
            background: var(--dark);
            color: #fff;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -20%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(79,70,229,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }
        .cta-section h2 {
            font-size: 2.6rem;
            font-weight: 800;
            letter-spacing: -1px;
            position: relative;
            z-index: 1;
        }
        .cta-section p {
            font-size: 1.1rem;
            opacity: 0.7;
            max-width: 520px;
            margin: 0 auto 2rem;
            position: relative;
            z-index: 1;
        }
        .btn-cta {
            padding: 0.85rem 2.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            position: relative;
            z-index: 1;
        }
        .btn-cta-white {
            background: var(--white);
            color: var(--primary);
            box-shadow: 0 8px 30px rgba(255,255,255,0.1);
        }
        .btn-cta-white:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(255,255,255,0.2);
            color: var(--primary);
        }
        .btn-cta-outline {
            background: transparent;
            color: #fff;
            border: 2px solid rgba(255,255,255,0.2);
        }
        .btn-cta-outline:hover {
            border-color: #fff;
            transform: translateY(-3px);
        }

        /* ============================================
           FOOTER
        ============================================ */
        .footer {
            background: var(--dark-secondary);
            color: rgba(255,255,255,0.7);
            padding: 50px 0 30px;
        }
        .footer h5 {
            color: #fff;
            font-weight: 700;
            margin-bottom: 1.2rem;
            font-size: 0.95rem;
        }
        .footer a {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            transition: var(--transition);
            display: block;
            padding: 0.3rem 0;
            font-size: 0.9rem;
        }
        .footer a:hover {
            color: #fff;
            transform: translateX(4px);
        }
        .footer .social-links a {
            display: inline-block;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
            text-align: center;
            line-height: 38px;
            margin-right: 8px;
            transition: var(--transition);
        }
        .footer .social-links a:hover {
            background: var(--primary);
            transform: translateY(-3px);
            color: #fff;
        }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.06);
            padding-top: 20px;
            margin-top: 30px;
            text-align: center;
            font-size: 0.85rem;
        }
        .footer-bottom .heart {
            color: var(--danger);
        }

        /* ============================================
           RESPONSIVE
        ============================================ */
        @media (max-width: 992px) {
            .hero-title { font-size: 3rem; }
            .hero-stats { gap: 1.5rem; flex-wrap: wrap; }
            .floating-card { display: none; }
        }
        @media (max-width: 768px) {
            .hero { padding: 100px 0 50px; text-align: center; }
            .hero-title { font-size: 2.2rem; }
            .hero-subtitle { margin: 0 auto 1.5rem; }
            .hero-stats { justify-content: center; }
            .section-header h2 { font-size: 2rem; }
            .stats-section .stat-item h2 { font-size: 2.2rem; }
            .cta-section h2 { font-size: 2rem; }
        }
        @media (max-width: 576px) {
            .hero-title { font-size: 1.8rem; }
            .btn-hero { padding: 0.6rem 1.4rem; font-size: 0.85rem; }
            .feature-card { padding: 1.5rem; }
        }

        /* ============================================
           ANIMATIONS
        ============================================ */
        .fade-up {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
        .fade-up.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .delay-1 { transition-delay: 0.1s; }
        .delay-2 { transition-delay: 0.2s; }
        .delay-3 { transition-delay: 0.3s; }
        .delay-4 { transition-delay: 0.4s; }
    </style>
</head>
<body>

    <!-- LOADING SCREEN -->
    <div id="loading-screen">
        <div class="loader-spinner"></div>
        <div class="loader-text">TransitOps</div>
    </div>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-custom" id="navbar">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-truck-fast brand-icon"></i> Transit<span>Ops</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#stats">Stats</a></li>
                    <li class="nav-item"><a class="nav-link" href="#testimonials">Testimonials</a></li>
                    <li class="nav-item">
                        <a class="nav-link btn-primary-nav" href="modules/auth/login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn-outline-nav" href="modules/auth/login.php">
                            Get Started
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero" id="home">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-badge">
                        <span class="dot"></span>
                        Next-Gen Transport Management
                    </div>
                    <h1 class="hero-title">
                        Smart Transport<br>
                        <span class="gradient-text">Operations Platform</span>
                    </h1>
                    <p class="hero-subtitle">
                        Digitize your entire fleet operations — from vehicle registration and driver management 
                        to dispatching, maintenance, and real-time analytics.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="modules/auth/login.php" class="btn-hero btn-hero-primary">
                            <i class="fas fa-rocket"></i> Get Started
                        </a>
                        <a href="#features" class="btn-hero btn-hero-secondary">
                            <i class="fas fa-play-circle"></i> Learn More
                        </a>
                    </div>
                    <!-- REAL STATS FROM DATABASE -->
                    <div class="hero-stats">
                        <div class="stat-item">
                            <h3><span class="stat-number" id="statEfficiency"><?php echo $fleetEfficiency; ?></span>%</h3>
                            <p>Fleet Efficiency</p>
                        </div>
                        <div class="stat-item">
                            <h3><span class="stat-number" id="statVehicles"><?php echo $totalVehicles; ?></span></h3>
                            <p>Vehicles Managed</p>
                        </div>
                        <div class="stat-item">
                            <h3><span class="stat-number" id="statDrivers"><?php echo $activeDrivers; ?></span></h3>
                            <p>Active Drivers</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mt-5 mt-lg-0">
                    <div class="hero-image">
                        <div class="dashboard-mockup">
                            <svg width="100%" height="320" viewBox="0 0 600 320" style="border-radius:12px;">
                                <rect width="600" height="320" fill="#F8FAFC" rx="12"/>
                                <rect x="20" y="20" width="560" height="36" rx="8" fill="#E2E8F0"/>
                                <!-- Stats Cards -->
                                <rect x="20" y="72" width="130" height="70" rx="8" fill="#4F46E5" opacity="0.12"/>
                                <text x="50" y="100" font-size="14" font-weight="700" fill="#0F172A"><?php echo $totalVehicles; ?></text>
                                <text x="50" y="125" font-size="10" fill="#64748B">Vehicles</text>
                                
                                <rect x="170" y="72" width="130" height="70" rx="8" fill="#10B981" opacity="0.12"/>
                                <text x="210" y="100" font-size="14" font-weight="700" fill="#0F172A"><?php echo $activeDrivers; ?></text>
                                <text x="210" y="125" font-size="10" fill="#64748B">Drivers</text>
                                
                                <rect x="320" y="72" width="130" height="70" rx="8" fill="#F59E0B" opacity="0.12"/>
                                <text x="370" y="100" font-size="14" font-weight="700" fill="#0F172A"><?php echo $activeTrips; ?></text>
                                <text x="370" y="125" font-size="10" fill="#64748B">Active Trips</text>
                                
                                <rect x="470" y="72" width="110" height="70" rx="8" fill="#818CF8" opacity="0.12"/>
                                <text x="510" y="100" font-size="14" font-weight="700" fill="#0F172A"><?php echo $fleetEfficiency; ?>%</text>
                                <text x="510" y="125" font-size="10" fill="#64748B">Efficiency</text>
                                
                                <!-- Recent Trips List -->
                                <rect x="20" y="160" width="560" height="110" rx="8" fill="#E2E8F0" opacity="0.5"/>
                                <text x="40" y="185" font-size="11" font-weight="600" fill="#0F172A">Recent Trips</text>
                                <?php
                                $recentTrips = $db->query("SELECT trip_number, status FROM trips ORDER BY created_at DESC LIMIT 3");
                                $y = 205;
                                while ($trip = $recentTrips->fetch_assoc()):
                                ?>
                                    <circle cx="50" cy="<?php echo $y; ?>" r="8" fill="<?php echo $trip['status'] == 'Completed' ? '#10B981' : ($trip['status'] == 'Dispatched' ? '#F59E0B' : '#94A3B8'); ?>"/>
                                    <text x="70" y="<?php echo $y + 4; ?>" font-size="10" fill="#0F172A"><?php echo $trip['trip_number']; ?></text>
                                    <text x="170" y="<?php echo $y + 4; ?>" font-size="10" fill="#64748B"><?php echo $trip['status']; ?></text>
                                <?php 
                                $y += 30;
                                endwhile; 
                                ?>
                            </svg>
                        </div>
                        <div class="floating-card card-1">
                            <div class="icon-circle green"><i class="fas fa-check"></i></div>
                            <div class="text">
                                <h6>Active Fleet</h6>
                                <p><?php echo $activeVehicles; ?> vehicles online</p>
                            </div>
                        </div>
                        <div class="floating-card card-2">
                            <div class="icon-circle blue"><i class="fas fa-chart-line"></i></div>
                            <div class="text">
                                <h6>Utilization</h6>
                                <p><?php echo $fleetEfficiency; ?>% efficiency</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <span class="tag"><i class="fas fa-star"></i> Features</span>
                <h2>Everything You Need to <span class="highlight">Manage Your Fleet</span></h2>
                <p>From vehicle registration to real-time analytics — all in one powerful platform.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card fade-up delay-1">
                        <div class="icon-box"><i class="fas fa-truck"></i></div>
                        <h5>Vehicle Management</h5>
                        <p>Register, track, and manage your entire fleet with real-time status updates and maintenance scheduling.</p>
                        <a href="#" class="learn-more">Learn More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card fade-up delay-2">
                        <div class="icon-box green"><i class="fas fa-users"></i></div>
                        <h5>Driver Management</h5>
                        <p>Maintain driver profiles, track licenses, monitor safety scores, and ensure compliance at all times.</p>
                        <a href="#" class="learn-more">Learn More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card fade-up delay-3">
                        <div class="icon-box orange"><i class="fas fa-route"></i></div>
                        <h5>Trip Management</h5>
                        <p>Create, dispatch, and track trips with automated status transitions and real-time progress monitoring.</p>
                        <a href="#" class="learn-more">Learn More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card fade-up delay-2">
                        <div class="icon-box purple"><i class="fas fa-tools"></i></div>
                        <h5>Maintenance Tracking</h5>
                        <p>Schedule and track vehicle maintenance with automated status updates and service reminders.</p>
                        <a href="#" class="learn-more">Learn More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card fade-up delay-3">
                        <div class="icon-box pink"><i class="fas fa-coins"></i></div>
                        <h5>Expense Management</h5>
                        <p>Track fuel costs, tolls, and operational expenses with detailed reporting and analytics.</p>
                        <a href="#" class="learn-more">Learn More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card fade-up delay-4">
                        <div class="icon-box cyan"><i class="fas fa-chart-bar"></i></div>
                        <h5>Advanced Analytics</h5>
                        <p>Gain actionable insights with real-time dashboards, ROI analysis, and performance metrics.</p>
                        <a href="#" class="learn-more">Learn More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- STATS SECTION - REAL DATA -->
    <section class="stats-section" id="stats">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 stat-item fade-up delay-1">
                    <h2 id="statTotalVehicles"><?php echo $totalVehicles; ?></h2>
                    <p>Total Vehicles</p>
                </div>
                <div class="col-md-3 stat-item fade-up delay-2">
                    <h2 id="statTotalDrivers"><?php echo $totalDrivers; ?></h2>
                    <p>Total Drivers</p>
                </div>
                <div class="col-md-3 stat-item fade-up delay-3">
                    <h2 id="statCompletedTrips"><?php echo $completedTrips; ?></h2>
                    <p>Completed Trips</p>
                </div>
                <div class="col-md-3 stat-item fade-up delay-4">
                    <h2 id="statFleetEfficiency"><?php echo $fleetEfficiency; ?>%</h2>
                    <p>Fleet Efficiency</p>
                </div>
            </div>
        </div>
    </section>

    <!-- TESTIMONIALS -->
    <section class="testimonials" id="testimonials">
        <div class="container">
            <div class="section-header">
                <span class="tag"><i class="fas fa-quote-left"></i> Testimonials</span>
                <h2>What Our <span class="highlight">Users Say</span></h2>
                <p>Trusted by fleet managers and logistics professionals worldwide.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="testimonial-card fade-up delay-1">
                        <div class="quote-icon">"</div>
                        <p>TransitOps transformed our fleet operations. We reduced downtime by 40% and improved efficiency dramatically.</p>
                        <div class="avatar">JS</div>
                        <h6>John Smith</h6>
                        <small>Fleet Manager, Logistics Inc.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card fade-up delay-2">
                        <div class="quote-icon">"</div>
                        <p>The real-time dashboard gives us complete visibility into our operations. It's a game-changer for our business.</p>
                        <div class="avatar">SW</div>
                        <h6>Sarah Wilson</h6>
                        <small>Safety Officer, Transport Co.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card fade-up delay-3">
                        <div class="quote-icon">"</div>
                        <p>Finally a platform that understands logistics. The maintenance tracking and expense management are outstanding.</p>
                        <div class="avatar">RB</div>
                        <h6>Robert Brown</h6>
                        <small>Financial Analyst, Fleet Corp.</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Transform Your Fleet?</h2>
            <p>Join thousands of logistics professionals using TransitOps to streamline their operations.</p>
            <div class="d-flex flex-wrap gap-3 justify-content-center">
                <a href="modules/auth/login.php" class="btn-cta btn-cta-white">
                    <i class="fas fa-rocket"></i> Get Started
                </a>
                <a href="modules/auth/login.php" class="btn-cta btn-cta-outline">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-truck-fast" style="color:var(--primary-light);"></i> TransitOps</h5>
                    <p style="margin-top:0.5rem;font-size:0.9rem;">Smart Transport Operations Platform. Digitizing fleet management for the modern logistics industry.</p>
                    <div class="social-links mt-3">
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-github"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-md-2">
                    <h5>Product</h5>
                    <a href="#features">Features</a>
                    <a href="#">Pricing</a>
                    <a href="#">Integrations</a>
                    <a href="#">Changelog</a>
                </div>
                <div class="col-md-2">
                    <h5>Company</h5>
                    <a href="#">About</a>
                    <a href="#">Blog</a>
                    <a href="#">Careers</a>
                    <a href="#">Contact</a>
                </div>
                <div class="col-md-2">
                    <h5>Support</h5>
                    <a href="#">Help Center</a>
                    <a href="#">Documentation</a>
                    <a href="#">API Reference</a>
                    <a href="#">Status</a>
                </div>
                <div class="col-md-2">
                    <h5>Legal</h5>
                    <a href="#">Privacy</a>
                    <a href="#">Terms</a>
                    <a href="#">Security</a>
                    <a href="#">Cookies</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> TransitOps. All rights reserved. Built with <span class="heart">♥</span> for the Odoo Hackathon.</p>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // LOADING SCREEN
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.getElementById('loading-screen').classList.add('hidden');
            }, 1200);
        });

        // NAVBAR SCROLL
        $(window).scroll(function() {
            if ($(this).scrollTop() > 50) {
                $('#navbar').addClass('scrolled');
            } else {
                $('#navbar').removeClass('scrolled');
            }
        });

        // FADE UP ON SCROLL
        const fadeElements = document.querySelectorAll('.fade-up');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });
        fadeElements.forEach(el => observer.observe(el));

        // SMOOTH SCROLL
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // REDIRECT LOGGED IN USERS
        <?php if (isset($_SESSION['user_id'])): ?>
            window.location.href = 'modules/dashboard/index.php';
        <?php endif; ?>
    </script>
</body>
</html>