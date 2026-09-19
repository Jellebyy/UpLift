<?php
if (!isset($_SESSION['user_id']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'student')) {
  header("Location: dashboard.php?page=forbidden");
  exit;
}

try {
  $stmt = $pdo->query("
    SELECT 
      u.user_id, 
      u.First_name, 
      u.Last_name, 
      u.email, 
      u.status, 
      r.role_name AS role 
    FROM users u 
    INNER JOIN roles r ON u.role_id = r.role_id 
    WHERE r.role_name != 'superadmin' 
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
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content-header -->

  <!-- Main content -->
  <div class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-header bg-navy">
              <h3 class="card-title">User Directory</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users as $u): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($u['First_name'] . ' ' . $u['Last_name']); ?></td>
                      <td><?php echo htmlspecialchars(explode('@', $u['email'])[0]); ?></td>
                      <td><?php echo htmlspecialchars($u['email']); ?></td>
                      <td class="text-capitalize"><?php echo htmlspecialchars($u['role']); ?></td>
                      <td>
                        <?php if (strtolower($u['status']) === 'active'): ?>
                          <span class="badge badge-success">Active</span>
                        <?php else: ?>
                          <span class="badge badge-danger">Inactive</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <a class="btn bg-navy btn-sm" href="dashboard.php?page=update_user&id=<?php echo $u['user_id']; ?>"><i class="fas fa-edit"></i> Edit</a>
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
        <!-- /.col-md-6 -->
      </div>
      <!-- /.row -->
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->