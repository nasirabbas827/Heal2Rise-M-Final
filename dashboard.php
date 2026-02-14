<?php
include('config.php');
session_start();

// Security Check: Must be logged in as a regular user
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'user') { 
    header("Location: login.php"); 
    exit(); 
}

$user_id = $_SESSION['id'];
$full_name = $_SESSION['full_name'] ?? 'User';

// 1. Fetch Total Cases
$stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM cases WHERE user_id = ?");
$stmt_total->bind_param("i", $user_id);
$stmt_total->execute();
$total_cases = $stmt_total->get_result()->fetch_assoc()['total'];
$stmt_total->close();

// 2. Fetch Upcoming Counseling Sessions (FIXED: Added bind_param)
$stmt_sess = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM counseling_sessions cs 
    JOIN cases c ON cs.case_id = c.case_id 
    WHERE c.user_id = ? AND cs.session_date >= CURDATE()
");
$stmt_sess->bind_param("i", $user_id); // This line fixes the fatal error
$stmt_sess->execute();
$upcoming_sessions = $stmt_sess->get_result()->fetch_assoc()['total'];
$stmt_sess->close();

// 3. Fetch User's Active Cases and assigned NGOs
$cases_query = $conn->prepare("
    SELECT c.*, n.ngo_name, tm.full_name as mentor_name 
    FROM cases c 
    LEFT JOIN ngos n ON c.ngo_id = n.id 
    LEFT JOIN team_members tm ON c.team_member_id = tm.team_member_id
    WHERE c.user_id = ? 
    ORDER BY c.last_updated DESC
");
$cases_query->bind_param("i", $user_id);
$cases_query->execute();
$cases_result = $cases_query->get_result();

// 4. Fetch Recent Skill Progress
$skills_query = $conn->prepare("
    SELECT sd.*, c.case_title 
    FROM skill_development sd 
    JOIN cases c ON sd.case_id = c.case_id 
    WHERE c.user_id = ? 
    ORDER BY sd.skill_id DESC LIMIT 3
");
$skills_query->bind_param("i", $user_id);
$skills_query->execute();
$skills_result = $skills_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | Heal2Rise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./CSS/style.css">

    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .dashboard-header { background: #1a1a1a; color: white; padding: 50px 0; border-radius: 0 0 40px 40px; }
        .stat-card { border: none; border-radius: 20px; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .case-card { border: none; border-radius: 15px; background: white; border-left: 5px solid #0d6efd; }
        .status-pill { font-size: 0.7rem; padding: 4px 12px; border-radius: 50px; font-weight: 600; text-transform: uppercase; }
        .mentor-box { background: #f8f9fa; border-radius: 10px; padding: 10px; margin-top: 10px; }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<header class="dashboard-header mb-5">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h1 class="fw-bold">Welcome back, <?= htmlspecialchars(explode(' ', $full_name)[0]) ?>!</h1>
            <p class="opacity-75 mb-0">Track your rehabilitation progress and upcoming sessions.</p>
        </div>
        <div class="text-end">
            <a href="submit_case.php" class="btn btn-warning rounded-pill px-4 fw-bold shadow">
                <i class="fas fa-plus me-2"></i>New Support Request
            </a>
        </div>
    </div>
</header>

<main class="container mb-5">
    <div class="row g-4 mb-5 text-center">
        <div class="col-md-4">
            <div class="card stat-card p-4">
                <div class="text-primary mb-2"><i class="fas fa-folder fa-2x"></i></div>
                <h3 class="fw-bold mb-0"><?= $total_cases ?></h3>
                <p class="text-muted mb-0">Total Cases</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card p-4">
                <div class="text-success mb-2"><i class="fas fa-calendar-alt fa-2x"></i></div>
                <h3 class="fw-bold mb-0"><?= $upcoming_sessions ?></h3>
                <p class="text-muted mb-0">Upcoming Sessions</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card p-4">
                <div class="text-warning mb-2"><i class="fas fa-user-check fa-2x"></i></div>
                <h3 class="fw-bold mb-0">Active</h3>
                <p class="text-muted mb-0">Account Status</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <h4 class="fw-bold mb-4">My Support Cases</h4>
            <?php if ($cases_result->num_rows > 0): ?>
                <?php while($case = $cases_result->fetch_assoc()): ?>
                    <div class="card case-card p-4 mb-3 shadow-sm">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="fw-bold mb-1"><?= htmlspecialchars($case['case_title']) ?></h5>
                                <p class="small text-muted mb-2">ID: #<?= $case['case_id'] ?> | <?= htmlspecialchars($case['issue_category']) ?></p>
                                <span class="status-pill bg-primary text-white"><?= htmlspecialchars($case['status']) ?></span>
                            </div>
                            <div class="text-end">
                                <a href="user_case_view.php?id=<?= $case['case_id'] ?>" class="btn btn-sm btn-outline-dark rounded-pill">View Full Timeline</a>
                            </div>
                        </div>
                        
                        <div class="mentor-box d-flex align-items-center mt-3">
                            <i class="fas fa-user-shield me-2 text-primary"></i>
                            <span class="small text-muted">
                                Handled by: <strong><?= htmlspecialchars($case['ngo_name'] ?? 'Assignment Pending') ?></strong> 
                                <?= $case['mentor_name'] ? " | Mentor: ".htmlspecialchars($case['mentor_name']) : "" ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                    <i class="fas fa-inbox fa-3x text-muted mb-3 opacity-25"></i>
                    <p class="text-muted">No active cases found. Need help?</p>
                    <a href="submit_case.php" class="btn btn-primary btn-sm rounded-pill">Submit a Case</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h5 class="fw-bold mb-3">Skill Development</h5>
                <?php if ($skills_result->num_rows > 0): ?>
                    <?php while($skill = $skills_result->fetch_assoc()): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-bold"><?= htmlspecialchars($skill['skill_name']) ?></span>
                                <span class="text-muted" style="font-size: 0.7rem;"><?= ucfirst($skill['status']) ?></span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <?php 
                                    $p = 10; 
                                    if($skill['status'] == 'completed') $p = 100;
                                    elseif($skill['status'] == 'in_progress') $p = 50;
                                ?>
                                <div class="progress-bar bg-success" style="width: <?= $p ?>%"></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="small text-muted mb-0">No vocational programs started yet.</p>
                <?php endif; ?>
            </div>

            <div class="card border-0 bg-dark text-white rounded-4 p-4">
                <h6 class="fw-bold"><i class="fas fa-headset me-2 text-warning"></i>Helpline</h6>
                <p class="small opacity-75">Speak to a counselor immediately if you feel overwhelmed.</p>
                <h5 class="text-warning fw-bold mb-0">0800-HEAL-ME</h5>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>