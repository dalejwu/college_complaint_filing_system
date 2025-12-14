<?php
include('../includes/db.php');
session_start();

if (isset($_POST['login'])) {
    $username_or_email = isset($_POST['username_or_email']) ? trim($_POST['username_or_email']) : (isset($_POST['email']) ? trim($_POST['email']) : '');
    $password = $_POST['password'];

    if (empty($username_or_email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Check if it's a student (by email)
        $stmt = $conn->prepare("SELECT * FROM students WHERE email = ?");
        $stmt->bind_param("s", $username_or_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($password === $row['password']) {
                $_SESSION['student_id'] = $row['id'];
                $_SESSION['student_name'] = $row['name'];
                header("Location: ../Student/dashboard.php");
                exit();
            } else {
                $error = "Incorrect password.";
            }
        } else {
            // Check if it's an admin (by username)
            $stmt->close(); // Close previous statement
            $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->bind_param("s", $username_or_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $admin = $result->fetch_assoc();
                if ($password === $admin['password']) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_user'] = $admin['username'];
                    header("Location: ../admin/dashboard.php");
                    exit();
                } else {
                    $error = "Incorrect password.";
                }
            } else {
                $error = "No account found with that email or username.";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Access Your Account</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>

<body>
    <div class="login-container">
        <div class="login-card student-card">
            <h2>Login</h2>
            <p class="login-subtitle">Welcome back! Sign in to continue</p>

            <?php if (isset($error)): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username_or_email">Email or Username</label>
                    <input type="text" id="username_or_email" name="username_or_email" required placeholder="Enter your email or username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>

                <button type="submit" name="login">Sign In</button>
            </form>

            <div class="text-center mt-20">
                <p style="color: var(--text-secondary); font-size: 14px;">
                    Don't have an account? <a href="register.php">Create one here</a>
                </p>
            </div>
        </div>
    </div>
</body>

</html>