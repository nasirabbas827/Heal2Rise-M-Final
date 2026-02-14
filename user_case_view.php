<?php
include('config.php');
session_start();

// Security Check
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'user') { 
    header("Location: login.php"); 
    exit(); 
}

$user_id = $_SESSION['id'];
$case_id = $_GET['id'] ?? 0;

// 1. Fetch Case Details and verify ownership
$stmt_case = $conn->prepare("
    SELECT c.*, n.ngo_name, n.phone as ngo_phone, tm.full_name as mentor_name 
    FROM cases c 
    LEFT JOIN ngos n ON c.ngo_id = n.id 
    LEFT JOIN team_members tm ON c.team_member_id = tm.team_member_id
    WHERE c.case_id = ? AND c.user_id = ?
");
$stmt_case->bind_param("ii", $case_id, $user_id);
$stmt_case->execute();
$case_data = $stmt_case->get_result()->fetch_assoc();

if (!$case_data) {
    die("Case not found or access denied.");
}

// 2. Fetch Counseling Sessions for this case
$sessions_query = $conn->prepare("SELECT * FROM counseling_sessions WHERE case_id = ? ORDER BY session_date DESC");
$sessions_query->bind_param("i", $case_id);
$sessions_query->execute();
$sessions = $sessions_query->get_result();

// 3. Fetch Skill Progress for this case
$skills_query = $conn->prepare("SELECT * FROM skill_development WHERE case_id = ?");
$skills_query->bind_param("i", $case_id);
$skills_query->execute();
$skills = $skills_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Timeline | Heal2Rise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./CSS/style.css">

    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .timeline { border-left: 3px solid #dee2e6; padding-left: 20px; position: relative; list-style: none; }
        .timeline-item { margin-bottom: 30px; position: relative; }
        .timeline-item::before { 
            content: ""; position: absolute; left: -28px; top: 5px; 
            width: 15px; height: 15px; border-radius: 50%; background: #0d6efd; 
            border: 3px solid #fff; box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .status-badge { padding: 5px 15px; border-radius: 50px; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5 mb-4">
    <div class="mb-4">
        <a href="dashboard.php" class="text-decoration-none text-muted"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card p-4 mb-4">
                <h5 class="fw-bold mb-3">Case Information</h5>
                <hr>
                <div class="mb-3">
                    <small class="text-muted d-block">Status</small>
                    <span class="status-badge bg-primary text-white"><?= htmlspecialchars($case_data['status']) ?></span>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Support Category</small>
                    <span class="fw-bold"><?= htmlspecialchars($case_data['issue_category']) ?></span>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Assigned NGO</small>
                    <span class="fw-bold text-primary"><?= htmlspecialchars($case_data['ngo_name'] ?? 'Pending Assignment') ?></span>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Personal Mentor</small>
                    <span class="fw-bold"><?= htmlspecialchars($case_data['mentor_name'] ?? 'Waiting for mentor...') ?></span>
                </div>
            </div>

            <div class="card bg-dark text-white p-4">
                <h6 class="fw-bold text-warning"><i class="fas fa-phone-alt me-2"></i>NGO Contact</h6>
                <p class="small mb-0">If you need to reschedule or have questions, contact your NGO directly:</p>
                <p class="fw-bold mt-2"><?= htmlspecialchars($case_data['ngo_phone'] ?? 'N/A') ?></p>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card p-4">
                <h4 class="fw-bold mb-4">Case Journey & Progress</h4>
                
                <ul class="timeline">
                    <?php if($skills->num_rows > 0): ?>
                        <?php while($skill = $skills->fetch_assoc()): ?>
                            <li class="timeline-item">
                                <div class="card p-3 bg-light border-start border-warning border-4">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="fw-bold mb-1">Vocational Training: <?= htmlspecialchars($skill['skill_name']) ?></h6>
                                    </div>
                                    <p class="small text-muted mb-1">Status: <strong><?= ucfirst($skill['status']) ?></strong></p>
                                    <p class="small mb-0 italic">"<?= htmlspecialchars($skill['notes']) ?>"</p>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    <?php endif; ?>

                    <?php if($sessions->num_rows > 0): ?>
                        <?php while($sess = $sessions->fetch_assoc()): ?>
                            <li class="timeline-item">
                                <div class="card p-3 border-start border-success border-4">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="fw-bold mb-1">Counseling Session</h6>
                                        <small class="text-muted"><?= date('M d, Y', strtotime($sess['session_date'])) ?></small>
                                    </div>
                                    <p class="small text-muted mb-2">Counselor Name: <?= htmlspecialchars($sess['counselor_name']) ?></p>
                                    <div class="bg-white p-2 rounded small">
                                        <strong>Duration:</strong><br>
                                        <?= nl2br(htmlspecialchars($sess['duration_minutes'])) ?>
                                    </div>
                                    <div class="bg-white p-2 rounded small">
                                        <strong>Notes:</strong><br>
                                        <?= nl2br(htmlspecialchars($sess['notes'])) ?>
                                    </div>
                                    <div class="bg-white p-2 rounded small">
                                        <strong>Next Suggested Date:</strong><br>
                                        <?= nl2br(htmlspecialchars($sess['next_suggested_date'])) ?>
                                    </div>
                                   
                                </div>
                            </li>
                        <?php endwhile; ?>
                    <?php endif; ?>

                    <li class="timeline-item">
                        <div class="card p-3 border-start border-primary border-4">
                            <h6 class="fw-bold mb-1">Case Filed: <?= htmlspecialchars($case_data['case_title']) ?></h6>
                            <small class="text-muted"><?= date('M d, Y', strtotime($case_data['last_updated'])) ?></small>
                            <p class="small mt-2 mb-0"><?= nl2br(htmlspecialchars($case_data['description'])) ?></p>
                        </div>
                    </li>
                </ul>

                <?php if($sessions->num_rows == 0 && $skills->num_rows == 0): ?>
                    <div class="text-center py-4">
                        <p class="text-muted">Your journey has just begun. Once sessions are logged, they will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>