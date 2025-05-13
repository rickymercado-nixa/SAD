<?php
// Separate file: generate_report.php
require 'connection.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["User_role"] !== "Teacher") {
    echo 'Access denied!';
    exit();
}

if (isset($_POST['action']) && $_POST['action'] == 'generate_report' && isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    $teacher_id = $_SESSION["user_id"];
    
    // Get student info
    $stmt = $conn->prepare("SELECT fname, lname FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    
    // Get exam scores
    $stmt = $conn->prepare("
        SELECT exams.exam_name, subjects.subject_name, SUM(student_answers.marks_obtained) AS total_score
        FROM student_answers
        JOIN exams ON student_answers.exam_id = exams.exam_id
        JOIN subjects ON exams.sub_id = subjects.sub_id
        WHERE student_answers.student_id = ? AND exams.teacher_id = ?
        GROUP BY exams.exam_id
    ");
    $stmt->bind_param("ii", $student_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exam_scores = [];
    while ($row = $result->fetch_assoc()) {
        $exam_scores[] = $row;
    }
    $stmt->close();
    
    // Get correct/incorrect answers
    $stmt = $conn->prepare("
        SELECT exams.exam_name,
            SUM(CASE WHEN student_answers.is_correct = 1 THEN 1 ELSE 0 END) AS correct_answers,
            SUM(CASE WHEN student_answers.is_correct = 0 THEN 1 ELSE 0 END) AS incorrect_answers
        FROM student_answers
        JOIN exams ON student_answers.exam_id = exams.exam_id
        WHERE student_answers.student_id = ? AND exams.teacher_id = ?
        GROUP BY exams.exam_id
    ");
    $stmt->bind_param("ii", $student_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $answers_data = [];
    while ($row = $result->fetch_assoc()) {
        $answers_data[] = $row;
    }
    $stmt->close();
    
    // Find strongest/weakest subjects
    $strongest_subject = null;
    $weakest_subject = null;
    $max_score = -1;
    $min_score = PHP_INT_MAX;
    
    foreach ($exam_scores as $exam) {
        if ($exam['total_score'] > $max_score) {
            $max_score = $exam['total_score'];
            $strongest_subject = $exam['subject_name'];
        }
        
        if ($exam['total_score'] < $min_score) {
            $min_score = $exam['total_score'];
            $weakest_subject = $exam['subject_name'];
        }
    }
    
    // Generate HTML content for Report
    $html = '
    <html>
    <head>
        <title>Student Exam Score Report</title>
        <style>
            body { font-family: Arial, sans-serif; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
            th { background-color: #f2f2f2; }
            h1 { text-align: center; }
        </style>
    </head>
    <body>
        <h1>Student Analytics Report</h1>
        <p><strong>Student Name:</strong> ' . htmlspecialchars($student['fname'] . ' ' . $student['lname']) . '</p>
        <p><strong>Report Date:</strong> ' . date('F j, Y, g:i a') . '</p>
        
        <h2>Exam Scores</h2>
        <table>
            <tr>
                <th>Exam Name</th>
                <th>Subject</th>
                <th>Score</th>
            </tr>';
    
    foreach ($exam_scores as $score) {
        $html .= '
        <tr>
            <td>' . htmlspecialchars($score['exam_name']) . '</td>
            <td>' . htmlspecialchars($score['subject_name']) . '</td>
            <td>' . htmlspecialchars($score['total_score']) . '</td>
        </tr>';
    }
    
    $html .= '
        </table>
        
        <h2>Correct vs Incorrect Answers</h2>
        <table>
            <tr>
                <th>Exam Name</th>
                <th>Correct Answers</th>
                <th>Incorrect Answers</th>
            </tr>';
    
    foreach ($answers_data as $answer) {
        $html .= '
        <tr>
            <td>' . htmlspecialchars($answer['exam_name']) . '</td>
            <td>' . htmlspecialchars($answer['correct_answers']) . '</td>
            <td>' . htmlspecialchars($answer['incorrect_answers']) . '</td>
        </tr>';
    }
    
    $html .= '
        </table>
        
        <h2>Student\'s Strength and Weakness</h2>
        <p><strong>Strongest Subject:</strong> ' . htmlspecialchars($strongest_subject) . ' (Score: ' . $max_score . ')</p>
        <p><strong>Weakest Subject:</strong> ' . htmlspecialchars($weakest_subject) . ' (Score: ' . $min_score . ')</p>
    </body>
    </html>';
    
    // Output the HTML to the browser
    echo $html;
    exit;
}
?>
