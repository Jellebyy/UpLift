<?php
if (!isset($_SESSION['user_id']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'student')) {
  header("Location: dashboard.php?page=forbidden");
  exit;
}

$toast_message = '';
$toast_type = '';

// Handle Delete User Action
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'delete') {
  $delete_id = intval($_POST['user_id']);
  
  // Prevent self-deletion
  if ($delete_id === $_SESSION['user_id']) {
    $toast_message = "You cannot delete your own logged-in account!";
    $toast_type = "Error";
  } else {
    try {
      $del_stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
      $del_stmt->execute([$delete_id]);
      $toast_message = "User deleted successfully!";
      $toast_type = "Success";
    } catch (PDOException $e) {
      $toast_message = "Error deleting user: " . $e->getMessage();
      $toast_type = "Error";
    }
  }
}

// Fetch all users with role names
try {
  $stmt = $pdo->query("
    SELECT 
      u.user_id, 
      u.First_name, 
      u.Last_name, 
      u.email, 
      u.department,
      u.status, 
      u.created_at,
      r.role_name AS role 
    FROM users u 
    INNER JOIN roles r ON u.role_id = r.role_id 
    ORDER BY u.created_at DESC
  ");
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
          <h1 class="m-0 text-dark">User Management</h1>
        </div>
        <div class="col-sm-6 text-right">
          <a href="dashboard.php?page=add_user" class="btn bg-navy btn-sm">
            <i class="fas fa-user-plus mr-1"></i> Add New User
          </a>
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
              <h3 class="card-title"><i class="fas fa-users mr-1"></i> User Directory</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users as $index => $u): ?>
                    <tr>
                      <td><?= $index + 1 ?></td>
                      <td><?= htmlspecialchars($u['First_name'] . ' ' . $u['Last_name']) ?></td>
                      <td><?= htmlspecialchars($u['email']) ?></td>
                      <td><?= htmlspecialchars($u['department'] ?? 'N/A') ?></td>
                      <td><span class="badge badge-info text-capitalize"><?= htmlspecialchars($u['role']) ?></span></td>
                      <td>
                        <?php if (strtolower($u['status']) === 'active'): ?>
                          <span class="badge badge-success">Active</span>
                        <?php else: ?>
                          <span class="badge badge-danger">Inactive</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <a class="btn bg-navy btn-sm" href="dashboard.php?page=update_user&id=<?= $u['user_id'] ?>">
                          <i class="fas fa-edit"></i> Edit
                        </a>
                        <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                          <form action="" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">
                              <i class="fas fa-trash"></i> Delete
                            </button>
                          </form>
                        <?php endif; ?>
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