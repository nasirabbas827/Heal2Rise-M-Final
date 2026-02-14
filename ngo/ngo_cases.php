<?php
include('config.php');
session_start();

// Security Check: Must be logged in AND be an NGO
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'ngo') { 
    header("Location: ../login.php"); 
    exit(); 
}

$ngo_id = $_SESSION['id'];
$ngo_name = $_SESSION['full_name'] ?? 'Organization';

// Fetch assigned cases
$stmt = mysqli_prepare($conn, "
    SELECT c.*, u.full_name as user_name, t.full_name as member_name
    FROM cases c
    LEFT JOIN users u ON c.user_id = u.id
    LEFT JOIN team_members t ON c.team_member_id = t.team_member_id
    WHERE c.ngo_id = ?
    ORDER BY c.assigned_date DESC
");
mysqli_stmt_bind_param($stmt, "i", $ngo_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$cases = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>NGO Assigned Cases | <?= htmlspecialchars($ngo_name) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../CSS/style.css">
<style>
.table thead { background: #2d2d2d; color: #fff; }
.card { border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
</style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4 fw-bold">Assigned Cases</h2>

    <?php if(empty($cases)): ?>
        <div class="alert alert-info">No cases assigned to your NGO yet.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Case Title</th>
                    <th>User</th>
                    <th>Team Member</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Assigned Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($cases as $case): ?>
                <tr>
                    <td><?= htmlspecialchars($case['case_title']) ?></td>
                    <td><?= htmlspecialchars($case['user_name']) ?></td>
                    <td><?= $case['member_name'] ?: '-' ?></td>
                    <td><?= htmlspecialchars($case['issue_category']) ?></td>
                    <td><?= ucfirst($case['status']) ?></td>
                    <td><?= date('d M Y', strtotime($case['assigned_date'])) ?></td>
                    <td>
                        <a href="ngo_case_details.php?case_id=<?= $case['case_id'] ?>" class="btn btn-sm btn-primary mb-1">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <a href="manage_counselling_session.php?case_id=<?= $case['case_id'] ?>" class="btn btn-sm btn-warning mb-1">
                            <i class="fas fa-user-md"></i> Manage Counselling
                        </a>
                        <a href="manage_skills.php?case_id=<?= $case['case_id'] ?>" class="btn btn-sm btn-success mb-1">
                            <i class="fas fa-graduation-cap"></i> Manage Skills
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
