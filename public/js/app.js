$(function() {
  // 2. Global AJAX error handler
  $(document).ajaxError(function(event, jqxhr, settings, thrownError){
    console.error('AJAX error:', thrownError, settings);
    alert('An unexpected error occurred. Please try again.');
  });


  /* === Vendor Panels === */

  // Initialize Flatpickr datepickers
  if (typeof flatpickr === 'function') {
    $('.datepicker').flatpickr({ dateFormat: 'Y-m-d' });
  }
});




// Left side bar show hide start
document.addEventListener('DOMContentLoaded', function () {
  const sidebar = document.getElementById('sidebarOffcanvas');
  const main = document.getElementById('mainContentCol');
  const toggleBtn = document.getElementById('sidebarToggleBtn');

  let sidebarVisible = true;

  function setLayout(visible) {
    sidebar.style.display = visible ? 'block' : 'none';
    if (visible) {
      main.classList.remove('col-12');
      main.classList.add('col-auto', 'col-md-9', 'col-xl-10');
    } else {
      main.classList.remove('col-auto', 'col-md-9', 'col-xl-10');
      main.classList.add('col-12');
    }
    sidebarVisible = visible;
  }

  function adjustInitialLayout() {
    const isLargeScreen = window.innerWidth >= 992;
    setLayout(isLargeScreen);
  }

  // On toggle button click
  toggleBtn.addEventListener('click', function () {
    setLayout(!sidebarVisible);
  });

  // Set initial layout
  adjustInitialLayout();

  // Adjust layout on window resize
  window.addEventListener('resize', adjustInitialLayout);
});
// Left side bar show hide end



// Meal subscription - Approval Document show if meal type is 2 ----start
const $mealType = $('select[name="meal_type_id"]');
const $approvalGroup = $('#approvalDocGroup');
const $approvalInput = $approvalGroup.find('input[name="approval_doc"]');

function toggleApprovalDoc() {
  const needsApproval = $mealType.val() === '2';
  // show/hide container
  $approvalGroup.toggleClass('d-none', !needsApproval);
  // enforce/remove required on the file input
  $approvalInput.prop('required', needsApproval);
}

// wire it up
$mealType.on('change', toggleApprovalDoc);
// run on page-load in case the select was pre-set
toggleApprovalDoc();
// Meal subscription - Approval Document show if meal type is 2 ----end





// Unsubscribe alert start
$(document).on('click', '#unsubscribe_btn', function (e) {
  e.preventDefault();

  const btn  = this;                                 // the clicked <button id="unsubscribe_btn">
  const form = btn.form || $(btn).closest('form')[0]; // outer bulk form

  Swal.fire({
    title: 'Unsubscribe this subscription?',
    text: 'Are you sure?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, Unsubscribe it!',
    cancelButtonText: 'No',
    reverseButtons: true,
    confirmButtonColor: '#dc3545'
  }).then((result) => {
    if (!result.isConfirmed) return;

    // Use the button as the submitter so formaction/formmethod are used
    if (form.requestSubmit) {
      form.requestSubmit(btn);
      return;
    }

    // Fallback for older browsers
    const origAction = form.getAttribute('action') || '';
    const origMethod = form.getAttribute('method') || 'post';
    const newAction  = btn.getAttribute('formaction') || origAction;
    const newMethod  = btn.getAttribute('formmethod') || 'post';

    if (newAction) form.setAttribute('action', newAction);
    form.setAttribute('method', newMethod);
    form.submit();

    // Optional restore
    if (origAction) form.setAttribute('action', origAction); else form.removeAttribute('action');
    form.setAttribute('method', origMethod);
  });
});

// Unsubscribe alert end


// For delete alert
// Make sure this runs after SweetAlert2 is loaded
$(document).on('click', '.btn-delete', function(e) {
  e.preventDefault();
  const $form = $(this).closest('form');

  Swal.fire({
    title: 'Are you sure?',
    text: 'This action will permanently delete the data.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel',
    reverseButtons: true,
    confirmButtonColor: '#dc3545'
  }).then((result) => {
    if (result.isConfirmed) {
      $form.trigger('submit');  // now submit the form
    }
  });
});





function dataTableInit(selector = '.datatable', exportTitle = 'Exported_Table', checkboxs = false) {
  const config = {
    responsive: false,
    scrollY: '400px',
    scrollX: true,
    scrollCollapse: true,
    autoWidth: false,
    paging: true,
    dom:
      "<'row align-items-center mb-1'<'col-md-4 col-sm-12'l><'col-md-8 col-sm-12 d-flex justify-content-md-end justify-content-start gap-2 mt-2 mt-md-0'fB>>" +
      "<'row'<'col-sm-12'tr>>" +
      "<'row mt-2'<'col-sm-6'i><'col-sm-6'p>>",
    lengthMenu: [5, 10, 25, 50, 100],
    pageLength: 10,
    buttons: []
  };

  // Set export button with dynamic column exclusion
  let exportCols = ':visible:not(:last-child)'; // default
  if (checkboxs === true) {
    config.order = [[1, 'asc']]; // Sort by Serial
    config.columnDefs = [
      { orderable: false, targets: 0 } // Disable checkbox column sort
    ];
    exportCols = ':visible:not(:first-child):not(:last-child)'; // Exclude checkbox and Action
  }

  // Add Excel button now with computed exportCols
  config.buttons.push({
    extend: 'excelHtml5',
    title: exportTitle,
    className: 'btn btn-sm btn-success',
    text: '<i class="bi bi-file-earmark-excel"></i> Download Excel',
    exportOptions: {
      columns: exportCols
    }
  });

  $(selector).DataTable(config);
}


// Automatically init if table exists
$(document).ready(function () {
  if ($('.datatable').length > 0) {
    dataTableInit();
  }
});

// unsubs btn iffect
$(function () {
  $('[data-bs-toggle="tooltip"]').tooltip();
});


// Using Select2 input filter
$(function() {
  $('#employee_id').select2({
    placeholder: 'Select employee...',
    allowClear: true,
    width: '100%'
  });
});


function bulkSelectedUnsubscription() {
  // Check all
  $('#checkAll').on('change', function () {
    $('.row-checkbox').prop('checked', this.checked).trigger('change');
  });

  // Enable/disable bulk button
  $(document).on('change', '.row-checkbox', function () {
    const anyChecked = $('.row-checkbox:checked').length > 0;
    $('#bulkUnsubscribeBtn').prop('disabled', !anyChecked);

    if (!this.checked) {
      $('#checkAll').prop('checked', false);
    } else if ($('.row-checkbox').length === $('.row-checkbox:checked').length) {
      $('#checkAll').prop('checked', true);
    }
  });


  // Selected Unsubscribe flow
  $(document).on('click', '#bulkUnsubscribeBtn', function(e) {
    e.preventDefault();
    const form = $(this).closest('form');

    // Step 1: confirm
    Swal.fire({
      title: 'Unsubscribe all selected items?',
      text:  'Are you sure?',
      icon:  'warning',
      showCancelButton:   true,
      confirmButtonText:  'Yes, Unsubscribe all!',
      cancelButtonText:   'No',
      reverseButtons:     true,
      confirmButtonColor: '#dc3545'
    }).then((res) => {
      if (!res.isConfirmed) return;

      // Step 2: ask for remark
      Swal.fire({
        title: 'Enter remark',
        input: 'textarea',
        inputPlaceholder: 'Type your remark here…',
        inputAttributes: {
          'aria-label': 'Remark'
        },
        showCancelButton:  true,
        confirmButtonText: 'Submit',
        cancelButtonText:  'Cancel',
        reverseButtons:    true
      }).then((inputRes) => {
        if (!inputRes.isConfirmed) return;
        // inject remark into form and submit
        form.append(
          $('<input>')
            .attr('type', 'hidden')
            .attr('name', 'remark')
            .val(inputRes.value)
        );
        form.trigger('submit');
      });
    });
  });
}

