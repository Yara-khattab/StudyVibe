<?php
session_start();
$is_logged_in = isset($_SESSION['user_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home page - StudyVibe</title>

    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        .nav-buttons {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .profile-link {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #fff;
            background: rgba(108, 92, 231, 0.2); 
            padding: 8px 18px;
            border-radius: 30px;
            transition: all 0.3s ease;
            border: 1px solid rgba(108, 92, 231, 0.4);
            font-family: 'Poppins', sans-serif; 
            font-weight: 500;
        }

        .profile-link:hover {
            background: #6c5ce7;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(108, 92, 231, 0.4);
            border-color: transparent;
            color: #fff;
        }

        .profile-link i {
            font-size: 1.3rem;
            color: #a29bfe;
        }

        .profile-link:hover i {
            color: #fff;
        }

        .logout-btn {
            background: none;
            border: 1px solid #ff7675;
            color: #ff7675;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
        }

        .logout-btn:hover {
            background: #ff7675;
            color: white;
        }

        .nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 5%;
            background: #2d3436; 
            color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body>

<main class="home-page">

    <nav class="nav-bar">
        <h2 class="logo">
            <i class="fa-solid fa-book-open"></i> StudyVibe
        </h2>

        <div class="nav-buttons">
            <?php if($is_logged_in): ?>
                <a href="Profile.php" class="profile-link">
                    <i class="fa-solid fa-circle-user"></i>
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </a>

                <button onclick="location.href='logout.php'" class="logout-btn" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            <?php else: ?>
                <a href="login.php" class="login-btn">Login</a>
                <a href="register.php" class="register-btn">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <section class="hero">
            <div class="hero-text">
                <h1>Stay Focused, Stay Together</h1>
                <p>
                    Join virtual study rooms, manage your time,
                    and stay productive with structured sessions.
                </p>
                
                <a class="start-btn" href="<?php echo $is_logged_in ? 'rooms.php' : 'login.php'; ?>">
                    Get Started
                </a>
            </div>
        </section>

        <section class="features">
            <div class="feature-box">
                <i class="fa-solid fa-clock"></i>
                <h3>Focus Timer</h3>
            </div>

            <div class="vertical-divider"></div>

            <div class="feature-box">
                <i class="fa-solid fa-users"></i>
                <h3>Study Rooms</h3>
            </div>

            <div class="vertical-divider"></div>

            <div class="feature-box">
                <i class="fa-solid fa-mug-hot"></i>
                <h3>Smart Breaks</h3>
            </div>
        </section>
    </div>

</main>

</body>
</html>
