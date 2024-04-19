<!DOCTYPE html>
<html lang="nl">
<?php include_once "../db_connect.php"; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stageformulier Bewerken</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .top {
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
            background-color: red;
            color: white;
            text-align: center;
        }
    </style>
</head>

<body>
    <?php
    session_start();
    if (!isset($_SESSION['id'])) {
        header('Location: login.php'); // Redirect to login page if not logged in
        exit();
    }

    $id = $_SESSION['id'];
    $conn = connectDB();
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch main internship details
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
    $query->bind_param("i", $id);
    $query->execute();
    $response = $query->get_result()->fetch_assoc();

    // Fetch grades
    $queryGrades = $conn->prepare("
        SELECT
            sk.id AS skill_id,
            sk.name AS skill_name,
            sk.type AS type,
            ss.grade AS grade
        FROM skills sk
        JOIN skill_student ss ON sk.id = ss.skill_id
        WHERE ss.student_id = ?
    ");
    $queryGrades->bind_param("i", $id);
    $queryGrades->execute();
    $resultGrades = $queryGrades->get_result();
    $responseGrades = $resultGrades->fetch_all(MYSQLI_ASSOC);

    $total = 0;
    $gradeHard = 0;
    $gradeSoft = 0;
    $intHard = 0;
    $intSoft = 0;

    foreach ($responseGrades as $grade) {
        $total += $grade['grade'];
        if ($grade['type'] == 'hard') {
            $gradeHard += $grade['grade'];
            $intHard++;
        } else {
            $gradeSoft += $grade['grade'];
            $intSoft++;
        }
    }
    $totalPercent = $intHard + $intSoft > 0 ? round($total / (10 * ($intHard + $intSoft)) * 100, 2) : 0;
    $gradeHardPercent = $intHard > 0 ? round($gradeHard / (10 * $intHard) * 100, 2) : 0;
    $gradeSoftPercent = $intSoft > 0 ? round($gradeSoft / (10 * $intSoft) * 100, 2) : 0;
    ?>

    <div class="container">
        <div class="row">
                <div class="col-md-12">
                    <nav class="navbar bg-body-tertiary">
                        <button class="btn btn-outline-danger"><a href="student.php" class="nav-link">Terug</a></button>
                        <button class="btn btn-outline-danger" id="export-student-btn" onclick="exportStudent()">export</button>
                    </nav>
                </div>
            </div>
        <form method="post" action="update_data.php">
            <div class="mb-3">
                <p><?php echo htmlspecialchars($response['contact_person_first_name'] . ' ' . $response['contact_person_last_name']); ?></p>
                <p><?php echo htmlspecialchars($response['contact_person_email']); ?></p>
                <p><?php echo htmlspecialchars($response['contact_person_phone']); ?></p>
            </div>
            <div class="mb-3">
                <label>Docent</label>
                <p><?php echo htmlspecialchars($response['teacher_first_name'] . ' ' . $response['teacher_last_name']); ?></p>
            </div>
            <div class="mb-3">
                <label>Stageperiode</label>
                <p><?php echo htmlspecialchars($response['internship_start_date'] . ' tot ' . $response['internship_end_date']); ?></p>
            </div>
            <div class="mb-3">
                <label>Bedrijf</label>
                <p><?php echo htmlspecialchars($response['company_name']); ?></p>
            </div>
            <div class="mb-3">
                <label>Over</label>
                <textarea class="form-control" name="about"><?php echo htmlspecialchars($response['about']); ?></textarea>
            </div>
            <div class="mb-3">
                <label>Omvang</label>
                <textarea class="form-control" name="scope"><?php echo htmlspecialchars($response['scope']); ?></textarea>
            </div>
            <div class="mb-3">
                <label>Evaluatie (Hard/Soft Skills)</label>
                <table class="table">
                    <?php foreach ($responseGrades as $grade) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($grade['skill_name']) . " (" . $grade['type'] . ")"; ?></td>
                        <td>
                            <input type="number" class="form-control" name="grades[<?php echo $grade['skill_id']; ?>]" value="<?php echo $grade['grade']; ?>" min="0" max="10">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <div class="mb-3">
                <label>Goede ervaringen en werkpunten: te bepalen door stagementor</label>
                <textarea class="form-control" name="feedback"><?php echo htmlspecialchars($response['feedback']); ?></textarea>
            </div>
            <div class="mb-3">
                <label>Zou u deze stagair op basis van uw ervaringen aannemen in uw bedrijf</label>
                <textarea class="form-control" name="employment"><?php echo htmlspecialchars($response['employment']); ?></textarea>
            </div>
            <div class="mb-3">
                <p><b>Totaal: <?php echo $totalPercent ?>%</b> (verandert niet tot deze gegevens zijn opgeslagen)</p>
                <p>Softskill: <?php echo $gradeSoftPercent ?>%, Hardskill: <?php echo $gradeHardPercent ?>%</p>
                <button type="submit" class="btn btn-primary">Wijzigingen Opslaan</button>
            </div>
        </form>
    </div>

    <script>
        function exportStudent() {
            window.open("export_student.php", '_blank');
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
