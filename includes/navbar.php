<?php
// Navigation Bar
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="../dashboard/index.php">
            <i class="fas fa-truck-fast"></i> TransitOps
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" 
                       href="../dashboard/index.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                
                <?php if (in_array($_SESSION['role'], ['Fleet_Manager', 'Safety_Officer'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-truck"></i> Vehicles
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../vehicles/index.php">
                            <i class="fas fa-list"></i> All Vehicles
                        </a></li>
                        <?php if (isAdmin()): ?>
                        <li><a class="dropdown-item" href="../vehicles/create.php">
                            <i class="fas fa-plus"></i> Add Vehicle
                        </a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
                
                <?php if (in_array($_SESSION['role'], ['Fleet_Manager', 'Safety_Officer'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-users"></i> Drivers
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../drivers/index.php">
                            <i class="fas fa-list"></i> All Drivers
                        </a></li>
                        <?php if (isAdmin()): ?>
                        <li><a class="dropdown-item" href="../drivers/create.php">
                            <i class="fas fa-plus"></i> Add Driver
                        </a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
                
                <?php if (in_array($_SESSION['role'], ['Fleet_Manager', 'Driver'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-route"></i> Trips
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../trips/index.php">
                            <i class="fas fa-list"></i> All Trips
                        </a></li>
                        <?php if (isAdmin() || isDriver()): ?>
                        <li><a class="dropdown-item" href="../trips/create.php">
                            <i class="fas fa-plus"></i> Create Trip
                        </a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
                
                <?php if (in_array($_SESSION['role'], ['Fleet_Manager', 'Safety_Officer'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($current_page, 'maintenance') !== false ? 'active' : ''; ?>" 
                       href="../maintenance/index.php">
                        <i class="fas fa-tools"></i> Maintenance
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (in_array($_SESSION['role'], ['Fleet_Manager', 'Financial_Analyst'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($current_page, 'expenses') !== false ? 'active' : ''; ?>" 
                       href="../expenses/index.php">
                        <i class="fas fa-dollar-sign"></i> Expenses
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (in_array($_SESSION['role'], ['Fleet_Manager', 'Financial_Analyst'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($current_page, 'reports') !== false ? 'active' : ''; ?>" 
                       href="../reports/index.php">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle fa-lg"></i>
                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text">
                            <small class="text-muted">Role: <?php echo str_replace('_', ' ', $_SESSION['role']); ?></small>
                        </span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-user"></i> Profile
                        </a></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-cog"></i> Settings
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>