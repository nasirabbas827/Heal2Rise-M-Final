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
    $session_id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM counseling_sessions WHERE session_id = $session_id");
    $message = "<div class='alert alert-success'>Session deleted successfully.</div>";
}

// Handle Add/Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $session_id      = !empty($_POST['session_id']) ? intval($_POST['session_id']) : null;
    $session_date    = $_POST['session_date'];
    $counselor_name  = mysqli_real_escape_string($conn, $_POST['counselor_name']);
    $duration        = intval($_POST['duration_minutes']);
    $notes           = mysqli_real_escape_string($conn, $_POST['notes']);
    $attended        = isset($_POST['attended']) ? 1 : 0;
    $next_date       = !empty($_POST['next_suggested_date']) ? "'".$_POST['next_suggested_date']."'" : "NULL";

    if ($session_id) {
        // Update
        $sql = "UPDATE counseling_sessions SET 
                    session_date='$session_date', counselor_name='$counselor_name',
                    duration_minutes=$duration, notes='$notes', attended=$attended,
                    next_suggested_date=$next_date
                WHERE session_id=$session_id AND case_id=$case_id";
        mysqli_query($conn, $sql);
        $message = "<div class='alert alert-success'>Session updated successfully!</div>";
    } else {
        // Insert
        $sql = "INSERT INTO counseling_sessions 
                (case_id, session_date, counselor_name, duration_minutes, notes, attended, next_suggested_date)
                VALUES ($case_id, '$session_date', '$counselor_name', $duration, '$notes', $attended, $next_date)";
        mysqli_query($conn, $sql);
        $message = "<div class='alert alert-success'>New session added successfully!</div>";
    }
}

// Fetch all sessions for this case
$sessions = mysqli_query($conn, "SELECT * FROM counseling_sessions WHERE case_id=$case_id ORDER BY session_date DESC");

// Fetch case title for heading
$case_result = mysqli_query($conn, "SELECT case_title FROM cases WHERE case_id=$case_id");
$case = mysqli_fetch_assoc($case_result);
$case_title = $case['case_title'] ?? 'Case';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Counselling Sessions | <?= htmlspecialchars($case_title) ?></title>
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
        <h2 class="fw-bold"><i class="fas fa-user-md me-2"></i>Counselling Sessions for "<?= htmlspecialchars($case_title) ?>"</h2>
        <button class="btn btn-dark rounded-pill px-4" onclick="openModal()">
            <i class="fas fa-plus me-2"></i>Add Session
        </button>
    </div>

    <?= $message ?>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Session Date</th>
                        <th>Counselor</th>
                        <th>Duration (min)</th>
                        <th>Attended</th>
                        <th>Next Suggested Date</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($sessions)): ?>
                    <tr>
                        <td><?= $row['session_date'] ?></td>
                        <td><?= htmlspecialchars($row['counselor_name']) ?></td>
                        <td><?= $row['duration_minutes'] ?></td>
                        <td>
                            <?php if($row['attended']): ?>
                                <span class="badge bg-success">Yes</span>
                            <?php else: ?>
                                <span class="badge bg-danger">No</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $row['next_suggested_date'] ?: '-' ?></td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-primary me-2" onclick='editSession(<?= json_encode($row) ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="manage_counselling_session.php?case_id=<?= $case_id ?>&delete_id=<?= $row['session_id'] ?>" 
                               class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Delete this session?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if(mysqli_num_rows($sessions) == 0): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No sessions added yet.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="sessionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Counselling Session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="session_id" id="session_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Session Date & Time</label>
                        <input type="datetime-local" name="session_date" id="session_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Counselor Name</label>
                        <input type="text" name="counselor_name" id="counselor_name" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Duration (minutes)</label>
                        <input type="number" name="duration_minutes" id="duration_minutes" class="form-control" value="45" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="attended" name="attended" checked>
                        <label class="form-check-label" for="attended">Attended</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Next Suggested Date</label>
                        <input type="date" name="next_suggested_date" id="next_suggested_date" class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4">Save Session</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modalEl = document.getElementById('sessionModal');
const sessionModal = new bootstrap.Modal(modalEl);

function openModal() {
    document.getElementById('session_id').value = '';
    document.getElementById('modalTitle').innerText = 'Add Counselling Session';
    document.querySelector('form').reset();
    document.getElementById('attended').checked = true;
    sessionModal.show();
}

function editSession(data) {
    document.getElementById('session_id').value = data.session_id;
    document.getElementById('session_date').value = data.session_date.replace(' ', 'T');
    document.getElementById('counselor_name').value = data.counselor_name;
    document.getElementById('duration_minutes').value = data.duration_minutes;
    document.getElementById('notes').value = data.notes;
    document.getElementById('attended').checked = data.attended == 1;
    document.getElementById('next_suggested_date').value = data.next_suggested_date || '';
    document.getElementById('modalTitle').innerText = 'Edit Counselling Session';
    sessionModal.show();
}
</script>
</body>
</html>
