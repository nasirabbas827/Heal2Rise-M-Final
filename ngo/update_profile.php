<?php
session_start();
include "config.php";

// Security Check: Must be logged in AND be an NGO
if (!isset($_SESSION["id"]) || $_SESSION["usertype"] !== 'ngo') {
    header("Location: ../login.php"); 
    exit;
}

$ngoId = $_SESSION["id"];
$success = $error = "";

// Fetch NGO data based on the ngos table schema
$stmt = $conn->prepare("SELECT ngo_name, username, email, phone, contact_person, address, city, specialties FROM ngos WHERE id = ?");
$stmt->bind_param("i", $ngoId);
$stmt->execute();
$result = $stmt->get_result();
$ngo = $result->fetch_assoc();
$stmt->close();

if (!$ngo) {
    die("NGO record not found.");
}

// Update profile if form submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ngo_name       = trim($_POST["ngo_name"]);
    $username       = trim($_POST["username"]);
    $email          = trim($_POST["email"]);
    $phone          = trim($_POST["phone"]);
    $contact_person = trim($_POST["contact_person"]);
    $address        = trim($_POST["address"]);
    $city           = trim($_POST["city"]);
    $specialties    = trim($_POST["specialties"]);

    if (!empty($ngo_name) && !empty($username) && !empty($email)) {
        $stmt = $conn->prepare("UPDATE ngos SET ngo_name=?, username=?, email=?, phone=?, contact_person=?, address=?, city=?, specialties=? WHERE id=?");
        $stmt->bind_param("ssssssssi", $ngo_name, $username, $email, $phone, $contact_person, $address, $city, $specialties, $ngoId);

        if ($stmt->execute()) {
            $success = "Organization profile updated successfully!";
            // Update session if name changed
            $_SESSION["full_name"] = $ngo_name;
            // Refresh local data for display
            $ngo["ngo_name"] = $ngo_name;
            $ngo["username"] = $username;
            $ngo["email"] = $email;
            $ngo["phone"] = $phone;
            $ngo["contact_person"] = $contact_person;
            $ngo["address"] = $address;
            $ngo["city"] = $city;
            $ngo["specialties"] = $specialties;
        } else {
            $error = "Error updating profile: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "NGO Name, Username, and Email are required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit NGO Profile | Heal2Rise Partners</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style.css">
    <style>
        .profile-wrapper {
            background: linear-gradient(135deg, #fdfcfb 0%, #e2d1c3 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .profile-card {
            background: white;
            border-radius: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: none;
            overflow: hidden;
        }
        .profile-header {
            background: #2d2d2d;
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .profile-header h2 {
            font-family: 'Playfair Display', serif;
            margin: 0;
            font-size: 2rem;
        }
        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }
        .input-group-custom label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 8px;
            display: block;
        }
        .input-group-custom i {
            position: absolute;
            left: 18px;
            top: 45px;
            color: #bc8a5f;
            z-index: 10;
        }
        .input-group-custom .form-control {
            padding-left: 48px;
            height: 50px;
            border-radius: 12px;
            border: 1px solid #eee;
            background: #fbfbfb;
        }
        .btn-update {
            background: #2d2d2d;
            color: white;
            border-radius: 50px;
            padding: 15px;
            font-weight: 600;
            width: 100%;
            border: none;
            transition: 0.3s;
            margin-top: 20px;
        }
        .btn-update:hover {
            background: #444;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="profile-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card profile-card">
                    <div class="profile-header">
                        <h2><i class="fas fa-building me-2"></i>Edit Organization Profile</h2>
                        <p class="text-white-50 mb-0 mt-2">Update your NGO details for the Heal2Rise community</p>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success border-0 shadow-sm mb-4">
                                <i class="fas fa-check-circle me-2"></i> <?= $success ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger border-0 shadow-sm mb-4">
                                <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="input-group-custom">
                                        <label>NGO Official Name</label>
                                        <i class="fas fa-university"></i>
                                        <input type="text" name="ngo_name" class="form-control" value="<?= htmlspecialchars($ngo['ngo_name']) ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group-custom">
                                        <label>Username</label>
                                        <i class="fas fa-at"></i>
                                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($ngo['username']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group-custom">
                                        <label>Contact Phone</label>
                                        <i class="fas fa-phone"></i>
                                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($ngo['phone']) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group-custom">
                                        <label>Email Address</label>
                                        <i class="fas fa-envelope"></i>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($ngo['email']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group-custom">
                                        <label>Contact Person Name</label>
                                        <i class="fas fa-user-tie"></i>
                                        <input type="text" name="contact_person" class="form-control" value="<?= htmlspecialchars($ngo['contact_person']) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group-custom">
                                        <label>City</label>
                                        <i class="fas fa-city"></i>
                                        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($ngo['city']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group-custom">
                                        <label>Office Address</label>
                                        <i class="fas fa-map-marker-alt"></i>
                                        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($ngo['address']) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="input-group-custom">
                                <label>Specialties & Description</label>
                                <i class="fas fa-hands-helping"></i>
                                <textarea name="specialties" class="form-control" rows="4" style="padding-left:48px; padding-top:12px;"><?= htmlspecialchars($ngo['specialties']) ?></textarea>
                            </div>

                            <button type="submit" class="btn-update shadow-sm">
                                <i class="fas fa-save me-2"></i> Save Organization Changes
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <a href="ngo_dashboard.php" class="text-decoration-none text-muted small">
                                <i class="fas fa-arrow-left me-1"></i> Back to NGO Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>