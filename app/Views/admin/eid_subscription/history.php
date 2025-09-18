<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h4 class="mb-2">My Subscriptions</h4>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif ?>

<form method="post" action="<?= site_url('admin/eid-subscription/unsubscribe_bulk') ?>" id="bulkUnsubscribeForm">
<table id="subscriptionTable" class="table table-bordered table-striped nowrap w-100">
  <thead class="table-light">
    <tr>
      <th><input type="checkbox" id="checkAll"></th>
      <th>#</th>
      <th>Subs/Unsubs date</th>
      <th>Date</th>
      <th>Meal Type</th>
      <th>Cafeteria</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
  </thead>
  <?= view('partials/admin/subs_list_tbody', [
    'rows' => $subs, 'list'=>'ramadan', 'unsubs' => 'eid-subscription',
    'showUnsubs' => true,
  ]) ?>
</table>

<?= csrf_field() ?>
  <div class="mb-2">
    <button type="submit" class="btn btn-danger btn-sm" id="bulkUnsubscribeBtn" disabled>
      Unsubscribe Selected
    </button>
  </div>
</form>
<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<script>
  // Initialize DataTable and capture instance
  dataTableInit('#subscriptionTable', 'My_Subscriptions_Eid');

  bulkSelectedUnsubscription();
</script>
<?= $this->endSection() ?>