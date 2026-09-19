<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-light elevation-4">

  <!-- Sidebar -->
  <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="info">
          <a href="#" class="d-block"><?php echo htmlspecialchars($_SESSION['firstname'].' '.$_SESSION['lastname']);?>
        </a>
        </div>
      </div>

    <!-- Sidebar Menu -->
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        
        <?php $current_page = isset($_GET['page']) ?$_GET['page'] : ''; ?>

        <!-- Dashboard -->
        <li class="nav-item">
          <a href="dashboard.php?page=dashboard" class="nav-link <?php echo ($current_page === 'dashboard') ? 'active' : ''; ?>">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <!-- User Management (users, roles) -->
        <li class="nav-item <?php echo in_array($current_page, ['add_user', 'update_user', 'view_user']) ? 'menu-open' : ''; ?>">
          <a href="#" class="nav-link <?php echo in_array($current_page, ['add_user', 'update_user', 'view_user']) ? 'active' : ''; ?>">
            <i class="nav-icon fas fa-users"></i>
            <p>
              User Management
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="dashboard.php?page=view_user" class="nav-link <?php echo ($current_page === 'view_user') ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-user"></i>
                <p>View Users</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="dashboard.php?page=add_user" class="nav-link <?php echo ($current_page === 'add_user') ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-user-plus"></i>
                <p>Add User</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- Tutor Management (tutor_profiles, tutor_capabilities, tutor_availability) -->
        <li class="nav-item <?php echo in_array($current_page, ['tutor_profiles', 'tutor_capabilities', 'tutor_availability']) ? 'menu-open' : ''; ?>">
          <a href="#" class="nav-link <?php echo in_array($current_page, ['tutor_profiles', 'tutor_capabilities', 'tutor_availability']) ? 'active' : ''; ?>">
            <i class="nav-icon fas fa-user-graduate"></i>
            <p>
              Tutors
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="dashboard.php?page=tutor_profiles" class="nav-link <?php echo ($current_page === 'tutor_profiles') ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-id-card"></i>
                <p>Profiles</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="dashboard.php?page=tutor_capabilities" class="nav-link <?php echo ($current_page === 'tutor_capabilities') ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-book-reader"></i>
                <p>Capabilities & Subjects</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="dashboard.php?page=tutor_availability" class="nav-link <?php echo ($current_page === 'tutor_availability') ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-clock"></i>
                <p>Availability</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- Tutoring Requests & Matches (tutoring_requests, tutoring_matches) -->
        <li class="nav-item <?php echo in_array($current_page, ['tutoring_requests', 'tutoring_matches']) ? 'menu-open' : ''; ?>">
          <a href="#" class="nav-link <?php echo in_array($current_page, ['tutoring_requests', 'tutoring_matches']) ? 'active' : ''; ?>">
            <i class="nav-icon fas fa-hands-helping"></i>
            <p>
              Requests & Matches
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="dashboard.php?page=tutoring_requests" class="nav-link <?php echo ($current_page === 'tutoring_requests') ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-clipboard-list"></i>
                <p>Tutoring Requests</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="dashboard.php?page=tutoring_matches" class="nav-link <?php echo ($current_page === 'tutoring_matches') ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-link"></i>
                <p>Tutor Matches</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- Sessions & Attendance (tutoring_sessions, session_attendance) -->
        <li class="nav-item <?php echo in_array($current_page, ['tutoring_sessions', 'session_attendance']) ? 'menu-open' : ''; ?>">
          <a href="#" class="nav-link <?php echo in_array($current_page, ['tutoring_sessions', 'session_attendance']) ? 'active' : ''; ?>">
            <i class="nav-icon fas fa-calendar-alt"></i>
            <p>
              Sessions
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="dashboard.php?page=tutoring_sessions" class="nav-link <?php echo ($current_page === 'tutoring_sessions') ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-chalkboard-teacher"></i>
                <p>Session Schedule</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="dashboard.php?page=session_attendance" class="nav-link <?php echo ($current_page === 'session_attendance') ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-user-check"></i>
                <p>Attendance Logs</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- Reports & Evaluations (session_reports, session_evaluations, generated_reports) -->
        <li class="nav-item <?php echo in_array($current_page, ['session_reports', 'session_evaluations', 'generated_reports']) ? 'menu-open' : ''; ?>">
          <a href="#" class="nav-link <?php echo in_array($current_page, ['session_reports', 'session_evaluations', 'generated_reports']) ? 'active' : ''; ?>">
            <i class="nav-icon fas fa-chart-line"></i>
            <p>
              Reports & Feedback
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="dashboard.php?page=session_reports" class="nav-link <?php echo ($current_page === 'session_reports') ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-file-alt"></i>
                <p>Session Reports</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="dashboard.php?page=session_evaluations" class="nav-link <?php echo ($current_page === 'session_evaluations') ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-star"></i>
                <p>Evaluations</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="dashboard.php?page=generated_reports" class="nav-link <?php echo ($current_page === 'generated_reports') ? 'active' : ''; ?>">
                <i class="nav-icon fas fa-file-pdf"></i>
                <p>Generated Reports</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- Session Resources (session_resources) -->
        <li class="nav-item">
          <a href="dashboard.php?page=session_resources" class="nav-link <?php echo ($current_page === 'session_resources') ? 'active' : ''; ?>">
            <i class="nav-icon fas fa-folder-open"></i>
            <p>Resources</p>
          </a>
        </li>

        <!-- Logout -->
        <li class="nav-item">
          <a href="logout.php" class="nav-link">
            <i class="nav-icon fas fa-sign-out-alt"></i>
            <p>Logout</p>
          </a>
        </li>

      </ul>
    </nav>
    <!-- /.sidebar-menu -->
  </div>
  <!-- /.sidebar -->
</aside>