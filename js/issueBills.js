import SuperSaver from "./SuperSaver.js";
function btn_edit_customer(customerId) {
  console.log("Edit button clicked for customer ID:", customerId);
  // Rest of your code here
}
var selectedBills = [];
let recoveryCreationPending = false;
let pendingRecoveryRequest = null;
// Needed Code Dont Delete
let messageBox = $("#messageBox");
let messageBody = $("#messageBody");
messageBox.hide();
$("#close_messageBox").click(() => {
  messageBox.hide();
});
let api = "functionality/allFunctionality.php";
// DataTable Initialization
const selected_bill_table = $("#selected_bill_table").DataTable({
  columns: [
    { data: 'bill_id', title: 'Bill ID' },
    {
      data: null,
      title: 'Action',
      render: function (data, type, row) {
        if (type == 'display') {
          return '<button class="btn btn-danger btn-remove-bill">Remove</button>'
        }
        return data;
      }
    }
  ]
})
const tbl_bill_info = $('#table_bills_info').DataTable({
  // Define the table columns
  columns: [
    { data: 'bill_id', title: 'Bill ID' },
    { data: 'customer_name', title: 'Customer' },
    { data: 'saleman_name', title: 'Saleman' },
    { data: 'sector_name', title: 'Sector' },
    { data: 'bill_date', title: 'Date' },
    { data: 'bill_amount', title: 'Amount' },
    {
      data: null,
      title: 'Action',
      render: function (data, type, row) {
        if (type === 'display') {
          // Create a button for the action
          return '<button class="btn btn-primary btn-action">Add</button>';
        }
        return data;
      },
    },
  ],
});
// ON LOAD
const mySuperSaver = new SuperSaver(api);

mySuperSaver.performAjaxRequest({
  data: {
    function_to_call: 'load_saleman_data'
  }
}).then((responce) => {
  mySuperSaver.addOptionsToSelect('select_recovery_saleman', responce)
  mySuperSaver.addOptionsToSelect('filter_by_salesman', responce)
  // Re-fetch bills once the salesman list is loaded and a selection is made
  get_bills();
}).catch((error) => {
  console.error(error);
});

function get_bills() {
  const saleman_id = $("#select_recovery_saleman").val();
  mySuperSaver.LoadDataTable(tbl_bill_info, 'getBillsData', { saleman_id: saleman_id });
};

// Wrap all event handlers in document ready
$(document).ready(function () {
  // BULK SELECTION HANDLER
  $('#btn_bulk_add_bills').click(function () {
    const selectedDay = $('#filter_by_day').val();
    const selectedSalesmanId = $('#filter_by_salesman').val();
    const selectedSalesman = $('#select_recovery_saleman').val();

    if (!selectedSalesman) {
      alert('Please select a salesman first');
      return;
    }

    // Query database for filtered bills based on route
    let data = {
      data: {
        function_to_call: 'get_bills_by_route',
        saleman_id: selectedSalesman,
        day: selectedDay,
        filter_saleman_id: selectedSalesmanId
      }
    };

    mySuperSaver.performAjaxRequest(data).then((response) => {
      console.log('Raw response from server:', response);

      // Response is already parsed as an array/object, no need to JSON.parse
      let filteredBills = Array.isArray(response) ? response : [];

      if (filteredBills.length === 0) {
        alert('No bills found matching the selected criteria');
        return;
      }

      // Add filtered bills to selectedBills, avoiding duplicates
      let addedCount = 0;
      filteredBills.forEach(bill => {
        const isAlreadySelected = selectedBills.some(item => item.bill_id === bill.bill_id);
        if (!isAlreadySelected) {
          selectedBills.push(bill);
          addedCount++;
        }
      });

      if (addedCount === 0) {
        alert('All matching bills are already selected');
      } else if (addedCount === filteredBills.length) {
        alert(`Added ${addedCount} bills`);
      } else {
        alert(`Added ${addedCount} bills (${filteredBills.length - addedCount} were already selected)`);
      }

      selected_bill_table.clear().rows.add(selectedBills).draw();
      updateRecoveryFooter();
    }).catch((error) => {
      console.error('Error fetching filtered bills:', error);
      alert('Error loading bills. Please try again.');
    });
  });

  // CLEAR FILTERS
  $('#btn_clear_filters').click(function () {
    $('#filter_by_day').val('');
    $('#filter_by_salesman').val('');
  });

  // Change event to re-filter when salesman changes
  $('#select_recovery_saleman').change(function () {
    $('#filter_by_day').val('');
    $('#filter_by_salesman').val('');
    // Re-fetch bills for the newly selected salesman
    get_bills();
  });

  $('#table_bills_info tbody').on('click', '.btn-action', function () {
    // Get the selected row data
    const rowData = tbl_bill_info.row($(this).closest('tr')).data();
    if (!rowData) return;

    const isAlreadySelected = selectedBills.some(function (item) {
      return item.bill_id === rowData.bill_id;
    });

    if (isAlreadySelected) {
      // Row is already in the JSON array, display an alert
      alert('Bill Already Selected');
    } else {
      // Row is not already in the JSON array, add it
      selectedBills.push(rowData);
      console.log('Added bill:', rowData);
      selected_bill_table.clear().rows.add(selectedBills).draw();
      updateRecoveryFooter();
    }
  });

  $('#selected_bill_table tbody').on('click', '.btn-remove-bill', function () {
    const rowData = selected_bill_table.row($(this).closest('tr')).data();
    if (!rowData) return;

    //console.log(rowData);
    var bill_id = rowData.bill_id;
    for (let i = 0; i < selectedBills.length; i++) {
      if (selectedBills[i].bill_id === bill_id) {
        selectedBills.splice(i, 1); // Remove the element at index i
        break; // Exit the loop after deleting the element
      }
    }
    selected_bill_table.clear().rows.add(selectedBills).draw();
    updateRecoveryFooter();
  });

  $("#check_if_merge").change(function () {
    if (this.checked) {
      $("#mergeto_recovery_sheet_id").prop('readonly', false);
    } else {
      $("#mergeto_recovery_sheet_id").prop('readonly', true);
    }
  });

  $("#btn_generate_recovery_sheet").on("click", createOrAddRecoverySheet);

  // PREVIEW RECOVERY SHEET
  $('#btn_preview_recovery_sheet').click(function () {
    if (selectedBills.length === 0) {
      alert('Please select at least one bill first');
      // Prevent modal from opening if possible, though BS5 attributes might still trigger it.
      // We'll just show empty table or close it via JS if needed.
      return;
    }

    const selectedSaleman = $('#select_recovery_saleman').val();
    // Use optional chaining or fallback
    const selectedSalemanName = selectedSaleman
      ? $(`#select_recovery_saleman option[value="${selectedSaleman}"]`).text().trim()
      : 'Multiple / Not Selected';

    // Get current date
    const today = new Date();
    const dateStr = today.toISOString().split('T')[0];

    // Calculate totals
    let totalAmount = 0;
    selectedBills.forEach(bill => {
      totalAmount += Number(bill.bill_amount || 0);
    });

    // Populate modal with preview data
    $('#preview_saleman_name').text(selectedSalemanName);
    $('#preview_date').text(dateStr);
    $('#preview_total_bills').text(selectedBills.length);
    $('#preview_total_amount').text(totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

    // Populate bills table
    let billsHtml = '';
    selectedBills.forEach(bill => {
      billsHtml += `
        <tr>
          <td>${bill.bill_id || '-'}</td>
          <td>${bill.customer_name || '-'}</td>
          <td>${bill.saleman_name || '-'}</td>
          <td>${bill.sector_name || '-'}</td>
          <td>${bill.bill_date || '-'}</td>
          <td>${Number(bill.bill_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
        </tr>
      `;
    });
    $('#preview_bills_table').empty().html(billsHtml);
  });

  // CONFIRM GENERATE FROM PREVIEW
  $('#btn_confirm_generate').on("click", createOrAddRecoverySheet);

}); // End of $(document).ready()

function updateRecoveryFooter() {
  $("#total_bills").text(selectedBills.length);
  const bill_amount = $("#total_bill_amount");
  let amount = 0;
  for (let i = 0; i < selectedBills.length; i++) {
    amount += Number(selectedBills[i].bill_amount || 0);
  }
  bill_amount.text(amount.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 }));
}

async function createOrAddRecoverySheet() {
  if (recoveryCreationPending) return;
  if (selectedBills.length === 0) {
    alert('Please select bills first');
    return;
  }

  const mergeEnabled = $("#check_if_merge").prop("checked");
  const recoverySheetId = $("#mergeto_recovery_sheet_id").val();
  const salesmanId = $("#select_recovery_saleman").val();
  if (mergeEnabled && !recoverySheetId) {
    alert('Enter Recovery Sheet Id you want to merge with or \nUncheck the checkbox on top right');
    return;
  }
  if (!mergeEnabled && !salesmanId) {
    alert("Please select a salesman for the recovery sheet.");
    return;
  }

  const billIds = selectedBills.map((bill) => String(bill.bill_id)).sort();
  const requestSignature = JSON.stringify({ salesmanId, billIds });
  if (!mergeEnabled && pendingRecoveryRequest?.signature !== requestSignature) {
    const randomBytes = new Uint8Array(16);
    window.crypto.getRandomValues(randomBytes);
    pendingRecoveryRequest = {
      signature: requestSignature,
      key: Array.from(randomBytes, (byte) => byte.toString(16).padStart(2, "0")).join(""),
    };
  }

  recoveryCreationPending = true;
  const $buttons = $("#btn_generate_recovery_sheet, #btn_confirm_generate");
  const originalButtonHtml = $buttons.map(function () { return $(this).html(); }).get();
  $buttons.prop("disabled", true).attr("aria-busy", "true").text("Creating sheet...");

  try {
    if (mergeEnabled) {
      await save_rec_detail_w_merger(recoverySheetId);
      alert("Bills added to Recovery Sheet: " + recoverySheetId);
    } else {
      const response = await mySuperSaver.performAjaxRequest({
        data: {
          function_to_call: "store_recovery_sheet",
          saleman: salesmanId,
          request_key: pendingRecoveryRequest.key,
          bills: JSON.stringify(billIds),
        },
        contentType: "application/x-www-form-urlencoded",
      });
      if (!response?.success || !response.recovery_sheet_id) {
        throw new Error(response?.message || "The recovery sheet could not be created.");
      }
      pendingRecoveryRequest = null;
      alert("Recovery Sheet Created Successfully: ID " + response.recovery_sheet_id);
    }
    window.location.reload();
  } catch (error) {
    reportRecoverySheetError("Generate recovery sheet", error);
  } finally {
    recoveryCreationPending = false;
    $buttons.each(function (index) {
      $(this).html(originalButtonHtml[index]).prop("disabled", false).removeAttr("aria-busy");
    });
  }
}

function save_rec_detail_w_merger(recovery_sheet_id) {
  selectedBills.forEach((item) => {
    item.recovery_sheet_id = recovery_sheet_id;
  });
  const recovery_sheet_detail = selectedBills;
  let data = {
    data: {
      function_to_call: 'save_rec_details',
      recovery_sheet_detail: JSON.stringify(recovery_sheet_detail)
    },
    contentType: 'application/x-www-form-urlencoded'
  };
  return mySuperSaver.performAjaxRequest(data).then((response) => {
    if (response !== 1 && response !== true) {
      throw new Error(response?.message || JSON.stringify(response));
    }
  });
}

function reportRecoverySheetError(stage, error) {
  const message = error?.message || String(error);
  console.error(`${stage}: ${message}`, error);
  alert(message);
}

function create_bill_ledger(recovery_sheet_id) {

}
