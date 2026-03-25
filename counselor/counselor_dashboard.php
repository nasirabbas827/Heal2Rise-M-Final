<?php
include('../config.php');
session_start();

// Security Check: Must be logged in as a Counselor
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'counselor') { 
    header("Location: ../login.php"); 
    exit(); 
}

$counselor_id = $_SESSION['id'];
$full_name = $_SESSION['full_name'] ?? 'Counselor';

// 1. Fetch Total Assigned Cases (Active)
$stmt_active = $conn->prepare("SELECT COUNT(*) as total FROM cases WHERE counselor_id = ? AND status != 'closed'");
$stmt_active->bind_param("i", $counselor_id);
$stmt_active->execute();
$active_cases_count = $stmt_active->get_result()->fetch_assoc()['total'];
$stmt_active->close();

// 2. Fetch Total Sessions Conducted by this Counselor
$stmt_sess = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM counseling_sessions cs 
    JOIN cases c ON cs.case_id = c.case_id 
    WHERE c.counselor_id = ?
");
$stmt_sess->bind_param("i", $counselor_id);
$stmt_sess->execute();
$total_sessions = $stmt_sess->get_result()->fetch_assoc()['total'];
$stmt_sess->close();

// 3. Fetch Assigned Patients/Cases
$cases_query = $conn->prepare("
    SELECT c.*, u.full_name as patient_name, u.email as patient_email 
    FROM cases c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.counselor_id = ? 
    ORDER BY c.last_updated DESC
");
$cases_query->bind_param("i", $counselor_id);
$cases_query->execute();
$cases_result = $cases_query->get_result();

// 4. Fetch Recent Counseling Logs
$logs_query = $conn->prepare("
    SELECT cs.*, c.case_title, u.full_name as patient_name 
    FROM counseling_sessions cs 
    JOIN cases c ON cs.case_id = c.case_id 
    JOIN users u ON c.user_id = u.id
    WHERE c.counselor_id = ? 
    ORDER BY cs.session_date DESC LIMIT 5
");
$logs_query->bind_param("i", $counselor_id);
$logs_query->execute();
$logs_result = $logs_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counselor Dashboard | Heal2Rise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .dashboard-header { background: #2c3e50; color: white; padding: 50px 0; border-radius: 0 0 40px 40px; }
        .stat-card { border: none; border-radius: 20px; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .patient-card { border: none; border-radius: 15px; background: white; border-left: 5px solid #198754; position: relative; }
        .status-pill { font-size: 0.7rem; padding: 4px 12px; border-radius: 50px; font-weight: 600; text-transform: uppercase; }
        .info-box { background: #f8f9fa; border-radius: 10px; padding: 10px; margin-top: 10px; }
        .btn-action-group .btn { font-size: 0.75rem; font-weight: 600; }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<header class="dashboard-header mb-5">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h1 class="fw-bold">Welcome, Dr. <?= htmlspecialchars(explode(' ', $full_name)[0]) ?></h1>
            <p class="opacity-75 mb-0">Manage your assigned patients and rehabilitation progress.</p>
        </div>
        <div class="text-end">
            <span class="badge bg-light text-dark rounded-pill px-3 py-2 fw-bold shadow-sm">
                <i class="fas fa-stethoscope me-2 text-primary"></i>Certified Counselor
            </span>
        </div>
    </div>
</header>

<main class="container mb-5">
    <div class="row g-4 mb-5 text-center">
        <div class="col-md-4">
            <div class="card stat-card p-4">
                <div class="text-success mb-2"><i class="fas fa-user-injured fa-2x"></i></div>
                <h3 class="fw-bold mb-0"><?= $active_cases_count ?></h3>
                <p class="text-muted mb-0">Active Patients</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card p-4">
                <div class="text-primary mb-2"><i class="fas fa-comments fa-2x"></i></div>
                <h3 class="fw-bold mb-0"><?= $total_sessions ?></h3>
                <p class="text-muted mb-0">Total Sessions</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card p-4">
                <div class="text-warning mb-2"><i class="fas fa-calendar-check fa-2x"></i></div>
                <h3 class="fw-bold mb-0"><?= date('M d') ?></h3>
                <p class="text-muted mb-0">Rehab Schedule</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <h4 class="fw-bold mb-4">My Patient Assignments</h4>
            <?php if ($cases_result->num_rows > 0): ?>
                <?php while($case = $cases_result->fetch_assoc()): ?>
                    <div class="card patient-card p-4 mb-3 shadow-sm">
                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                            <div class="mb-3">
                                <h5 class="fw-bold mb-1"><?= htmlspecialchars($case['patient_name']) ?></h5>
                                <p class="small text-muted mb-2">
                                    <span class="text-dark fw-bold">Case:</span> <?= htmlspecialchars($case['case_title']) ?> <br>
                                    <span class="text-dark fw-bold">Category:</span> <?= htmlspecialchars($case['issue_category']) ?>
                                </p>
                                <span class="status-pill bg-<?= ($case['status'] == 'completed') ? 'success' : 'primary' ?> text-white">
                                    <?= htmlspecialchars($case['status']) ?>
                                </span>
                            </div>
                            
                            <div class="btn-action-group d-flex gap-2">
                                <a href="view_case_details.php?id=<?= $case['case_id'] ?>" class="btn btn-sm btn-outline-dark rounded-pill">
                                    <i class="fas fa-eye me-1"></i> Details
                                </a>
                                <a href="manage_skills.php?case_id=<?= $case['case_id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                    <i class="fas fa-tools me-1"></i> Skills
                                </a>
                                <a href="log_session.php?case_id=<?= $case['case_id'] ?>" class="btn btn-sm btn-dark rounded-pill shadow-sm">
                                    <i class="fas fa-plus me-1"></i> Log Session
                                </a>
                            </div>
                        </div>
                        
                        <div class="info-box d-flex align-items-center mt-2">
                            <i class="fas fa-envelope me-2 text-muted"></i>
                            <span class="small text-muted">Contact: <?= htmlspecialchars($case['patient_email']) ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                    <i class="fas fa-user-md fa-3x text-muted mb-3 opacity-25"></i>
                    <p class="text-muted">No patients assigned yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="fas fa-history me-2"></i>Recent Logs</h5>
                <?php if ($logs_result->num_rows > 0): ?>
                    <?php while($log = $logs_result->fetch_assoc()): ?>
                        <div class="mb-3 pb-2 border-bottom">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-bold"><?= htmlspecialchars($log['patient_name']) ?></span>
                                <span class="text-muted" style="font-size: 0.7rem;"><?= date('d M', strtotime($log['session_date'])) ?></span>
                            </div>
                            <p class="text-muted small mb-0 text-truncate"><?= htmlspecialchars($log['notes'] ?? 'Session completed.') ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="small text-muted mb-0">No sessions recorded.</p>
                <?php endif; ?>
            </div>

            <div class="card border-0 bg-dark text-white rounded-4 p-4">
                <h6 class="fw-bold"><i class="fas fa-shield-alt me-2 text-warning"></i>Compliance</h6>
                <p class="small opacity-75 mb-0">All entries are timestamped and logged for clinical audit. Maintain patient anonymity in public notes.</p>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>