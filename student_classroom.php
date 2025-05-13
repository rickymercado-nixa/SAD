<?php require 'connection.php'; ?>
<?php
if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Student") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

$student_id = $_SESSION['user_id'];

if (isset($_POST['join_classroom'])) {
    $class_code = mysqli_real_escape_string($conn, $_POST['class_code']);
    
    // Check if the classroom exists
    $classroom_query = "SELECT * FROM classrooms WHERE class_code = '$class_code'";
    $classroom_result = mysqli_query($conn, $classroom_query);
    
    if (mysqli_num_rows($classroom_result) > 0) {
        $classroom = mysqli_fetch_assoc($classroom_result);
        $classroom_id = $classroom['classroom_id'];
        
        // Check if student already joined
        $check_enrollment = "SELECT * FROM classroom_students WHERE student_id = '$student_id' AND classroom_id = '$classroom_id'";
        $enrollment_result = mysqli_query($conn, $check_enrollment);
        
        if (mysqli_num_rows($enrollment_result) == 0) {
            $enroll_query = "INSERT INTO classroom_students (student_id, classroom_id) VALUES ('$student_id', '$classroom_id')";
            mysqli_query($conn, $enroll_query);
            echo '<script>alert("Successfully joined the classroom!");</script>';
        } else {
            echo '<script>alert("You are already in this classroom.");</script>';
        }
    } else {
        echo '<script>alert("Invalid class code.");</script>';
    }
}

// Fetch classrooms the student has joined
$joined_classes_query = "SELECT classrooms.classroom_id, classrooms.classroom_name FROM classroom_students 
                         JOIN classrooms ON classroom_students.classroom_id = classrooms.classroom_id 
                         WHERE classroom_students.student_id = '$student_id'";
$joined_classes_result = mysqli_query($conn, $joined_classes_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Classrooms</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/fontawesome-free-6.7.2-web/css/all.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .sidebar {
            width: 250px;
            height: 100vh;
            background: #198754;
            color: white;
            padding: 20px;
            position: fixed;
        }
        .sidebar h2 {
            text-align: center;
        }
        .sidebar ul {
            padding: 0;
            list-style: none;
        }
        .sidebar ul li {
            margin: 20px 0;
        }
        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            border-radius: 5px;
            transition: 0.3s;
        }
        .sidebar ul li a:hover {
            background: #145c32;
        }
        .main-content {
            margin-left: 270px;
            padding: 30px;
            width: 100%;
        }
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .classroom-list a {
            text-decoration: none;
            font-weight: bold;
            color: #198754;
            transition: 0.3s;
        }
        .classroom-list a:hover {
            color: #145c32;
        }
    </style>
</head>
<body>
<div class="sidebar">
    <h2>Student Panel</h2>
    <ul>
        <li><a href="student_dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
        <li><a href="student_classroom.php"><i class="fa-solid fa-book"></i> Classroom</a></li>
        <li><a href="student_leaderboard.php"><i class="fa-solid fa-trophy"></i> Overall Leaderboard</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="container">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card p-4">
                    <h3 class="text-center">Join a Classroom</h3>
                    <form method="POST" class="mt-3">
                        <div class="mb-3">
                            <input type="text" name="class_code" class="form-control" placeholder="Enter Classroom Code" required>
                        </div>
                        <button type="submit" name="join_classroom" class="btn btn-success w-100">Join</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-8 offset-md-2">
                <div class="card p-4">
                    <h3 class="text-center">Your Classrooms</h3>
                    <ul class="list-group classroom-list">
                        <?php while ($row = mysqli_fetch_assoc($joined_classes_result)) { ?>
                            <li class="list-group-item">
                                <a href="student_subjects.php?classroom_id=<?php echo $row['classroom_id']; ?>">
                                    <?php echo $row['classroom_name']; ?>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
