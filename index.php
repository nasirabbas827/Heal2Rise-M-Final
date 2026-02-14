<?php
session_start();
include "config.php";

// Fetch some stats for the impact section
$total_cases = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM cases"))['count'];
$total_ngos = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM ngos WHERE is_active = 1"))['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heal2Rise | Empowering Survivors & Restoring Hope</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./CSS/style.css">

    <style>
        :root {
            --primary-teal: #0d6efd;
            --soft-white: #f8f9fa;
            --dark-blue: #0a2351;
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--soft-white); color: #444; }
        h1, h2, h3 { font-family: 'Playfair Display', serif; color: var(--dark-blue); }

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(10, 35, 81, 0.8), rgba(10, 35, 81, 0.8)), url('https://images.unsplash.com/photo-1573497620053-ea5310f94a17?auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            height: 90vh;
            display: flex;
            align-items: center;
            color: white;
            clip-path: polygon(0 0, 100% 0, 100% 90%, 0% 100%);
        }

        .cta-button {
            padding: 15px 40px;
            background: #fff;
            color: var(--dark-blue);
            border-radius: 50px;
            font-weight: 700;
            text-decoration: none;
            transition: 0.3s;
            display: inline-block;
        }

        .cta-button:hover { background: var(--primary-teal); color: white; transform: translateY(-3px); }

        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-top: -80px;
            border-bottom: 5px solid var(--primary-teal);
        }

        /* Mission Section */
        .step-icon {
            width: 80px;
            height: 80px;
            background: rgba(13, 110, 253, 0.1);
            color: var(--primary-teal);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.5rem;
        }

        .category-pill {
            background: #e9ecef;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 10px;
            display: inline-block;
        }
    </style>
</head>
<body>
    
    <?php include('navbar.php'); ?>

    <header class="hero mb-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-7">
                    <h1 class="display-2 fw-bold mb-4 text-white">Rise Above the Shadows.</h1>
                    <p class="lead mb-5 opacity-90">Heal2Rise is a dedicated platform connecting survivors with NGOs and legal support. We provide a bridge to counseling, skill development, and a fresh start.</p>
                    <div class="d-flex gap-3">
                        <a href="submit_case.php" class="cta-button shadow mb-4">Seek Help Now</a>
                        <a href="#mission" class="btn btn-outline-light rounded-pill px-4 py-3 mb-4">Our Mission</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container position-relative z-index-10 mt-4">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <h2 class="display-5 fw-bold text-primary"><?= $total_cases + 150 ?>+</h2>
                    <p class="text-muted mb-0">Cases Supported</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h2 class="display-5 fw-bold text-primary"><?= $total_ngos ?></h2>
                    <p class="text-muted mb-0">Partner NGOs</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h2 class="display-5 fw-bold text-primary">24/7</h2>
                    <p class="text-muted mb-0">Monitoring & Care</p>
                </div>
            </div>
        </div>
    </div>

    <section class="py-5 mt-5" id="mission">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">The Path to Rehabilitation</h2>
                <p class="text-muted">A structured journey from crisis to independence.</p>
                <div class="mx-auto" style="height: 4px; width: 60px; background: var(--primary-teal);"></div>
            </div>

            <div class="row g-5 text-center">
                <div class="col-md-4">
                    <div class="step-icon"><i class="fas fa-file-medical"></i></div>
                    <h4>1. Request Case</h4>
                    <p class="text-muted small">Submit your details securely. Our admins review every request with strict confidentiality.</p>
                </div>
                <div class="col-md-4">
                    <div class="step-icon"><i class="fas fa-hands-helping"></i></div>
                    <h4>2. Expert Assignment</h4>
                    <p class="text-muted small">We match you with specialized NGOs and dedicated team members who understand your specific needs.</p>
                </div>
                <div class="col-md-4">
                    <div class="step-icon"><i class="fas fa-chart-line"></i></div>
                    <h4>3. Skill & Recovery</h4>
                    <p class="text-muted small">Track your progress through counseling sessions and vocational training programs tailored for you.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1529070538774-1843cb3265df?auto=format&fit=crop&w=800&q=80" class="img-fluid rounded-4 shadow" alt="Support">
                </div>
                <div class="col-lg-6 ps-lg-5">
                    <h2 class="mb-4">Areas of Support</h2>
                    <p class="mb-4">We cover a wide range of rehabilitation services to ensure no one is left behind.</p>
                    <div class="row g-2">
                        <?php 
                        $cats = ['Legal Aid', 'Trauma Counseling', 'Medical Help', 'Shelter Support', 'Job Training', 'Child Protection'];
                        foreach($cats as $c) echo "<div class='col-6'><div class='p-3 border rounded-3'><i class='fas fa-check-circle text-success me-2'></i>$c</div></div>";
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container text-center">
            <h3 class="text-white mb-4">Heal2Rise</h3>
            <p class="opacity-50">Providing a second chance at life through digital empowerment.</p>
            <hr class="my-5 opacity-25">
            <p class="small mb-0">&copy; <?php echo date("Y"); ?> Heal2Rise Platform. Managed by Admin Panel.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>