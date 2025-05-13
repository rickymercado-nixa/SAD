<?php
require 'connection.php';

// Make sure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the POST data (sent as JSON from JavaScript)
$data = json_decode(file_get_contents("php://input"), true);

// Check if we have valid event data
if ($data && isset($data['event'])) {
    // Get student_id and exam_id from the request data if available,
    // otherwise fall back to session variables
    $student_id = isset($data['student_id']) ? $data['student_id'] : $_SESSION['user_id'];
    $exam_id = isset($data['exam_id']) ? $data['exam_id'] : $_SESSION['exam_id'];
    
    // Sanitize inputs to prevent SQL injection
    $event = mysqli_real_escape_string($conn, $data['event']);
    $student_id = mysqli_real_escape_string($conn, $student_id);
    $exam_id = mysqli_real_escape_string($conn, $exam_id);
    
    // Add timestamp
    $timestamp = date('Y-m-d H:i:s');
    
    // Insert into database with timestamp
    $result = mysqli_query($conn, "INSERT INTO cheating_logs 
                          (exam_id, student_id, event_type, timestamp) 
                          VALUES ('$exam_id', '$student_id', '$event', '$timestamp')");
    
    // Check if query was successful
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
}
?>