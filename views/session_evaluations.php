<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='dashboard.php?page=forbidden';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

// Handle Evaluation Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_eval') {
    $session_id   = $_POST['session_id'];
    $evaluator_id = $user_id;
    $rating       = $_POST['rating'];
    $comments     = trim($_POST['comments']);

    try {
        $stmtInsert = $pdo->prepare("
            INSERT INTO session_evaluations (session_id, evaluator_id, rating, comments) 
            VALUES (?, ?, ?, ?)
        ");
        $stmtInsert->execute([$session_id, $evaluator_id, $rating, $comments]);
        $toast_message = "Thank you! Your feedback has been submitted.";
        $toast_type = "success";
    } catch (Exception $e) {
        $toast_message = "Failed to submit evaluation: " . $e->getMessage();
        $toast_type = "error";
    }
}

// Fetch Completed Sessions Ready for Student Rating
if ($role_id == 3) {
    $stmtEvalSessions = $pdo->prepare("
        SELECT ts.session_id, ts.session_date, tr.subject, tutor.First_name, tutor.Last_name 
        FROM tutoring_sessions ts
        JOIN tutoring_matches tm ON ts.match_id = tm.match_id
        JOIN tutoring_requests tr ON tm.request_id = tr.request_id
        JOIN users tutor ON tm.tutor_id = tutor.user_id
        WHERE tr.student_id = ? AND ts.session_status = 'Completed' 
        AND ts.session_id NOT IN (SELECT session_id FROM session_evaluations WHERE evaluator_id = ?)
    ");
    $stmtEvalSessions->execute([$user_id, $user_id]);
    $evaluatable_sessions = $stmtEvalSessions->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch Evaluation Feedback Logs
$baseQuery = "
    SELECT se.*, ts.session_date, tr.subject, u.First_name AS eval_fname, u.Last_name AS eval_lname
    FROM session_evaluations se
    JOIN tutoring_sessions ts ON se.session_id = ts.session_id
    JOIN tutoring_matches tm ON ts.match_id = tm.match_id
    JOIN tutoring_requests tr ON tm.request_id = tr.request_id
    JOIN users u ON se.evaluator_id = u.user_id
";

if (in_array($role_id, [1, 2])) {
    $stmtEvals = $pdo->prepare($baseQuery . " ORDER BY se.created_at DESC");
    $stmtEvals->execute();
} elseif ($role_id == 4) {
    $stmtEvals = $pdo->prepare($baseQuery . " WHERE tm.tutor_id = ? ORDER BY se.created_at DESC");
    $stmtEvals->execute([$user_id]);
} else {
    $stmtEvals = $pdo->prepare($baseQuery . " WHERE se.evaluator_id = ? ORDER BY se.created_at DESC");
    $stmtEvals->execute([$user_id]);
}
$evaluations = $stmtEvals->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6"><h1 class="m-0">Session Evaluations</h1></div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if ($role_id == 3): ?>
            <div class="card card-navy">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-star mr-1"></i> Rate a Completed Session</h3></div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="submit_eval">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8 form-group">
                                <label>Session <span class="text-danger">*</span></label>
                                <select name="session_id" class="form-control" required>
                                    <option value="" disabled selected>-- Select Session to Evaluate --</option>
                                    <?php foreach ($evaluatable_sessions as $es): ?>
                                        <option value="<?php echo $es['session_id']; ?>">
                                            Session #<?php echo $es['session_id']; ?>: <?php echo htmlspecialchars($es['subject']); ?> (Tutor: <?php echo htmlspecialchars($es['First_name'] . ' ' . $es['Last_name']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Rating (1 - 5 Stars) <span class="text-danger">*</span></label>
                                <select name="rating" class="form-control" required>
                                    <option value="5">5 - Excellent</option>
                                    <option value="4">4 - Good</option>
                                    <option value="3">3 - Satisfactory</option>
                                    <option value="2">2 - Poor</option>
                                    <option value="1">1 - Very Poor</option>
                                </select>
                            </div>
                            <div class="col-md-12 form-group">
                                <label>Feedback & Comments</label>
                                <textarea name="comments" class="form-control" rows="3" placeholder="How was your tutoring experience?"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-navy"><i class="fas fa-star"></i> Submit Evaluation</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="card card-outline card-navy">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-comments mr-1"></i> Evaluation Logs</h3></div>
                <div class="card-body">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Eval ID</th>
                                <th>Session ID</th>
                                <th>Subject</th>
                                <th>Evaluator</th>
                                <th>Rating</th>
                                <th>Comments</th>
                                <th>Submitted Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evaluations as $row): ?>
                                <tr>
                                    <td><?php echo $row['eval_id']; ?></td>
                                    <td>#<?php echo $row['session_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($row['eval_fname'] . ' ' . $row['eval_lname']); ?></td>
                                    <td>
                                        <span class="badge badge-warning">
                                            <?php echo str_repeat('★', $row['rating']); ?> (<?php echo $row['rating']; ?>/5)
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['comments'] ?? 'No comments provided'); ?></td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>