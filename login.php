<?php
include "db.php";
session_start();
$error_message = ""; 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    if (empty($email) || empty($password)) {
        $error_message = "Please enter both email and password!";
    } else { 
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();                    
            if (password_verify($password, $user['password'])) {              
                $_SESSION['user_name'] = $user['user_name']; 
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_id'] = $user['id'];
                header("Location: index.php");
                exit();
            } else {              
                $error_message = "Invalid email or password!";
            }
        } else {
            $error_message = "Invalid email or password!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyVibe Login</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/all.css">
</head>
<body>
    <section class="login_page">
        <div class="login_form">
            <h2 class="logo"><i class="fa-solid fa-book-open"></i> StudyVibe</h2>
            <h3 class="Login">Login</h3>
         <?php if (!empty($error_message)): ?>
                <div style="background: rgba(255, 0, 0, 0.2); color: #ff7675; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 15px; border: 1px solid rgba(255, 0, 0, 0.3); font-size: 14px;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            <form action="login.php" method="POST">     
                <div class="input-box">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>
                <div class="input-box">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit">Login</button>
            </form>
            <p class="signup-text">Don’t have an account? <a href="register.php">SignUp</a></p>
        </div>
    </section>
</body>
</html>