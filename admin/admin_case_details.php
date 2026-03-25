<?php
session_start();
include "config.php";

// Admin Protection
if (!isset($_SESSION['admin_logged_in'])) { 
    header("Location: adminlogin.php"); 
    exit(); 
}

$case_id = intval($_GET['id'] ?? 0);

// 1. Fetch Case, User, NGO, and Counselor Info
// Added join with counselors table to get the assigned professional's name
$case_sql = "SELECT c.*, u.full_name as user_name, n.ngo_name, co.full_name as assigned_counselor 
             FROM cases c 
             JOIN users u ON c.user_id = u.id 
             LEFT JOIN ngos n ON c.ngo_id = n.id 
             LEFT JOIN counselors co ON c.counselor_id = co.id
             WHERE c.case_id = $case_id";
$case_result = mysqli_query($conn, $case_sql);
$case_info = mysqli_fetch_assoc($case_result);

if (!$case_info) { die("Case not found."); }

// 2. Fetch Progress Notes (Linked to Team Members)
$progress = mysqli_query($conn, "SELECT cp.*, t.full_name as member_name 
                                 FROM case_progress cp 
                                 LEFT JOIN team_members t ON cp.updated_by_team_member = t.team_member_id 
                                 WHERE cp.case_id = $case_id ORDER BY cp.created_at DESC");

// 3. Fetch Counseling Sessions
$sessions = mysqli_query($conn, "SELECT * FROM counseling_sessions WHERE case_id = $case_id ORDER BY session_date DESC");

// 4. Fetch Skill Development
$skills = mysqli_query($conn, "SELECT * FROM skill_development WHERE case_id = $case_id ORDER BY start_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case History | Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/style.css">
    <style>
        :root { --admin-dark: #212529; }
        .section-header { border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 20px; font-weight: 700; color: var(--admin-dark); }
        .log-card { border-left: 5px solid var(--admin-dark); margin-bottom: 15px; border-radius: 8px; }
        .nav-pills .nav-link.active { background-color: var(--admin-dark); }
        .nav-pills .nav-link { color: #666; font-weight: 500; }
        .sticky-info { position: sticky; top: 20px; }
        .status-badge-completed { background-color: #198754 !important; }
    </style>
</head>
<body class="bg-light">

<?php include('navbar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="manage_cases.php" class="text-decoration-none">Cases</a></li>
                    <li class="breadcrumb-item active">History</li>
                </ol>
            </nav>
            <h2 class="fw-bold text-dark">Case #<?= $case_id ?>: <?= htmlspecialchars($case_info['case_title']) ?></h2>
        </div>
        <a href="manage_cases.php" class="btn btn-outline-dark rounded-pill px-4">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 p-4 sticky-info">
                <h5 class="fw-bold mb-3 border-bottom pb-2">Client File</h5>
                
                <label class="text-muted small text-uppercase fw-bold">Full Name</label>
                <p class="mb-3"><?= htmlspecialchars($case_info['user_name']) ?></p>
                
                <label class="text-muted small text-uppercase fw-bold">Assigned NGO</label>
                <p class="mb-3 text-primary fw-bold"><?= $case_info['ngo_name'] ?: 'Central Authority' ?></p>

                <label class="text-muted small text-uppercase fw-bold">Professional Counselor</label>
                <p class="mb-3">
                    <?php if($case_info['assigned_counselor']): ?>
                        <span class="text-success"><i class="fas fa-user-md me-1"></i> <?= htmlspecialchars($case_info['assigned_counselor']) ?></span>
                    <?php else: ?>
                        <span class="text-muted italic">Not Assigned</span>
                    <?php endif; ?>
                </p>
                
                <hr>
                
                <h5 class="fw-bold mb-3">Case Status</h5>
                <?php 
                    $statusClass = ($case_info['status'] == 'completed') ? 'status-badge-completed' : 'bg-dark';
                ?>
                <span class="badge <?= $statusClass ?> p-2 w-100 fs-6"><?= strtoupper($case_info['status']) ?></span>
                
                <?php if($case_info['closure_date']): ?>
                    <p class="mt-3 mb-1 small text-muted fw-bold">Closure Date:</p>
                    <p class="small text-danger fw-bold"><?= date('d M, Y', strtotime($case_info['closure_date'])) ?></p>
                <?php endif; ?>

                <p class="mt-3 small text-muted bg-light p-2 rounded">
                    <i class="fas fa-info-circle me-1"></i> <?= nl2br(htmlspecialchars($case_info['description'])) ?>
                </p>
            </div>
        </div>

        <div class="col-lg-8">
            <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm d-flex justify-content-between" id="pills-tab" role="tablist">
                <li class="nav-item flex-fill text-center">
                    <button class="nav-link active w-100" data-bs-toggle="pill" data-bs-target="#tab-progress">
                        <i class="fas fa-tasks me-2"></i>Progress
                    </button>
                </li>
                <li class="nav-item flex-fill text-center">
                    <button class="nav-link w-100" data-bs-toggle="pill" data-bs-target="#tab-sessions">
                        <i class="fas fa-comments me-2"></i>Counseling
                    </button>
                </li>
                <li class="nav-item flex-fill text-center">
                    <button class="nav-link w-100" data-bs-toggle="pill" data-bs-target="#tab-skills">
                        <i class="fas fa-graduation-cap me-2"></i>Skills
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="pills-tabContent">
                
                <div class="tab-pane fade show active" id="tab-progress">
                    <h5 class="section-header">Rehabilitation Timeline</h5>
                    <?php if(mysqli_num_rows($progress) > 0): ?>
                        <?php while($p = mysqli_fetch_assoc($progress)): ?>
                            <div class="card log-card shadow-sm border-0 p-3 bg-white">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-secondary">Update by <?= htmlspecialchars($p['member_name'] ?: 'System') ?></span>
                                    <small class="text-muted"><i class="far fa-clock me-1"></i><?= date('d M, Y | h:i A', strtotime($p['created_at'])) ?></small>
                                </div>
                                <p class="mb-2 text-dark"><?= nl2br(htmlspecialchars($p['progress_note'])) ?></p>
                                <div class="d-flex gap-2">
                                    <span class="badge bg-light text-dark border"><i class="fas fa-chart-line me-1 text-primary"></i> Skill: <?= $p['skill_training_status'] ?></span>
                                    <span class="badge bg-light text-dark border"><i class="fas fa-heartbeat me-1 text-danger"></i> Counseling: <?= $p['counseling_status'] ?></span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-5 bg-white rounded shadow-sm">
                            <i class="fas fa-history fa-3x text-light mb-3"></i>
                            <p class="text-muted">No progress logs recorded for this case.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="tab-sessions">
                    <h5 class="section-header">Counseling Logs</h5>
                    <?php if(mysqli_num_rows($sessions) > 0): ?>
                        <div class="table-responsive bg-white rounded shadow-sm p-3">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Session Date</th>
                                        <th>Counselor</th>
                                        <th>Status</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($s = mysqli_fetch_assoc($sessions)): ?>
                                    <tr>
                                        <td class="fw-bold"><?= date('d M, Y', strtotime($s['session_date'])) ?></td>
                                        <td><?= htmlspecialchars($s['counselor_name']) ?></td>
                                        <td>
                                            <?= $s['attended'] ? 
                                                '<span class="text-success"><i class="fas fa-check-circle"></i> Attended</span>' : 
                                                '<span class="text-danger"><i class="fas fa-times-circle"></i> Missed</span>' 
                                            ?>
                                        </td>
                                        <td><small class="text-muted"><?= htmlspecialchars($s['notes']) ?></small></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border text-center py-4">
                            No counseling sessions found.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="tab-skills">
                    <h5 class="section-header">Vocational Development</h5>
                    <div class="row g-3">
                        <?php if(mysqli_num_rows($skills) > 0): ?>
                            <?php while($sk = mysqli_fetch_assoc($skills)): ?>
                            <div class="col-md-6">
                                <div class="card p-3 border-0 shadow-sm h-100">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="fw-bold text-primary mb-1"><?= htmlspecialchars($sk['skill_name']) ?></h6>
                                        <i class="fas fa-tools text-light"></i>
                                    </div>
                                    <p class="small text-muted mb-3">Enrolled: <?= date('d M, Y', strtotime($sk['start_date'])) ?></p>
                                    <div class="mt-auto d-flex justify-content-between align-items-center">
                                        <?php 
                                            $skBadge = ($sk['status'] == 'completed') ? 'bg-success' : 'bg-warning text-dark';
                                        ?>
                                        <span class="badge <?= $skBadge ?> rounded-pill px-3">
                                            <?= strtoupper($sk['status']) ?>
                                        </span>
                                        <small class="text-muted">Target: <?= $sk['target_date'] ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-light border text-center">No vocational training records.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>