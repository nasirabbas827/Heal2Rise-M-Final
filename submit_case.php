<?php
include('config.php');
session_start();

// Security Check: Must be logged in as a regular user
if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'user') { 
    header("Location: login.php"); 
    exit(); 
}

$user_id = $_SESSION['id'];
$success_msg = "";
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $case_title = mysqli_real_escape_string($conn, $_POST['case_title']);
    $issue_category = mysqli_real_escape_string($conn, $_POST['issue_category']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $start_date = date('Y-m-d'); // Case starts the day it's submitted

    // Insert into cases table. 
    // ngo_id and team_member_id remain NULL until an admin assigns them.
    $sql = "INSERT INTO cases (user_id, case_title, issue_category, description, start_date, status) 
            VALUES (?, ?, ?, ?, ?, 'open')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $user_id, $case_title, $issue_category, $description, $start_date);

    if ($stmt->execute()) {
        $success_msg = "Your request has been submitted successfully! An administrator will review it soon.";
    } else {
        $error_msg = "Something went wrong. Please try again later.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Support Request | Heal2Rise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./CSS/style.css">

    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .form-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .btn-submit { background: #1a1a1a; color: white; border-radius: 10px; padding: 12px; transition: 0.3s; }
        .btn-submit:hover { background: #333; color: #fff; }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="mb-4">
                <a href="dashboard.php" class="text-decoration-none text-muted small">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>

            <div class="card form-card p-4 p-md-5">
                <h2 class="fw-bold mb-2">Request Support</h2>
                <p class="text-muted mb-4">Tell us about your situation so we can assign the right NGO and mentor to help you.</p>

                <?php if($success_msg): ?>
                    <div class="alert alert-success border-0 shadow-sm mb-4">
                        <i class="fas fa-check-circle me-2"></i> <?= $success_msg ?>
                    </div>
                <?php endif; ?>

                <?php if($error_msg): ?>
                    <div class="alert alert-danger border-0 shadow-sm mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?= $error_msg ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Case Title</label>
                        <input type="text" name="case_title" class="form-control" placeholder="e.g., Assistance for Vocational Training" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Issue Category</label>
                        <select name="issue_category" class="form-select" required>
                            <option value="">Select a category...</option>
                            <option value="Health">Health & Medical</option>
                            <option value="Education">Education</option>
                            <option value="Legal">Legal Aid</option>
                            <option value="Psychology">Psychological Support</option>
                            <option value="Vocational Training">Vocational Training</option>
                            <option value="Financial">Financial Assistance</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Detailed Description</label>
                        <textarea name="description" class="form-control" rows="5" placeholder="Please provide as much detail as possible about the help you need..." required></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-submit fw-bold">
                            <i class="fas fa-paper-plane me-2"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="text-center mt-4">
                <p class="small text-muted">
                    <i class="fas fa-lock me-1"></i> Your information is confidential and will only be shared with assigned counselors.
                </p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>