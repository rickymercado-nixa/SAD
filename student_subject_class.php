<?php 
require 'connection.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Student") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit();
}

$student_id = $_SESSION['user_id'];

if (!isset($_GET['subject_id'])) {
    echo '<script>alert("Invalid subject."); window.location.href = "student_subjects.php";</script>';
    exit();
}

$subject_id = mysqli_real_escape_string($conn, $_GET['subject_id']);
$student_id = $_SESSION['user_id'];

$subject_query = "SELECT subjects.subject_name, users.Fname AS teacher_name 
                  FROM subjects 
                  JOIN users ON subjects.teacher_id = users.user_id
                  WHERE subjects.sub_id = '$subject_id'";
$subject_result = mysqli_query($conn, $subject_query);
$subject = mysqli_fetch_assoc($subject_result);

if (!$subject) {
    echo '<script>alert("Subject not found."); window.location.href = "student_subjects.php";</script>';
    exit();
}

$classmates_query = "SELECT users.fname, users.lname FROM enrollments 
                     JOIN users ON enrollments.student_id = users.user_id
                     WHERE enrollments.sub_id = '$subject_id'";
$classmates_result = mysqli_query($conn, $classmates_query);

$exams_query = "SELECT exams.exam_id, exams.exam_name, exams.status, exams.start_time, exams.end_time, 
                       (SELECT COUNT(*) FROM exam_submissions
                        WHERE exam_submissions.exam_id = exams.exam_id 
                        AND exam_submissions.student_id = '$student_id') AS taken 
                FROM exams 
                WHERE sub_id = '$subject_id'";
$exams_result = mysqli_query($conn, $exams_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $subject['subject_name']; ?> - Subject Class</title>
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
<div class="sidebar bg-success">
    <h2>Student Panel</h2>
    <ul>
        <li><a href="student_dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
        <li><a href="student_subjects.php"><i class="fa-solid fa-book"></i> Subject Class</a></li>
        <li><a href="logout.php"><i class="fa fa-sign-out" aria-hidden="true"></i> Logout</a></li>
    </ul>
</div>

<div class="main-content">
        <h1><?php echo $subject['subject_name']; ?></h1>
        <p><strong>Teacher:</strong> <?php echo $subject['teacher_name']; ?></p>

        <h3>Classmates</h3>
        <ul class="list-group">
            <?php while ($row = mysqli_fetch_assoc($classmates_result)) { ?>
                <li class="list-group-item"><?php echo $row['fname']. ' ' .$row['lname']; ?></li>
            <?php } ?>
        </ul>

        <h3 class="mt-4">Exams</h3>
        <div class="mb-3">
    <label for="examFilter" class="form-label"><strong>Filter Exams:</strong></label>
    <select class="form-select" id="examFilter" onchange="filterExams()">
        <option value="Ongoing" selected>🟢 Ongoing Exams</option>
        <option value="Upcoming">🟡 Upcoming Exams</option>
        <option value="Finished">🔴 Finished Exams</option>
    </select>
</div>

            <?php
            date_default_timezone_set('Asia/Manila');
            $current_time = date("Y-m-d H:i:s");

            $ongoing_exams = [];
            $upcoming_exams = [];
            $finished_exams = [];
            
            while ($exam = mysqli_fetch_assoc($exams_result)) {
                $start_time = $exam['start_time'];
                $end_time = $exam['end_time'];
                $taken = $exam['taken'];
            
                // If already taken, put into Finished no matter the time
                if ($taken) {
                    $finished_exams[] = $exam;
                } elseif ($current_time < $start_time) {
                    $upcoming_exams[] = $exam;
                } elseif ($current_time >= $start_time && $current_time <= $end_time) {
                    $ongoing_exams[] = $exam;
                } else {
                    $finished_exams[] = $exam;
                }
            }            
            
            function renderExamList($exams, $status_label, $current_time, $student_id) {
                foreach ($exams as $exam) {
                    $start_time = $exam['start_time'];
                    $end_time = $exam['end_time'];
                    $taken = $exam['taken'];
                    echo "<li class='list-group-item'>";
                    echo "<strong>{$exam['exam_name']}</strong><br>";
                    echo "<small>Start Time: " . date("F j, Y, g:i A", strtotime($start_time)) . "</small><br>";
                    echo "<small>End Time: " . date("F j, Y, g:i A", strtotime($end_time)) . "</small><br>";
                    echo "<div id='countdown_{$exam['exam_id']}'></div>";
            
                    if ($status_label === 'Upcoming') {
                        echo "<span class='text-warning'>Not Started</span>";
                    } elseif ($status_label === 'Ongoing') {
                        if ($taken) {
                            echo "<span class='text-secondary'>Already Taken</span>";
                        } else {
                            echo "<a href='student_take_exam.php?exam_id={$exam['exam_id']}' class='btn btn-primary mt-2'>Take Exam</a>";
                        }
                    } else {
                        echo "<span class='text-danger'>Expired</span>";
                    }
            
                    if ($status_label !== 'Finished') {
                        echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            startCountdown('countdown_{$exam['exam_id']}', '{$start_time}', '{$end_time}');
                        });
                        </script>";
                    }                    
                    echo "</li>";
                }
            }
            
?>
<!-- Ongoing Exams -->
<div id="ongoingExams" style="display: block;">
    <h3 class="mt-4">🟢 Ongoing Exams</h3>
    <ul class="list-group mb-4">
        <?php 
            if (count($ongoing_exams) > 0) {
                renderExamList($ongoing_exams, 'Ongoing', $current_time, $student_id);
            } else {
                echo "<li class='list-group-item'>No ongoing exams.</li>";
            }
        ?>
    </ul>
</div>

<!-- Upcoming Exams -->
<div id="upcomingExams" style="display: none;">
    <h3>🟡 Upcoming Exams</h3>
    <ul class="list-group mb-4">
        <?php 
            if (count($upcoming_exams) > 0) {
                renderExamList($upcoming_exams, 'Upcoming', $current_time, $student_id);
            } else {
                echo "<li class='list-group-item'>No upcoming exams.</li>";
            }
        ?>
    </ul>
</div>

<!-- Finished Exams -->
<div id="finishedExams" style="display: none;">
    <h3>🔴 Finished Exams</h3>
    <ul class="list-group">
        <?php 
            if (count($finished_exams) > 0) {
                renderExamList($finished_exams, 'Finished', $current_time, $student_id);
            } else {
                echo "<li class='list-group-item'>No finished exams.</li>";
            }
        ?>
    </ul>
</div>

</div>
</div>
<script>
function startCountdown(elementId, startTime, endTime) {
    const countdownElement = document.getElementById(elementId);

    function updateCountdown() {
        const now = new Date().getTime();
        const startTimestamp = new Date(startTime).getTime();
        const endTimestamp = new Date(endTime).getTime();

        if (now < startTimestamp) {
            const remainingTime = startTimestamp - now;
            countdownElement.innerHTML = 'Exam starts in: ' + formatTime(remainingTime);
        } else if (now >= startTimestamp && now <= endTimestamp) {
            const remainingTime = endTimestamp - now;
            countdownElement.innerHTML = 'Time left: ' + formatTime(remainingTime);
        } else {
            countdownElement.innerHTML = '<span class=\"text-danger\">Exam has ended</span>';
        }
    }

    function formatTime(ms) {
        const totalSeconds = Math.floor(ms / 1000);
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        return `${hours}h ${minutes}m ${seconds}s`;
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
}
</script>
<script>
function filterExams() {
    const filter = document.getElementById("examFilter").value;

    const ongoing = document.getElementById("ongoingExams");
    const upcoming = document.getElementById("upcomingExams");
    const finished = document.getElementById("finishedExams");

    ongoing.style.display = "none";
    upcoming.style.display = "none";
    finished.style.display = "none";

    if (filter === "Ongoing") {
        ongoing.style.display = "block";
    } else if (filter === "Upcoming") {
        upcoming.style.display = "block";
    } else if (filter === "Finished") {
        finished.style.display = "block";
    }
}

</script>
</body>
</html>