<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Dashboard</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa; /* Light background */
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
                <input name="user" type="text" class="form-control" id="user" required>
            </div>

            <div class="mb-3 text-start">
                <label for="password" class="form-label">Password</label>
                <input name="pass" type="password" class="form-control" id="password" required>
            </div>

            <button type="submit" name="submit" class="btn btn-success">LOGIN</button>

        </form>

        <?php 
        include 'connection.php';

        if(isset($_POST['submit'])) {
            $users = trim($_POST['user']);
            $passs = trim($_POST['pass']);

            if(empty($users) || empty($passs)) {
                echo '<p class="text-danger">Please enter username and password.</p>';
            } else {
                $sql = "SELECT * FROM `admin` WHERE `Username` = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $users);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();

                if(!$row || $passs !== $row['Password']) {
                    echo '<p class="text-danger">Incorrect Credentials, Please try again!</p>';
                } else {
                        header("Location: dashboard_admin.php");
                        exit();
                    }
                }
            }
        ?>
    </div>
</div>

<script src="bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
