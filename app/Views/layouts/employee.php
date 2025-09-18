<?= $this->extend('layouts/base') ?>

<?= $this->section('title') ?>Employee Portal – bKash LMS<?= $this->endSection() ?>

<?= $this->section('sidebar') ?>
    <?= $this->include('partials/sidebar/sidebar_employee') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
  <!-- Employee-specific content here -->
<?= $this->endSection() ?>