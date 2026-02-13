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
  var recovery_sheet_array = [];

  // Loop through each row in the table body
  $("#recovery_table tbody tr").each(function () {
    var bill_id = $(this).find("td:eq(0)").text().trim();
    var date = $(this).find("td:eq(1)").text();
    var days = parseInt($(this).find("td:eq(2)").text());
    var shop = $(this).find("td:eq(3)").text();
    var amount = parseFloat($(this).find("td:eq(4)").data("amount")) || 0;
    var recovered = parseFloat($(this).find(".btn-recovery").val()) || 0;
    var returned = parseFloat($(this).find(".btn-returned").val()) || 0;
    var remaining = amount - (recovered + returned);
    var status = $(this).find(".selc-status").val();

    if (bill_id == "") {
      return true;
    }
    // Create an object for each row and push it to the array
    var rowData = {
      ID: bill_id,
      DATE: date,
      Days: days,
      Shop: shop,
      Amount: amount,
      Recovered: recovered,
      Returned: returned,
      Remaining: remaining,
      Status: remaining === 0 && status !== "Return" ? "Nill" : status,
    };
    // console.log(rowData);
    recovery_sheet_array.push(rowData);
  });

  // Now, the recovery_sheet_array contains all the rows' data except the last one
  return recovery_sheet_array;
}

// Call the function to extract and store the data

$("#btn-process").click(async () => {
  let recovery_sheet = extractDataAndStoreInArray();
  const currentURL = window.location.href;
  const url = new URL(currentURL);
  const recId = url.searchParams.get("rec_id");

  if (!recId) {
    alert("Recovery ID not found in URL");
    return;
  }

  // Inject recID into each element
  recovery_sheet.forEach(element => {
    element.recID = recId;
  });

  let returns = [];
  let nill = [];
  let BF = [];

  for (const item of recovery_sheet) {
    if (item.Remaining < 0 || item.Status === "0") {
      give_bad_bills();
      alert("Please fix bills highlighted in red/yellow before processing.");
      return;
    } else if (item.Remaining > 0 && item.Status === "Nill") {
      give_bad_bills();
      alert("Bills with remaining balance cannot be marked as Nill.");
      return;
    } else {
      if (item.Status === "Return") {
        returns.push(item);
      } else if (item.Status === "BF") {
        BF.push(item);
      } else if (item.Status === "Nill") {
        nill.push(item);
      }
    }
  }

  try {
    let processPromises = [];

    if (nill.length > 0) processPromises.push(process_nill(nill));
    if (returns.length > 0) processPromises.push(process_return(returns));

    // Wait for Nill and Return processing
    await Promise.all(processPromises);

    // Handle BF bills (creates new recovery sheet, so we do it sequentially if needed)
    if (BF.length > 0) {
      await process_forward(BF);
      const saleman_id_data = await getSalemanID();
      const new_rec_id = await save_rec_sheet_wo_merger(saleman_id_data);

      let bf_details = BF.map(bill => ({
        "bill_id": bill.ID,
        "bill_amount": bill.Remaining
      }));

      await save_rec_detail_wo_merger(new_rec_id, bf_details);
      alert("Forwarded BF bills to new Recovery Sheet: " + new_rec_id);
    }

    // Finalize the current sheet and ledger
    await updateRecoverySheetDetails();

    alert("Recovery Sheet processed and balances updated successfully.");
    window.location.reload();

  } catch (error) {
    console.error("Processing failed:", error);
    alert("An error occurred during processing. Check console for details.");
  }
});


function give_bad_bills() {
  let recovery_sheet = extractDataAndStoreInArray();
  let bad_status_bill = [];
  let bad_remaining_bill = [];
  let bad_both_bill = [];

  // Remove any existing highlight classes
  $("#recovery_table tbody tr").removeClass(
    "table-danger table-warning table-info"
  );

  for (const item of recovery_sheet) {
    if (item["Status"] === "0" || item["Remaining"] < 0) {
      if (item["Status"] === "0") {
        bad_status_bill.push(item["ID"]);
      }
      if (item["Remaining"] < 0) {
        bad_remaining_bill.push(item["ID"]);
      }
    }
    if (item["Status"] === "Nill" && item["Remaining"] > 0) {
      bad_both_bill.push(item["ID"]);
    }
  }

  highlightRowsWithBillIDs(bad_status_bill, "table-danger");
  highlightRowsWithBillIDs(bad_remaining_bill, "table-warning");
  highlightRowsWithBillIDs(bad_both_bill, "table-info");
}

function highlightRowsWithBillIDs(billIDs, myclass) {
  // Loop through each row in the table body
  $("#recovery_table tbody tr").each(function () {
    var row = $(this);
    var currentBillID = row.find("td:eq(0)").text();

    // Check if the current row's Bill ID is in the provided list
    if (billIDs.includes(currentBillID)) {
      // If it's in the list, add the specified class to highlight the row
      row.addClass(myclass);
    }
  });
}
function process_forward(BF) {
  let data = {
    data: {
      function_to_call: "process_BF_bills",
      customer_bills: encodeURIComponent(JSON.stringify(BF)),
    },
    contentType: "application/x-www-form-urlencoded",
  };
  return mySuperSaver.performAjaxRequest(data).then((response) => {
    if (response !== true) {
      throw new Error("Failed to process Forward (BF) bills.");
    }
    return response;
  });
}

function process_return(returns) {
  let bills = returns;
  let data = {
    data: {
      function_to_call: "process_return_bills",
      customer_bills: encodeURIComponent(JSON.stringify(bills)),
    },
    contentType: "application/x-www-form-urlencoded",
  };
  return mySuperSaver.performAjaxRequest(data).then((response) => {
    if (response !== true) {
      throw new Error("Failed to process Return bills.");
    }
    return response;
  });
}

function process_nill(nill) {
  let bills = nill;
  let data = {
    data: {
      function_to_call: "process_nill_bills",
      customer_bills: encodeURIComponent(JSON.stringify(bills)),
    },
    contentType: "application/x-www-form-urlencoded",
  };
  return mySuperSaver.performAjaxRequest(data).then((response) => {
    if (response !== true) {
      throw new Error("Failed to process Nill bills.");
    }
    return response;
  });
}
function getSalemanID() {
  var currentURL = window.location.href;

  // Create a URL object
  var url = new URL(currentURL);

  // Get the value of the rec_id parameter
  var recId = url.searchParams.get("rec_id");

  // Check if recId is not null or empty
  if (recId) {
    // recId contains the value of the rec_id parameter
    let data = {
      data: {
        function_to_call: "get_saleman_from_recovery_sheet",
        recID: recId,
      }
    };

    // Return the Promise from the AJAX request
    return mySuperSaver.performAjaxRequest(data).catch((error) => {
      console.error(error);
      throw error; // Re-throw the error to propagate it to the Promise chain
    });
  } else {
    // Return a rejected Promise indicating the error
    return Promise.reject("rec_id not found in the URL");
  }
}

function save_rec_sheet_wo_merger(saleman_id) {
  let id = saleman_id[0].recovery_sheet_saleman_id;
  let data = {
    data: {
      function_to_call: "store_recovery_sheet",
      saleman: id,
    },
  };

  // Return the Promise to be resolved later
  return mySuperSaver.performAjaxRequest(data).catch((error) => {
    console.error("Store Recovery Sheet: ", error);
  });
}
function save_rec_detail_wo_merger(response, BF) {
  let selectedBills = BF;
  let recovery_sheet_id = response;
  selectedBills.forEach((item) => {
    item.recovery_sheet_id = response;
  });
  const recovery_sheet_detail = selectedBills;

  return new Promise((resolve, reject) => {
    let data = {
      data: {
        function_to_call: "save_rec_details",
        recovery_sheet_detail: encodeURIComponent(JSON.stringify(recovery_sheet_detail)),
      },
      contentType: "application/x-www-form-urlencoded",
    };

    mySuperSaver
      .performAjaxRequest(data)
      .then((ajaxResponse) => {
        if (ajaxResponse == 1) {
          resolve(recovery_sheet_id); // Resolve the promise with recovery_sheet_id
        } else {
          console.error(ajaxResponse);
          reject("Error while saving recovery details");
        }
      })
      .catch((error) => {
        console.error("Store Recovery Details: ", error);
        reject(error);
      });
  });
}

function updateRecoverySheetDetails() {
  const url = new URL(window.location.href);
  const recId = url.searchParams.get("rec_id");

  let data = {
    data: {
      function_to_call: "update_recovery_sheet_header",
      recID: recId,
    }
  };

  return mySuperSaver.performAjaxRequest(data).then((response) => {
    if (response) {
      let bills = extractDataAndStoreInArray();
      let bill_ledger = [];

      for (const bill of bills) {
        let bill_status = bill.Status;
        // Map status to DB ENUM (bill_ledger table): NILL, BF, INFILE
        if (bill_status === "Return") bill_status = "INFILE";
        else if (bill_status === "Nill") bill_status = "NILL";
        else if (bill_status === "BF") bill_status = "BF";
        else bill_status = "INFILE"; // Default for active/0 or others

        bill_ledger.push({
          "ref_id": recId,
          "bill_id": bill.ID,
          "bill_amount": bill.Amount,
          "recived_amount": bill.Recovered,
          "return_amount": bill.Returned || 0,
          "remaining_amount": bill.Remaining,
          "bill_status": bill_status
        });
      }

      let ledgerData = {
        data: {
          function_to_call: "save_bill_ledger",
          bill_ledger: encodeURIComponent(JSON.stringify(bill_ledger)),
        },
        contentType: "application/x-www-form-urlencoded",
      };

      return mySuperSaver.performAjaxRequest(ledgerData).then((ledgerResponse) => {
        if (ledgerResponse === true) {
          return true;
        } else {
          throw new Error("Failed to save bill ledger entries.");
        }
      });
    } else {
      throw new Error("Failed to update recovery sheet header");
    }
  });
}
