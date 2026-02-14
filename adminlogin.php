<?php
include('config.php');
session_start();

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $entered_username = trim($_POST['username']);
        $entered_password = trim($_POST['password']);

        // Securely fetch admin record
        $sql = "SELECT * FROM admin WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $entered_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $admin = $result->fetch_assoc();
            // Note: In a production environment, use password_verify() here.
            // Using plain text check as per your current logic.
            if ($entered_password === $admin['password']) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $entered_username;
                header("Location: ./admin/admin_dashboard.php");
                exit();
            } else {
                $error_message = "Invalid administrative password.";
            }
        } else {
            $error_message = "Unauthorized username.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | Heal2Rise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./CSS/style.css">
    <style>
        .admin-wrapper {
            min-height: calc(100vh - 80px);
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f4f7f6; /* Neutral professional gray */
            padding: 20px;
        }

        .admin-card {
            max-width: 400px;
            width: 100%;
            background: #ffffff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            border-top: 5px solid #343a40; /* Darker accent for Admin */
        }

        .admin-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .admin-header i {
            color: #343a40;
            margin-bottom: 15px;
        }

        .admin-header h2 {
            font-weight: 700;
            color: #343a40;
            font-size: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group-custom label {
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 5px;
            display: block;
            color: #666;
        }

        .input-group-custom i {
            position: absolute;
            left: 15px;
            top: 38px;
            color: #343a40;
            opacity: 0.7;
        }

        .input-group-custom .form-control {
            padding-left: 45px;
            height: 48px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .btn-admin {
            background-color: #343a40;
            color: white;
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-admin:hover {
            background-color: #000;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .alert-custom {
            background-color: #fff5f5;
            color: #c53030;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            border: 1px solid #feb2b2;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="admin-wrapper">
    <div class="admin-card">
        <div class="admin-header">
            <i class="fas fa-user-shield fa-3x"></i>
            <h2>Admin Portal</h2>
            <p class="text-muted small">Authorized Access Only</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert-custom">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form action="adminlogin.php" method="POST">
            <div class="input-group-custom">
                <label>Admin Username</label>
                <i class="fas fa-user"></i>
                <input type="text" name="username" class="form-control" placeholder="Enter username" required>
            </div>

            <div class="input-group-custom">
                <label>Security Password</label>
                <i class="fas fa-key"></i>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>

            <button type="submit" class="btn-admin">
                Verify & Enter
            </button>
            
            <div class="text-center mt-4">
                <a href="login.php" class="text-muted small text-decoration-none">
                    <i class="fas fa-arrow-left"></i> Back to Client Login
                </a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>