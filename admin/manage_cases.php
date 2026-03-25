<?php
session_start();
include "config.php";

// Admin Protection
if (!isset($_SESSION['admin_logged_in'])) { 
    header("Location: adminlogin.php"); 
    exit(); 
}

$message = "";

// --- Handle Delete ---
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM cases WHERE case_id = $id");
    $message = "<div class='alert alert-success'>Case deleted successfully.</div>";
}

// --- Handle Add/Update ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id        = intval($_POST['user_id']);
    $ngo_id         = !empty($_POST['ngo_id']) ? intval($_POST['ngo_id']) : "NULL";
    $team_member_id = !empty($_POST['team_member_id']) ? intval($_POST['team_member_id']) : "NULL";
    $counselor_id   = !empty($_POST['counselor_id']) ? intval($_POST['counselor_id']) : "NULL";
    $case_title     = mysqli_real_escape_string($conn, $_POST['case_title']);
    $issue_category = mysqli_real_escape_string($conn, $_POST['issue_category']);
    $description    = mysqli_real_escape_string($conn, $_POST['description']);
    $start_date     = $_POST['start_date'];
    $status         = $_POST['status'];
    $closure_date   = !empty($_POST['closure_date']) ? "'".$_POST['closure_date']."'" : "NULL";
    $closure_reason = !empty($_POST['closure_reason']) ? "'".mysqli_real_escape_string($conn, $_POST['closure_reason'])."'" : "NULL";

    if (!empty($_POST['case_id'])) {
        // UPDATE
        $case_id = intval($_POST['case_id']);
        $sql = "UPDATE cases SET 
                    user_id=$user_id, ngo_id=$ngo_id, team_member_id=$team_member_id, counselor_id=$counselor_id,
                    case_title='$case_title', issue_category='$issue_category', description='$description',
                    start_date='$start_date', status='$status', closure_date=$closure_date, closure_reason=$closure_reason
                WHERE case_id=$case_id";
        mysqli_query($conn, $sql);
        $message = "<div class='alert alert-success fw-bold'>Case updated successfully!</div>";
    } else {
        // INSERT
        $sql = "INSERT INTO cases (user_id, ngo_id, team_member_id, counselor_id, case_title, issue_category, description, start_date, status, closure_date, closure_reason)
                VALUES ($user_id, $ngo_id, $team_member_id, $counselor_id, '$case_title', '$issue_category', '$description', '$start_date', '$status', $closure_date, $closure_reason)";
        mysqli_query($conn, $sql);
        $message = "<div class='alert alert-success fw-bold'>New case added successfully!</div>";
    }
}

// Dropdown Data
$users = mysqli_query($conn, "SELECT id as user_id, full_name FROM users");
$ngos = mysqli_query($conn, "SELECT id, ngo_name FROM ngos WHERE is_active = 1");
$members = mysqli_query($conn, "SELECT team_member_id, full_name FROM team_members");
$counselors_list = mysqli_query($conn, "SELECT id, full_name FROM counselors");

// Fetch cases
$cases = mysqli_query($conn, "SELECT c.*, u.full_name as user_name, n.ngo_name, t.full_name as member_name, co.full_name as counselor_name 
                              FROM cases c 
                              LEFT JOIN users u ON c.user_id = u.id
                              LEFT JOIN ngos n ON c.ngo_id = n.id
                              LEFT JOIN team_members t ON c.team_member_id = t.team_member_id
                              LEFT JOIN counselors co ON c.counselor_id = co.id
                              ORDER BY c.assigned_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Case Management | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .table thead { background: #2d2d2d; color: #fff; }
        .modal-content { border-radius: 20px; border: none; }
        .badge-completed { background-color: #198754; color: white; }
    </style>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-folder-open me-2"></i>Case Management</h2>
        <button class="btn btn-dark rounded-pill px-4" onclick="openModal()">
            <i class="fas fa-plus me-2"></i>Add Case
        </button>
    </div>

    <?= $message; ?>

    <div class="card overflow-hidden shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Title & Category</th>
                        <th>User</th>
                        <th>Assignments</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($cases)): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold"><?= htmlspecialchars($row['case_title']) ?></div>
                            <span class="text-muted small"><?= htmlspecialchars($row['issue_category']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                        <td>
                            <div class="small"><strong>T:</strong> <?= $row['member_name'] ?: '-' ?></div>
                            <div class="small"><strong>C:</strong> <?= $row['counselor_name'] ?: '-' ?></div>
                        </td>
                        <td>
                            <?php 
                                $badge = match($row['status']) {
                                    'open' => 'bg-warning text-dark',
                                    'in_progress' => 'bg-primary text-white',
                                    'rehabilitation' => 'bg-info text-dark',
                                    'completed' => 'badge-completed',
                                    'closed' => 'bg-dark text-white',
                                    'rejected' => 'bg-danger text-white',
                                    default => 'bg-secondary'
                                };
                            ?>
                            <span class="badge <?= $badge ?>"><?= ucwords(str_replace('_', ' ', $row['status'])) ?></span>
                        </td>
                            
                        <td class="text-end pe-4">
                            <a href="admin_case_details.php?id=<?= $row['case_id'] ?>" class="btn btn-sm btn-outline-dark me-2" title="View Progress History">
        <i class="fas fa-history"></i>
    </a>
                            <button class="btn btn-sm btn-outline-primary me-2" onclick='editCase(<?= json_encode($row) ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="manage_cases.php?delete_id=<?= $row['case_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Confirm deletion?')">
                               <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="caseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content p-3 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Case Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="case_id" id="case_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">User</label>
                            <select name="user_id" id="user_id" class="form-select" required>
                                <option value="">Select User...</option>
                                <?php mysqli_data_seek($users, 0); while($u=mysqli_fetch_assoc($users)): ?>
                                <option value="<?= $u['user_id'] ?>"><?= $u['full_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Assign NGO</label>
                            <select name="ngo_id" id="ngo_id" class="form-select">
                                <option value="">-- Central --</option>
                                <?php mysqli_data_seek($ngos, 0); while($n=mysqli_fetch_assoc($ngos)): ?>
                                <option value="<?= $n['id'] ?>"><?= $n['ngo_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Assign Team Member</label>
                            <select name="team_member_id" id="team_member_id" class="form-select">
                                <option value="">-- None --</option>
                                <?php mysqli_data_seek($members, 0); while($m=mysqli_fetch_assoc($members)): ?>
                                <option value="<?= $m['team_member_id'] ?>"><?= $m['full_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Assign Counselor</label>
                            <select name="counselor_id" id="counselor_id" class="form-select">
                                <option value="">-- None --</option>
                                <?php mysqli_data_seek($counselors_list, 0); while($cl=mysqli_fetch_assoc($counselors_list)): ?>
                                <option value="<?= $cl['id'] ?>"><?= $cl['full_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Case Title</label>
                        <input type="text" name="case_title" id="case_title" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Issue Category</label>
                            <select name="issue_category" id="issue_category" class="form-select" required>
                                <option value="">Select a category...</option>
                                <option value="Health">Health & Medical</option>
                                <option value="Education">Education</option>
                                <option value="Legal">Legal Aid</option>
                                <option value="Psychology">Psychological Support</option>
                                <option value="Vocational Training">Vocational Training</option>
                                <option value="Financial">Financial Assistance</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="rehabilitation">Rehabilitation</option>
                                <option value="completed">Completed</option>
                                <option value="closed">Closed</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Closure Date</label>
                            <input type="date" name="closure_date" id="closure_date" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4">Save Case</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modalEl = document.getElementById('caseModal');
const caseModal = new bootstrap.Modal(modalEl);

function openModal() {
    document.getElementById('case_id').value = '';
    document.getElementById('modalTitle').innerText = 'Add Case';
    document.querySelector('form').reset();
    caseModal.show();
}

function editCase(data) {
    document.getElementById('case_id').value = data.case_id;
    document.getElementById('user_id').value = data.user_id;
    document.getElementById('ngo_id').value = data.ngo_id || '';
    document.getElementById('team_member_id').value = data.team_member_id || '';
    document.getElementById('counselor_id').value = data.counselor_id || '';
    document.getElementById('issue_category').value = data.issue_category;
    document.getElementById('case_title').value = data.case_title;
    document.getElementById('description').value = data.description;
    document.getElementById('start_date').value = data.start_date;
    document.getElementById('status').value = data.status;
    document.getElementById('closure_date').value = data.closure_date || '';
    
    document.getElementById('modalTitle').innerText = 'Edit Case';
    caseModal.show();
}
</script>
</body>
</html>