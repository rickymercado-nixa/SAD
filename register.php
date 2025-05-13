<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.bundle.min.css">
    <link rel="stylesheet" href="assets/fontawesome-free-6.7.2-web/css/all.css">
<style>
    body {
        background-color: #f8f9fa;
    }

    .register-container {
        max-width: 500px;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0px 4px 10px rgba(0, 128, 0, 0.2);
    }

    .register-container h2 {
        color: green;
        font-weight: bold;
        text-align: center;
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

    .logo img {
        width: 80px;
        display: block;
        margin: 0 auto;
    }

    .extra-links {
        text-align: center;
        margin-top: 10px;
    }

    .extra-links a {
        text-decoration: none;
        color: green;
        font-weight: bold;
    }

    .extra-links a:hover {
        text-decoration: underline;
    }

    .input-group button {
    border: none;
    background: transparent;
    padding: 8px 12px;
    }

    .input-group .btn-outline-secondary {
    border: 1px solid green;
    border-left: none;
    }

    
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="register-container">
            <div class="logo">
                <img src="assets/images/pnhs.jpg" alt="School Logo">
            </div>
            <h2>SIGN UP</h2>
            <?php
        include 'connection.php';

        if (isset($_POST['submit'])) {
            $users = $_POST['user'];
            $passs = $_POST['pass'];
            $fnames = $_POST['fname'];
            $mnames = $_POST['mname'];
            $lnames = $_POST['lname'];
            $emails = $_POST['email'];
            $User_role = $_POST['user_role'];

            $query = "SELECT * FROM `users` WHERE `Username` = ?";
            $stmts = $conn->prepare($query);
            $stmts->bind_param("s", $users);
            $stmts->execute();
            $result = $stmts->get_result();
            $row = $result->fetch_assoc();

            if ($row) {
                echo '<p class="text-danger text-center">User already exists! Please try another username.</p>';
            } else {
                $hashed_password = password_hash($passs, PASSWORD_DEFAULT);

                $sql = "INSERT INTO `users`(`Username`, `Password`, `Fname`, `Mname`, `Lname`, `Email`, `User_role`) VALUES (?,?,?,?,?,?,?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssss", $users, $hashed_password, $fnames, $mnames, $lnames, $emails, $User_role);

                if ($stmt->execute()) {
                    echo '<p class="text-success text-center">Registered Successfully! <a href="login.php">Login Here</a></p>';
                } else {
                    echo '<p class="text-danger text-center">Registration failed. Please try again.</p>';
                }
            }
        }
        ?>

            <form method="post">
                <div class="row">
                <div class="col-md-6 mb-3">
    <label class="form-label" id="user-label">LRN</label>

    <input id="lrn-input" class="form-control" required
    oninput="this.value = this.value.replace(/[^0-9]/g, '')">

<input id="username-input" class="form-control d-none" required>

</div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password</label>
                            <input name="pass" type="password" id="password" class="form-control" required>
                        </div>
                    </div>



                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input name="fname" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Middle Name</label>
                            <input name="mname" class="form-control">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input name="lname" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <input name="email" type="email" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">User Role</label>
                            <select name="user_role" id="user-role" class="form-control" required onchange="toggleUserInput()">
    <option value="Student">Student</option>
    <option value="Teacher">Teacher</option>
</select>

                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <button type="submit" name="submit" class="btn btn-success w-100">Register</button>
                        </div>
                    </div>

                    <div class="extra-links text-center">
                        <p>Already have an account? <a href="login.php">Login Here</a></p>
                    </div>
            </form>
        </div>
    </div>

    <script src="assets/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js">
    </script>
<script>
function toggleUserInput() {
    const role = document.getElementById('user-role').value;
    const lrnInput = document.getElementById('lrn-input');
    const usernameInput = document.getElementById('username-input');
    const userLabel = document.getElementById('user-label');

    if (role === "Student") {
        lrnInput.classList.remove("d-none");
        lrnInput.setAttribute("name", "user");
        lrnInput.disabled = false;

        usernameInput.classList.add("d-none");
        usernameInput.removeAttribute("name");
        usernameInput.disabled = true;

        userLabel.innerText = "LRN";
    } else {
        usernameInput.classList.remove("d-none");
        usernameInput.setAttribute("name", "user");
        usernameInput.disabled = false;

        lrnInput.classList.add("d-none");
        lrnInput.removeAttribute("name");
        lrnInput.disabled = true;

        userLabel.innerText = "Username";
    }
}

document.addEventListener("DOMContentLoaded", toggleUserInput);
</script>


</body>
</html>