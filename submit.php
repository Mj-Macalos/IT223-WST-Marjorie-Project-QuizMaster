<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'db_connect.php';

// Get data from POSTed FormData
$data = json_decode($_POST['studentData'] ?? '', true);

if (!$data) {
    echo json_encode(["error" => "No student data received."]);
    exit;
}

// Required fields
$requiredFields = ['student_id', 'quiz_id', 'first_name', 'last_name', 'program', 'year_level'];
foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        echo json_encode(["error" => "Missing required field: $field"]);
        exit;
    }
}

// Validate names
if (!preg_match("/^[a-zA-Z]+$/", $data['first_name']) ||
    !preg_match("/^[a-zA-Z]+$/", $data['last_name']) ||
    (!empty($data['middle_initial']) && !preg_match("/^[a-zA-Z]$/", $data['middle_initial'])) ||
    (!empty($data['suffix']) && !preg_match("/^[a-zA-Z.]+$/", $data['suffix']))
) {
    echo json_encode(["error" => "Name fields can only contain letters (and '.' for suffix)."]);
    exit;
}

// Check attempt count
$stmt = $mysqli->prepare("SELECT COUNT(*) as cnt FROM student_attempts WHERE student_id = ? AND quiz_id = ? AND first_name = ? AND last_name = ?");
$stmt->bind_param("siss", $data['student_id'], $data['quiz_id'], $data['first_name'], $data['last_name']);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

$attemptCount = intval($res['cnt'] ?? 0);
if ($attemptCount >= 1) { // You can customize this to $quiz['attempts_allowed'] if passed in
    echo json_encode(["error" => "You have reached the maximum number of attempts."]);
    exit;
}

$newAttempt = $attemptCount + 1;

// Insert attempt
$stmt = $mysqli->prepare("INSERT INTO student_attempts 
    (first_name, middle_initial, last_name, suffix, year_level, program, student_id, quiz_id, attempt_number)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param(
    "sssssssii",
    $data['first_name'],
    $data['middle_initial'],
    $data['last_name'],
    $data['suffix'],
    $data['year_level'],
    $data['program'],
    $data['student_id'],
    $data['quiz_id'],
    $newAttempt
);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "attempt_id" => $mysqli->insert_id]);
} else {
    echo json_encode(["error" => "Failed to insert attempt."]);
}
?>
