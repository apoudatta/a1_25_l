
<!-- then, after SweetAlert2’s JS include, add: -->
<?php if ($msg = session()->getFlashdata('success')): ?>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      Swal.fire({
        toast: true,                 // make it a toast
        position: 'top-end',
        icon: 'success',
        title: <?= json_encode($msg) ?>, // safely inject your PHP string
        showConfirmButton: false,
        timer: 8000,
        timerProgressBar: true
      });
    });
  </script>
<?php endif; ?>


<?php if ($err = session()->getFlashdata('error')): ?>
  <script>
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'error',
      title: <?= json_encode($err) ?>,
      showConfirmButton: false,
      timer: 10000
    });
  </script>
<?php endif; ?>
