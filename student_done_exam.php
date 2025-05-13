<?php 
require 'connection.php'; 

// Make sure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Student") {
    echo '<script>alert("Access denied! Redirecting..."); window.location.href = "login.php";</script>';
    exit(); 
}

$student_id = $_SESSION['user_id']; 
$exam_id = $_GET['exam_id']; 
date_default_timezone_set('Asia/Manila');

// ✅ Fetch exam details
$exam_query = "SELECT exam_name, total_marks FROM exams WHERE exam_id = '$exam_id'";
$exam_result = mysqli_query($conn, $exam_query);
$exam_data = mysqli_fetch_assoc($exam_result);
$exam_name = $exam_data['exam_name'] ?? 'Unknown Exam';
$total_possible_marks = $exam_data['total_marks'] ?? 0;

// ✅ Fetch total score
$score_query = "SELECT SUM(marks_obtained) AS total_score FROM student_answers
                WHERE student_id = '$student_id' AND exam_id = '$exam_id'"; 
$score_result = mysqli_query($conn, $score_query); 
$score_data = mysqli_fetch_assoc($score_result); 
$total_score = $score_data['total_score'] ?? 0;  

// ✅ Fetch student's submission time
$submission_query = "SELECT submit_time, taken FROM exam_submissions
                    WHERE student_id = '$student_id' AND exam_id = '$exam_id'"; 
$submission_result = mysqli_query($conn, $submission_query); 
$submission_data = mysqli_fetch_assoc($submission_result);

$submit_time = !empty($submission_data['submit_time']) ? strtotime($submission_data['submit_time']) : null; 
$exam_status = $submission_data['taken'] ?? 0;

// ✅ Get part-wise breakdown of scores
$part_scores_query = "SELECT questions.question_part, SUM(student_answers.marks_obtained) AS part_score, 
                    COUNT(questions.question_id) AS question_count, 
                    SUM(questions.marks) AS possible_marks
                FROM student_answers
                JOIN questions ON student_answers.question_id = questions.question_id
                WHERE student_answers.student_id = '$student_id' AND student_answers.exam_id = '$exam_id'
                GROUP BY questions.question_part
                ORDER BY questions.question_part";
$part_scores_result = mysqli_query($conn, $part_scores_query);
$part_scores = [];
while ($row = mysqli_fetch_assoc($part_scores_result)) {
    $part_scores[] = $row;
}



// ✅ Get part timers (allowed time)
$part_timer_query = "SELECT question_part, timer 
                   FROM exam_part_timers
                   WHERE exam_id = '$exam_id'
                   ORDER BY question_part";
$part_timer_result = mysqli_query($conn, $part_timer_query);
$part_timers = [];
while ($row = mysqli_fetch_assoc($part_timer_result)) {
    $part_timers[$row['question_part']] = $row['timer'];
}

// Calculate percentage score
$percentage = ($total_possible_marks > 0) ? round(($total_score / $total_possible_marks) * 100, 1) : 0;

// Determine grade/remarks based on percentage
function getRemarks($percentage) {
    if ($percentage >= 90) return ['Excellent', 'text-success'];
    if ($percentage >= 80) return ['Very Good', 'text-primary'];
    if ($percentage >= 70) return ['Good', 'text-info'];
    if ($percentage >= 60) return ['Satisfactory', 'text-warning'];
    return ['Needs Improvement', 'text-danger'];
}

$remarks = getRemarks($percentage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Completed</title>
    <link rel="stylesheet" href="assets/bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <style>
        .score-card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .part-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        .time-badge {
            font-size: 0.8rem;
            display: inline-block;
            margin-top: 8px;
        }
        .score-circle {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px;
            border: 5px solid #0d6efd;
        }
        .score-value {
            font-size: 2.5rem;
            font-weight: bold;
            line-height: 1;
        }
        .score-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .progress {
            height: 10px;
        }
    </style>
</head>
<body>
<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="score-card bg-white">
                <h2 class="text-center mb-4"><?php echo htmlspecialchars($exam_name); ?> - Results</h2>
                
                <div class="score-circle">
                    <div class="score-value"><?php echo $percentage; ?>%</div>
                    <div class="score-label">Score</div>
                </div>
                
                <div class="text-center mb-4">
                    <h4><?php echo $total_score; ?> / <?php echo $total_possible_marks; ?> points</h4>
                    <span class="badge bg-light <?php echo $remarks[1]; ?> fs-6 px-3 py-2">
                        <?php echo $remarks[0]; ?>
                    </span>
                    
                    <?php if ($submit_time): ?>
                    <p class="text-muted mt-2">
                        Submitted on <?php echo date('F j, Y g:i A', $submit_time); ?>
                    </p>
                    <?php endif; ?>
                </div>

                <h4 class="mb-3">Part Breakdown</h4>
                
                <?php foreach ($part_scores as $part): ?>
                <div class="part-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5><?php echo htmlspecialchars($part['question_part']); ?></h5>
                            <p class="mb-1">
                                Score: <strong><?php echo $part['part_score']; ?> / <?php echo $part['possible_marks']; ?></strong>
                                (<?php echo ($part['possible_marks'] > 0) ? round(($part['part_score'] / $part['possible_marks']) * 100) : 0; ?>%)
                            </p>
                            <p class="mb-2">Questions: <?php echo $part['question_count']; ?></p>
                            
                            <?php if (isset($part_timers[$part['question_part']])): ?>
                            <span class="badge bg-secondary time-badge">
                                Time spent: <?php echo $part_timers[$part['question_part']]; ?> minutes
                                <?php if (isset($part_timers[$part['question_part']])): ?>
                                of <?php echo $part_timers[$part['question_part']]; ?> minutes allowed
                                <?php endif; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-end" style="min-width: 100px;">
                            <?php 
                            $part_percentage = ($part['possible_marks'] > 0) ? ($part['part_score'] / $part['possible_marks']) * 100 : 0;
                            $progress_class = 'bg-danger';
                            if ($part_percentage >= 60) $progress_class = 'bg-warning';
                            if ($part_percentage >= 70) $progress_class = 'bg-info';
                            if ($part_percentage >= 80) $progress_class = 'bg-primary';
                            if ($part_percentage >= 90) $progress_class = 'bg-success';
                            ?>
                            <div class="progress mb-2">
                                <div class="progress-bar <?php echo $progress_class; ?>" role="progressbar" 
                                    style="width: <?php echo $part_percentage; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="d-flex justify-content-center mt-4 gap-3">
                    <a href="student_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                    <a href="student_review_exam.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-info">Review Exam</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>