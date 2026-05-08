<?php
include "db.php";
session_start();

$error_message = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? '';
    $username = $_POST['username'] ?? ''; 
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "Please fill all fields!";
    } 
    elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } 
    else {
        
        $check_sql = "SELECT id FROM users WHERE email = ? OR user_name = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $email, $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error_message = "This email or username is already registered!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            
            $sql = "INSERT INTO users (name, user_name, email, password) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $name, $username, $email, $hashed_password);

            if ($stmt->execute()) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                header("Location: index.php");
                exit();
            } else {
                $error_message = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>StudyVibe Sign Up</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/all.css">
</head>

<body>
  <section class="register_page">
     <div class="register_form">
        <h2 class="logo"><i class="fa-solid fa-book-open"></i> StudyVibe</h2>
        <h3 class="signup">Sign Up</h3>

      
        <?php if (!empty($error_message)): ?>
            <div style="background: rgba(255, 0, 0, 0.2); color: #ff7675; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 15px; border: 1px solid rgba(255, 0, 0, 0.3); font-size: 14px;">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
          <!-- Full Name -->
          <div class="input-box">
            <i class="fa-solid fa-user"></i>
            <input type="text" name="name" placeholder="Full Name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
          </div>

         
          <div class="input-box">
            <i class="fa-solid fa-user-pen"></i>
            <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
          </div>

         
          <div class="input-box">
            <i class="fa-solid fa-envelope"></i>
            <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
          </div>

       
          <div class="input-box">
            <i class="fa-solid fa-lock"></i>
            <input type="password" name="password" placeholder="Password" required>
          </div>

        
          <div class="input-box">
            <i class="fa-solid fa-lock"></i>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
          </div>

          <button type="submit">Sign Up</button>
        </form>

      
          Already have an account? <a href="login.php">Login</a>
        </p>
     </div>
  </section>
</body>
</html>