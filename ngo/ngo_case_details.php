<?php
include('../config.php');
session_start();

if (!isset($_SESSION['id']) || $_SESSION['usertype'] !== 'ngo') { 
    header("Location: ../login.php"); 
    exit(); 
}

$ngo_id = $_SESSION['id'];
$case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;

// 1. Fetch Main Case, Patient, Counselor, and Team Member Data
$query = "
    SELECT c.*, 
           u.full_name as patient_name, u.email as patient_email, u.contact_number, u.address, u.dob,
           t.full_name as member_name, t.phone as member_phone,
           cn.full_name as counselor_name, cn.phone as counselor_phone
    FROM cases c
    LEFT JOIN users u ON c.user_id = u.id
    LEFT JOIN team_members t ON c.team_member_id = t.team_member_id
    LEFT JOIN counselors cn ON c.counselor_id = cn.id
    WHERE c.case_id = ? AND c.ngo_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $case_id, $ngo_id);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();

if(!$case) { die("<div class='container mt-5 alert alert-danger'>Case not found or unauthorized.</div>"); }

// 2. Fetch All Counseling Sessions for this case
$sessions_sql = "SELECT * FROM counseling_sessions WHERE case_id = ? ORDER BY session_date DESC";
$s_stmt = $conn->prepare($sessions_sql);
$s_stmt->bind_param("i", $case_id);
$s_stmt->execute();
$sessions_res = $s_stmt->get_result();

// 3. Fetch All Skill Development Progress for this case
$skills_sql = "SELECT * FROM skill_development WHERE case_id = ? ORDER BY start_date DESC";
$sk_stmt = $conn->prepare($skills_sql);
$sk_stmt->bind_param("i", $case_id);
$sk_stmt->execute();
$skills_res = $sk_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Master Case File | <?= htmlspecialchars($case['patient_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Inter', sans-serif; }
        .master-card { border: none; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); background: white; }
        .section-header { background: #f8f9fa; padding: 10px 20px; border-radius: 10px; font-weight: 700; color: #344767; margin-bottom: 20px; border-left: 5px solid #2c3e50; }
        .label-text { font-size: 0.75rem; text-transform: uppercase; color: #8392ab; font-weight: 700; }
        .value-text { font-size: 0.95rem; color: #344767; font-weight: 600; margin-bottom: 15px; }
        .table-custom { font-size: 0.85rem; }
        .status-badge { font-size: 0.7rem; padding: 5px 12px; border-radius: 50px; }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark"><i class="fas fa-file-medical me-2"></i>Master Case File</h3>
        <button onclick="window.print()" class="btn btn-dark btn-sm rounded-pill px-3"><i class="fas fa-print me-2"></i>Print Report</button>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card master-card p-4 mb-4">
                <div class="section-header">Patient Profile</div>
                <div class="row">
                    <div class="col-12">
                        <p class="label-text">Full Name</p>
                        <p class="value-text"><?= htmlspecialchars($case['patient_name']) ?></p>
                        
                        <p class="label-text">Contact & Email</p>
                        <p class="value-text"><?= htmlspecialchars($case['contact_number']) ?><br><span class="small text-muted"><?= htmlspecialchars($case['patient_email']) ?></span></p>
                        
                        <p class="label-text">Address</p>
                        <p class="value-text small"><?= htmlspecialchars($case['address'] ?: 'N/A') ?></p>
                    </div>
                </div>

                <div class="section-header mt-3">Assignment Details</div>
                <p class="label-text">Clinical Counselor</p>
                <p class="value-text text-primary"><i class="fas fa-user-md me-2"></i><?= $case['counselor_name'] ?: 'Unassigned' ?></p>
                
                <p class="label-text">Team Member (Field)</p>
                <p class="value-text text-success"><i class="fas fa-users me-2"></i><?= $case['member_name'] ?: 'Unassigned' ?></p>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card master-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="section-header w-75">Case Summary: <?= htmlspecialchars($case['case_title']) ?></div>
                    <span class="badge bg-dark status-badge"><?= strtoupper($case['status']) ?></span>
                </div>
                <p class="label-text">Issue Category</p>
                <p class="value-text"><?= htmlspecialchars($case['issue_category']) ?></p>
                <p class="label-text">Initial Description</p>
                <div class="p-3 bg-light rounded small mb-3"><?= nl2br(htmlspecialchars($case['description'])) ?></div>
            </div>

            <div class="card master-card p-4 mb-4">
                <div class="section-header">Counseling History</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-custom">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Duration</th>
                                <th>Clinical Notes</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($sessions_res->num_rows > 0): ?>
                                <?php while($s = $sessions_res->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-nowrap fw-bold"><?= date('d M, Y', strtotime($s['session_date'])) ?></td>
                                    <td><?= $s['duration_minutes'] ?>m</td>
                                    <td class="small text-muted"><?= substr(htmlspecialchars($s['notes']), 0, 100) ?>...</td>
                                    <td><span class="badge <?= $s['attended'] ? 'bg-light text-success' : 'bg-light text-danger' ?>"><?= $s['attended'] ? 'Present' : 'Missed' ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted">No sessions logged yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card master-card p-4">
                <div class="section-header">Skill & Vocational Progress</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-custom">
                        <thead class="table-light">
                            <tr>
                                <th>Skill</th>
                                <th>Timeline</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($skills_res->num_rows > 0): ?>
                                <?php while($sk = $skills_res->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($sk['skill_name']) ?></td>
                                    <td class="small"><?= $sk['start_date'] ?> to <?= $sk['target_date'] ?: 'Ongoing' ?></td>
                                    <td><span class="badge bg-secondary status-badge"><?= strtoupper($sk['status']) ?></span></td>
                                    <td class="small text-muted"><?= htmlspecialchars($sk['notes']) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted">No skills being tracked.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>