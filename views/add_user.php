<?php
if (!isset($_SESSION['user_id']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'student')) {
  header("Location: dashboard.php?page=forbidden");
  exit;
}

$toast_message = '';
$toast_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  $username   = trim($_POST['username']);
  $email      = trim($_POST['email']);
  $password   = $_POST['password'];
  $firstname  = trim($_POST['firstname']);
  $middlename = trim($_POST['middlename']);
  $lastname   = trim($_POST['lastname']);
  $role_id    = $_POST['role'];
  $status     = ucfirst(strtolower(trim($_POST['status'])));

  // Check if email already exists in 'users' table
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
  $stmt->execute([$email]);

  if ($stmt->fetchColumn() > 0) {
    $toast_message = 'Error: Email already exists!';
    $toast_type = 'Error';
  } else {
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert into `users` table adhering to uplift.sql column names
    $insert = $pdo->prepare("INSERT INTO users (role_id, First_name, Last_name, email, password, status)
      VALUES (:role_id, :firstname, :lastname, :email, :password, :status)");

    try {
      $insert->execute([
        'role_id'   => $role_id,
        'firstname' => $firstname,
        'lastname'  => $lastname,
        'email'     => $email,
        'password'  => $hashed_password,
        'status'    => $status,
      ]);

      // Automatically create a tutor profile if role is 'tutors' (role_id = 4)
      if ($role_id == 4) {
        $new_user_id = $pdo->lastInsertId();
        $tutor_stmt = $pdo->prepare("INSERT INTO tutor_profiles (user_id) VALUES (?)");
        $tutor_stmt->execute([$new_user_id]);
      }

      $toast_message = "User added Successfully!";
      $toast_type = "Success";
    } catch (PDOException $e) {
      $toast_message = "Database Error: " . $e->getMessage();
      $toast_type = "Error";
    }
  }
}

// Fetch roles from the database to dynamically populate the select dropdown
$roles = [];
try {
  $roles_stmt = $pdo->query("SELECT role_id, role_name FROM roles");
  $roles = $roles_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  // Fallback roles if query fails
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
        <div class="col-lg-6"> <!-- Widened slightly for the extra fields -->
          <div class="card card-navy">
            <div class="card-header">
              <h3 class="card-title">Add New User</h3>
            </div>
           
            <form action="" method="post" id="addUserForm">
              <div class="card-body">
               
                <div class="row">
                  <div class="col-md-6 form-group">
                    <label for="username">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="username" name="username" placeholder="Enter username" required="">
                    <small id="usernameError" class="text-danger d-none">Username is already taken.</small>
                  </div>
                 
                  <div class="col-md-6 form-group">
                    <label for="email">Email address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control form-control-sm" id="email" name="email" placeholder="Enter email" required="">
                    <small id="emailError" class="text-danger d-none">Email is already taken.</small>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-4 form-group">
                    <label for="firstname">First Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="firstname" name="firstname" required="">
                  </div>
                  <div class="col-md-4 form-group">
                    <label for="middlename">Middle Name</label>
                    <input type="text" class="form-control form-control-sm" id="middlename" name="middlename">
                  </div>
                  <div class="col-md-4 form-group">
                    <label for="lastname">Last Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="lastname" name="lastname" required="">
                  </div>
                </div>

                <div class="form-group">
                  <label for="password">Password <span class="text-danger">*</span></label>
                  <input type="password" class="form-control form-control-sm" id="password" name="password" placeholder="Password" required="" minlength="6">
                </div>

                <div class="row">
                  <div class="col-md-6 form-group">
                    <label>Role</label>
                    <select class="form-control form-control-sm" name="role" required="">
                      <?php foreach ($roles as $r): ?>
                        <option value="<?= htmlspecialchars($r['role_id']) ?>">
                          <?= htmlspecialchars(ucfirst($r['role_name'])) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6 form-group">
                    <label>Status</label>
                    <select class="form-control form-control-sm" name="status" required="">
                      <option value="Active">Active</option>
                      <option value="Inactive">Inactive</option>
                    </select>
                  </div>
                </div>

              </div>

              <div class="card-footer">
                <button type="submit" class="btn bg-navy" id="submitBtn">Submit</button>
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