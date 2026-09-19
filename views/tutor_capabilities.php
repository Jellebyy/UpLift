<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='dashboard.php?page=forbidden';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

// Handle Adding Subject Capability
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_capability') {
    $tutor_id          = (in_array($role_id, [1, 2]) && isset($_POST['tutor_id'])) ? $_POST['tutor_id'] : $user_id;
    $subject_code      = trim($_POST['subject_code']);
    $subject_name      = trim($_POST['subject_name']);
    $proficiency_level = $_POST['proficiency_level'];

    try {
        $stmtInsert = $pdo->prepare("
            INSERT INTO tutor_capabilities (tutor_id, subject_code, subject_name, proficiency_level) 
            VALUES (?, ?, ?, ?)
        ");
        $stmtInsert->execute([$tutor_id, $subject_code, $subject_name, $proficiency_level]);
        $toast_message = "Subject capability added successfully!";
        $toast_type = "success";
    } catch (Exception $e) {
        $toast_message = "Failed to add capability: " . $e->getMessage();
        $toast_type = "error";
    }
}

// Handle Deleting Capability
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        if (in_array($role_id, [1, 2])) {
            $stmtDelete = $pdo->prepare("DELETE FROM tutor_capabilities WHERE capability_id = ?");
            $stmtDelete->execute([$delete_id]);
        } else {
            $stmtDelete = $pdo->prepare("DELETE FROM tutor_capabilities WHERE capability_id = ? AND tutor_id = ?");
            $stmtDelete->execute([$delete_id, $user_id]);
        }
        $toast_message = "Subject capability removed.";
        $toast_type = "success";
    } catch (Exception $e) {
        $toast_message = "Error removing capability: " . $e->getMessage();
        $toast_type = "error";
    }
}

// Fetch Capability Data
if (in_array($role_id, [1, 2])) {
    $stmtCap = $pdo->prepare("
        SELECT tc.*, u.First_name, u.Last_name 
        FROM tutor_capabilities tc 
        JOIN users u ON tc.tutor_id = u.user_id 
        ORDER BY tc.subject_code ASC
    ");
    $stmtCap->execute();

    $stmtTutors = $pdo->prepare("SELECT user_id, First_name, Last_name FROM users WHERE role_id = 4 AND status = 'Active'");
    $stmtTutors->execute();
    $tutors = $stmtTutors->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmtCap = $pdo->prepare("
        SELECT tc.*, u.First_name, u.Last_name 
        FROM tutor_capabilities tc 
        JOIN users u ON tc.tutor_id = u.user_id 
        WHERE tc.tutor_id = ? 
        ORDER BY tc.subject_code ASC
    ");
    $stmtCap->execute([$user_id]);
}
$capabilities = $stmtCap->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Tutor Capabilities</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if (in_array($role_id, [1, 2, 4])): ?>
            <div class="card card-navy">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-book-reader mr-1"></i> Add Subject Proficiency</h3>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_capability">
                    <div class="card-body">
                        <div class="row">
                            <?php if (in_array($role_id, [1, 2])): ?>
                            <div class="col-md-3 form-group">
                                <label>Tutor <span class="text-danger">*</span></label>
                                <select name="tutor_id" class="form-control" required>
                                    <option value="" disabled selected>-- Select Tutor --</option>
                                    <?php foreach ($tutors as $t): ?>
                                        <option value="<?php echo $t['user_id']; ?>"><?php echo htmlspecialchars($t['First_name'] . ' ' . $t['Last_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-<?php echo in_array($role_id, [1, 2]) ? '3' : '4'; ?> form-group">
                                <label>Subject Code <span class="text-danger">*</span></label>
                                <input type="text" name="subject_code" class="form-control" placeholder="e.g., MATH101" required>
                            </div>
                            <div class="col-md-<?php echo in_array($role_id, [1, 2]) ? '3' : '4'; ?> form-group">
                                <label>Subject Title <span class="text-danger">*</span></label>
                                <input type="text" name="subject_name" class="form-control" placeholder="e.g., College Algebra" required>
                            </div>
                            <div class="col-md-<?php echo in_array($role_id, [1, 2]) ? '3' : '4'; ?> form-group">
                                <label>Proficiency Level <span class="text-danger">*</span></label>
                                <select name="proficiency_level" class="form-control" required>
                                    <option value="Beginner">Beginner</option>
                                    <option value="Intermediate" selected>Intermediate</option>
                                    <option value="Advanced">Advanced</option>
                                    <option value="Expert">Expert</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-navy"><i class="fas fa-plus"></i> Add Capability</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="card card-outline card-navy">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-graduation-cap mr-1"></i> Subject Competencies</h3>
                </div>
                <div class="card-body">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tutor Name</th>
                                <th>Subject Code</th>
                                <th>Subject Title</th>
                                <th>Proficiency</th>
                                <?php if (in_array($role_id, [1, 2, 4])): ?><th>Action</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($capabilities as $row): ?>
                                <tr>
                                    <td><?php echo $row['capability_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['First_name'] . ' ' . $row['Last_name']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['subject_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $row['proficiency_level'] === 'Expert' ? 'success' : ($row['proficiency_level'] === 'Advanced' ? 'primary' : 'secondary'); 
                                        ?>">
                                            <?php echo htmlspecialchars($row['proficiency_level']); ?>
                                        </span>
                                    </td>
                                    <?php if (in_array($role_id, [1, 2, 4])): ?>
                                    <td>
                                        <a href="dashboard.php?page=tutor_capabilities&delete_id=<?php echo $row['capability_id']; ?>" 
                                           class="btn btn-sm btn-danger" onclick="return confirm('Remove this subject capability?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>