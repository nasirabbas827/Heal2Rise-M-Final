<?php
include('config.php');
session_start();

// Security Check: Must be logged in AND be an NGO
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'ngo') { 
    header("Location: ../login.php"); 
    exit(); 
}

$ngo_id = $_SESSION['id'];
$ngo_name = $_SESSION['full_name'] ?? 'Organization';

// 1. Fetch NGO basic details
$stmt = mysqli_prepare($conn, "SELECT specialties, city, phone FROM ngos WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $ngo_id);
mysqli_stmt_execute($stmt);
$ngo_data = mysqli_stmt_get_result($stmt)->fetch_assoc();

// 2. Fetch Stats: Total Cases, Total Counseling Sessions, and Total Skills under this NGO
// We join with the 'cases' table to filter by ngo_id
$stat_cases = $conn->query("SELECT COUNT(*) as total FROM cases WHERE ngo_id = $ngo_id")->fetch_assoc()['total'];

$stat_sessions = $conn->query("SELECT COUNT(*) as total FROM counseling_sessions cs 
                               JOIN cases c ON cs.case_id = c.case_id 
                               WHERE c.ngo_id = $ngo_id")->fetch_assoc()['total'];

$stat_skills = $conn->query("SELECT COUNT(*) as total FROM skill_development sd 
                             JOIN cases c ON sd.case_id = c.case_id 
                             WHERE c.ngo_id = $ngo_id")->fetch_assoc()['total'];

// 3. Fetch cases assigned to this NGO
$cases_query = mysqli_prepare($conn, "
    SELECT c.case_id, c.case_title, c.status, u.full_name AS user_name, c.assigned_date
    FROM cases c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.ngo_id = ?
    ORDER BY c.assigned_date DESC
");
mysqli_stmt_bind_param($cases_query, "i", $ngo_id);
mysqli_stmt_execute($cases_query);
$cases_result = mysqli_stmt_get_result($cases_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NGO Dashboard | Heal2Rise Partners</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style.css">

    <style>
        body { background-color: #fcfaf8; font-family: 'Inter', sans-serif; }
        .dashboard-header { background: linear-gradient(45deg, #1a1a1a, #333); color: white; padding: 60px 0; border-radius: 0 0 40px 40px; }
        .stat-card { border: none; border-radius: 20px; background: #ffffff; transition: 0.3s; box-shadow: 0 5px 15px rgba(0,0,0,0.02); }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0,0,0,0.08); }
        .icon-box { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; }
        .bg-luxury-gold { background: #fff8e1; color: #ffa000; }
        .bg-luxury-blue { background: #e3f2fd; color: #1976d2; }
        .bg-luxury-green { background: #e8f5e9; color: #388e3c; }
        .table thead { background: #f8f9fa; color: #333; font-size: 0.85rem; text-transform: uppercase; }
        .status-badge { padding: 5px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 600; }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<header class="dashboard-header mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-warning text-dark mb-2">NGO Partner Panel</span>
                <h2 class="display-5 fw-bold" style="font-family: 'Playfair Display';"><?= htmlspecialchars($ngo_name) ?></h2>
                <p class="opacity-75 mb-0">
                    <i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($ngo_data['city'] ?? 'Location not set') ?>
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-4 mt-md-0">
                <a href="update_profile.php" class="btn btn-light rounded-pill px-4 shadow-sm">
                    <i class="fas fa-users me-2"></i>My Profile
                </a>
            </div>
        </div>
    </div>
</header>

<main class="container">
    <div class="row g-4 mb-5 text-center">
        <div class="col-md-4">
            <div class="card stat-card p-4">
                <div class="icon-box bg-luxury-blue mx-auto"><i class="fas fa-folder-open"></i></div>
                <h3 class="fw-bold"><?= $stat_cases ?></h3>
                <p class="text-muted mb-0">Total Cases</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card p-4">
                <div class="icon-box bg-luxury-green mx-auto"><i class="fas fa-user-md"></i></div>
                <h3 class="fw-bold"><?= $stat_sessions ?></h3>
                <p class="text-muted mb-0">Sessions Conducted</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card p-4">
                <div class="icon-box bg-luxury-gold mx-auto"><i class="fas fa-graduation-cap"></i></div>
                <h3 class="fw-bold"><?= $stat_skills ?></h3>
                <p class="text-muted mb-0">Skill Programs</p>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold m-0">Case Monitoring</h4>
                    <span class="text-muted small">Showing latest assignments</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Client Name</th>
                                <th>Case Title</th>
                                <th>Status</th>
                                <th>Assigned Date</th>
                                <th class="text-end">Management Tools</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($cases_result) > 0): ?>
                                <?php while($case = mysqli_fetch_assoc($cases_result)): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($case['user_name']) ?></td>
                                    <td><?= htmlspecialchars($case['case_title']) ?></td>
                                    <td>
                                        <span class="status-badge bg-primary text-white"><?= strtoupper($case['status']) ?></span>
                                    </td>
                                    <td class="small text-muted"><?= date("M d, Y", strtotime($case['assigned_date'])) ?></td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="ngo_case_details.php?case_id=<?= $case['case_id'] ?>" class="btn btn-sm btn-outline-dark" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="manage_counselling_session.php?case_id=<?= $case['case_id'] ?>" class="btn btn-sm btn-outline-dark" title="Counselling">
                                                <i class="fas fa-comments"></i>
                                            </a>
                                            <a href="manage_skills.php?case_id=<?= $case['case_id'] ?>" class="btn btn-sm btn-outline-dark" title="Skill Training">
                                                <i class="fas fa-tools"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No cases currently assigned.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-3">Organization Specialties</h5>
                <div class="d-flex flex-wrap gap-2">
                    <?php 
                    $specs = explode(',', $ngo_data['specialties']);
                    foreach($specs as $s) {
                        echo '<span class="badge bg-light text-dark border px-3 py-2 rounded-pill">'.trim(htmlspecialchars($s)).'</span>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 bg-dark text-white rounded-4 p-4">
                <h6>Need Assistance?</h6>
                <p class="small opacity-75">Contact the central admin team for technical support regarding case assignments.</p>
                <a href="mailto:admin@heal2rise.org" class="btn btn-warning btn-sm rounded-pill w-100 fw-bold">Contact Admin</a>
            </div>
        </div>
    </div>
</main>

<footer class="mt-5 py-4 text-center text-muted small">
    &copy; <?= date('Y') ?> Heal2Rise Partners. All Rights Reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>