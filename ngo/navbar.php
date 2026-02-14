<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm py-2">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="ngo_dashboard.php">
            <i class="fas fa-magic me-2 text-primary"></i>
            <span class="fw-bold" style="letter-spacing: 1px;">Heal2Rise</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if (isset($_SESSION["id"])): ?>
                    <li class="nav-item ms-lg-3">
                        <a class="nav-link fw-bold text-dark d-flex align-items-center px-3" href="ngo_dashboard.php">
                            <i class="fas fa-chart-line me-2 text-primary"></i> Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link fw-bold text-dark d-flex align-items-center px-3" href="update_profile.php">
                            <i class="fas fa-user-circle me-2 text-primary"></i> Profile
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link fw-bold text-dark d-flex align-items-center px-3 border-end-lg me-lg-2" href="ngo_cases.php">
                            <i class="fas fa-calendar-check me-2 text-primary"></i> Cases
                        </a>
                    </li>

                    <li class="nav-item ms-2">
                        <a class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold" href="logout.php">
                            Logout
                        </a>
                    </li>

                <?php else: ?>
                    <li class="nav-item ms-lg-3"><a class="nav-link fw-bold" href="login.php">Login</a></li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" href="register.php">Join Now</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
    /* Styling to match the Business Analytics font and feel */
    .navbar-nav .nav-link {
        font-size: 0.9rem;
        transition: color 0.2s ease-in-out;
    }
    .navbar-nav .nav-link:hover {
        color: #0d6efd !important;
    }
    .navbar-nav .nav-link i {
        font-size: 1.1rem; /* Slightly larger icons for better scanability */
    }
    
    @media (max-width: 991px) {
        .navbar-nav .nav-item { width: 100%; padding: 5px 0; }
        .ms-lg-3 { margin-left: 0 !important; }
    }
</style>