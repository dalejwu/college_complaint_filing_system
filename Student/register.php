<?php
include('../includes/db.php');
session_start();

if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($password)) {
        $msg = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Please enter a valid email address!";
    } elseif (strlen($password) < 6) {
        $msg = "Password must be at least 6 characters long!";
    } else {
        $stmt = $conn->prepare("SELECT * FROM students WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $check = $stmt->get_result();

        if ($check->num_rows > 0) {
            $msg = "Email already registered!";
        } else {
            $stmt2 = $conn->prepare("INSERT INTO students (name, email, password, approved) VALUES (?, ?, ?, 0)");
            $stmt2->bind_param("sss", $name, $email, $password);

            if ($stmt2->execute()) {
                // Do not auto-login; require admin approval
                $msg = "Registration successful! Your account is pending admin approval.";
            } else {
                $msg = "Error: " . $stmt2->error;
            }
            $stmt2->close();
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
    <title>Student Registration - Join Our Platform</title>
    <link rel="stylesheet" href="../CSS/style.css">
</head>

<body>
    <div class="login-container">
        <div class="login-card student-card">
            <h2>Create Account</h2>
            <p class="login-subtitle">Join our student community today</p>

            <?php if (isset($msg)): ?>
                <div class="message <?php echo strpos($msg, 'successful') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required placeholder="Enter your full name">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email address">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Create a secure password">
                </div>

                <button type="submit" name="register">Create Account</button>
            </form>

            <div class="text-center mt-20">
                <p style="color: var(--text-secondary); font-size: 14px;">
                    Already have an account? <a href="../Login/index.php">Sign in here</a>
                </p>
            </div>
        </div>
    </div>
</body>

</html>