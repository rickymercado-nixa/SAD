<?php require 'connection.php'; ?>
<?php
if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Student") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

	if (isset($_POST['logout'])) {
		if (isset($_SESSION['username'])) {
			$username = $_SESSION['username'];
			$sql = "UPDATE `users` SET `Status` = '0' WHERE `Username` = '$username'";
			$result = mysqli_query($conn, $sql);

			session_destroy();
			header("Location: logout.php");
			exit();
		}
	}

	$student_id = $_SESSION['user_id'];

$student_id = $_SESSION['user_id'];
$fname = "SELECT Fname FROM users WHERE user_id = '$student_id'";
$res = mysqli_query($conn, $fname);
$row = mysqli_fetch_assoc($res);
$student_name = $row['Fname'];

$query = "SELECT exams.exam_name, SUM(student_answers.marks_obtained) AS total_score
          FROM exam_submissions
          JOIN exams ON exam_submissions.exam_id = exams.exam_id
          JOIN student_answers ON exam_submissions.exam_id = student_answers.exam_id AND exam_submissions.student_id = student_answers.student_id
          WHERE exam_submissions.student_id = '$student_id'
          GROUP BY exam_submissions.exam_id";

$result = mysqli_query($conn, $query);

$exam_labels = [];
$exam_scores = [];

while ($row = mysqli_fetch_assoc($result)) {
    $exam_labels[] = $row['exam_name'];
    $exam_scores[] = $row['total_score'];
}

$exam_labels_json = json_encode($exam_labels);
$exam_scores_json = json_encode($exam_scores);

$exams_query = "SELECT exam_id, exam_name, status, start_time, end_time FROM exams WHERE status IN ('Ongoing', 'Upcoming')";
$exams_result = mysqli_query($conn, $exams_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Student Dashboard</title>
	<link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="assets/fontawesome-free-6.7.2-web/fontawesome-free-6.7.2-web/css/all.css">
	<script src="assets/Chart.js-4.4.8/package/dist/chart.umd.js"></script>
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
<h1>Welcome, <?php echo htmlspecialchars($student_name); ?>!</h1>

	<div class="row">
		<div class="col-md-8">
			<div class="card">
				<div class="card-header bg-success text-white">Your Exams Scores</div>
				<div class="card-body">
					<canvas id="progressChart"></canvas>
				</div>
			</div>
		</div>

		<div class="col-md-4">
		<div class="card">
    <div class="card-header bg-success text-white">Ongoing Exams</div>
    <div class="card-body">
        <ul>
            <?php
            date_default_timezone_set('Asia/Manila');
            $current_time = date("Y-m-d H:i:s");

            $exams = []; // Store exams in an array
while ($exam = mysqli_fetch_assoc($exams_result)) {
    $exams[] = $exam;
}

$going = false;

foreach ($exams as $exam) {
    if ($exam['status'] === 'Ongoing') {
        $going = true;
        echo "<li class='list-group-item'>";
        echo "<strong>{$exam['exam_name']}</strong><br>";
        echo "<small>Start Time: " . date("F j, Y, g:i A", strtotime($exam['start_time'])) . "</small><br>";
        echo "<small>End Time: " . date("F j, Y, g:i A", strtotime($exam['end_time'])) . "</small><br>";
        echo "<div id='countdown_{$exam['exam_id']}'></div>";
        echo "</li>";
    }
}
if (!$going) {
    echo "<li class='list-group-item text-danger'>No Upcoming Exams.</li>";
}

            ?>
        </ul>
    </div>
</div>

			<div class="card">
    <div class="card-header bg-success text-white">Upcoming Exams</div>
    <div class="card-body">
        <ul>
            <?php
            date_default_timezone_set('Asia/Manila');
            $current_time = date("Y-m-d H:i:s");

            $upcoming = false;

foreach ($exams as $exam) {
    if ($exam['status'] === 'Upcoming') {
        $upcoming = true;
        echo "<li class='list-group-item'>";
        echo "<strong>{$exam['exam_name']}</strong><br>";
        echo "<small>Start Time: " . date("F j, Y, g:i A", strtotime($exam['start_time'])) . "</small><br>";
        echo "<small>End Time: " . date("F j, Y, g:i A", strtotime($exam['end_time'])) . "</small><br>";
        echo "<div id='countdown_{$exam['exam_id']}'></div>";
        echo "</li>";
    }
}
if (!$upcoming) {
    echo "<li class='list-group-item text-danger'>No Upcoming Exams.</li>";
}
            
            ?>
        </ul>
    </div>
</div>

		</div>
	</div>
</div>

<script>
var ctx = document.getElementById('progressChart').getContext('2d');
var progressChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo $exam_labels_json; ?>,
        datasets: [{
            label: 'Exam Scores',
            data: <?php echo $exam_scores_json; ?>,
            backgroundColor: 'rgba(0, 128, 0, 0.5)',
            borderColor: 'green',
            borderWidth: 2
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                max: 100 // Adjust according to exam scoring system
            }
        }
    }
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    function startCountdown(examId, startTime) {
        var countdownElement = document.getElementById("countdown_" + examId);
        var examStartTime = new Date(startTime).getTime();
        
        var timer = setInterval(function () {
            var now = new Date().getTime();
            var timeLeft = examStartTime - now;

            if (timeLeft > 0) {
                var days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                var hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

                countdownElement.innerHTML = `<span class='badge bg-danger'>Starts in: ${days}d ${hours}h ${minutes}m ${seconds}s</span>`;
            } else {
                countdownElement.innerHTML = "<span class='badge bg-success'>Exam Started!</span>";
                clearInterval(timer);
            }
        }, 1000);
    }

    <?php
    mysqli_data_seek($exams_result, 0); // Reset result pointer
    while ($exam = mysqli_fetch_assoc($exams_result)) {
        if ($exam['start_time'] > $current_time) {
            echo "startCountdown('{$exam['exam_id']}', '{$exam['start_time']}');";
        }
    }
    ?>
});
</script>


<script src="assets/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
