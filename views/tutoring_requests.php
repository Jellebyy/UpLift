<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='dashboard.php?page=forbidden';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

// Handle Student Request Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_request') {
    $subject            = trim($_POST['subject']);
    $topic_description  = trim($_POST['topic_description']);
    $preferred_mode     = $_POST['preferred_mode'];
    $preferred_schedule = trim($_POST['preferred_schedule']);

    try {
        $stmtInsert = $pdo->prepare("
            INSERT INTO tutoring_requests (student_id, subject, topic_description, preferred_mode, preferred_schedule, status) 
            VALUES (?, ?, ?, ?, ?, 'Pending')
        ");
        $stmtInsert->execute([$user_id, $subject, $topic_description, $preferred_mode, $preferred_schedule]);
        $toast_message = "Tutoring request submitted successfully!";
        $toast_type = "success";
    } catch (Exception $e) {
        $toast_message = "Failed to submit request: " . $e->getMessage();
        $toast_type = "error";
    }
}

// Handle Admin Approval and Tutor Matching
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve_match') {
    if (in_array($role_id, [1, 2])) {
        $request_id  = $_POST['request_id'];
        $tutor_id    = $_POST['tutor_id'];
        $approved_by = $user_id;

        try {
            $pdo->beginTransaction();

            $stmtUpdate = $pdo->prepare("UPDATE tutoring_requests SET status = 'Approved' WHERE request_id = ?");
            $stmtUpdate->execute([$request_id]);

            $stmtMatch = $pdo->prepare("
                INSERT INTO tutoring_matches (request_id, tutor_id, approved_by, approval_status) 
                VALUES (?, ?, ?, 'Approved')
            ");
            $stmtMatch->execute([$request_id, $tutor_id, $approved_by]);

            $pdo->commit();
            $toast_message = "Request approved and tutor matched successfully.";
            $toast_type = "success";
        } catch (Exception $e) {
            $pdo->rollBack();
            $toast_message = "Error matching tutor: " . $e->getMessage();
            $toast_type = "error";
        }
    }
}

// Fetch Data Based on Role
if (in_array($role_id, [1, 2])) {
    // Admin View: All Requests
    $stmtRequests = $pdo->prepare("
        SELECT tr.*, u.First_name, u.Last_name 
        FROM tutoring_requests tr 
        JOIN users u ON tr.student_id = u.user_id 
        ORDER BY tr.created_at DESC
    ");
    $stmtRequests->execute();
    $requests = $stmtRequests->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Tutors for Assignment Dropdown
    $stmtTutors = $pdo->prepare("SELECT user_id, First_name, Last_name FROM users WHERE role_id = 4 AND status = 'Active'");
    $stmtTutors->execute();
    $tutors = $stmtTutors->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Student/Tutor View: Personal Requests
    $stmtRequests = $pdo->prepare("
        SELECT tr.*, u.First_name, u.Last_name 
        FROM tutoring_requests tr 
        JOIN users u ON tr.student_id = u.user_id 
        WHERE tr.student_id = ? 
        ORDER BY tr.created_at DESC
    ");
    $stmtRequests->execute([$user_id]);
    $requests = $stmtRequests->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Tutoring Requests</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <!-- Student New Request Form -->
            <?php if ($role_id == 3): ?>
            <div class="card card-navy">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-paper-plane mr-1"></i> Submit a Request</h3>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create_request">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Subject / Course <span class="text-danger">*</span></label>
                                <input type="text" name="subject" class="form-control" placeholder="e.g., IT 101 - Computer Programming" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Preferred Mode <span class="text-danger">*</span></label>
                                <select name="preferred_mode" class="form-control" required>
                                    <option value="Online">Online</option>
                                    <option value="In-Person">In-Person</option>
                                    <option value="Hybrid">Hybrid</option>
                                </select>
                            </div>
                            <div class="col-md-12 form-group">
                                <label>Preferred Schedule / Days <span class="text-danger">*</span></label>
                                <input type="text" name="preferred_schedule" class="form-control" placeholder="e.g., MWF 2:00 PM - 4:00 PM" required>
                            </div>
                            <div class="col-md-12 form-group">
                                <label>Topics / Areas Needed</label>
                                <textarea name="topic_description" class="form-control" rows="3" placeholder="Describe the specific topics you need help with..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-navy"><i class="fas fa-check-circle"></i> Submit Request</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Requests Table -->
            <div class="card card-outline card-navy">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-1"></i> Request History</h3>
                </div>
                <div class="card-body">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Subject</th>
                                <th>Mode</th>
                                <th>Schedule</th>
                                <th>Status</th>
                                <th>Submitted Date</th>
                                <?php if (in_array($role_id, [1, 2])): ?><th>Action</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $row): ?>
                                <tr>
                                    <td><?php echo $row['request_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['First_name'] . ' ' . $row['Last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($row['preferred_mode']); ?></td>
                                    <td><?php echo htmlspecialchars($row['preferred_schedule']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $row['status'] === 'Approved' ? 'success' : ($row['status'] === 'Pending' ? 'warning' : 'secondary'); ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <?php if (in_array($role_id, [1, 2])): ?>
                                    <td>
                                        <?php if ($row['status'] === 'Pending'): ?>
                                            <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#approveModal<?php echo $row['request_id']; ?>">
                                                <i class="fas fa-user-plus"></i> Match Tutor
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled>Matched</button>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>

                                <!-- Match Modal for Admin -->
                                <?php if (in_array($role_id, [1, 2])): ?>
                                <div class="modal fade" id="approveModal<?php echo $row['request_id']; ?>" tabindex="-1" role="dialog">
                                    <div class="modal-dialog">
                                        <form method="POST" action="">
                                            <div class="modal-content">
                                                <div class="modal-header bg-navy text-white">
                                                    <h5 class="modal-title">Match Request #<?php echo $row['request_id']; ?></h5>
                                                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="approve_match">
                                                    <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                                    <div class="form-group">
                                                        <label>Select Active Tutor</label>
                                                        <select name="tutor_id" class="form-control" required>
                                                            <option value="" disabled selected>-- Choose Tutor --</option>
                                                            <?php foreach ($tutors as $tutor): ?>
                                                                <option value="<?php echo $tutor['user_id']; ?>"><?php echo htmlspecialchars($tutor['First_name'] . ' ' . $tutor['Last_name']); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-success">Confirm Match</button>
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