<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
  header("Location: dashboard.php?page=dashboard");
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login_id) || empty($password)) {
        $error = "Please enter your Email or First Name and password.";
    } else {
        // Query database for matching email or First_name
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE (`email` = :login_id OR `First_name` = :login_id) AND `status` = 'Active' LIMIT 1");
        $stmt->execute([':login_id' => $login_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $authenticated = false;

            // Check if stored password is standard bcrypt hash
            if (substr($user['password'], 0, 4) === '$2y$') {
                $authenticated = password_verify($password, $user['password']);
            } else {
                // Check plain text match
                if ($password === $user['password']) {
                    $authenticated = true;

                    // Auto-convert plain text to secure hash in DB on login
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $pdo->prepare("UPDATE `users` SET `password` = :hash WHERE `user_id` = :id");
                    $update_stmt->execute([':hash' => $new_hash, ':id' => $user['user_id']]);
                }
            }

            if ($authenticated) {
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role_id']   = $user['role_id'];
                $_SESSION['firstname'] = $user['First_name'];
                $_SESSION['lastname']  = $user['Last_name'];

                header("Location: dashboard.php?page=dashboard");
                exit();
            } else {
                $error = "Invalid login credentials or password.";
            }
        } else {
            $error = "Invalid login credentials or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Uplift System | Log in</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <!-- Toastr CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="login-logo">
    <a href="index.php"><b>UpLift </b> System</a>
  </div>
  
  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">Sign in to start your session</p>

      <form action="" method="post">
        <div class="input-group mb-3">
          <input type="text" name="login_id" class="form-control" placeholder="Email Address or First Name" required
                 value="<?php echo isset($_POST['login_id']) ? htmlspecialchars($_POST['login_id']) : ''; ?>">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Password" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <button type="submit" class="btn bg-navy btn-block">Sign In</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>
<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<?php if (!empty($error)): ?>
<script>
  $(document).ready(function() {
      toastr.options = {
          "closeButton": true,
          "progressBar": true,
          "positionClass": "toast-top-center",
          "timeOut": "5000"
      };
      toastr.error("<?php echo addslashes($error); ?>");
  });
</script>
<?php endif; ?>

</body>
</html>