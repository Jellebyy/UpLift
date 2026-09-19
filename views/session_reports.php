<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='dashboard.php?page=forbidden';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

// Handle Session Report Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_report') {
    $session_id       = $_POST['session_id'];
    $tutor_id         = $user_id;
    $topics_covered   = trim($_POST['topics_covered']);
    $student_progress = trim($_POST['student_progress']);

    try {
        $stmtInsert = $pdo->prepare("
            INSERT INTO session_reports (session_id, tutor_id, topics_covered, student_progress) 
            VALUES (?, ?, ?, ?)
        ");
        $stmtInsert->execute([$session_id, $tutor_id, $topics_covered, $student_progress]);
        $toast_message = "Session report submitted successfully!";
        $toast_type = "success";
    } catch (Exception $e) {
        $toast_message = "Failed to submit report: " . $e->getMessage();
        $toast_type = "error";
    }
}

// Fetch Sessions Pending Reports for Tutors
if ($role_id == 4) {
    $stmtTutorSessions = $pdo->prepare("
        SELECT ts.session_id, ts.session_date, tr.subject, student.First_name, student.Last_name 
        FROM tutoring_sessions ts
        JOIN tutoring_matches tm ON ts.match_id = tm.match_id
        JOIN tutoring_requests tr ON tm.request_id = tr.request_id
        JOIN users student ON tr.student_id = student.user_id
        WHERE tm.tutor_id = ? AND ts.session_status = 'Completed'
    ");
    $stmtTutorSessions->execute([$user_id]);
    $completed_sessions = $stmtTutorSessions->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch Reports
$baseQuery = "
    SELECT sr.*, ts.session_date, tr.subject, tutor.First_name AS t_fname, tutor.Last_name AS t_lname
    FROM session_reports sr
    JOIN tutoring_sessions ts ON sr.session_id = ts.session_id
    JOIN tutoring_matches tm ON ts.match_id = tm.match_id
    JOIN tutoring_requests tr ON tm.request_id = tr.request_id
    JOIN users tutor ON sr.tutor_id = tutor.user_id
";

if (in_array($role_id, [1, 2])) {
    $stmtReports = $pdo->prepare($baseQuery . " ORDER BY sr.submitted_at DESC");
    $stmtReports->execute();
} elseif ($role_id == 4) {
    $stmtReports = $pdo->prepare($baseQuery . " WHERE sr.tutor_id = ? ORDER BY sr.submitted_at DESC");
    $stmtReports->execute([$user_id]);
} else {
    $stmtReports = $pdo->prepare($baseQuery . " WHERE tr.student_id = ? ORDER BY sr.submitted_at DESC");
    $stmtReports->execute([$user_id]);
}
$reports = $stmtReports->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6"><h1 class="m-0">Session Reports</h1></div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if ($role_id == 4): ?>
            <div class="card card-navy">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-file-alt mr-1"></i> Submit Progress Report</h3></div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="submit_report">
                    <div class="card-body">
                        <div class="form-group">
                            <label>Completed Session <span class="text-danger">*</span></label>
                            <select name="session_id" class="form-control" required>
                                <option value="" disabled selected>-- Select Completed Session --</option>
                                <?php foreach ($completed_sessions as $cs): ?>
                                    <option value="<?php echo $cs['session_id']; ?>">
                                        Session #<?php echo $cs['session_id']; ?> - <?php echo htmlspecialchars($cs['subject']); ?> (Student: <?php echo htmlspecialchars($cs['First_name'] . ' ' . $cs['Last_name']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Topics Covered <span class="text-danger">*</span></label>
                            <textarea name="topics_covered" class="form-control" rows="3" placeholder="List key concepts discussed..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Student Progress & Notes <span class="text-danger">*</span></label>
                            <textarea name="student_progress" class="form-control" rows="3" placeholder="Describe student understanding and areas for improvement..." required></textarea>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-navy"><i class="fas fa-paper-plane"></i> Submit Report</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="card card-outline card-navy">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-folder-open mr-1"></i> Report Logs</h3></div>
                <div class="card-body">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Report ID</th>
                                <th>Session ID</th>
                                <th>Subject</th>
                                <th>Tutor</th>
                                <th>Topics Covered</th>
                                <th>Student Progress</th>
                                <th>Submitted At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $row): ?>
                                <tr>
                                    <td><?php echo $row['report_id']; ?></td>
                                    <td>#<?php echo $row['session_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($row['t_fname'] . ' ' . $row['t_lname']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($row['topics_covered'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($row['student_progress'])); ?></td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($row['submitted_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>