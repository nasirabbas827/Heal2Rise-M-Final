<?php
include('../config.php');
session_start();

// Security check: Must be logged in AND be a team member
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'team_member') {
    header("Location: ../login.php");
    exit();
}

$team_id = $_SESSION['id'];
$team_name = $_SESSION['full_name'] ?? 'Team Member';
$team_email = $_SESSION['email'] ?? '';
$message = "";

// --- Handle Case Completion (New Logic) ---
if (isset($_GET['complete_id'])) {
    $complete_id = intval($_GET['complete_id']);
    $today = date('Y-m-d');
    
    // Update status to 'completed' and set closure date
    $stmt_comp = mysqli_prepare($conn, "UPDATE cases SET status = 'completed', closure_date = ? WHERE case_id = ? AND team_member_id = ?");
    mysqli_stmt_bind_param($stmt_comp, "sii", $today, $complete_id, $team_id);
    
    if (mysqli_stmt_execute($stmt_comp)) {
        $message = "<div class='alert alert-success alert-dismissible fade show mb-4' role='alert'>
                        <i class='fas fa-check-double me-2'></i> Case marked as <strong>Completed</strong> successfully.
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
    }
    mysqli_stmt_close($stmt_comp);
}

// --- Handle Counselor Assignment/Update ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_counselor'])) {
    $case_id = intval($_POST['case_id']);
    $counselor_id = !empty($_POST['counselor_id']) ? intval($_POST['counselor_id']) : NULL;

    $stmt_update = mysqli_prepare($conn, "UPDATE cases SET counselor_id = ? WHERE case_id = ? AND team_member_id = ?");
    mysqli_stmt_bind_param($stmt_update, "iii", $counselor_id, $case_id, $team_id);
    
    if (mysqli_stmt_execute($stmt_update)) {
        $message = "<div class='alert alert-success alert-dismissible fade show mb-4' role='alert'>
                        <i class='fas fa-check-circle me-2'></i> Counselor assignment updated successfully.
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
    }
    mysqli_stmt_close($stmt_update);
}

// 1. Fetch team member's assigned cases count
$stmt_cases = mysqli_prepare($conn, "SELECT COUNT(*) FROM cases WHERE team_member_id = ?");
mysqli_stmt_bind_param($stmt_cases, "i", $team_id);
mysqli_stmt_execute($stmt_cases);
mysqli_stmt_bind_result($stmt_cases, $total_cases);
mysqli_stmt_fetch($stmt_cases);
mysqli_stmt_close($stmt_cases);

// 2. Fetch counselling sessions count
$stmt_sessions = mysqli_prepare($conn, "
    SELECT COUNT(*) FROM counseling_sessions cs
    INNER JOIN cases c ON cs.case_id = c.case_id
    WHERE c.team_member_id = ?
");
mysqli_stmt_bind_param($stmt_sessions, "i", $team_id);
mysqli_stmt_execute($stmt_sessions);
mysqli_stmt_bind_result($stmt_sessions, $total_sessions);
mysqli_stmt_fetch($stmt_sessions);
mysqli_stmt_close($stmt_sessions);

// 3. Fetch skill development tasks count
$stmt_skills = mysqli_prepare($conn, "
    SELECT COUNT(*) FROM skill_development sd
    INNER JOIN cases c ON sd.case_id = c.case_id
    WHERE c.team_member_id = ?
");
mysqli_stmt_bind_param($stmt_skills, "i", $team_id);
mysqli_stmt_execute($stmt_skills);
mysqli_stmt_bind_result($stmt_skills, $total_skills);
mysqli_stmt_fetch($stmt_skills);
mysqli_stmt_close($stmt_skills);

// 4. Fetch assigned cases details
$stmt_cases_list = mysqli_prepare($conn, "
    SELECT c.case_id, u.full_name as client_name, c.status, c.last_updated, c.case_title, 
           co.full_name as counselor_name, co.id as counselor_id
    FROM cases c 
    JOIN users u ON c.user_id = u.id
    LEFT JOIN counselors co ON c.counselor_id = co.id
    WHERE c.team_member_id = ? 
    ORDER BY c.last_updated DESC
");
mysqli_stmt_bind_param($stmt_cases_list, "i", $team_id);
mysqli_stmt_execute($stmt_cases_list);
$result_cases = mysqli_stmt_get_result($stmt_cases_list);
$assigned_cases = mysqli_fetch_all($result_cases, MYSQLI_ASSOC);
mysqli_stmt_close($stmt_cases_list);

// 5. Fetch all counselors for the dropdown
$counselors_list = mysqli_query($conn, "SELECT id, full_name FROM counselors ORDER BY full_name ASC");

// 6. Fetch 5 Most Recent Progress Updates
$recent_updates = mysqli_query($conn, "
    SELECT cp.progress_note, cp.created_at, u.full_name as client_name
    FROM case_progress cp
    JOIN cases c ON cp.case_id = c.case_id
    JOIN users u ON c.user_id = u.id
    WHERE cp.updated_by_team_member = $team_id
    ORDER BY cp.created_at DESC LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Dashboard | Heal2Rise Partners</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --dark-bg: #1a1a1a; --gold-accent: #d4af37; }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .dashboard-header { 
            background: linear-gradient(135deg, #2d2d2d 0%, #1a1a1a 100%); 
            color: white; padding: 50px 0; border-radius: 0 0 30px 30px; margin-bottom: -40px;
        }
        .stat-card { border: none; border-radius: 15px; background: #ffffff; transition: all 0.3s ease; box-shadow: 0 10px 20px rgba(0,0,0,0.05); height: 100%; }
        .stat-card:hover { transform: translateY(-5px); }
        .icon-box { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; }
        .action-card { background: white; border-radius: 15px; padding: 20px; border-left: 4px solid var(--dark-bg); }
        .status-pill { font-size: 0.7rem; font-weight: 600; padding: 4px 12px; border-radius: 50px; text-transform: uppercase; }
        /* Style for Complete Button */
        .btn-complete { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .btn-complete:hover { background-color: #2e7d32; color: white; }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<header class="dashboard-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-7">
                <h1 class="display-6 fw-bold" style="font-family: 'Playfair Display';">
                    Hello, <?= explode(' ', htmlspecialchars($team_name))[0] ?>
                </h1>
                <p class="opacity-75"><i class="fas fa-calendar-alt me-2"></i> Today is <?= date('l, F jS') ?></p>
            </div>
            <div class="col-md-5 text-md-end">
                <div class="d-inline-block text-start p-3 bg-white bg-opacity-10 rounded-3">
                    <small class="d-block opacity-50">Logged in as</small>
                    <span class="fw-bold"><?= htmlspecialchars($team_email) ?></span>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="container mt-5">
    <?= $message; ?>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card stat-card p-4">
                <div class="icon-box bg-primary text-white"><i class="fas fa-briefcase"></i></div>
                <h2 class="fw-bold mb-0"><?= $total_cases ?></h2>
                <p class="text-muted small">Active Assignments</p>
                <div class="progress mt-3" style="height: 4px;"><div class="progress-bar bg-primary" style="width: 70%"></div></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card p-4">
                <div class="icon-box bg-success text-white"><i class="fas fa-heartbeat"></i></div>
                <h2 class="fw-bold mb-0"><?= $total_sessions ?></h2>
                <p class="text-muted small">Counseling Logs</p>
                <div class="progress mt-3" style="height: 4px;"><div class="progress-bar bg-success" style="width: 45%"></div></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card p-4">
                <div class="icon-box bg-warning text-white"><i class="fas fa-tools"></i></div>
                <h2 class="fw-bold mb-0"><?= $total_skills ?></h2>
                <p class="text-muted small">Skill Milestones</p>
                <div class="progress mt-3" style="height: 4px;"><div class="progress-bar bg-warning" style="width: 60%"></div></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-4">Assigned Case Load</h5>
                
                <?php if (count($assigned_cases) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr class="small text-uppercase">
                                <th>Case/Client</th>
                                <th>Status</th>
                                <th>Counselor</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($assigned_cases as $case): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($case['case_title'] ?: 'General Support') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($case['client_name']) ?></small>
                                </td>
                                <td>
                                    <?php 
                                        $badgeClass = match($case['status']) {
                                            'open' => 'bg-primary',
                                            'in_progress' => 'bg-info text-dark',
                                            'rehabilitation' => 'bg-warning text-dark',
                                            'completed' => 'bg-success',
                                            'closed' => 'bg-dark',
                                            default => 'bg-secondary'
                                        };
                                    ?>
                                    <span class="status-pill text-white <?= $badgeClass ?>"><?= $case['status'] ?></span>
                                </td>
                                <td>
                                    <div class="mb-1 small"><?= $case['counselor_name'] ? htmlspecialchars($case['counselor_name']) : '<em class="text-muted">None</em>' ?></div>
                                    <button class="btn btn-sm btn-outline-dark py-0 px-2" style="font-size: 0.7rem;" 
                                            onclick="openCounselorModal(<?= $case['case_id'] ?>, '<?= addslashes($case['case_title']) ?>', '<?= $case['counselor_id'] ?>')">
                                        <i class="fas fa-user-md me-1"></i> <?= $case['counselor_id'] ? 'Change' : 'Assign' ?>
                                    </button>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="team_case_details.php?case_id=<?= $case['case_id'] ?>" class="btn btn-sm btn-dark px-3 rounded-pill">Details</a>
                                        
                                        <?php if($case['status'] != 'completed' && $case['status'] != 'closed'): ?>
                                        <a href="?complete_id=<?= $case['case_id'] ?>" 
                                           class="btn btn-sm btn-complete px-3 rounded-pill" 
                                           onclick="return confirm('Mark this case as fully completed?')">
                                            <i class="fas fa-check-circle"></i> Complete
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard-list fa-3x opacity-25 mb-3"></i>
                        <p class="text-muted">No cases currently assigned to you.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h5 class="fw-bold mb-4">Recent Progress</h5>
                <?php if(mysqli_num_rows($recent_updates) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php while($row = mysqli_fetch_assoc($recent_updates)): ?>
                        <div class="list-group-item border-0 px-0 mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold small"><?= htmlspecialchars($row['client_name']) ?></span>
                                <small class="text-muted"><?= date('H:i', strtotime($row['created_at'])) ?></small>
                            </div>
                            <p class="small text-muted mb-0 text-truncate"><?= htmlspecialchars($row['progress_note']) ?></p>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="small text-muted">No recent activity logged.</p>
                <?php endif; ?>
            </div>

            <div class="action-card">
                <h6 class="fw-bold"><i class="fas fa-info-circle me-2"></i> Assignment Tip</h6>
                <p class="small text-muted mb-0">Completing a case will automatically set today's date as the closure date for NGO records.</p>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="counselorModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title small fw-bold">Assign/Change Counselor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p id="modal_case_title" class="fw-bold small mb-3 text-primary"></p>
                    <input type="hidden" name="case_id" id="modal_case_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Professional Counselor</label>
                        <select name="counselor_id" id="modal_counselor_id" class="form-select">
                            <option value="">-- No Counselor / Unassign --</option>
                            <?php mysqli_data_seek($counselors_list, 0); while($cl = mysqli_fetch_assoc($counselors_list)): ?>
                                <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['full_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="assign_counselor" class="btn btn-dark btn-sm px-4">Update Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<footer class="container py-5 mt-5 text-center text-muted small">
    <hr class="opacity-10">
    &copy; <?= date('Y') ?> Heal2Rise Partners - Rehabilitation Monitoring System
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openCounselorModal(caseId, caseTitle, currentCounselorId) {
    document.getElementById('modal_case_id').value = caseId;
    document.getElementById('modal_case_title').innerText = "Case: " + caseTitle;
    document.getElementById('modal_counselor_id').value = currentCounselorId || "";
    new bootstrap.Modal(document.getElementById('counselorModal')).show();
}
</script>
</body>
</html>