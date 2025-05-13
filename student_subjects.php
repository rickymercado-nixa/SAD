<?php require 'connection.php'; ?>
<?php
if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Student") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

$student_id = $_SESSION['user_id'];


if (isset($_POST['join_subject'])) {
    $subject_code = mysqli_real_escape_string($conn, $_POST['subject_code']);
    
    // Check if the subject exists in the classroom
    $subject_query = "SELECT * FROM subjects WHERE subject_code = '$subject_code'";
    $subject_result = mysqli_query($conn, $subject_query);
    
    if (mysqli_num_rows($subject_result) > 0) {
        $subject = mysqli_fetch_assoc($subject_result);
        $subject_id = $subject['sub_id'];
        
        // Check if student already joined
        $check_enrollment = "SELECT * FROM enrollments WHERE student_id = '$student_id' AND sub_id = '$subject_id'";
        $enrollment_result = mysqli_query($conn, $check_enrollment);
        
        if (mysqli_num_rows($enrollment_result) == 0) {
            $enroll_query = "INSERT INTO enrollments (student_id, sub_id) VALUES ('$student_id', '$subject_id')";
            mysqli_query($conn, $enroll_query);
            echo '<script>alert("Successfully joined the subject!");</script>';
        } else {
            echo '<script>alert("You are already enrolled in this subject.");</script>';
        }
    } else {
        echo '<script>alert("Invalid subject code.");</script>';
    }
}

// Fetch subjects
$enrolled_query = "SELECT subjects.sub_id, subjects.subject_name FROM enrollments 
                   JOIN subjects ON enrollments.sub_id = subjects.sub_id 
                   WHERE enrollments.student_id = '$student_id'";
$enrolled_result = mysqli_query($conn, $enrolled_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Subjects</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/fontawesome-free-6.7.2-web/fontawesome-free-6.7.2-web/css/all.css">
    <style>
		body {
			display: flex;
			min-height: 100vh;
		}
		.sidebar {
			width: 250px;
			height: 100vh;
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
			background: darkgreen;
		}
		.main-content {
			margin-left: 260px;
			padding: 20px;
			width: 100%;
		}
		.card {
			margin-bottom: 20px;
		}
	</style>
</head>
<body>
    <div class="sidebar bg-success">
        <h2>Student Panel</h2>
        <ul>
        <li><a href="student_dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
        <li><a href="student_subjects.php"><i class="fa-solid fa-book"></i> Subject Class</a></li>
        <li><a href="logout.php"><i class="fa fa-sign-out" aria-hidden="true"></i> Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <h1>Subject Class</h1>
        <form method="POST">
            <div class="mb-3">
                <label for="subject_code" class="form-label">Enter Subject Code:</label>
                <input type="text" name="subject_code" id="subject_code" class="form-control" required>
            </div>
            <button type="submit" name="join_subject" class="btn btn-success">Join Subject</button>
        </form>
        
        <h2 class="mt-4">Enrolled Subjects</h2>
        <ul class="list-group">
    <?php while ($row = mysqli_fetch_assoc($enrolled_result)) { ?>
        <li class="list-group-item">
            <a href="student_subject_class.php?subject_id=<?php echo $row['sub_id']; ?>" class="text-decoration-none">
                <?php echo $row['subject_name']; ?>
            </a>
        </li>
    <?php } ?>
</ul>
    </div>
</body>
</html>
