<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='dashboard.php?page=forbidden';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

// Handle New Session Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_session') {
    $match_id         = $_POST['match_id'];
    $session_date     = $_POST['session_date'];
    $start_time       = $_POST['start_time'];
    $end_time         = $_POST['end_time'];
    $mode             = $_POST['mode'];
    $location_or_link = trim($_POST['location_or_link']);

    try {
        $stmtInsert = $pdo->prepare("
            INSERT INTO tutoring_sessions (match_id, session_date, start_time, end_time, mode, location_or_link, session_status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Scheduled')
        ");
        $stmtInsert->execute([$match_id, $session_date, $start_time, $end_time, $mode, $location_or_link]);
        $toast_message = "Tutoring session successfully scheduled!";
        $toast_type = "success";
    } catch (Exception $e) {
        $toast_message = "Failed to schedule session: " . $e->getMessage();
        $toast_type = "error";
    }
}

// Handle Status Updates (Completed / Cancelled)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $session_id     = $_POST['session_id'];
    $session_status = $_POST['session_status'];

    try {
        $stmtUpdate = $pdo->prepare("UPDATE tutoring_sessions SET session_status = ? WHERE session_id = ?");
        $stmtUpdate->execute([$session_status, $session_id]);
        $toast_message = "Session status updated to " . htmlspecialchars($session_status) . ".";
        $toast_type = "success";
    } catch (Exception $e) {
        $toast_message = "Failed to update status: " . $e->getMessage();
        $toast_type = "error";
    }
}

// Pre-selected Match ID from GET parameter if passed from tutoring_matches.php
$selected_match_id = $_GET['match_id'] ?? null;

// Fetch Available Matches for Dropdown Schedule Form
if (in_array($role_id, [1, 2])) {
    $stmtMatches = $pdo->prepare("
        SELECT tm.match_id, tr.subject, student.First_name AS s_fname, student.Last_name AS s_lname, tutor.First_name AS t_fname, tutor.Last_name AS t_lname 
        FROM tutoring_matches tm
        JOIN tutoring_requests tr ON tm.request_id = tr.request_id
        JOIN users student ON tr.student_id = student.user_id
        JOIN users tutor ON tm.tutor_id = tutor.user_id
        WHERE tm.approval_status = 'Approved'
    ");
    $stmtMatches->execute();
} else {
    $stmtMatches = $pdo->prepare("
        SELECT tm.match_id, tr.subject, student.First_name AS s_fname, student.Last_name AS s_lname, tutor.First_name AS t_fname, tutor.Last_name AS t_lname 
        FROM tutoring_matches tm
        JOIN tutoring_requests tr ON tm.request_id = tr.request_id
        JOIN users student ON tr.student_id = student.user_id
        JOIN users tutor ON tm.tutor_id = tutor.user_id
        WHERE tm.approval_status = 'Approved' AND tm.tutor_id = ?
    ");
    $stmtMatches->execute([$user_id]);
}
$available_matches = $stmtMatches->fetchAll(PDO::FETCH_ASSOC);

// Fetch Tutoring Sessions Table Data
$baseQuery = "
    SELECT ts.*, tr.subject, 
           student.First_name AS student_fname, student.Last_name AS student_lname,
           tutor.First_name AS tutor_fname, tutor.Last_name AS tutor_lname
    FROM tutoring_sessions ts
    JOIN tutoring_matches tm ON ts.match_id = tm.match_id
    JOIN tutoring_requests tr ON tm.request_id = tr.request_id
    JOIN users student ON tr.student_id = student.user_id
    JOIN users tutor ON tm.tutor_id = tutor.user_id
";

if (in_array($role_id, [1, 2])) {
    $stmtSessions = $pdo->prepare($baseQuery . " ORDER BY ts.session_date DESC, ts.start_time DESC");
    $stmtSessions->execute();
} elseif ($role_id == 4) {
    $stmtSessions = $pdo->prepare($baseQuery . " WHERE tm.tutor_id = ? ORDER BY ts.session_date DESC, ts.start_time DESC");
    $stmtSessions->execute([$user_id]);
} else {
    $stmtSessions = $pdo->prepare($baseQuery . " WHERE tr.student_id = ? ORDER BY ts.session_date DESC, ts.start_time DESC");
    $stmtSessions->execute([$user_id]);
}
$sessions = $stmtSessions->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Tutoring Sessions</h1>
                </div>
                <?php if (in_array($role_id, [1, 2, 4])): ?>
                <div class="col-sm-6 text-right">
                    <button class="btn btn-navy" data-toggle="modal" data-target="#scheduleModal">
                        <i class="fas fa-calendar-plus mr-1"></i> Schedule New Session
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <!-- Sessions Table -->
            <div class="card card-outline card-navy">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-alt mr-1"></i> Scheduled Sessions</h3>
                </div>
                <div class="card-body">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Session ID</th>
                                <th>Subject</th>
                                <th>Student</th>
                                <th>Tutor</th>
                                <th>Date & Time</th>
                                <th>Mode</th>
                                <th>Location / Link</th>
                                <th>Status</th>
                                <?php if (in_array($role_id, [1, 2, 4])): ?>
                                <th>Action</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $row): ?>
                                <tr>
                                    <td><?php echo $row['session_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($row['student_fname'] . ' ' . $row['student_lname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['tutor_fname'] . ' ' . $row['tutor_lname']); ?></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($row['session_date'])); ?><br>
                                        <small class="text-muted">
                                            <?php echo date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?>
                                        </small>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['mode']); ?></td>
                                    <td>
                                        <?php if (filter_var($row['location_or_link'], FILTER_VALIDATE_URL)): ?>
                                            <a href="<?php echo htmlspecialchars($row['location_or_link']); ?>" target="_blank" class="btn btn-xs btn-outline-primary">
                                                <i class="fas fa-video"></i> Join Link
                                            </a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($row['location_or_link']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $row['session_status'] === 'Completed' ? 'success' : ($row['session_status'] === 'Cancelled' ? 'danger' : 'info'); 
                                        ?>">
                                            <?php echo htmlspecialchars($row['session_status']); ?>
                                        </span>
                                    </td>
                                    <?php if (in_array($role_id, [1, 2, 4])): ?>
                                    <td>
                                        <button class="btn btn-sm btn-navy" data-toggle="modal" data-target="#updateModal<?php echo $row['session_id']; ?>">
                                            <i class="fas fa-edit"></i> Status
                                        </button>
                                    </td>
                                    <?php endif; ?>
                                </tr>

                                <!-- Status Update Modal -->
                                <?php if (in_array($role_id, [1, 2, 4])): ?>
                                <div class="modal fade" id="updateModal<?php echo $row['session_id']; ?>" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-sm">
                                        <form method="POST" action="">
                                            <div class="modal-content">
                                                <div class="modal-header bg-navy text-white">
                                                    <h5 class="modal-title">Update Status</h5>
                                                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="session_id" value="<?php echo $row['session_id']; ?>">
                                                    <div class="form-group">
                                                        <label>Session Status</label>
                                                        <select name="session_status" class="form-control" required>
                                                            <option value="Scheduled" <?php echo $row['session_status'] === 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                                            <option value="Completed" <?php echo $row['session_status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                                            <option value="Cancelled" <?php echo $row['session_status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-navy">Save Changes</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Session Modal -->
<?php if (in_array($role_id, [1, 2, 4])): ?>
<div class="modal fade" id="scheduleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="">
            <div class="modal-content">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title"><i class="fas fa-calendar-plus mr-1"></i> Schedule Tutoring Session</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_session">
                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label>Select Matched Request <span class="text-danger">*</span></label>
                            <select name="match_id" class="form-control" required>
                                <option value="" disabled <?php echo !$selected_match_id ? 'selected' : ''; ?>>-- Choose Match Pair --</option>
                                <?php foreach ($available_matches as $m): ?>
                                    <option value="<?php echo $m['match_id']; ?>" <?php echo $selected_match_id == $m['match_id'] ? 'selected' : ''; ?>>
                                        Match #<?php echo $m['match_id']; ?>: <?php echo htmlspecialchars($m['subject']); ?> (Student: <?php echo htmlspecialchars($m['s_fname'] . ' ' . $m['s_lname']); ?> | Tutor: <?php echo htmlspecialchars($m['t_fname'] . ' ' . $m['t_lname']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Session Date <span class="text-danger">*</span></label>
                            <input type="date" name="session_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Session Mode <span class="text-danger">*</span></label>
                            <select name="mode" class="form-control" required>
                                <option value="Online">Online</option>
                                <option value="In-Person">In-Person</option>
                            </select>
                        </div>
                        <div class="col-md-8 form-group">
                            <label>Location or Online Link <span class="text-danger">*</span></label>
                            <input type="text" name="location_or_link" class="form-control" placeholder="e.g., Room 302 / https://zoom.us/j/123456" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-navy"><i class="fas fa-save"></i> Schedule Session</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>