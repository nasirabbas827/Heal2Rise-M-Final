<?php
include('../config.php');
session_start();

// Security Check
if (!isset($_SESSION['id']) || ($_SESSION['usertype'] !== 'counselor' && $_SESSION['usertype'] !== 'team_member')) { 
    header("Location: ../login.php"); 
    exit(); 
}

$case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;
$success_msg = $error_msg = "";

// --- 1. HANDLE DELETE ACTION ---
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $stmt_del = $conn->prepare("DELETE FROM skill_development WHERE skill_id = ? AND case_id = ?");
    $stmt_del->bind_param("ii", $del_id, $case_id);
    if ($stmt_del->execute()) {
        $success_msg = "Skill record removed.";
    }
    $stmt_del->close();
}

// --- 2. HANDLE FORM SUBMISSION (CREATE/UPDATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $skill_name = trim($_POST['skill_name']);
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $target_date = !empty($_POST['target_date']) ? $_POST['target_date'] : null;
    $completion_date = !empty($_POST['completion_date']) ? $_POST['completion_date'] : null;
    $status = $_POST['status'];
    $notes = trim($_POST['notes']);

    if (isset($_POST['skill_id']) && !empty($_POST['skill_id'])) {
        // UPDATE
        $s_id = intval($_POST['skill_id']);
        $sql = "UPDATE skill_development SET skill_name=?, start_date=?, target_date=?, completion_date=?, status=?, notes=? WHERE skill_id=? AND case_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssii", $skill_name, $start_date, $target_date, $completion_date, $status, $notes, $s_id, $case_id);
    } else {
        // INSERT
        $sql = "INSERT INTO skill_development (case_id, skill_name, start_date, target_date, status, notes) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssss", $case_id, $skill_name, $start_date, $target_date, $status, $notes);
    }
    
    if ($stmt->execute()) {
        $success_msg = "Skill progress updated successfully!";
        $conn->query("UPDATE cases SET last_updated = CURRENT_TIMESTAMP WHERE case_id = $case_id");
    } else {
        $error_msg = "Database error: " . $conn->error;
    }
    $stmt->close();
}

// --- 3. FETCH DATA ---
$stmt_case = $conn->prepare("SELECT c.case_title, u.full_name as patient_name FROM cases c JOIN users u ON c.user_id = u.id WHERE c.case_id = ?");
$stmt_case->bind_param("i", $case_id);
$stmt_case->execute();
$case_data = $stmt_case->get_result()->fetch_assoc();

$skills_query = $conn->prepare("SELECT * FROM skill_development WHERE case_id = ? ORDER BY skill_id DESC");
$skills_query->bind_param("i", $case_id);
$skills_query->execute();
$skills_result = $skills_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Skill Development | Heal2Rise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .skill-card, .history-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .btn-primary-custom { background: #0d6efd; color: white; border-radius: 10px; border: none; }
        .status-planned { border-left: 5px solid #6c757d; }
        .status-in_progress { border-left: 5px solid #ffc107; }
        .status-completed { border-left: 5px solid #198754; }
        .status-dropped { border-left: 5px solid #dc3545; }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card skill-card p-4 bg-white sticky-top" style="top: 20px;">
                <h4 class="fw-bold mb-1" id="form-title">Track New Skill</h4>
                <p class="text-muted small mb-4">Patient: <?= htmlspecialchars($case_data['patient_name'] ?? 'Unknown') ?></p>

                <?php if($success_msg): ?> <div class="alert alert-success py-2 small"><?= $success_msg ?></div> <?php endif; ?>

                <form method="POST" id="skill-form">
                    <input type="hidden" name="skill_id" id="skill_id">
                    
                    <div class="mb-3">
                        <label class="small fw-bold">Skill Name (e.g. Graphic Design)</label>
                        <input type="text" name="skill_name" id="f_name" class="form-control" required placeholder="Enter skill name">
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Current Status</label>
                        <select name="status" id="f_status" class="form-select">
                            <option value="planned">Planned</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="dropped">Dropped</option>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="small fw-bold">Start Date</label>
                            <input type="date" name="start_date" id="f_start" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold">Target Date</label>
                            <input type="date" name="target_date" id="f_target" class="form-control">
                        </div>
                    </div>

                    <div id="completion_div" class="mb-3" style="display:none;">
                        <label class="small fw-bold text-success">Completion Date</label>
                        <input type="date" name="completion_date" id="f_done" class="form-control border-success">
                    </div>

                    <div class="mb-4">
                        <label class="small fw-bold">Instructor Notes</label>
                        <textarea name="notes" id="f_notes" class="form-control" rows="3"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-2 shadow-sm">Save Skill Update</button>
                    <button type="button" onclick="resetForm()" class="btn btn-light w-100 mt-2 btn-sm" id="cancel-edit" style="display:none;">Cancel Edit</button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <h5 class="fw-bold mb-4">Skill Development Timeline</h5>
            <?php if ($skills_result->num_rows > 0): ?>
                <?php while($row = $skills_result->fetch_assoc()): ?>
                <div class="card history-card p-3 mb-3 bg-white status-<?= $row['status'] ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-light text-dark mb-1"><?= strtoupper(str_replace('_', ' ', $row['status'])) ?></span>
                            <h5 class="fw-bold mb-0"><?= htmlspecialchars($row['skill_name']) ?></h5>
                            <p class="small text-muted mb-1">
                                <i class="far fa-calendar-alt me-1"></i> Start: <?= $row['start_date'] ?? 'N/A' ?> 
                                | <i class="fas fa-flag-checkered ms-2 me-1"></i> Target: <?= $row['target_date'] ?? 'N/A' ?>
                            </p>
                            <?php if($row['notes']): ?>
                                <p class="small text-secondary mb-0 mt-2 bg-light p-2 rounded"><?= htmlspecialchars($row['notes']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="text-end">
                            <button class="btn btn-sm btn-outline-primary border-0" onclick='editSkill(<?= json_encode($row) ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?case_id=<?= $case_id ?>&delete_id=<?= $row['skill_id'] ?>" 
                               class="btn btn-sm btn-outline-danger border-0" 
                               onclick="return confirm('Remove this skill tracking?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                    <i class="fas fa-tools fa-3x text-muted mb-3 opacity-25"></i>
                    <p class="text-muted">No skill development records found for this patient.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.getElementById('f_status').addEventListener('change', function() {
    document.getElementById('completion_div').style.display = (this.value === 'completed') ? 'block' : 'none';
});

function editSkill(data) {
    document.getElementById('form-title').innerText = "Edit Skill Info";
    document.getElementById('skill_id').value = data.skill_id;
    document.getElementById('f_name').value = data.skill_name;
    document.getElementById('f_status').value = data.status;
    document.getElementById('f_start').value = data.start_date;
    document.getElementById('f_target').value = data.target_date;
    document.getElementById('f_done').value = data.completion_date;
    document.getElementById('f_notes').value = data.notes;
    
    // Show completion date field if status is completed
    document.getElementById('completion_div').style.display = (data.status === 'completed') ? 'block' : 'none';
    
    document.getElementById('cancel-edit').style.display = "block";
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('skill-form').reset();
    document.getElementById('skill_id').value = "";
    document.getElementById('form-title').innerText = "Track New Skill";
    document.getElementById('cancel-edit').style.display = "none";
    document.getElementById('completion_div').style.display = "none";
}
</script>
</body>
</html>