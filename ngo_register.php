<?php
include('config.php');

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ngo_name = trim($_POST["ngo_name"]);
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $contact_person = trim($_POST["contact_person"]);
    $phone = trim($_POST["phone"]);
    $address = trim($_POST["address"]);
    $city = trim($_POST["city"]);
    $specialties = trim($_POST["specialties"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    if ($password !== $confirm_password) {
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Passwords do not match!</div>";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM ngos WHERE username=? OR email=? OR ngo_name=?");
        mysqli_stmt_bind_param($stmt, "sss", $username, $email, $ngo_name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> NGO Name, Username or Email already exists!</div>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($conn, 
                "INSERT INTO ngos (ngo_name, username, email, password_hash, contact_person, phone, address, city, specialties) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "sssssssss", $ngo_name, $username, $email, $hashed_password, $contact_person, $phone, $address, $city, $specialties);

            if (mysqli_stmt_execute($stmt)) {
                $message = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> NGO Registration Successful! Welcome to the mission.</div>";
            } else {
                $message = "<div class='alert alert-danger'><i class='fas fa-times-circle'></i> Registration failed. Please try again.</div>";
            }
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NGO Registration</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./CSS/style.css">

    <style>
        .page-wrapper {
            padding: 40px 20px;
            background: linear-gradient(135deg, #fdfcfb 0%, #e2d1c3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-container {
            max-width: 850px;
            width: 100%;
            background: #ffffff;
            padding: 50px;
            border-radius: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #2d2d2d;
            margin-bottom: 10px;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 25px;
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
            color: var(--primary-color, #a88d7b); /* Fallback to a neutral gold/brown if variable is missing */
            z-index: 10;
        }

        .input-group-custom .form-control {
            padding-left: 48px;
            height: 50px;
            border-radius: 12px;
            border: 1px solid #eee;
            background: #fbfbfb;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        /* Modern Alerts */
        .alert {
            border-radius: 15px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 25px;
        }

        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .form-container { padding: 30px; }
        }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="page-wrapper">
    <div class="form-container">
        <div class="form-header">
            <h2>NGO Registration</h2>
            <p class="text-muted">Partner with Heal2Rise to support individuals in need.</p>
        </div>

        <?php if (!empty($message)) echo $message; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            
            <div class="form-row">
                <div class="input-group-custom">
                    <label>NGO Name</label>
                    <i class="fas fa-building"></i>
                    <input type="text" name="ngo_name" class="form-control" placeholder="Official NGO Name" required>
                </div>
                <div class="input-group-custom">
                    <label>Suggested Username</label>
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" class="form-control" placeholder="e.g. heal_foundation" required>
                </div>
            </div>

            <div class="form-row">
                <div class="input-group-custom">
                    <label>Email Address</label>
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" class="form-control" placeholder="contact@ngo.org" required>
                </div>
                <div class="input-group-custom">
                    <label>Contact Person</label>
                    <i class="fas fa-id-badge"></i>
                    <input type="text" name="contact_person" class="form-control" placeholder="Full name of representative">
                </div>
            </div>

            <div class="form-row">
                <div class="input-group-custom">
                    <label>Phone Number</label>
                    <i class="fas fa-phone"></i>
                    <input type="text" name="phone" class="form-control" placeholder="03XX-XXXXXXX">
                </div>
                <div class="input-group-custom">
                    <label>City</label>
                    <i class="fas fa-city"></i>
                    <input type="text" name="city" class="form-control" placeholder="e.g. Karachi">
                </div>
            </div>

            <div class="input-group-custom">
                <label>Complete Office Address</label>
                <i class="fas fa-map-marker-alt"></i>
                <textarea name="address" class="form-control" rows="2" placeholder="Street address, building, floor..." style="padding-top:12px;"></textarea>
            </div>

            <div class="input-group-custom">
                <label>NGO Specialties</label>
                <i class="fas fa-hands-helping"></i>
                <textarea name="specialties" class="form-control" rows="2" placeholder="e.g. Medical Care, Education, Women Empowerment..." style="padding-top:12px;"></textarea>
            </div>

            <div class="form-row">
                <div class="input-group-custom">
                    <label>Create Password</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="input-group-custom">
                    <label>Confirm Password</label>
                    <i class="fas fa-shield-alt"></i>
                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <div class="text-center mt-3">
                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill shadow-sm">
                    <i class="fas fa-paper-plane me-2"></i> Register NGO
                </button>
                <p class="mt-4 text-muted">Already registered? <a href="login.php" class="text-primary fw-bold text-decoration-none">Login here</a></p>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>