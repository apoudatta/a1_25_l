
<header>
  <nav class="navbar navbar-expand-lg sticky-top bg-white">
    <div class="container-fluid">
      <!-- 1) Sidebar toggle (mobile only) -->
      <button class="btn me-2" id="sidebarToggleBtn">
        <i class="bi bi-list-nested me-2"></i>
      </button>

      <!-- 2) Brand -->
      <a class="navbar-brand d-flex align-items-center" href="<?= site_url('dashboard') ?>">
        <span class="ms-2">
          Lunch Management portal
        </span>
      </a>

      <!-- 3) Header-nav toggler (mobile) -->
      <button class="navbar-toggler btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#headerNav"
        aria-controls="headerNav" aria-expanded="false" aria-label="Toggle navigation">
        <i class="bi bi-border-width"></i>
      </button>

      <!-- 4) Collapsible header nav -->
      <div class="collapse navbar-collapse" id="headerNav">

        <!-- Notifications + Profile dropdown -->
        <ul class="navbar-nav align-items-center ms-auto">

          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle text-secondary fw-bold" href="#" id="userDropdown" role="button"
              data-bs-toggle="dropdown" aria-expanded="false">
              <?= session('user_name') ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <!-- <li>
                <a class="dropdown-item" href="<?= site_url('admin/profile') ?>">
                  Profile
                </a>
              </li>
              <li>
                <hr class="dropdown-divider">
              </li> -->
              <li>
                <a class="dropdown-item" href="<?= site_url('auth/logout') ?>">
                  Logout
                </a>
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</header>