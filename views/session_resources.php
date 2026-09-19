<?php
if (!isset($_SESSION['user_id'])) {
  header("Location: dashboard.php?page=forbidden");
  exit;
}

$toast_message = '';
$toast_type = '';

// Upload directory configuration
$upload_dir = 'uploads/resources/';
if (!is_dir($upload_dir)) {
  mkdir($upload_dir, 0777, true);
}

// Handle Resource Actions (Upload / Delete)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
  if ($_POST['action'] === 'add') {
    $session_id  = intval($_POST['session_id']);
    $uploaded_by = $_SESSION['user_id'];
    $file_name   = trim($_POST['file_name']);

    if (!empty($session_id) && isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === UPLOAD_ERR_OK) {
      $tmp_name   = $_FILES['resource_file']['tmp_name'];
      $orig_name  = basename($_FILES['resource_file']['name']);
      $ext        = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
      
      // Generate unique file path
      $unique_file_name = uniqid('res_') . '.' . $ext;
      $target_path      = $upload_dir . $unique_file_name;

      // Fallback file title to original filename if blank
      if (empty($file_name)) {
        $file_name = $orig_name;
      }

      if (move_uploaded_file($tmp_name, $target_path)) {
        try {
          $stmt = $pdo->prepare("INSERT INTO session_resources (session_id, uploaded_by, file_name, file_path) VALUES (?, ?, ?, ?)");
          $stmt->execute([$session_id, $uploaded_by, $file_name, $target_path]);
          $toast_message = "Resource uploaded successfully!";
          $toast_type = "Success";
        } catch (PDOException $e) {
          $toast_message = "Database Error: " . $e->getMessage();
          $toast_type = "Error";
        }
      } else {
        $toast_message = "Failed to upload file to the server destination.";
        $toast_type = "Error";
      }
    } else {
      $toast_message = "Please select a valid file and target session.";
      $toast_type = "Error";
    }
  } elseif ($_POST['action'] === 'delete') {
    $resource_id = intval($_POST['resource_id']);

    try {
      // Retrieve file path to remove physical file
      $path_stmt = $pdo->prepare("SELECT file_path FROM session_resources WHERE resource_id = ?");
      $path_stmt->execute([$resource_id]);
      $res = $path_stmt->fetch(PDO::FETCH_ASSOC);

      if ($res && file_exists($res['file_path'])) {
        unlink($res['file_path']);
      }

      $stmt = $pdo->prepare("DELETE FROM session_resources WHERE resource_id = ?");
      $stmt->execute([$resource_id]);
      $toast_message = "Resource deleted successfully!";
      $toast_type = "Success";
    } catch (PDOException $e) {
      $toast_message = "Error deleting resource: " . $e->getMessage();
      $toast_type = "Error";
    }
  }
}

// Fetch resources joined with Session, Subject, and Uploader details
try {
  $stmt = $pdo->query("
    SELECT 
      sr.resource_id,
      sr.session_id,
      sr.file_name,
      sr.file_path,
      sr.uploaded_at,
      CONCAT(u.First_name, ' ', u.Last_name) AS uploader_name,
      tr.subject,
      ts.session_date
    FROM session_resources sr
    INNER JOIN users u ON sr.uploaded_by = u.user_id
    INNER JOIN tutoring_sessions ts ON sr.session_id = ts.session_id
    INNER JOIN tutoring_matches tm ON ts.match_id = tm.match_id
    INNER JOIN tutoring_requests tr ON tm.request_id = tr.request_id
    ORDER BY sr.uploaded_at DESC
  ");
  $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Database Error: " . $e->getMessage());
}

// Fetch available sessions for select dropdown
try {
  $sessions_stmt = $pdo->query("
    SELECT 
      ts.session_id,
      ts.session_date,
      tr.subject,
      CONCAT(u_student.First_name, ' ', u_student.Last_name) AS student_name
    FROM tutoring_sessions ts
    INNER JOIN tutoring_matches tm ON ts.match_id = tm.match_id
    INNER JOIN tutoring_requests tr ON tm.request_id = tr.request_id
    INNER JOIN users u_student ON tr.student_id = u_student.user_id
    ORDER BY ts.session_date DESC
  ");
  $active_sessions = $sessions_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $active_sessions = [];
}
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0 text-dark">Session Resources</h1>
        </div>
        <div class="col-sm-6 text-right">
          <button type="button" class="btn bg-navy btn-sm" data-toggle="modal" data-target="#uploadResourceModal">
            <i class="fas fa-file-upload mr-1"></i> Upload Resource
          </button>
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content-header -->

  <!-- Main content -->
  <div class="content">
    <div class="container-fluid">

      <?php if (!empty($toast_message)): ?>
        <div class="alert alert-<?= $toast_type === 'Success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($toast_message) ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      <?php endif; ?>

      <div class="row">
        <div class="col-lg-12">
          <div class="card card-navy card-outline">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-folder-open mr-1"></i> Shared Learning Materials</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>File Name</th>
                    <th>Associated Subject</th>
                    <th>Session Date</th>
                    <th>Uploaded By</th>
                    <th>Upload Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($resources as $index => $r): ?>
                    <tr>
                      <td><?= $index + 1 ?></td>
                      <td>
                        <i class="far fa-file-alt text-primary mr-1"></i>
                        <strong><?= htmlspecialchars($r['file_name']) ?></strong>
                      </td>
                      <td><span class="badge badge-info"><?= htmlspecialchars($r['subject']) ?></span></td>
                      <td><?= date('M d, Y', strtotime($r['session_date'])) ?></td>
                      <td><?= htmlspecialchars($r['uploader_name']) ?></td>
                      <td><?= date('M d, Y h:i A', strtotime($r['uploaded_at'])) ?></td>
                      <td>
                        <!-- Download Link -->
                        <a href="<?= htmlspecialchars($r['file_path']) ?>" download class="btn btn-outline-success btn-sm" title="Download Resource">
                          <i class="fas fa-download"></i> Download
                        </a>

                        <!-- Delete Resource Form -->
                        <form action="" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this resource?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="resource_id" value="<?= $r['resource_id'] ?>">
                          <button type="submit" class="btn btn-danger btn-sm" title="Delete Resource">
                            <i class="fas fa-trash"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <!-- /.card-body -->
          </div>
          <!-- /.card -->
        </div>
      </div>
      <!-- /.row -->
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<!-- Upload Resource Modal -->
<div class="modal fade" id="uploadResourceModal" tabindex="-1" role="dialog" aria-labelledby="uploadResourceModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-navy text-white">
        <h5 class="modal-title" id="uploadResourceModalLabel"><i class="fas fa-file-upload mr-1"></i> Upload Session Resource</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="" method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" name="action" value="add">

          <div class="form-group">
            <label for="session_id">Target Session <span class="text-danger">*</span></label>
            <select name="session_id" id="session_id" class="form-control form-control-sm" required>
              <option value="">-- Select Session --</option>
              <?php foreach ($active_sessions as $s): ?>
                <option value="<?= $s['session_id'] ?>">
                  <?= htmlspecialchars($s['subject']) ?> (<?= htmlspecialchars($s['student_name']) ?> - <?= date('M d, Y', strtotime($s['session_date'])) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="file_name">Resource Display Name</label>
            <input type="text" name="file_name" id="file_name" class="form-control form-control-sm" placeholder="e.g. Chapter 1 Lecture Notes">
          </div>

          <div class="form-group">
            <label for="resource_file">File Upload <span class="text-danger">*</span></label>
            <input type="file" name="resource_file" id="resource_file" class="form-control-file border p-1 rounded" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
          <button type="submit" class="btn bg-navy btn-sm">Upload Material</button>
        </div>
      </form>
    </div>
  </div>
</div>