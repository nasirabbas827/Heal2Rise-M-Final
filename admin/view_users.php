<?php
session_start();
include "config.php";

// Admin Protection
if (!isset($_SESSION['admin_logged_in'])) { 
    header("Location: adminlogin.php"); 
    exit(); 
}

// Toggle User Status (Active/Inactive)
if (isset($_GET['toggle_id']) && isset($_GET['current_status'])) {
    $user_id = intval($_GET['toggle_id']);
    $new_status = ($_GET['current_status'] == 1) ? 0 : 1;
    
    $update_query = "UPDATE users SET is_active = $new_status WHERE id = $user_id";
    if (mysqli_query($conn, $update_query)) {
        $message = "<div class='alert alert-success'>User status updated successfully!</div>";
    }
}

// Fetch all users
$query = "SELECT * FROM users ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users | Heal2Rise Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/style.css">

    <style>
        body { background-color: #f4f7f6; }
        .user-card { border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .table thead { background-color: #212529; color: white; }
        .status-badge { cursor: pointer; transition: 0.3s; }
        .status-badge:hover { opacity: 0.8; transform: scale(1.05); }
        .detail-row { font-size: 0.85rem; color: #6c757d; }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-users-cog me-2 text-warning"></i>Users Directory</h2>
        <span class="badge bg-dark px-3 py-2"><?php echo mysqli_num_rows($result); ?> Registered Users</span>
    </div>

    <?php if(isset($message)) echo $message; ?>

    <div class="card user-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Users Info</th>
                        <th>Identity (CNIC/DOB)</th>
                        <th>Contact & Address</th>
                        <th>Joined Date</th>
                        <th>Account Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-3 bg-soft-gold p-2 rounded-circle text-center" style="width:40px; background:#eee;">
                                    <i class="fas fa-user text-muted"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['full_name']); ?></div>
                                    <div class="text-muted small">@<?= htmlspecialchars($row['username']); ?></div>
                                    <div class="small text-primary"><?= htmlspecialchars($row['email']); ?></div>
                                </div>
                            </div>
                        </td>

                        <td>
                            <div class="small"><strong>CNIC:</strong> <?= $row['cnic'] ?: 'Not Provided'; ?></div>
                            <div class="small"><strong>DOB:</strong> <?= $row['dob'] ? date('d M, Y', strtotime($row['dob'])) : 'Not Provided'; ?></div>
                        </td>

                        <td>
                            <div class="small"><i class="fas fa-phone-alt me-1 text-success"></i> <?= $row['contact_number']; ?></div>
                            <div class="detail-row text-truncate" style="max-width: 200px;" title="<?= $row['address']; ?>">
                                <i class="fas fa-map-marker-alt me-1 text-danger"></i> <?= htmlspecialchars($row['address']); ?>
                            </div>
                        </td>

                        <td class="small">
                            <?= date('M d, Y', strtotime($row['created_at'])); ?>
                        </td>

                        <td>
                            <a href="view_users.php?toggle_id=<?= $row['id']; ?>&current_status=<?= $row['is_active']; ?>" 
                               class="text-decoration-none">
                                <?php if($row['is_active'] == 1): ?>
                                    <span class="badge bg-success status-badge px-3 py-2 rounded-pill">
                                        <i class="fas fa-check-circle me-1"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger status-badge px-3 py-2 rounded-pill">
                                        <i class="fas fa-times-circle me-1"></i> Suspended
                                    </span>
                                <?php endif; ?>
                            </a>
                            <div class="x-small text-muted mt-1" style="font-size: 0.7rem;">Click to change</div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>