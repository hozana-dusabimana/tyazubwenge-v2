<!-- Top Bar -->
<div class="topbar">
    <div class="topbar-content">
        <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <h4 class="topbar-title"><?php echo SITE_NAME; ?></h4>
        <div class="topbar-user">
            <div class="dropdown">
                <a class="dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i> <?php echo $_SESSION['full_name']; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h5 class="sidebar-brand"><?php echo SITE_NAME; ?></h5>
        <button class="sidebar-close" onclick="toggleSidebar()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <nav class="sidebar-nav">
        <ul class="sidebar-menu">
            <li class="menu-item">
                <a href="index.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <?php if (hasPermission('sales')): ?>
            <li class="menu-item">
                <a href="pos.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : ''; ?>">
                    <i class="bi bi-cart-plus"></i>
                    <span>POS</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('products')): ?>
            <li class="menu-item">
                <a href="products.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                    <i class="bi bi-box-seam"></i>
                    <span>Products</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="categories.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                    <i class="bi bi-tags"></i>
                    <span>Categories</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="brands.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'brands.php' ? 'active' : ''; ?>">
                    <i class="bi bi-award"></i>
                    <span>Brands</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('stock')): ?>
            <li class="menu-item">
                <a href="stock.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'stock.php' ? 'active' : ''; ?>">
                    <i class="bi bi-archive"></i>
                    <span>Stock</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="suppliers.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'suppliers.php' ? 'active' : ''; ?>">
                    <i class="bi bi-truck"></i>
                    <span>Suppliers</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('sales')): ?>
            <li class="menu-item">
                <a href="sales.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'active' : ''; ?>">
                    <i class="bi bi-receipt"></i>
                    <span>Sales</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('sales')): ?>
            <li class="menu-item">
                <a href="customers.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    <span>Customers</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('reports')): ?>
            <li class="menu-item">
                <a href="reports.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <i class="bi bi-graph-up"></i>
                    <span>Reports</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasRole('admin')): ?>
            <li class="menu-item">
                <a href="users.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person-gear"></i>
                    <span>Users</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<!-- Sidebar Overlay (for mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

