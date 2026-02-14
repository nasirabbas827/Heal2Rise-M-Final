<?php
include('config.php');

$message = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST["full_name"]);
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $contact_number = trim($_POST["contact_number"]);
    $dob = trim($_POST["dob"]);
    $cnic = trim($_POST["cnic"]);
    $address = trim($_POST["address"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    // Basic Validation for Re-entering password
    if ($password !== $confirm_password) {
        $message = "<div class='alert alert-error'><i class='fas fa-exclamation-circle'></i> Passwords do not match!</div>";
    } else {
        // Check if user already exists
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? OR email = ? OR contact_number = ?");
        mysqli_stmt_bind_param($stmt, "sss", $username, $email, $contact_number);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $message = "<div class='alert alert-error'><i class='fas fa-exclamation-circle'></i> Username, Email, or Contact already exists!</div>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, username, email, password, dob, cnic, contact_number, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssssssss", $full_name, $username, $email, $hashed_password, $dob, $cnic, $contact_number, $address);

            if (mysqli_stmt_execute($stmt)) {
                $message = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Welcome to Heal2Rise! Registration successful.</div>";
            } else {
                $message = "<div class='alert alert-error'><i class='fas fa-times-circle'></i> Registration failed. Please try again.</div>";
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
    <title>Register</title>
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
            color: var(--primary-color);
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
            <h2>Create an Account</h2>
            <p class="lead mb-4 fw-light">Your Journey to Recovery Starts Here.</p>
        </div>

        <?php if (!empty($message)) echo $message; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-row">
                <div class="input-group-custom">
                    <label>Full Name</label>
                    <i class="fas fa-signature"></i>
                    <input type="text" name="full_name" class="form-control" placeholder="Enter full name" required>
                </div>
                <div class="input-group-custom">
                    <label>Suggested Username</label>
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" class="form-control" placeholder="e.g. jane or john" required>
                </div>
            </div>

            <div class="form-row">
                <div class="input-group-custom">
                    <label>Contact Number</label>
                    <i class="fas fa-mobile-alt"></i>
                    <input type="text" name="contact_number" class="form-control" placeholder="03XX-XXXXXXX" required>
                </div>
                <div class="input-group-custom">
                    <label>Email Address</label>
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                </div>
            </div>

            <div class="form-row">
                <div class="input-group-custom">
                    <label>Date of Birth</label>
                    <i class="fas fa-calendar-heart"></i>
                    <input type="date" name="dob" class="form-control">
                </div>
                <div class="input-group-custom">
                    <label>CNIC Number</label>
                    <i class="fas fa-id-card"></i>
                    <input type="text" name="cnic" class="form-control" placeholder="42XXX-XXXXXXX-X">
                </div>
            </div>

            <div class="input-group-custom">
                <label>Complete Home Address</label>
                <i class="fas fa-house-user"></i>
                <textarea name="address" class="form-control" rows="2" placeholder="Street, Sector, City..." style="padding-top:12px;"></textarea>
            </div>

            <div class="form-row">
                <div class="input-group-custom">
                    <label>Create Password</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="input-group-custom">
                    <label>Re-Enter Password</label>
                    <i class="fas fa-shield-check"></i>
                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <div class="text-center mt-3">
                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill shadow-sm">
                    <i class="fas fa-magic me-2"></i> Register for Heal2Rise
                </button>
                <p class="mt-4 text-muted">Already have an account? <a href="login.php" class="text-primary fw-bold text-decoration-none">Login here</a></p>
                <p class="mt-4 text-muted">Register as NGO? <a href="ngo_register.php" class="text-primary fw-bold text-decoration-none">Register Now</a></p>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>