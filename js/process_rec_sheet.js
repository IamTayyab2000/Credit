import SuperSaver from "./SuperSaver.js";
// Needed Code Dont Delete
let messageBox = $("#messageBox");
let messageBody = $("#messageBody");
messageBox.hide();
$("#close_messageBox").click(() => {
  messageBox.hide();
});
let api = "functionality/allFunctionality.php";
const mySuperSaver = new SuperSaver(api);
function updateSelectedBillCount() {
  const selectedCount = $("#recovery_table tbody .bill-select:checked").length;
  $("#selected_bill_count").text(selectedCount);
  const billCount = $("#recovery_table tbody .bill-select").length;
  $("#select_all_bills").prop("checked", billCount > 0 && selectedCount === billCount);
}

$("#select_all_bills").on("change", function () {
  $("#recovery_table tbody .bill-select").prop("checked", this.checked);
  updateSelectedBillCount();
});

$(document).on("change", ".bill-select", updateSelectedBillCount);

$("#bulk_action").on("change", function () {
  $("#bulk_status_wrap").prop("hidden", this.value !== "set_status");
});

$("#btn-apply-bulk").on("click", function () {
  const action = $("#bulk_action").val();
  const $selectedRows = $("#recovery_table tbody tr").has(".bill-select:checked");
  if (!action) {
    alert("Choose a bulk action first.");
    return;
  }
  if ($selectedRows.length === 0) {
    alert("Select at least one bill first.");
    return;
  }

  $selectedRows.each(function () {
    const $row = $(this);
    const amount = Number($row.find("td:eq(4)").data("amount")) || 0;
    const recovered = Number($row.find(".btn-recovery").val()) || 0;
    const returned = Number($row.find(".btn-returned").val()) || 0;

    if (action === "recover_remaining") {
      $row.find(".btn-recovery").val(Math.max(0, amount - returned)).trigger("input");
    } else if (action === "return_remaining") {
      $row.find(".btn-returned").val(Math.max(0, amount - recovered)).trigger("input");
    } else {
      const $status = $row.find(".selc-status");
      if (!$status.prop("disabled")) {
        $status.val($("#bulk_status").val());
      }
    }
  });
  updateTotalValues();
});

// Listener for both recovery and return inputs
$(".btn-recovery, .btn-returned").on("input", function () {
  // Find the parent row of the current element
  var $row = $(this).closest("tr");

  // Get values from the row - Use data-amount for raw numeric value
  var amountValue = parseFloat($row.find("td:eq(4)").data("amount")) || 0;
  var recoveryValue = parseFloat($row.find(".btn-recovery").val()) || 0;
  var returnedValue = parseFloat($row.find(".btn-returned").val()) || 0;

  var selectedStatus = $row.find(".selc-status");

  // Calculate the remaining value
  var remainingValue = amountValue - (recoveryValue + returnedValue);

  // Status automation logic
  if (remainingValue <= 0) {
    if (selectedStatus.val() !== "Return" && selectedStatus.val() !== "Nill") {
      selectedStatus.val("Nill");
    }
    selectedStatus.prop("disabled", true);
  } else {
    selectedStatus.prop("disabled", false);
    if (selectedStatus.val() === "Nill") {
      selectedStatus.val("0");
    }
  }

  // Update visually
  $row.find(".txt_remaining").text(remainingValue.toFixed(0));
  updateTotalValues();
});

updateTotalValues();
function updateTotalValues() {
  var totalRecovered = 0;
  var totalReturned = 0;
  var totalRemaining = 0;

  // Loop through each row in the table body
  $("#recovery_table tbody tr").each(function () {
    var recoveredValue = parseFloat($(this).find(".btn-recovery").val()) || 0;
    var returnedValue = parseFloat($(this).find(".btn-returned").val()) || 0;
    var remainingValue = parseFloat($(this).find(".txt_remaining").text()) || 0;

    // Update totals
    totalRecovered += recoveredValue;
    totalReturned += returnedValue;
    totalRemaining += remainingValue;
  });

  // Update the total elements with consistent formatting
  const formatOptions = { minimumFractionDigits: 2, maximumFractionDigits: 2 };
  $("#total_recovery").text(totalRecovered.toLocaleString('en-US', formatOptions));
  $("#total_returned").text(totalReturned.toLocaleString('en-US', formatOptions));
  $("#total_remaining").text(totalRemaining.toLocaleString('en-US', formatOptions));
}

function extractDataAndStoreInArray() {
  const recoverySheet = [];
  $("#recovery_table tbody tr").each(function () {
    const $row = $(this);
    const billId = $row.find("td:eq(0)").text().trim();
    if (!billId) return true;

    const amount = Number($row.find("td:eq(4)").data("amount")) || 0;
    const recovered = Number($row.find(".btn-recovery").val()) || 0;
    const returned = Number($row.find(".btn-returned").val()) || 0;
    const remaining = amount - recovered - returned;
    const selectedStatus = $row.find(".selc-status").val();

    recoverySheet.push({
      ID: billId,
      DATE: $row.find("td:eq(1)").text().trim(),
      Days: parseInt($row.find("td:eq(2)").text(), 10) || 0,
      Shop: $row.find("td:eq(3)").text().trim(),
      Amount: amount,
      Recovered: recovered,
      Returned: returned,
      Remaining: remaining,
      Status: remaining === 0 && selectedStatus !== "Return" ? "Nill" : selectedStatus,
    });
  });
  return recoverySheet;
}

$("#btn-process").click(async () => {
  const recoverySheet = extractDataAndStoreInArray();
  if (recoverySheet.length === 0) {
    alert("This recovery sheet has no bills to process. Check that its bill details were saved.");
    return;
  }

  const recId = new URL(window.location.href).searchParams.get("rec_id");
  if (!recId) {
    alert("Recovery ID not found in URL");
    return;
  }

  for (const bill of recoverySheet) {
    if (bill.Recovered < 0 || bill.Returned < 0 || !Number.isInteger(bill.Recovered) || !Number.isInteger(bill.Returned)) {
      alert("Recovered and returned amounts must be whole numbers greater than or equal to zero.");
      return;
    }
    if (bill.Remaining < 0 || bill.Status === "0" || (bill.Status === "Nill" && bill.Remaining > 0)) {
      give_bad_bills();
      alert("Please fix bills highlighted in red/yellow before processing.");
      return;
    }
  }

  try {
    const response = await mySuperSaver.performAjaxRequest({
      data: {
        function_to_call: "finalize_recovery_sheet",
        recID: recId,
        customer_bills: JSON.stringify(recoverySheet),
      },
      contentType: "application/x-www-form-urlencoded",
    });
    if (!response?.success) {
      throw new Error(response?.message || "Recovery sheet processing failed.");
    }
    if (response.new_recovery_id) {
      alert("Forwarded BF bills to new Recovery Sheet: " + response.new_recovery_id);
    }
    alert("Recovery Sheet processed and balances updated successfully.");
    window.location.reload();
  } catch (error) {
    console.error("Processing failed:", error);
    alert(`Recovery sheet processing failed: ${error.message || error}`);
  }
});

function give_bad_bills() {
  const recoverySheet = extractDataAndStoreInArray();
  const badStatusBills = [];
  const badRemainingBills = [];
  const badNillBills = [];
  $("#recovery_table tbody tr").removeClass("table-danger table-warning table-info");

  for (const bill of recoverySheet) {
    if (bill.Status === "0") badStatusBills.push(bill.ID);
    if (bill.Remaining < 0) badRemainingBills.push(bill.ID);
    if (bill.Status === "Nill" && bill.Remaining > 0) badNillBills.push(bill.ID);
  }
  highlightRowsWithBillIDs(badStatusBills, "table-danger");
  highlightRowsWithBillIDs(badRemainingBills, "table-warning");
  highlightRowsWithBillIDs(badNillBills, "table-info");
}

function highlightRowsWithBillIDs(billIDs, className) {
  $("#recovery_table tbody tr").each(function () {
    const $row = $(this);
    if (billIDs.includes($row.find("td:eq(0)").text().trim())) {
      $row.addClass(className);
    }
  });
}
