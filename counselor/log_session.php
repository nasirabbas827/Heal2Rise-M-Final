<?php
include('../config.php');
session_start();

// Security Check
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'counselor') { 
    header("Location: ../login.php"); 
    exit(); 
}

$counselor_id = $_SESSION['id'];
$counselor_name = $_SESSION['full_name'];
$case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;
$success_msg = $error_msg = "";

// --- 1. HANDLE DELETE ACTION ---
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $stmt_del = $conn->prepare("DELETE FROM counseling_sessions WHERE session_id = ? AND case_id = ?");
    $stmt_del->bind_param("ii", $del_id, $case_id);
    if ($stmt_del->execute()) {
        $success_msg = "Session record deleted successfully.";
    }
    $stmt_del->close();
}

// --- 2. HANDLE FORM SUBMISSION (CREATE/UPDATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $session_date = $_POST['session_date'];
    $duration = intval($_POST['duration_minutes']);
    $notes = trim($_POST['notes']);
    $attended = isset($_POST['attended']) ? 1 : 0;
    $next_date = !empty($_POST['next_suggested_date']) ? $_POST['next_suggested_date'] : null;

    if (isset($_POST['session_id']) && !empty($_POST['session_id'])) {
        // UPDATE EXISTING
        $s_id = intval($_POST['session_id']);
        $sql = "UPDATE counseling_sessions SET session_date=?, duration_minutes=?, notes=?, attended=?, next_suggested_date=? WHERE session_id=? AND case_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisissi", $session_date, $duration, $notes, $attended, $next_date, $s_id, $case_id);
    } else {
        // INSERT NEW
        $sql = "INSERT INTO counseling_sessions (case_id, session_date, counselor_name, duration_minutes, notes, attended, next_suggested_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issisis", $case_id, $session_date, $counselor_name, $duration, $notes, $attended, $next_date);
    }
    
    if ($stmt->execute()) {
        $success_msg = "Database updated successfully!";
        $conn->query("UPDATE cases SET last_updated = CURRENT_TIMESTAMP WHERE case_id = $case_id");
    } else {
        $error_msg = "Error: " . $conn->error;
    }
    $stmt->close();
}

// --- 3. FETCH DATA ---
$stmt_case = $conn->prepare("SELECT c.case_title, u.full_name as patient_name FROM cases c JOIN users u ON c.user_id = u.id WHERE c.case_id = ? AND c.counselor_id = ?");
$stmt_case->bind_param("ii", $case_id, $counselor_id);
$stmt_case->execute();
$case_data = $stmt_case->get_result()->fetch_assoc();

if (!$case_data) { die("Unauthorized access or invalid case."); }

$history_query = $conn->prepare("SELECT * FROM counseling_sessions WHERE case_id = ? ORDER BY session_date DESC");
$history_query->bind_param("i", $case_id);
$history_query->execute();
$history_result = $history_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Sessions | Heal2Rise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .log-card, .history-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .btn-save { background: #1a1a1a; color: white; border-radius: 10px; border: none; }
        .table-custom thead { background: #f8f9fa; color: #666; font-size: 0.8rem; text-transform: uppercase; }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card log-card p-4 bg-white sticky-top" style="top: 20px; z-index: 1000;">
                <h4 class="fw-bold mb-1" id="form-title">New Session Log</h4>
                <p class="text-muted small mb-4">Patient: <?= htmlspecialchars($case_data['patient_name']) ?></p>

                <?php if($success_msg): ?> <div class="alert alert-success small"><?= $success_msg ?></div> <?php endif; ?>

                <form method="POST" id="session-form">
                    <input type="hidden" name="session_id" id="session_id">
                    <div class="mb-3">
                        <label class="small fw-bold">Date & Time</label>
                        <input type="datetime-local" name="session_date" id="f_date" class="form-control" required value="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="small fw-bold">Duration (Min)</label>
                            <input type="number" name="duration_minutes" id="f_duration" class="form-control" value="45">
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="attended" id="f_attended" checked>
                                <label class="small fw-bold">Attended</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Clinical Notes</label>
                        <textarea name="notes" id="f_notes" class="form-control" rows="5" placeholder="Session details..."></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="small fw-bold">Next Date</label>
                        <input type="date" name="next_suggested_date" id="f_next" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-save w-100 py-2 shadow-sm">Save Log Entry</button>
                    <button type="button" onclick="resetForm()" class="btn btn-light w-100 mt-2 btn-sm" id="cancel-edit" style="display:none;">Cancel Edit</button>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card history-card p-4 bg-white">
                <h5 class="fw-bold mb-4">Session History</h5>
                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $history_result->fetch_assoc()): ?>
                            <tr>
                                <td class="small fw-bold text-nowrap"><?= date('M d, H:i', strtotime($row['session_date'])) ?></td>
                                <td>
                                    <span class="badge rounded-pill <?= $row['attended'] ? 'bg-light text-success' : 'bg-light text-danger' ?>">
                                        <?= $row['attended'] ? 'Attended' : 'Missed' ?>
                                    </span>
                                </td>
                                <td class="small text-muted text-truncate" style="max-width: 150px;"><?= htmlspecialchars($row['notes']) ?></td>
                                <td class="text-end text-nowrap">
                                    <button class="btn btn-sm btn-outline-primary border-0" 
                                            onclick='editSession(<?= json_encode($row) ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?case_id=<?= $case_id ?>&delete_id=<?= $row['session_id'] ?>" 
                                       class="btn btn-sm btn-outline-danger border-0" 
                                       onclick="return confirm('Delete this record?')">
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
    </div>
</div>

<script>
function editSession(data) {
    document.getElementById('form-title').innerText = "Edit Session Log";
    document.getElementById('session_id').value = data.session_id;
    document.getElementById('f_date').value = data.session_date.replace(" ", "T").substring(0, 16);
    document.getElementById('f_duration').value = data.duration_minutes;
    document.getElementById('f_notes').value = data.notes;
    document.getElementById('f_attended').checked = (data.attended == 1);
    document.getElementById('f_next').value = data.next_suggested_date;
    document.getElementById('cancel-edit').style.display = "block";
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('session-form').reset();
    document.getElementById('session_id').value = "";
    document.getElementById('form-title').innerText = "New Session Log";
    document.getElementById('cancel-edit').style.display = "none";
}
</script>
</body>
</html>