<!-- student_login.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Login</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <style>
    body {
        background-color: #f8f9fa;
        /* Light background */
    }

    .login-container {
        max-width: 400px;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0px 4px 10px rgba(0, 128, 0, 0.2);
        text-align: center;
    }

    .login-container h2 {
        color: green;
        font-weight: bold;
    }

    .form-control {
        border-radius: 5px;
        box-shadow: none;
        border: 1px solid green;
    }

    .btn-success {
        width: 100%;
        border-radius: 5px;
        font-weight: bold;
        background-color: green;
        border: none;
    }

    .btn-success:hover {
        background-color: darkgreen;
    }

    .extra-links a {
        text-decoration: none;
        color: green;
        font-weight: bold;
    }

    .extra-links a:hover {
        text-decoration: underline;
    }

    .logo img {
        width: 80px;
        margin-bottom: 10px;
    }
    </style>
</head>
<body>
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="login-container">
        <div class="logo">
            <img src="assets/images/pnhs.jpg" alt="School Logo">
        </div>
        <h2>TEACHER LOGIN</h2>
        <form method="post">
            <div class="mb-3 text-start">
                <label for="user" class="form-label">Username</label>
                <input name="user" class="form-control" type="text" required>
            </div>
            <div class="mb-3 text-start">
                <label for="password" class="form-label">Password</label>
                <input name="pass" type="password" class="form-control" required>
            </div>
            <button type="submit" name="submit" class="btn btn-success">LOGIN</button>
            <div class="mt-3 extra-links">
                <p>Not registered? <a href="register.php">Register</a></p>
                <p>Not teacher? <a href="login.php">Student Login</a></p>
            </div>
        </form>

<?php 
include 'connection.php'; 

if (isset($_POST['submit'])) {
    $user = trim($_POST['user']);
    $pass = trim($_POST['pass']);

    $sql = "SELECT * FROM users WHERE Username = ? AND User_role = 'Teacher'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row || !password_verify($pass, $row['Password'])) {
        echo '<p class="text-danger">Incorrect credentials or not a student account.</p>';
    } else {
        $_SESSION['username'] = $row['Username'];
        $_SESSION['User_role'] = $row['User_role'];
        $_SESSION['user_id'] = $row['user_id'];

        if ($row['Status'] == 0) {
            $update = $conn->prepare("UPDATE users SET Status = 1 WHERE Username = ?");
            $update->bind_param("s", $user);
            $update->execute();
        }

        header("Location: teacher_dashboard.php");
        exit();
    }
}
?>
    </div>
</div>
</body>
</html>
