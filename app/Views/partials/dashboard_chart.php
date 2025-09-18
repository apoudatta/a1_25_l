<div class="col-md-6 g-0">
    <div class="card shadow border-0">
      <div class="card-body">
        <!-- Header: title + filters -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
          <h6 class="mb-0 fw-semibold">Registration vs Consumption</h6>
        </div>

        <div class="row g-4 align-items-center">
          <!-- Donut -->
          <div class="col-md-6 text-center">
            <!-- set values via data-* (you can echo PHP numbers here) -->
            <div class="donut" data-consumed="<?= $meal_consumed ?>" data-not="<?= $not_consumed ?>">
              <div class="donut-center">
                <div class="small text-muted">Total Registration</div>
                <div class="h2 fw-bold mb-0" id="totalReg"><?= $total_registrations ?></div>
              </div>
            </div>
          </div>

          <!-- Legend + stat -->
          <div class="col-md-6">
            <div class="d-flex flex-column gap-3">
              <div class="d-flex justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <span class="legend-dot bg-consumed"></span>
                  <span class="fw-semibold">Consumed</span>
                </div>
                <span class="fw-semibold text-muted" id="consumedCount"><?= $meal_consumed ?></span>
              </div>

              <div class="d-flex justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <span class="legend-dot bg-not"></span>
                  <span class="fw-semibold">Not Consumed</span>
                </div>
                <span class="fw-semibold text-muted" id="notCount"><?= $not_consumed ?></span>
              </div>

              <div class="card stat-card bg-light border-0 mt-2">
                <div class="card-body py-3">
                  <div class="small text-muted mb-1">Meal Consumed Rate</div>
                  <div class="h2 m-0 fw-bold rate" id="ratePct">93%</div>
                </div>
              </div>
            </div>
          </div>
        </div><!-- /row -->
      </div>
    </div>
  </div>

  <?= $this->section('scripts') ?>
<script>
  // Make the donut dynamic from the counts
  (function () {
    const d = document.querySelector('.donut');
    if (!d) return;

    const consumed = Number(d.dataset.consumed || 0);
    const notc     = Number(d.dataset.not || 0);
    const total    = consumed + notc;
    const pct      = total ? Math.round((consumed/total)*100) : 0;

    // fill ring
    d.style.setProperty('--p', pct);

    // update texts (optional)
    document.getElementById('totalReg').textContent   = total;
    document.getElementById('consumedCount').textContent = consumed;
    document.getElementById('notCount').textContent      = notc;
    document.getElementById('ratePct').textContent       = pct + '%';
  })();
</script>
<?= $this->endSection() ?>