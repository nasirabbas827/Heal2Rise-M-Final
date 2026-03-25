<?php
include('../config.php');
session_start();

// Security Check: Must be logged in as a Counselor
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'counselor') { 
    header("Location: ../login.php"); 
    exit(); 
}

$counselor_id = $_SESSION['id'];
$case_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 1. Fetch Comprehensive Case & Patient Data
$query = "SELECT c.*, 
          u.full_name as patient_name, u.email as patient_email, u.contact_number, u.dob, u.address,
          n.ngo_name, 
          tm.full_name as mentor_name
          FROM cases c 
          JOIN users u ON c.user_id = u.id 
          LEFT JOIN ngos n ON c.ngo_id = n.id 
          LEFT JOIN team_members tm ON c.team_member_id = tm.team_member_id
          WHERE c.case_id = ? AND c.counselor_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $case_id, $counselor_id);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();

if (!$case) {
    die("<div class='container mt-5 alert alert-danger'>Case not found or you do not have permission to view it.</div>");
}

// 2. Fetch Counseling Sessions
$sessions_stmt = $conn->prepare("SELECT * FROM counseling_sessions WHERE case_id = ? ORDER BY session_date DESC");
$sessions_stmt->bind_param("i", $case_id);
$sessions_stmt->execute();
$sessions = $sessions_stmt->get_result();

// 3. Fetch Skill Development Progress
$skills_stmt = $conn->prepare("SELECT * FROM skill_development WHERE case_id = ? ORDER BY status DESC");
$skills_stmt->bind_param("i", $case_id);
$skills_stmt->execute();
$skills = $skills_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Case Details | Heal2Rise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .detail-card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); background: white; margin-bottom: 20px; }
        .section-title { font-weight: 700; color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .status-badge { padding: 6px 15px; border-radius: 50px; font-weight: 600; font-size: 0.85rem; }
        .info-label { color: #888; font-size: 0.8rem; text-transform: uppercase; font-weight: 600; margin-bottom: 2px; }
        .info-value { color: #333; font-weight: 500; margin-bottom: 15px; }
        .timeline-item { border-left: 2px solid #dee2e6; padding-left: 20px; position: relative; padding-bottom: 20px; }
        .timeline-item::before { content: ""; position: absolute; left: -7px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: #0d6efd; }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="counselor_dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Case #<?= $case['case_id'] ?></li>
                </ol>
            </nav>
            <h2 class="fw-bold"><?= htmlspecialchars($case['case_title']) ?></h2>
        </div>
        <div class="d-flex gap-2">
            <a href="log_session.php?case_id=<?= $case_id ?>" class="btn btn-primary rounded-pill px-4">
                <i class="fas fa-plus me-2"></i>Log Session
            </a>
            <a href="manage_skills.php?case_id=<?= $case_id ?>" class="btn btn-outline-dark rounded-pill px-4">
                <i class="fas fa-tools me-2"></i>Manage Skills
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card detail-card p-4">
                <h5 class="section-title">Patient Profile</h5>
                <div class="text-center mb-4">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-user fa-2x text-secondary"></i>
                    </div>
                    <h5 class="mb-0 fw-bold"><?= htmlspecialchars($case['patient_name']) ?></h5>
                    <p class="text-muted small"><?= htmlspecialchars($case['patient_email']) ?></p>
                </div>

                <div class="info-label">Contact Number</div>
                <div class="info-value"><?= htmlspecialchars($case['contact_number'] ?? 'N/A') ?></div>

                <div class="info-label">Date of Birth</div>
                <div class="info-value"><?= $case['dob'] ? date('M d, Y', strtotime($case['dob'])) : 'N/A' ?></div>

                <div class="info-label">Address</div>
                <div class="info-value small"><?= htmlspecialchars($case['address'] ?? 'No address provided') ?></div>
                
                <hr>

                <h5 class="section-title mt-4">Case Assignment</h5>
                <div class="info-label">Managing NGO</div>
                <div class="info-value"><?= htmlspecialchars($case['ngo_name'] ?? 'Direct Assignment') ?></div>

                <div class="info-label">Assigned Mentor</div>
                <div class="info-value"><?= htmlspecialchars($case['mentor_name'] ?? 'Pending') ?></div>

                <div class="info-label">Current Status</div>
                <div>
                    <span class="status-badge bg-primary text-white"><?= strtoupper($case['status']) ?></span>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card detail-card p-4">
                <h5 class="section-title">Issue Description</h5>
                <p class="text-muted"><?= nl2br(htmlspecialchars($case['description'])) ?></p>
            </div>

            <div class="card detail-card p-4">
                <h5 class="section-title"><i class="fas fa-history me-2 text-primary"></i>Counseling Progress</h5>
                <?php if ($sessions->num_rows > 0): ?>
                    <div class="ms-2">
                        <?php while($s = $sessions->fetch_assoc()): ?>
                            <div class="timeline-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-bold mb-0"><?= date('M d, Y | h:i A', strtotime($s['session_date'])) ?></h6>
                                        <p class="small text-muted mb-2">Duration: <?= $s['duration_minutes'] ?> mins | Attended: <?= $s['attended'] ? 'Yes' : 'No' ?></p>
                                    </div>
                                    <a href="log_session.php?case_id=<?= $case_id ?>" class="btn btn-sm btn-light border"><i class="fas fa-edit"></i></a>
                                </div>
                                <div class="p-3 bg-light rounded-3 small">
                                    <?= nl2br(htmlspecialchars($s['notes'])) ?>
                                </div>
                                <?php if($s['next_suggested_date']): ?>
                                    <div class="mt-2 text-primary small fw-bold">
                                        <i class="far fa-calendar-check me-1"></i> Next Session: <?= date('M d, Y', strtotime($s['next_suggested_date'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4">No counseling sessions recorded yet.</p>
                <?php endif; ?>
            </div>

            <div class="card detail-card p-4">
                <h5 class="section-title"><i class="fas fa-graduation-cap me-2 text-success"></i>Skill Development</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Skill Name</th>
                                <th>Status</th>
                                <th>Target Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($skills->num_rows > 0): ?>
                                <?php while($sk = $skills->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($sk['skill_name']) ?></td>
                                        <td><span class="badge bg-secondary rounded-pill"><?= $sk['status'] ?></span></td>
                                        <td class="small text-muted"><?= $sk['target_date'] ? date('M d, Y', strtotime($sk['target_date'])) : 'Not Set' ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center text-muted">No skill programs enrolled.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>