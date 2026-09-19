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
          <div class="col-lg-4 col-6">
            <!--small box-->
            <div class="small-box bg-primary" >
              <div class="inner">
                <h3>
                  <?php
                  $stmtTotalStatus = $pdo->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.role_id WHERE r.role_name != 'superadmin'");
                  $stmtTotalStatus->execute();
                  $countTotalStatus = $stmtTotalStatus->fetchColumn();
                  echo $countTotalStatus;
                  ?>
                </h3>
                <p>Total Users</p>
              </div>
              <div class="icon">
                <i class="fas fa-user"></i>
              </div>
              <a href="dashboard.php?page=view_user" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>

          <div class="col-lg-4 col-6">
            <div class="small-box bg-green" >
              <div class="inner">
                <h3>
                  <?php
                  $stmtTotalStatus = $pdo->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.status = 'Active' AND r.role_name != 'superadmin'");
                  $stmtTotalStatus->execute();
                  $countTotalStatus = $stmtTotalStatus->fetchColumn();
                  echo $countTotalStatus;
                  ?>
                </h3>
                <p>Total Active Users</p>
              </div>
              <div class="icon">
                <i class="fas fa-user"></i>
              </div>
              <a href="dashboard.php?page=view_user" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>

          <div class="col-lg-4 col-6">
            <!--small box-->
            <div class="small-box bg-danger" >
              <div class="inner">
                <h3>
                  <?php
                  $stmtTotalStatus = $pdo->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.status = 'Inactive' AND r.role_name != 'superadmin'");
                  $stmtTotalStatus->execute();
                  $countTotalStatus = $stmtTotalStatus->fetchColumn();
                  echo $countTotalStatus;
                  ?>
                </h3>
                <p>Total Inactive Users</p>
              </div>
              <div class="icon">
                <i class="fas fa-user"></i>
              </div>
              <a href="dashboard.php?page=view_user" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->