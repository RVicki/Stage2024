<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Including Composer Autoload
include_once "../db_connect.php"; // Including the database connection script

use Dompdf\Dompdf;

session_start();
if (!isset($_SESSION['id'])) {
    die("Access denied. Please log in first.");
}

// Establishing a database connection
$conn = connectDB();

$id = $_SESSION['id'];

// Querying internship information
$query = $conn->prepare("
    SELECT
        s.first_name AS student_first_name,
        s.last_name AS student_last_name,
        c.name AS course_name,
        t.first_name AS teacher_first_name,
        t.last_name AS teacher_last_name,
        ist.start_date AS internship_start_date,
        ist.end_date AS internship_end_date,
        ist.scope AS scope,
        ist.about AS about,
        ist.feedback AS feedback,
        ist.employment AS employment,
        cp.first_name AS contact_person_first_name,
        cp.last_name AS contact_person_last_name,
        cp.email AS contact_person_email,
        cp.phone AS contact_person_phone,
        co.name AS company_name
    FROM students s
    JOIN internship_student ist ON s.id = ist.student_id
    JOIN student_course sc ON s.id = sc.student_id
    JOIN courses c ON sc.course_id = c.id
    JOIN course_teacher ct ON c.id = ct.course_id
    JOIN teachers t ON ct.teacher_id = t.id
    JOIN internships i ON ist.internship_id = i.id
    JOIN contact_person cp ON i.contact_person_id = cp.id
    JOIN companies co ON i.company_id = co.id
    WHERE s.id = ?
");
$query->bind_param('i', $id);
$query->execute();
$result = $query->get_result();
$response = $result->fetch_assoc();

// Querying grades
$queryGrades = $conn->prepare("
    SELECT
        sk.name AS skill_name,
        ss.grade AS grade,
        sk.type AS skill_type
    FROM skills sk
    JOIN skill_student ss ON sk.id = ss.skill_id
    WHERE ss.student_id = ?
");
$queryGrades->bind_param('i', $id);
$queryGrades->execute();
$gradesResult = $queryGrades->get_result();
$responseGrades = $gradesResult->fetch_all(MYSQLI_ASSOC);

// Calculating total grade points and skill points
$totalGradePoints = 0;
$totalHardSkillPoints = 0;
$totalSoftSkillPoints = 0;
$totalHardSkills = 0;
$totalSoftSkills = 0;

foreach ($responseGrades as $grade) {
    $totalGradePoints += $grade['grade'];
    if ($grade['skill_type'] == 'hard') {
        $totalHardSkillPoints += $grade['grade'];
        $totalHardSkills++;
    } else {
        $totalSoftSkillPoints += $grade['grade'];
        $totalSoftSkills++;
    }
}

// Calculating percentages
$totalPercent = round($totalGradePoints / ($totalHardSkills + $totalSoftSkills) * 10, 2);
$hardSkillPercent = round($totalHardSkillPoints / $totalHardSkills * 10, 2);
$softSkillPercent = round($totalSoftSkillPoints / $totalSoftSkills * 10, 2);

$conn->close();

// Creating PDF
$pdf = new Dompdf();
$pdf->set_option('isHtml5ParserEnabled', true);
ob_start(); // Start output buffering
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Stagereport</title>
    <style>
        body {
            font-family: Arial, sans-serif; 
            color: black; 
            background-color: white; 
            margin-left: 20px; 
            margin-right: 20px; 
        }
        table {
            width: 70%;
            border-collapse: collapse;
            margin-left: left; 
            margin-right: auto;
        }
        th, td {
            border: 1px solid black; 
            padding: 8px; 
            text-align: left;
        }
        h1, h2, h3 {
            color: black; 
            font-size: 1em; 
            margin-top: 30px; 
        }
        .section { 
            margin-top: 30px;
        }
        .contact-name { font-weight: bold; } 
        
    </style>
</head>
<body>
    <p class="contact-name"><?php echo htmlspecialchars($response['contact_person_first_name']) . ' ' . htmlspecialchars($response['contact_person_last_name']); ?></p>
    <p><a href="mailto:<?php echo htmlspecialchars($response['contact_person_email']); ?>"><?php echo htmlspecialchars($response['contact_person_email']); ?></a></p>
    <p><?php echo htmlspecialchars($response['contact_person_phone']); ?></p>
    <p><strong>Docent:</strong><br><?php echo htmlspecialchars($response['teacher_first_name']) . ' ' . htmlspecialchars($response['teacher_last_name']); ?></p>
    <p><strong>Period:</strong><br><?php echo date('d/m/Y', strtotime($response['internship_start_date'])) . ' - ' . date('d/m/Y', strtotime($response['internship_end_date'])); ?></p>

    <h2 class="section">Omgeving:</h2>
    <p><?php echo nl2br(htmlspecialchars($response['about'])); ?></p>

    <h2 class="section">Scope:</h2>
    <p><?php echo nl2br(htmlspecialchars($response['scope'])); ?></p>

    <h2 class="section">Evaluation: (Hard Skill/Soft Skill)</h2>
    <table>
        <thead>
            <tr>
                <th>Skill</th>
                <th>Grade</th>
                <th>Type</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($responseGrades as $grade) : ?>
            <tr>
                <td><?php echo htmlspecialchars($grade['skill_name']); ?></td>
                <td><?php echo htmlspecialchars($grade['grade']); ?></td>
                <td><?php echo ($grade['skill_type'] == 'hard' ? 'Hard Skill' : 'Soft Skill'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="section">Good Experiences and Points for Improvement</h2>
    <p><?php echo nl2br(htmlspecialchars($response['feedback'])); ?></p>

    <h2 class="section">Would you hire this intern based on your experience?</h2>
    <p><?php echo htmlspecialchars($response['employment']); ?></p>

    <h2 class="section">Total Score Breakdown</h2>
    <p><strong>Soft Skill:</strong> <?php echo $softSkillPercent; ?>%, <strong>Hard Skill:</strong> <?php echo $hardSkillPercent; ?>%, <strong>Total:</strong> <?php echo $totalPercent; ?>%</p>
</body>
</html>

<?php
$html = ob_get_clean(); // Get the contents of the output buffer and delete it
$pdf->loadHtml($html); // Load HTML into Dompdf
$pdf->setPaper('A4', 'portrait'); // Set paper size and orientation
$pdf->render(); // Render PDF
$pdf->stream("Rapportage.pdf", array("Attachment" => 0)); // Output PDF to browser, use "1" to force download
?>
