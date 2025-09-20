<?php
// app/Views/partials/flash_message.php

$success = session()->getFlashdata('success');
$error   = session()->getFlashdata('error');

if ($success || $error): ?>
<script>
(function () {
  const successMsg = <?= json_encode($success) ?>;
  const errorMsg   = <?= json_encode($error) ?>;

  function fireToast(icon, title, timer) {
    if (!window.Swal) {              // SweetAlert2 not ready yet → try again shortly
      return setTimeout(() => fireToast(icon, title, timer), 50);
    }
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: icon,
      title: title,
      showConfirmButton: false,
      timer: timer,
      timerProgressBar: true
    });
  }

  function onReady() {
    if (successMsg) fireToast('success', successMsg, 8000);
    if (errorMsg)   fireToast('error',   errorMsg,   10000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', onReady);
  } else {
    onReady();
  }
})();
</script>
<?php endif; ?>
