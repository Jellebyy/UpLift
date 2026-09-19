<?php
if (!isset($_SESSION['user_id']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'student')) {
  header("Location: dashboard.php?page=forbidden");
  exit;
}

$toast_message = '';
$toast_type = '';

// Get user ID from URL query string
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
  header("Location: dashboard.php?page=user");
  exit;
}

// Handle Form Update Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

  $email      = trim($_POST['email']);
  $firstname  = trim($_POST['firstname']);
  $lastname   = trim($_POST['lastname']);
  $role_id    = intval($_POST['role']);
  $status     = ucfirst(strtolower(trim($_POST['status'])));
  $password   = $_POST['password'];

  // Check if email already belongs to another user
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
  $stmt->execute([$email, $user_id]);

  if ($stmt->fetchColumn() > 0) {
    $toast_message = 'Error: Email is already in use by another account!';
    $toast_type = 'Error';
  } else {
    try {
      // Update password if a new one was entered
      if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET First_name = :firstname, Last_name = :lastname, email = :email, role_id = :role_id, status = :status, password = :password WHERE user_id = :user_id");
        $update->execute([
          'firstname' => $firstname,
          'lastname'  => $lastname,
          'email'     => $email,
          'role_id'   => $role_id,
          'status'    => $status,
          'password'  => $hashed_password,
          'user_id'   => $user_id
        ]);
      } else {
        // Update without altering password
        $update = $pdo->prepare("UPDATE users SET First_name = :firstname, Last_name = :lastname, email = :email, role_id = :role_id, status = :status WHERE user_id = :user_id");
        $update->execute([
          'firstname' => $firstname,
          'lastname'  => $lastname,
          'email'     => $email,
          'role_id'   => $role_id,
          'status'    => $status,
          'user_id'   => $user_id
        ]);
      }

      // Automatically create a tutor profile if role is changed to Tutors (role_id = 4)
      if ($role_id == 4) {
        $tutor_check = $pdo->prepare("SELECT COUNT(*) FROM tutor_profiles WHERE user_id = ?");
        $tutor_check->execute([$user_id]);
        if ($tutor_check->fetchColumn() == 0) {
          $tutor_stmt = $pdo->prepare("INSERT INTO tutor_profiles (user_id) VALUES (?)");
          $tutor_stmt->execute([$user_id]);
        }
      }

      $toast_message = "User updated successfully!";
      $toast_type = "Success";
    } catch (PDOException $e) {
      $toast_message = "Database Error: " . $e->getMessage();
      $toast_type = "Error";
    }
  }
}

// Fetch target user's details
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  header("Location: dashboard.php?page=user");
  exit;
}

// Fetch dynamic roles
$roles = [];
try {
  $roles_stmt = $pdo->query("SELECT role_id, role_name FROM roles");
  $roles = $roles_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $roles = [
    ['role_id' => 1, 'role_name' => 'superadmin'],
    ['role_id' => 2, 'role_name' => 'admin'],
    ['role_id' => 3, 'role_name' => 'student'],
    ['role_id' => 4, 'role_name' => 'tutors']
  ];
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
        <div class="col-lg-6">
          
          <?php if (!empty($toast_message)): ?>
            <div class="alert alert-<?= $toast_type === 'Success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
              <?= htmlspecialchars($toast_message) ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          <?php endif; ?>

          <div class="card card-navy">
            <div class="card-header">
              <h3 class="card-title">Edit User Details</h3>
            </div>
           
            <form action="" method="post" id="editUserForm">
              <div class="card-body">
               
                <div class="row">
                  <div class="col-md-6 form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control form-control-sm" id="username" name="username" value="<?= htmlspecialchars(explode('@', $user['email'])[0]) ?>" readonly>
                  </div>
                 
                  <div class="col-md-6 form-group">
                    <label for="email">Email address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control form-control-sm" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6 form-group">
                    <label for="firstname">First Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="firstname" name="firstname" value="<?= htmlspecialchars($user['First_name']) ?>" required>
                  </div>
                  <div class="col-md-6 form-group">
                    <label for="lastname">Last Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="lastname" name="lastname" value="<?= htmlspecialchars($user['Last_name']) ?>" required>
                  </div>
                </div>

                <div class="form-group">
                  <label for="password">Password <small class="text-muted">(Leave blank to keep current password)</small></label>
                  <input type="password" class="form-control form-control-sm" id="password" name="password" placeholder="Enter new password" minlength="6">
                </div>

                <div class="row">
                  <div class="col-md-6 form-group">
                    <label>Role</label>
                    <select class="form-control form-control-sm" name="role" required>
                      <?php foreach ($roles as $r): ?>
                        <option value="<?= htmlspecialchars($r['role_id']) ?>" <?= $r['role_id'] == $user['role_id'] ? 'selected' : '' ?>>
                          <?= htmlspecialchars(ucfirst($r['role_name'])) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6 form-group">
                    <label>Status</label>
                    <select class="form-control form-control-sm" name="status" required>
                      <option value="Active" <?= strtolower($user['status']) === 'active' ? 'selected' : '' ?>>Active</option>
                      <option value="Inactive" <?= strtolower($user['status']) === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                  </div>
                </div>

              </div>

              <div class="card-footer">
                <button type="submit" class="btn bg-navy" id="submitBtn">Update User</button>
                <a href="dashboard.php?page=user" class="btn btn-secondary float-right">Cancel</a>
              </div>
            </form>
          </div>
        </div>
      </div>
      <!-- /.row -->
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->