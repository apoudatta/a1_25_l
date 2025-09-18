<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>
    <?= $this->renderSection('title') ?: 'bKash LMS' ?>
  </title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <!-- Your custom CSS -->

  <!-- SweetAlert2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

  <!-- datatable section -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
  <!-- Select2 -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  <link href="<?= base_url('css/app.css') ?>" rel="stylesheet">
</head>

<body class="d-flex flex-column">
  <div class="container-fluid">
    <div class="row flex-nowrap">

      <div id="sidebarOffcanvas" class="col-auto col-md-3 col-xl-2 px-0 bg-dark">
        <div class="d-flex flex-column align-items-center align-items-sm-start px-1 pt-2 text-white min-vh-100">
          
          <form action="<?= site_url('dashboard_url') ?>" method="post" class="m-0">
            <?= csrf_field() ?>
            <input type="hidden" name="user_type" value="<?= esc(session('user_type') ?? ($user->user_type ?? 'EMPLOYEE')) ?>">
            <input type="hidden" name="userId"    value="<?= esc(session('user_id')   ?? ($user->id        ?? '')) ?>">

            <button type="submit"
                    class="d-flex align-items-center ps-3 pb-3 mb-md-0 me-md-auto text-white text-decoration-none bg-transparent border-0">
              <span class="fs-4 d-none d-sm-inline">bKash</span>
              <img src="<?= base_url('images/bkash-logo.svg') ?>" alt="bKash" height="30">
            </button>
          </form>

          <?= $this->renderSection('sidebar') ?>
        </div>
      </div>


      <div id="mainContentCol" class="col-auto col-md-9 col-xl-10 px-sm-10 px-0">
        <?= $this->include('partials/header') ?>
          
        <div class="p-2 min-vh-80">
          <?= $this->include('partials/thumbnail_bar') ?>
          <div class="bg-white px-md-3 py-md-2">
            <?= $this->renderSection('content') ?>
          </div>
        </div>

        <?= $this->include('partials/footer') ?>
      </div>
    </div>


  <script>
    const SITE_URL = '<?= rtrim(site_url(), '/') ?>/';
  </script>
  <!-- jQuery & Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Chart.js (for dashboards) -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

  <!-- dataTable js -->
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
  <!-- Select2 -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  
  <!-- Our custom JS -->
  <script src="<?= base_url('js/app.js') ?>"></script>
  <script src="<?= base_url('js/meal-calendar.js') ?>"></script>

  <?= $this->renderSection('scripts') ?>
</body>

</html>