<?php
if (!isset($_SESSION['user_id'])) {
  header("Location: dashboard.php?page=forbidden");
  exit;
}

$toast_message = '';
$toast_type = '';

// Handle Actions (Toggle Availability / Delete Profile)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
  if ($_POST['action'] === 'toggle_availability') {
    $tutor_id      = intval($_POST['tutor_id']);
    $current_avail = intval($_POST['current_availability']);
    $new_avail     = $current_avail === 1 ? 0 : 1;

    try {
      $stmt = $pdo->prepare("UPDATE tutor_profiles SET is_available = ? WHERE tutor_id = ?");
      $stmt->execute([$new_avail, $tutor_id]);
      $toast_message = "Tutor availability status updated!";
      $toast_type = "Success";
    } catch (PDOException $e) {
      $toast_message = "Error updating availability: " . $e->getMessage();
      $toast_type = "Error";
    }
  } elseif ($_POST['action'] === 'delete') {
    $tutor_id = intval($_POST['tutor_id']);

    try {
      $stmt = $pdo->prepare("DELETE FROM tutor_profiles WHERE tutor_id = ?");
      $stmt->execute([$tutor_id]);
      $toast_message = "Tutor profile deleted successfully!";
      $toast_type = "Success";
    } catch (PDOException $e) {
      $toast_message = "Error deleting profile: " . $e->getMessage();
      $toast_type = "Error";
    }
  }
}

// Fetch Tutor Profiles joined with User details
try {
  $stmt = $pdo->query("
    SELECT 
      tp.tutor_id,
      tp.user_id,
      tp.bio,
      tp.cumulative_rating,
      tp.is_available,
      tp.updated_at,
      CONCAT(u.First_name, ' ', u.Last_name) AS tutor_name,
      u.email,
      u.department,
      u.status AS account_status
    FROM tutor_profiles tp
    INNER JOIN users u ON tp.user_id = u.user_id
    ORDER BY tp.updated_at DESC
  ");
  $tutors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Database Error: " . $e->getMessage());
}
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0 text-dark">Tutor Profiles</h1>
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
              <h3 class="card-title"><i class="fas fa-user-graduate mr-1"></i> Registered Tutors Directory</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Tutor Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Bio</th>
                    <th>Rating</th>
                    <th>Availability</th>
                    <th>Last Updated</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($tutors as $index => $t): ?>
                    <tr>
                      <td><?= $index + 1 ?></td>
                      <td>
                        <strong><?= htmlspecialchars($t['tutor_name']) ?></strong>
                      </td>
                      <td><?= htmlspecialchars($t['email']) ?></td>
                      <td><?= htmlspecialchars($t['department'] ?? 'N/A') ?></td>
                      <td>
                        <span title="<?= htmlspecialchars($t['bio'] ?? '') ?>">
                          <?= htmlspecialchars(!empty($t['bio']) ? (strlen($t['bio']) > 50 ? substr($t['bio'], 0, 50) . '...' : $t['bio']) : 'No bio provided') ?>
                        </span>
                      </td>
                      <td>
                        <span class="badge badge-warning">
                          <i class="fas fa-star mr-1"></i><?= number_format((float)$t['cumulative_rating'], 2) ?>
                        </span>
                      </td>
                      <td>
                        <?php if ((int)$t['is_available'] === 1): ?>
                          <span class="badge badge-success">Available</span>
                        <?php else: ?>
                          <span class="badge badge-secondary">Unavailable</span>
                        <?php endif; ?>
                      </td>
                      <td><?= date('M d, Y', strtotime($t['updated_at'])) ?></td>
                      <td>
                        <!-- Toggle Availability Action -->
                        <form action="" method="POST" class="d-inline">
                          <input type="hidden" name="action" value="toggle_availability">
                          <input type="hidden" name="tutor_id" value="<?= $t['tutor_id'] ?>">
                          <input type="hidden" name="current_availability" value="<?= $t['is_available'] ?>">
                          <button type="submit" class="btn <?= (int)$t['is_available'] === 1 ? 'btn-outline-warning' : 'btn-outline-success' ?> btn-sm" title="Toggle Availability">
                            <i class="fas <?= (int)$t['is_available'] === 1 ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                            <?= (int)$t['is_available'] === 1 ? 'Set Unavailable' : 'Set Available' ?>
                          </button>
                        </form>

                        <!-- Delete Profile Action -->
                        <form action="" method="POST" class="d-inline" onsubmit="return confirm('Delete this tutor profile?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="tutor_id" value="<?= $t['tutor_id'] ?>">
                          <button type="submit" class="btn btn-danger btn-sm" title="Delete Profile">
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