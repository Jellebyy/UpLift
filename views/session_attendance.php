<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='dashboard.php?page=forbidden';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

// Handle Log Attendance Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_attendance') {
    $session_id = $_POST['session_id'];
    $target_user_id = $_POST['user_id'];
    $status     = $_POST['status'];
    $remarks    = trim($_POST['remarks']);

    try {
        $stmtInsert = $pdo->prepare("
            INSERT INTO session_attendance (session_id, user_id, status, remarks) 
            VALUES (?, ?, ?, ?)
        ");
        $stmtInsert->execute([$session_id, $target_user_id, $status, $remarks]);
        $toast_message = "Attendance recorded successfully!";
        $toast_type = "success";
    } catch (Exception $e) {
        $toast_message = "Failed to record attendance: " . $e->getMessage();
        $toast_type = "error";
    }
}

// Fetch Active Sessions for Dropdown
if (in_array($role_id, [1, 2])) {
    $stmtSessionsSelect = $pdo->prepare("
        SELECT ts.session_id, ts.session_date, tr.subject, student.user_id AS s_id, student.First_name AS s_fname, student.Last_name AS s_lname, tutor.user_id AS t_id, tutor.First_name AS t_fname, tutor.Last_name AS t_lname
        FROM tutoring_sessions ts
        JOIN tutoring_matches tm ON ts.match_id = tm.match_id
        JOIN tutoring_requests tr ON tm.request_id = tr.request_id
        JOIN users student ON tr.student_id = student.user_id
        JOIN users tutor ON tm.tutor_id = tutor.user_id
        WHERE ts.session_status != 'Cancelled'
    ");
    $stmtSessionsSelect->execute();
} else {
    $stmtSessionsSelect = $pdo->prepare("
        SELECT ts.session_id, ts.session_date, tr.subject, student.user_id AS s_id, student.First_name AS s_fname, student.Last_name AS s_lname, tutor.user_id AS t_id, tutor.First_name AS t_fname, tutor.Last_name AS t_lname
        FROM tutoring_sessions ts
        JOIN tutoring_matches tm ON ts.match_id = tm.match_id
        JOIN tutoring_requests tr ON tm.request_id = tr.request_id
        JOIN users student ON tr.student_id = student.user_id
        JOIN users tutor ON tm.tutor_id = tutor.user_id
        WHERE ts.session_status != 'Cancelled' AND tm.tutor_id = ?
    ");
    $stmtSessionsSelect->execute([$user_id]);
}
$available_sessions = $stmtSessionsSelect->fetchAll(PDO::FETCH_ASSOC);

// Fetch Attendance Log Table
$baseQuery = "
    SELECT sa.*, ts.session_date, tr.subject, u.First_name, u.Last_name, r.role_name
    FROM session_attendance sa
    JOIN tutoring_sessions ts ON sa.session_id = ts.session_id
    JOIN tutoring_matches tm ON ts.match_id = tm.match_id
    JOIN tutoring_requests tr ON tm.request_id = tr.request_id
    JOIN users u ON sa.user_id = u.user_id
    JOIN roles r ON u.role_id = r.role_id
";

if (in_array($role_id, [1, 2])) {
    $stmtAtt = $pdo->prepare($baseQuery . " ORDER BY sa.recorded_at DESC");
    $stmtAtt->execute();
} elseif ($role_id == 4) {
    $stmtAtt = $pdo->prepare($baseQuery . " WHERE tm.tutor_id = ? ORDER BY sa.recorded_at DESC");
    $stmtAtt->execute([$user_id]);
} else {
    $stmtAtt = $pdo->prepare($baseQuery . " WHERE tr.student_id = ? ORDER BY sa.recorded_at DESC");
    $stmtAtt->execute([$user_id]);
}
$attendance_logs = $stmtAtt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6"><h1 class="m-0">Session Attendance</h1></div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if (in_array($role_id, [1, 2, 4])): ?>
            <div class="card card-navy">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-user-check mr-1"></i> Log Attendance</h3></div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="record_attendance">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-5 form-group">
                                <label>Session <span class="text-danger">*</span></label>
                                <select name="session_id" class="form-control" required>
                                    <option value="" disabled selected>-- Select Session --</option>
                                    <?php foreach ($available_sessions as $s): ?>
                                        <option value="<?php echo $s['session_id']; ?>">
                                            #<?php echo $s['session_id']; ?>: <?php echo htmlspecialchars($s['subject']); ?> (<?php echo date('M d, Y', strtotime($s['session_date'])); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label>Attendee User ID <span class="text-danger">*</span></label>
                                <input type="number" name="user_id" class="form-control" placeholder="Enter User ID" required>
                            </div>
                            <div class="col-md-2 form-group">
                                <label>Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-control" required>
                                    <option value="Present">Present</option>
                                    <option value="Late">Late</option>
                                    <option value="Absent">Absent</option>
                                </select>
                            </div>
                            <div class="col-md-2 form-group">
                                <label>Remarks</label>
                                <input type="text" name="remarks" class="form-control" placeholder="Optional notes">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-navy"><i class="fas fa-check"></i> Record Attendance</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="card card-outline card-navy">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-list mr-1"></i> Attendance Records</h3></div>
                <div class="card-body">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Log ID</th>
                                <th>Session ID</th>
                                <th>Subject</th>
                                <th>Attendee</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th>Recorded At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance_logs as $row): ?>
                                <tr>
                                    <td><?php echo $row['attendance_id']; ?></td>
                                    <td>#<?php echo $row['session_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($row['First_name'] . ' ' . $row['Last_name']); ?></td>
                                    <td><span class="badge badge-light"><?php echo htmlspecialchars($row['role_name']); ?></span></td>
                                    <td>
                                        <span class="badge badge-<?php echo $row['status'] === 'Present' ? 'success' : ($row['status'] === 'Late' ? 'warning' : 'danger'); ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['remarks'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($row['recorded_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>