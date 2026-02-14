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
    mysqli_query($conn, "DELETE FROM team_members WHERE team_member_id = $id");
    $message = "<div class='alert alert-success'>Member deleted successfully.</div>";
}

// --- Handle Add/Update ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email     = mysqli_real_escape_string($conn, $_POST['email']);
    $phone     = mysqli_real_escape_string($conn, $_POST['phone']);
    $expertise = mysqli_real_escape_string($conn, $_POST['expertise']);
    $ngo_id    = !empty($_POST['ngo_id']) ? intval($_POST['ngo_id']) : "NULL";
    $is_active = isset($_POST['is_active']) ? 1 : 0; // New Active/Inactive field
    
    if (!empty($_POST['team_member_id'])) {
        // UPDATE
        $mid = intval($_POST['team_member_id']);
        $sql = "UPDATE team_members 
                SET full_name='$full_name', email='$email', phone='$phone', expertise='$expertise', ngo_id=$ngo_id, is_active=$is_active 
                WHERE team_member_id=$mid";
        mysqli_query($conn, $sql);
        $message = "<div class='alert alert-success'>Member updated successfully!</div>";
    } else {
        // INSERT
        $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $sql = "INSERT INTO team_members (full_name, email, password_hash, phone, expertise, ngo_id, is_active) 
                VALUES ('$full_name', '$email', '$pass', '$phone', '$expertise', $ngo_id, $is_active)";
        mysqli_query($conn, $sql);
        $message = "<div class='alert alert-success'>New member added successfully!</div>";
    }
}

// Fetch NGOs for dropdown
$ngos = mysqli_query($conn, "SELECT id, ngo_name FROM ngos WHERE is_active = 1");

// Fetch team members with NGO names
$query = "SELECT t.*, n.ngo_name FROM team_members t LEFT JOIN ngos n ON t.ngo_id = n.id ORDER BY t.created_at DESC";
$members = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Team | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/style.css">
    <style>
        .member-card { border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .table thead { background: #2d2d2d; color: #fff; }
        .modal-content { border-radius: 20px; border: none; }
        .btn-luxury { background: #a88d7b; color: white; border-radius: 10px; }
        .btn-luxury:hover { background: #8d6e63; color: white; }
    </style>
</head>
<body class="bg-light">

<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-users-cog me-2"></i>Team Management</h2>
        <button class="btn btn-dark rounded-pill px-4" onclick="openModal()">
            <i class="fas fa-plus me-2"></i>Add New Member
        </button>
    </div>

    <?= $message; ?>

    <div class="card member-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Member Info</th>
                        <th>Affiliation</th>
                        <th>Expertise</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($members)): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($row['email']) ?></div>
                        </td>
                        <td>
                            <?php if($row['ngo_name']): ?>
                                <span class="badge bg-info text-dark"><?= htmlspecialchars($row['ngo_name']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Central Team</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="text-muted small"><?= htmlspecialchars($row['expertise']) ?></span></td>
                        <td><div class="small"><?= $row['phone'] ?></div></td>
                        <td>
                            <?php if($row['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-primary me-2" 
                                    onclick='editMember(<?= json_encode($row) ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="manage_members.php?delete_id=<?= $row['team_member_id'] ?>" 
                               class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Delete this member?')">
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

<!-- MODAL -->
<div class="modal fade" id="memberModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Team Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="team_member_id" id="team_member_id">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="full_name" id="full_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>

                    <div id="passwordField" class="mb-3">
                        <label class="form-label small fw-bold">Password</label>
                        <input type="password" name="password" id="password" class="form-control">
                        <small class="text-muted">Only required for new members</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Assign to NGO</label>
                            <select name="ngo_id" id="ngo_id" class="form-select">
                                <option value="">-- Central (None) --</option>
                                <?php mysqli_data_seek($ngos, 0); ?>
                                <?php while($n = mysqli_fetch_assoc($ngos)): ?>
                                    <option value="<?= $n['id'] ?>"><?= $n['ngo_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Expertise</label>
                        <input type="text" name="expertise" id="expertise" class="form-control" placeholder="e.g. Psychology, Legal Aid">
                    </div>

                    <!-- Active/Inactive toggle -->
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input">
                        <label for="is_active" class="form-check-label small fw-bold">Active</label>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4">Save Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const modalEl = document.getElementById('memberModal');
    const memberModal = new bootstrap.Modal(modalEl);

    function openModal() {
        document.getElementById('team_member_id').value = '';
        document.getElementById('modalTitle').innerText = 'Add Team Member';
        document.getElementById('passwordField').style.display = 'block';
        document.getElementById('password').required = true;
        document.getElementById('is_active').checked = true; // default Active
        document.querySelector('form').reset();
        memberModal.show();
    }

    function editMember(data) {
        document.getElementById('team_member_id').value = data.team_member_id;
        document.getElementById('full_name').value = data.full_name;
        document.getElementById('email').value = data.email;
        document.getElementById('phone').value = data.phone;
        document.getElementById('expertise').value = data.expertise;
        document.getElementById('ngo_id').value = data.ngo_id || '';
        document.getElementById('is_active').checked = data.is_active == 1 ? true : false; // Active status

        document.getElementById('modalTitle').innerText = 'Edit Member Profile';
        document.getElementById('passwordField').style.display = 'none';
        document.getElementById('password').required = false;

        memberModal.show();
    }
</script>

</body>
</html>
