<?php
session_start();
include "config.php";

// Admin Protection
if (!isset($_SESSION['admin_logged_in'])) { 
    header("Location: adminlogin.php"); 
    exit(); 
}

$case_id = intval($_GET['id'] ?? 0);

// 1. Fetch Case & User Basic Info
$case_sql = "SELECT c.*, u.full_name as user_name, n.ngo_name 
             FROM cases c 
             JOIN users u ON c.user_id = u.id 
             LEFT JOIN ngos n ON c.ngo_id = n.id 
             WHERE c.case_id = $case_id";
$case_result = mysqli_query($conn, $case_sql);
$case_info = mysqli_fetch_assoc($case_result);

if (!$case_info) { die("Case not found."); }

// 2. Fetch Progress Notes
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
    <title>Case History | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../CSS/style.css">

    <style>
        .section-header { border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; font-weight: bold; }
        .log-card { border-left: 4px solid #2d2d2d; margin-bottom: 15px; }
    </style>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark">Case History: <?= htmlspecialchars($case_info['case_title']) ?></h2>
        <a href="manage_cases.php" class="btn btn-secondary rounded-pill px-4">Back to List</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 p-4 sticky-top" style="top: 20px;">
                <h5 class="fw-bold mb-3">Client Information</h5>
                <p class="mb-1 text-muted small text-uppercase">Client Name</p>
                <p class="fw-bold"><?= $case_info['user_name'] ?></p>
                
                <p class="mb-1 text-muted small text-uppercase">Managing NGO</p>
                <p class="fw-bold text-primary"><?= $case_info['ngo_name'] ?: 'Central Authority' ?></p>
                
                <hr>
                
                <h5 class="fw-bold mb-3">Case Status</h5>
                <span class="badge bg-dark p-2 w-100"><?= strtoupper($case_info['status']) ?></span>
                <p class="mt-3 small text-muted"><?= $case_info['description'] ?></p>
            </div>
        </div>

        <div class="col-lg-8">
            
            <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm" id="pills-tab" role="tablist">
                <li class="nav-item w-33 text-center">
                    <button class="nav-link active w-100" data-bs-toggle="pill" data-bs-target="#tab-progress">Progress Notes</button>
                </li>
                <li class="nav-item w-33 text-center">
                    <button class="nav-link w-100" data-bs-toggle="pill" data-bs-target="#tab-sessions">Counseling</button>
                </li>
                <li class="nav-item w-33 text-center">
                    <button class="nav-link w-100" data-bs-toggle="pill" data-bs-target="#tab-skills">Skills</button>
                </li>
            </ul>

            <div class="tab-content" id="pills-tabContent">
                
                <div class="tab-pane fade show active" id="tab-progress">
                    <h5 class="section-header">Timeline of Progress</h5>
                    <?php if(mysqli_num_rows($progress) > 0): ?>
                        <?php while($p = mysqli_fetch_assoc($progress)): ?>
                            <div class="card log-card shadow-sm p-3">
                                <div class="d-flex justify-content-between">
                                    <small class="text-primary fw-bold">By: <?= $p['member_name'] ?: 'System' ?></small>
                                    <small class="text-muted"><?= date('d M, Y H:i', strtotime($p['created_at'])) ?></small>
                                </div>
                                <p class="mt-2 mb-1"><?= nl2br(htmlspecialchars($p['progress_note'])) ?></p>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark border">Skill: <?= $p['skill_training_status'] ?></span>
                                    <span class="badge bg-light text-dark border">Counseling: <?= $p['counseling_status'] ?></span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-info">No progress updates found.</div>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="tab-sessions">
                    <h5 class="section-header">Counseling Session Logs</h5>
                    <?php if(mysqli_num_rows($sessions) > 0): ?>
                        <div class="table-responsive bg-white rounded shadow-sm">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Counselor</th>
                                        <th>Attended</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($s = mysqli_fetch_assoc($sessions)): ?>
                                    <tr>
                                        <td><?= date('d M, Y', strtotime($s['session_date'])) ?></td>
                                        <td><?= $s['counselor_name'] ?></td>
                                        <td><?= $s['attended'] ? '✅' : '❌' ?></td>
                                        <td><small><?= $s['notes'] ?></small></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No counseling sessions recorded.</div>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="tab-skills">
                    <h5 class="section-header">Vocational Skill Tracking</h5>
                    <div class="row g-3">
                        <?php if(mysqli_num_rows($skills) > 0): ?>
                            <?php while($sk = mysqli_fetch_assoc($skills)): ?>
                            <div class="col-md-6">
                                <div class="card p-3 border-0 shadow-sm">
                                    <h6 class="fw-bold mb-1"><?= $sk['skill_name'] ?></h6>
                                    <p class="small text-muted mb-2">Started: <?= $sk['start_date'] ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge <?= $sk['status']=='completed' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                            <?= strtoupper($sk['status']) ?>
                                        </span>
                                        <small class="text-muted">Target: <?= $sk['target_date'] ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12"><div class="alert alert-info">No skill development entries.</div></div>
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