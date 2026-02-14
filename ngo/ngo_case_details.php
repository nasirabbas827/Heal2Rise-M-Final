<?php
include('config.php');
session_start();

// Security check
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'ngo') { 
    header("Location: ../login.php"); 
    exit(); 
}

$ngo_id = $_SESSION['id'];

if(!isset($_GET['case_id'])) {
    header("Location: ngo_cases.php");
    exit();
}

$case_id = intval($_GET['case_id']);

// Fetch case details
$stmt = mysqli_prepare($conn, "
    SELECT c.*, u.full_name as user_name, u.email as user_email, t.full_name as member_name, t.email as member_email
    FROM cases c
    LEFT JOIN users u ON c.user_id = u.id
    LEFT JOIN team_members t ON c.team_member_id = t.team_member_id
    WHERE c.case_id = ? AND c.ngo_id = ?
");
mysqli_stmt_bind_param($stmt, "ii", $case_id, $ngo_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$case = mysqli_fetch_assoc($result);

if(!$case) {
    die("<div class='alert alert-danger'>Case not found or not assigned to your NGO.</div>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Case Details | <?= htmlspecialchars($case['case_title']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/style.css">

</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <a href="ngo_cases.php" class="btn btn-light mb-3"><i class="fas fa-arrow-left me-2"></i>Back to Cases</a>
    <div class="card p-4">
        <h3 class="fw-bold mb-3"><?= htmlspecialchars($case['case_title']) ?></h3>

        <div class="row mb-3">
            <div class="col-md-6"><strong>User:</strong> <?= htmlspecialchars($case['user_name']) ?> (<?= htmlspecialchars($case['user_email']) ?>)</div>
            <div class="col-md-6"><strong>Team Member:</strong> <?= $case['member_name'] ?: '-' ?> <?= $case['member_email'] ? "(".$case['member_email'].")" : "" ?></div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6"><strong>Issue Category:</strong> <?= htmlspecialchars($case['issue_category']) ?></div>
            <div class="col-md-6"><strong>Status:</strong> <?= ucfirst($case['status']) ?></div>
        </div>

        <div class="mb-3"><strong>Description:</strong> <p><?= nl2br(htmlspecialchars($case['description'])) ?></p></div>
        <div class="row mb-3">
            <div class="col-md-4"><strong>Start Date:</strong> <?= $case['start_date'] ?></div>
            <div class="col-md-4"><strong>Closure Date:</strong> <?= $case['closure_date'] ?: '-' ?></div>
            <div class="col-md-4"><strong>Closure Reason:</strong> <?= $case['closure_reason'] ?: '-' ?></div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <a href="manage_counselling_session.php?case_id=<?= $case['case_id'] ?>" class="btn btn-warning">
                <i class="fas fa-user-md"></i> Manage Counselling
            </a>
            <a href="manage_skills.php?case_id=<?= $case['case_id'] ?>" class="btn btn-success">
                <i class="fas fa-graduation-cap"></i> Manage Skills
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
