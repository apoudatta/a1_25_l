<?= $this->extend('layouts/vendor') ?>
<?= $this->section('content') ?>

<h4 class="mb-4">My Profile</h4>

<?php if (empty($user)): ?>
  <div class="alert alert-warning">Profile not found.</div>
  <?php /* Optionally link back */ ?>
  <a href="<?= esc(base_url('vendor/dashboard')) ?>" class="btn btn-secondary btn-sm">Back to Dashboard</a>
<?php else: ?>
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="row g-3">
        <!-- Left: Avatar/initials -->
        <div class="col-md-3 d-flex align-items-start">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                 style="width:72px;height:72px;font-weight:600;">
              <?php
                $initials = '';
                $n = trim((string)($user['vendor_name'] ?? $user['name'] ?? ''));
                if ($n !== '') {
                  $parts = preg_split('/\s+/', $n);
                  $initials = strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
                }
                echo esc($initials ?: 'U');
              ?>
            </div>
            <div>
              <div class="fw-semibold">
                <?= esc($user['vendor_name'] ?? $user['name'] ?? '—') ?>
              </div>
              <div class="text-muted small">
                Vendor ID: <?= esc($user['vendor_id'] ?? '-') ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Details -->
        <div class="col-md-6">
          <div class="row">
            <div class="col-sm-12 mb-3">
              <div class="text-muted small">Name</div>
              <div class="fw-semibold">
                <?= esc($user['name'] ?? '-') ?>
              </div>
            </div>

            <div class="col-sm-12 mb-3">
              <div class="text-muted small">Phone</div>
              <div class="fw-semibold">
                <?= esc($user['phone'] ?? '-') ?>
              </div>
            </div>

            <div class="col-sm-12 mb-3">
              <div class="text-muted small">Email</div>
              <div class="fw-semibold">
                <?= esc($user['email'] ?? '-') ?>
              </div>
            </div>

          </div>
        </div>
      </div>

      <hr class="my-4">

      <div class="d-flex gap-2">
        <a href="<?= esc(base_url('vendor/dashboard')) ?>" class="btn btn-outline-secondary btn-sm">
          Back to Dashboard
        </a>
        <!-- No edit/save buttons since this is view-only -->
      </div>
    </div>
  </div>
<?php endif; ?>

<?= $this->endSection() ?>
