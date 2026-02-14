<?php
session_start();
include('config.php');

// Security check: Only logged-in NGOs
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'ngo') {
    header("Location: ../login.php");
    exit();
}

$ngo_id = $_SESSION['id'];
$message = "";

// Get the case_id from GET
if (!isset($_GET['case_id'])) {
    die("<div class='alert alert-danger m-5'>No case selected.</div>");
}
$case_id = intval($_GET['case_id']);

// Handle Delete
if (isset($_GET['delete_id'])) {
    $skill_id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM skill_development WHERE skill_id = $skill_id");
    $message = "<div class='alert alert-success'>Skill deleted successfully.</div>";
}

// Handle Add/Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $skill_id       = !empty($_POST['skill_id']) ? intval($_POST['skill_id']) : null;
    $skill_name     = mysqli_real_escape_string($conn, $_POST['skill_name']);
    $start_date     = !empty($_POST['start_date']) ? "'".$_POST['start_date']."'" : "NULL";
    $target_date    = !empty($_POST['target_date']) ? "'".$_POST['target_date']."'" : "NULL";
    $completion_date= !empty($_POST['completion_date']) ? "'".$_POST['completion_date']."'" : "NULL";
    $status         = $_POST['status'];
    $notes          = mysqli_real_escape_string($conn, $_POST['notes']);

    if ($skill_id) {
        // Update
        $sql = "UPDATE skill_development SET 
                    skill_name='$skill_name', start_date=$start_date, target_date=$target_date,
                    completion_date=$completion_date, status='$status', notes='$notes'
                WHERE skill_id=$skill_id AND case_id=$case_id";
        mysqli_query($conn, $sql);
        $message = "<div class='alert alert-success'>Skill updated successfully!</div>";
    } else {
        // Insert
        $sql = "INSERT INTO skill_development 
                (case_id, skill_name, start_date, target_date, completion_date, status, notes)
                VALUES ($case_id, '$skill_name', $start_date, $target_date, $completion_date, '$status', '$notes')";
        mysqli_query($conn, $sql);
        $message = "<div class='alert alert-success'>New skill added successfully!</div>";
    }
}

// Fetch all skills for this case
$skills = mysqli_query($conn, "SELECT * FROM skill_development WHERE case_id=$case_id ORDER BY start_date DESC");

// Fetch case title for heading
$case_result = mysqli_query($conn, "SELECT case_title FROM cases WHERE case_id=$case_id");
$case = mysqli_fetch_assoc($case_result);
$case_title = $case['case_title'] ?? 'Case';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Skill Development | <?= htmlspecialchars($case_title) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/style.css">

<style>
.card { border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
.modal-content { border-radius: 20px; }
</style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-lightbulb me-2"></i>Skill Development for "<?= htmlspecialchars($case_title) ?>"</h2>
        <button class="btn btn-dark rounded-pill px-4" onclick="openModal()">
            <i class="fas fa-plus me-2"></i>Add Skill
        </button>
    </div>

    <?= $message ?>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Skill Name</th>
                        <th>Start Date</th>
                        <th>Target Date</th>
                        <th>Completion Date</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($skills)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['skill_name']) ?></td>
                        <td><?= $row['start_date'] ?: '-' ?></td>
                        <td><?= $row['target_date'] ?: '-' ?></td>
                        <td><?= $row['completion_date'] ?: '-' ?></td>
                        <td>
                            <?php 
                                $badge = match($row['status']) {
                                    'planned' => 'bg-secondary text-white',
                                    'in_progress' => 'bg-primary text-white',
                                    'completed' => 'bg-success text-white',
                                    'dropped' => 'bg-danger text-white',
                                    default => 'bg-light text-dark'
                                };
                            ?>
                            <span class="badge <?= $badge ?>"><?= $row['status'] ?></span>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-primary me-2" onclick='editSkill(<?= json_encode($row) ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="manage_skills.php?case_id=<?= $case_id ?>&delete_id=<?= $row['skill_id'] ?>" 
                               class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Delete this skill?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if(mysqli_num_rows($skills) == 0): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No skills added yet.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="skillModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Skill</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="skill_id" id="skill_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Skill Name</label>
                        <input type="text" name="skill_name" id="skill_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Target Date</label>
                        <input type="date" name="target_date" id="target_date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Completion Date</label>
                        <input type="date" name="completion_date" id="completion_date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="planned">Planned</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="dropped">Dropped</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4">Save Skill</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modalEl = document.getElementById('skillModal');
const skillModal = new bootstrap.Modal(modalEl);

function openModal() {
    document.getElementById('skill_id').value = '';
    document.getElementById('modalTitle').innerText = 'Add Skill';
    document.querySelector('form').reset();
    skillModal.show();
}

function editSkill(data) {
    document.getElementById('skill_id').value = data.skill_id;
    document.getElementById('skill_name').value = data.skill_name;
    document.getElementById('start_date').value = data.start_date || '';
    document.getElementById('target_date').value = data.target_date || '';
    document.getElementById('completion_date').value = data.completion_date || '';
    document.getElementById('status').value = data.status;
    document.getElementById('notes').value = data.notes;
    document.getElementById('modalTitle').innerText = 'Edit Skill';
    skillModal.show();
}
</script>
</body>
</html>
