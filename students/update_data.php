<?php
include_once "../db_connect.php";

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['id']) && !empty($_SESSION['id'])) {
    $id = $_SESSION['id'];
    $conn = connectDB();
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Update internship details
    $about = $_POST['about'];
    $scope = $_POST['scope'];
    $feedback = $_POST['feedback'];
    $employment = $_POST['employment'];

    $stmt = $conn->prepare("UPDATE internship_student SET about = ?, scope = ?, feedback = ?, employment = ? WHERE student_id = ?");
    $stmt->bind_param("ssssi", $about, $scope, $feedback, $employment, $id);
    $stmt->execute();

    // Update Skill Scores
    foreach ($_POST['grades'] as $skill_id => $grade) {
        $stmt = $conn->prepare("UPDATE skill_student SET grade = ? WHERE student_id = ? AND skill_id = ?");
        $stmt->bind_param("iii", $grade, $id, $skill_id);
        $stmt->execute();
    }

    $stmt->close();
    $conn->close();
    header('Location: edit_form.php'); // Redirect to prevent resubmission
    exit;
}

?>
