<?php  
/*
|---------------------------------------
| Module Name     : DBA
|---------------------------------------
| Copyright       : Arena Phone BD Ltd.  
| Created on      : 2025                 
| Developed By    : masud.dev.bd@gmail.com
|---------------------------------------
*/

$session = \Config\Services::session(); 
$this->validation = \Config\Services::validation();
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?>
bKash :: DBA
<?= $this->endSection() ?>
<?php $activeController = $session->get('activeController');?>

<?= $this->section('content') ?>

<style>
	table {
		border-collapse: collapse;
		width: 100%;
	}
	th, td {
		border: 1px solid #ddd;
		padding: 8px;
		text-align: left;
	}
	thead {
		position: sticky;
		top: 0;
		background: white;
		z-index: 10;
	}
</style>
<div class="box-content no-pad">
 <div class="box-content profile" style="min-height: 300px;">
		<div class="user_detail">
			
			<form 
				action="<?= base_url('admin/dba'); ?>" 
				method="post" 
				enctype="multipart/form-data"
				class="card shadow-sm p-3"
			>
			<?= csrf_field() ?>

			<div class="mb-3">
				<label 
					for="query_string" 
					class="form-label fw-semibold"
				>
				Query
				</label>
				<textarea
					id="query_string"
					name="query_string"
					class="form-control <?= $this->validation->hasError('query_string') ? 'is-invalid' : '' ?>"
					rows="6"
					required
				><?= esc($query_string ?? '') ?></textarea>

				<?php if ($this->validation->getError('query_string')): ?>
				<div class="invalid-feedback">
					<?= esc($this->validation->getError('query_string')) ?>
				</div>
				<?php endif; ?>

				<div class="form-text text-danger small">
				It is not required to use <code>;</code> at the end of a line.
				</div>
			</div>

			<div class="d-flex justify-content-end">
				<button 
					type="submit" 
					class="btn btn-primary px-5"
				>
				Run
				</button>
			</div>
			</form>

			<?php if (isset($message) || !empty($query_result)): ?>
				<hr>
				<h3>Query Results:</h3>
				<?php if (isset($message)): ?>
					<h2 class="text-info"> <?= esc($message) ?> </h2>
				<?php else: ?>
					<h2 class="text-info">Selected Rows: <?= count($query_result) ?></h2>
					<div class="table-responsive">
  <table 
      id="queryResultTable" 
      class="table table-striped table-bordered table-sm align-middle"
      style="width:100%"
  >
    <thead class="table-light">
      <tr>
        <?php foreach (array_keys($query_result[0]) as $column_name): ?>
          <th><?= esc($column_name) ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($query_result as $row): ?>
        <tr>
          <?php foreach ($row as $value): ?>
            <td><?= esc($value ?? '') ?></td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
  $('#queryResultTable').DataTable({
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50, 100],
    ordering: true,
    searching: true,
    responsive: true,
    language: {
      search: "_INPUT_",
      searchPlaceholder: "Search results..."
    }
});
});
</script>
<?= $this->endSection() ?>
