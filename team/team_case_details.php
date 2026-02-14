<?php
include('../config.php');
session_start();

if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'team_member') {
    header("Location: ../login.php");
    exit();
}

$team_id = $_SESSION['id'];
$case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;

// --- HANDLE POST ACTIONS ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Add Progress Note
    if (isset($_POST['add_progress'])) {
        $note = mysqli_real_escape_string($conn, $_POST['progress_note']);
        $s_status = mysqli_real_escape_string($conn, $_POST['skill_status']);
        $c_status = mysqli_real_escape_string($conn, $_POST['counseling_status']);
        $stmt = $conn->prepare("INSERT INTO case_progress (case_id, updated_by_team_member, progress_note, skill_training_status, counseling_status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $case_id, $team_id, $note, $s_status, $c_status);
        $stmt->execute();
    }
}

// --- FETCH DATA ---

// 1. Case, User, and NGO Info
$case_query = "SELECT c.*, u.full_name as client_name, u.email as client_email, u.contact_number as client_phone,
               n.ngo_name, n.contact_person as ngo_contact, n.phone as ngo_phone
               FROM cases c 
               JOIN users u ON c.user_id = u.id 
               LEFT JOIN ngos n ON c.ngo_id = n.id 
               WHERE c.case_id = ? AND c.team_member_id = ?";
$stmt = $conn->prepare($case_query);
$stmt->bind_param("ii", $case_id, $team_id);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();

if (!$case) { die("Unauthorized access or case not found."); }

// 2. Fetch Progress History
$history = mysqli_query($conn, "SELECT * FROM case_progress WHERE case_id = $case_id ORDER BY created_at DESC");

// 3. Fetch Counseling Sessions
$sessions = mysqli_query($conn, "SELECT * FROM counseling_sessions WHERE case_id = $case_id ORDER BY session_date DESC");

// 4. Fetch Skill Development
$skills = mysqli_query($conn, "SELECT * FROM skill_development WHERE case_id = $case_id ORDER BY start_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Case Management | #<?= $case_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../CSS/style.css">

    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .nav-pills .nav-link { color: #6c757d; font-weight: 600; border-radius: 10px; }
        .nav-pills .nav-link.active { background-color: #212529; color: white; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .info-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #999; font-weight: 800; }
        .timeline-line { border-left: 2px solid #dee2e6; margin-left: 20px; padding-left: 20px; }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-4 mb-5">
    <div class="row mb-4 align-items-end">
        <div class="col-md-8">
            <h6 class="text-primary fw-bold mb-1">Case Monitoring Panel</h6>
            <h2 class="fw-bold"><?= htmlspecialchars($case['case_title']) ?></h2>
            <p class="text-muted mb-0">Assigned to: <strong><?= $_SESSION['full_name'] ?></strong> | Status: <span class="badge bg-dark"><?= strtoupper($case['status']) ?></span></p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#updateModal">
                <i class="fas fa-plus me-2"></i>New Progress Log
            </button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="fas fa-user-tag me-2 text-primary"></i>Client Info</h5>
                <p class="mb-1 fw-bold"><?= $case['client_name'] ?></p>
                <p class="small text-muted mb-3"><?= $case['client_email'] ?> | <?= $case['client_phone'] ?></p>
                <hr>
                <h5 class="fw-bold mb-3 mt-2"><i class="fas fa-handshake me-2 text-success"></i>Assigned NGO</h5>
                <?php if($case['ngo_name']): ?>
                    <p class="mb-1 fw-bold text-success"><?= $case['ngo_name'] ?></p>
                    <p class="small text-muted mb-0"><?= $case['ngo_contact'] ?></p>
                    <p class="small text-muted"><?= $case['ngo_phone'] ?></p>
                <?php else: ?>
                    <p class="text-muted small italic">No NGO assigned yet.</p>
                <?php endif; ?>
            </div>

            <div class="card p-4">
                <h5 class="fw-bold mb-3">Case Context</h5>
                <div class="info-label">Category</div>
                <p class="small"><?= $case['issue_category'] ?></p>
                <div class="info-label">Description</div>
                <p class="small text-muted"><?= nl2br(htmlspecialchars($case['description'])) ?></p>
            </div>
        </div>

        <div class="col-lg-8">
            <ul class="nav nav-pills mb-4 bg-white p-2 rounded-3 shadow-sm" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-progress" data-bs-toggle="pill" data-bs-target="#pills-progress" type="button">Progress Timeline</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-counseling" data-bs-toggle="pill" data-bs-target="#pills-counseling" type="button">Counseling Logs</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-skills" data-bs-toggle="pill" data-bs-target="#pills-skills" type="button">Skill Dev.</button>
                </li>
            </ul>

            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active" id="pills-progress">
                    <div class="card p-4">
                        <div class="timeline-line">
                            <?php while($h = mysqli_fetch_assoc($history)): ?>
                                <div class="mb-4 position-relative">
                                    <span class="badge bg-primary mb-2"><?= date("M d, Y", strtotime($h['created_at'])) ?></span>
                                    <p class="mb-1"><?= $h['progress_note'] ?></p>
                                    <div class="d-flex gap-2 mt-2">
                                        <small class="badge bg-light text-dark border">Skills: <?= $h['skill_training_status'] ?></small>
                                        <small class="badge bg-light text-dark border">Counseling: <?= $h['counseling_status'] ?></small>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="pills-counseling">
                    <div class="card p-4">
                        <h6 class="fw-bold mb-3">Session Records</h6>
                        <div class="table-responsive">
                            <table class="table small">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Counselor</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($s = mysqli_fetch_assoc($sessions)): ?>
                                        <tr>
                                            <td><?= date("d M, Y", strtotime($s['session_date'])) ?></td>
                                            <td><?= $s['counselor_name'] ?></td>
                                            <td><?= $s['duration_minutes'] ?>m</td>
                                            <td><?= $s['attended'] ? '<span class="text-success">Attended</span>' : '<span class="text-danger">Missed</span>' ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="pills-skills">
                    <div class="card p-4">
                        <h6 class="fw-bold mb-3">Training Progress</h6>
                        <?php while($sk = mysqli_fetch_assoc($skills)): ?>
                            <div class="border-bottom pb-2 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold"><?= $sk['skill_name'] ?></span>
                                    <span class="badge bg-info"><?= strtoupper($sk['status']) ?></span>
                                </div>
                                <small class="text-muted">Target: <?= date("M d, Y", strtotime($sk['target_date'])) ?></small>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="updateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Log Daily Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Update Note</label>
                        <textarea name="progress_note" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Skill Training Status</label>
                            <select name="skill_status" class="form-select">
                                <option>Planned</option>
                                <option>In Progress</option>
                                <option>Completed</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Counseling Status</label>
                            <select name="counseling_status" class="form-select">
                                <option>Active</option>
                                <option>Stable</option>
                                <option>Needs Attention</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_progress" class="btn btn-dark w-100">Post Progress</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>