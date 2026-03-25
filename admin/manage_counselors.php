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
    mysqli_query($conn, "DELETE FROM counselors WHERE id = $id");
    $message = "<div class='alert alert-success'>Counselor deleted successfully.</div>";
}

// --- Handle Add/Update ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name      = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email          = mysqli_real_escape_string($conn, $_POST['email']);
    $phone          = mysqli_real_escape_string($conn, $_POST['phone']);
    $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);
    $password       = $_POST['password'];

    if (!empty($_POST['counselor_id'])) {
        // UPDATE
        $id = intval($_POST['counselor_id']);
        
        // If password is provided, update it as hash; otherwise, keep old password
        if (!empty($password)) {
            $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
            $pw_query = ", password='$hashed_pw'";
        } else {
            $pw_query = "";
        }

        $sql = "UPDATE counselors SET 
                full_name='$full_name', email='$email', phone='$phone', 
                specialization='$specialization' $pw_query
                WHERE id=$id";
        
        if(mysqli_query($conn, $sql)) {
            $message = "<div class='alert alert-success'>Counselor updated successfully!</div>";
        }
    } else {
        // INSERT
        $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO counselors (full_name, email, phone, specialization, password)
                VALUES ('$full_name', '$email', '$phone', '$specialization', '$hashed_pw')";
        
        if(mysqli_query($conn, $sql)) {
            $message = "<div class='alert alert-success'>New counselor added successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: Email might already exist.</div>";
        }
    }
}

// Fetch counselors
$counselors = mysqli_query($conn, "SELECT * FROM counselors ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Counselors | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .table thead { background: #2d2d2d; color: #fff; }
        .modal-content { border-radius: 20px; border: none; }
        .form-label { margin-bottom: 0.2rem; }
    </style>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-user-tie me-2"></i>Counselor Management</h2>
        <button class="btn btn-dark rounded-pill px-4" onclick="openModal()">
            <i class="fas fa-plus me-2"></i>Add New Counselor
        </button>
    </div>

    <?= $message; ?>

    <div class="card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Specialization</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($counselors)): ?>
                    <tr>
                        <td class="ps-4 fw-bold"><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($row['specialization']) ?></span></td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-primary me-2" onclick='editCounselor(<?= json_encode($row) ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="manage_counselors.php?delete_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Are you sure you want to delete this counselor?')">
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

<div class="modal fade" id="counselorModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Counselor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="counselor_id" id="counselor_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="full_name" id="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Specialization</label>
                            <input type="text" name="specialization" id="specialization" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Leave blank to keep current">
                        <small class="text-muted" id="pwHelp">Required for new counselors.</small>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4">Save Counselor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modalEl = document.getElementById('counselorModal');
const counselorModal = new bootstrap.Modal(modalEl);

function openModal() {
    document.getElementById('counselor_id').value = '';
    document.getElementById('modalTitle').innerText = 'Add Counselor';
    document.getElementById('password').required = true;
    document.getElementById('pwHelp').innerText = "Must be a strong password.";
    document.querySelector('form').reset();
    counselorModal.show();
}

function editCounselor(data) {
    document.getElementById('counselor_id').value = data.id;
    document.getElementById('full_name').value = data.full_name;
    document.getElementById('email').value = data.email;
    document.getElementById('phone').value = data.phone;
    document.getElementById('specialization').value = data.specialization;
    
    document.getElementById('password').required = false;
    document.getElementById('pwHelp').innerText = "Leave blank if you don't want to change the password.";
    document.getElementById('modalTitle').innerText = 'Edit Counselor';
    counselorModal.show();
}
</script>
</body>
</html>