<?php
session_start();
include "config.php";

// Admin Protection
if (!isset($_SESSION['admin_logged_in'])) { 
    header("Location: adminlogin.php"); 
    exit(); 
}

// Toggle NGO Status (Active/Inactive)
if (isset($_GET['toggle_id']) && isset($_GET['current_status'])) {
    $ngo_id = intval($_GET['toggle_id']);
    $new_status = ($_GET['current_status'] == 1) ? 0 : 1;
    
    $update_query = "UPDATE ngos SET is_active = $new_status WHERE id = $ngo_id";
    if (mysqli_query($conn, $update_query)) {
        $message = "<div class='alert alert-success border-0 shadow-sm'><i class='fas fa-check-circle me-2'></i>NGO status updated successfully!</div>";
    }
}

// Fetch all NGOs
$query = "SELECT * FROM ngos ORDER BY registration_date DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage NGOs | Heal2Rise Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/style.css">

    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .ngo-card { border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .table thead { background-color: #2c3e50; color: white; }
        .status-badge { cursor: pointer; transition: 0.3s; }
        .status-badge:hover { opacity: 0.8; transform: scale(1.05); }
        .specialties-tag { font-size: 0.75rem; background: #e9ecef; color: #495057; padding: 2px 8px; border-radius: 4px; display: inline-block; margin-top: 4px; }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold"><i class="fas fa-hand-holding-heart me-2 text-primary"></i>Partner NGOs</h2>
            <p class="text-muted small mb-0">Manage registered organizations and their platform access.</p>
        </div>
        <span class="badge bg-dark px-3 py-2"><?php echo mysqli_num_rows($result); ?> Registered NGOs</span>
    </div>

    <?php if(isset($message)) echo $message; ?>

    <div class="card ngo-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Organization Info</th>
                        <th>Contact Person</th>
                        <th>Location & Phone</th>
                        <th>Specialties</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-3 bg-light p-2 rounded text-center" style="width:45px;">
                                    <i class="fas fa-building text-primary fa-lg"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['ngo_name']); ?></div>
                                    <div class="text-muted small">@<?= htmlspecialchars($row['username']); ?></div>
                                    <div class="small text-primary"><?= htmlspecialchars($row['email']); ?></div>
                                </div>
                            </div>
                        </td>

                        <td>
                            <div class="fw-semibold small"><?= htmlspecialchars($row['contact_person'] ?: 'N/A'); ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;">Registered: <?= date('d M, Y', strtotime($row['registration_date'])); ?></div>
                        </td>

                        <td>
                            <div class="small"><i class="fas fa-phone-alt me-1 text-success"></i> <?= $row['phone']; ?></div>
                            <div class="small text-muted">
                                <i class="fas fa-city me-1"></i> <?= htmlspecialchars($row['city']); ?>
                            </div>
                        </td>

                        <td>
                            <div class="small text-truncate" style="max-width: 180px;" title="<?= $row['specialties']; ?>">
                                <?php 
                                    $tags = explode(',', $row['specialties']);
                                    foreach(array_slice($tags, 0, 2) as $tag) {
                                        echo '<span class="specialties-tag me-1">'.htmlspecialchars(trim($tag)).'</span>';
                                    }
                                ?>
                            </div>
                        </td>

                        <td>
                            <a href="view_ngos.php?toggle_id=<?= $row['id']; ?>&current_status=<?= $row['is_active']; ?>" 
                               class="text-decoration-none">
                                <?php if($row['is_active'] == 1): ?>
                                    <span class="badge bg-success status-badge px-3 py-2 rounded-pill">
                                        <i class="fas fa-check-circle me-1"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger status-badge px-3 py-2 rounded-pill">
                                        <i class="fas fa-ban me-1"></i> Suspended
                                    </span>
                                <?php endif; ?>
                            </a>
                            <div class="text-muted mt-1" style="font-size: 0.65rem; text-align: center;">Click to Toggle</div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>