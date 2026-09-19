<?php
session_start();
require_once 'config/db.php';

if(!isset($_SESSION['user_id'])) {
	header("Location: index.php");
	exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once 'include/head.php'; ?>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
<?php 
require_once 'include/nav.php'; 
require_once 'include/sidebar.php'; 

// Content Routing
if (empty($_GET['page'])) {
	$page = '404';
} else {
	$page = $_GET['page'];
}

switch ($page) {
	case 'dashboard':
		require_once 'views/main.php';
		break;

    // User Management
	case 'view_user':
		require_once 'views/view_user.php';
		break;
	case 'add_user':
		require_once 'views/add_user.php';
		break;
    case 'update_user':
        require_once 'views/update_user.php';
        break;

    // Tutor Management
    case 'tutor_profiles':
        require_once 'views/tutor_profiles.php';
        break;
    case 'tutor_capabilities':
        require_once 'views/tutor_capabilities.php';
        break;
    case 'tutor_availability':
        require_once 'views/tutor_availability.php';
        break;

    // Requests & Matches
    case 'tutoring_requests':
        require_once 'views/tutoring_requests.php';
        break;
    case 'tutoring_matches':
        require_once 'views/tutoring_matches.php';
        break;

    // Sessions & Attendance
    case 'tutoring_sessions':
        require_once 'views/tutoring_sessions.php';
        break;
    case 'session_attendance':
        require_once 'views/session_attendance.php';
        break;

    // Reports & Evaluations
    case 'session_reports':
        require_once 'views/session_reports.php';
        break;
    case 'session_evaluations':
        require_once 'views/session_evaluations.php';
        break;
    case 'generated_reports':
        require_once 'views/generated_reports.php';
        break;

    // Resources
    case 'session_resources':
        require_once 'views/session_resources.php';
        break;

    // Errors
	case '404':
		require_once 'views/404.php';
		break;
    case 'forbidden':
        require_once 'views/403.php';
        break;
	default:
		require_once 'views/404.php';
		break;
}

require_once 'include/footer.php'; 
?>
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- DataTables  & Plugins -->
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="plugins/jszip/jszip.min.js"></script>
<script src="plugins/pdfmake/pdfmake.min.js"></script>
<script src="plugins/pdfmake/vfs_fonts.js"></script>
<script src="plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>
<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
  $(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
    $('#example2').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": false,
      "ordering": true,
      "info": true,
      "autoWidth": false,
      "responsive": true,
    });
  });
</script>

<script>
$(document).ready(function() {
    let emailExists = false;

    // Toggle the submit button state
    function checkFormStatus() {
        if (emailExists) {
            $('#submitBtn').prop('disabled', true);
        } else {
            $('#submitBtn').prop('disabled', false);
        }
    }

    // Check Email Uniqueness against `users` table
    $('#email').on('keyup blur', function() {
        let val = $(this).val();
        if (val.length > 5 && val.includes('@')) {
            $.post('check_existence.php', { field: 'email', value: val }, function(response) {
                let data = (typeof response === 'object') ? response : JSON.parse(response);
                if (data.exists) {
                    $('#email').addClass('is-invalid');
                    $('#emailError').removeClass('d-none');
                    emailExists = true;
                } else {
                    $('#email').removeClass('is-invalid').addClass('is-valid');
                    $('#emailError').addClass('d-none');
                    emailExists = false;
                }
                checkFormStatus();
            });
        }
    });
});
</script>

<!-- Toastr Notification Trigger -->
<?php if (!empty($toast_message)): ?>
<script>
  $(function() {
    toastr.<?php echo $toast_type; ?>('<?php echo addslashes($toast_message); ?>', 'System Notification', {
        positionClass: 'toast-top-center',
        timeOut: 5000
    });
   
    <?php if ($toast_type === 'success'): ?>
      if ($('#addUserForm').length) {
          $('#addUserForm')[0].reset();
      }
      $('.is-valid').removeClass('is-valid');
    <?php endif; ?>
  });
</script>
<?php endif; ?>
</body>
</html>