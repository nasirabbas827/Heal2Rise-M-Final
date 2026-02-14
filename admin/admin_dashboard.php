<?php
session_start();
include "config.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: adminlogin.php");
    exit();
}

// --- Fetch Real Statistics ---

// 1. Total Clients (Users)
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

// 2. Total Partner NGOs
$total_ngos = $conn->query("SELECT COUNT(*) as count FROM ngos WHERE is_active = 1")->fetch_assoc()['count'];

// 3. Total Active Cases (Not closed or rejected)
$active_cases = $conn->query("SELECT COUNT(*) as count FROM cases WHERE status NOT IN ('closed', 'rejected')")->fetch_assoc()['count'];

// 4. Fetch Recently Joined Clients
$recent_users = $conn->query("SELECT full_name, email, contact_number, created_at, is_active FROM users ORDER BY created_at DESC LIMIT 5");

// 5. Fetch Recent NGO Activity (Optional but helpful)
$recent_ngos = $conn->query("SELECT ngo_name, city, registration_date FROM ngos ORDER BY registration_date DESC LIMIT 3");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Heal2Rise Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style.css">

    <style>
        :root { --sidebar-bg: #212529; --accent-gold: #d4a373; }
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .main-wrapper { padding: 30px; }
        .stat-card { border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: 0.3s; background: #fff; }
        .stat-card:hover { transform: translateY(-5px); }
        .icon-shape { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .bg-soft-gold { background: rgba(212, 163, 115, 0.1); color: var(--accent-gold); }
        .bg-soft-blue { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        .bg-soft-green { background: rgba(25, 135, 84, 0.1); color: #198754; }
        .table-card { border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .status-pill { padding: 4px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 600; }
        .border-dashed { border-style: dashed !important; border-width: 2px !important; }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="main-wrapper container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">System Overview</h2>
            <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Super Admin'); ?></p>
        </div>
        <a href="admin_reports.php" class="btn btn-dark rounded-pill px-4 shadow-sm">
            <i class="fas fa-file-pdf me-2 text-warning"></i> Master Reports
        </a>
    </div>

    <div class="row g-4 mb-5 text-center text-md-start">
        <div class="col-md-4">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center">
                    <div class="icon-shape bg-soft-gold me-3"><i class="fas fa-users"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Total Clients</h6>
                        <h3 class="mb-0 fw-bold"><?= number_format($total_users) ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center">
                    <div class="icon-shape bg-soft-blue me-3"><i class="fas fa-university"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Partner NGOs</h6>
                        <h3 class="mb-0 fw-bold"><?= number_format($total_ngos) ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center">
                    <div class="icon-shape bg-soft-green me-3"><i class="fas fa-briefcase-medical"></i></div>
                    <div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Active Cases</h6>
                        <h3 class="mb-0 fw-bold"><?= number_format($active_cases) ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card table-card">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Recent Registrations</h5>
                    <a href="view_users.php" class="btn btn-link btn-sm text-decoration-none fw-bold text-dark">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4">Full Name</th>
                                    <th>CNIC / Contact</th>
                                    <th>Joined</th>
                                    <th class="text-end pe-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($recent_users->num_rows > 0): ?>
                                    <?php while($row = $recent_users->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($row['email']) ?></div>
                                        </td>
                                        <td>
                                            <span class="small d-block"><?= htmlspecialchars($row['contact_number'] ?: 'No Phone') ?></span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                        <td class="text-end pe-4">
                                            <span class="status-pill <?= $row['is_active'] ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' ?>">
                                                <?= $row['is_active'] ? 'Active' : 'Disabled' ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">No clients in database.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card table-card p-4">
                <h5 class="fw-bold mb-4">Core Controls</h5>
                <div class="d-grid gap-3">
                    <a href="manage_cases.php" class="btn btn-outline-dark text-start p-3 border-dashed">
                        <i class="fas fa-folder-open me-2 text-primary"></i> Case Assignments
                    </a>
                    <a href="view_ngos.php" class="btn btn-outline-dark text-start p-3 border-dashed">
                        <i class="fas fa-handshake me-2 text-warning"></i> Partner Organizations
                    </a>
                    <a href="manage_members.php" class="btn btn-outline-dark text-start p-3 border-dashed">
                        <i class="fas fa-user-tie me-2 text-info"></i> System Team Members
                    </a>
                </div>
                
                <div class="mt-5 p-4 bg-dark text-white rounded-4 shadow-sm">
                    <div class="d-flex align-items-center mb-3">
                        <div class="spinner-grow spinner-grow-sm text-success me-2" role="status"></div>
                        <h6 class="fw-bold mb-0">Portal Status: Live</h6>
                    </div>
                    <hr class="opacity-25">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="small opacity-75">NGO Partners</span>
                        <span class="small fw-bold text-warning"><?= $total_ngos ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="small opacity-75">Open Cases</span>
                        <span class="small fw-bold text-info"><?= $active_cases ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="mt-5 py-4 text-center text-muted small">
    &copy; <?= date('Y') ?> Heal2Rise Admin Portal | v2.0
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>