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
$(".btn-recovery").on("input", function () {
  // Find the parent row of the current .btn-recovery element
  var $row = $(this).closest("tr");

  // Get the Amount value from the row
  var amountValue = parseFloat($row.find("td:eq(4)").text());

  // Get the selected value from the corresponding .selc-status element
  var selectedStatus = $row.find(".selc-status");

  // Get the current value of .btn-recovery element
  var recoveryValue = parseFloat($(this).val());

  // Check if recoveryValue is NaN or empty, and set it to zero in that case
  if (isNaN(recoveryValue) || $(this).val() === "") {
    recoveryValue = 0;
  }

  // Calculate the remaining value based on the selectedStatus
  var remainingValue;
  remainingValue = amountValue - recoveryValue;
  if (remainingValue <= 0) {
    var newOption = $("<option>").val("Nill").text("Nill");

    // Append the new option to the select element
    $("#selectedStatus").append(newOption);
    selectedStatus.val("Nill");
    selectedStatus.prop("disabled", true); // Change "readonly" to "disabled"
  }
  if (remainingValue > 0) {
    selectedStatus.prop("disabled", false); // Change "readonly" to "disabled"
    $("#selectedStatus option[value='Nill']").remove();
    selectedStatus.val("0");
  }

  // Update the corresponding .txt_remaining element in the same row
  $row.find(".txt_remaining").text(remainingValue);
  updateTotalValues();
});

updateTotalValues();
function updateTotalValues() {
  var totalRecovered = 0;
  var totalRemaining = 0;

  // Loop through each row in the table body
  $("#recovery_table tbody tr").each(function () {
    var recoveredValue = parseFloat($(this).find(".btn-recovery").val()) || 0;
    var remainingValue = parseFloat($(this).find(".txt_remaining").text());

    // Check if remainingValue is NaN, and initialize it to 0 if NaN
    if (isNaN(remainingValue)) {
      remainingValue = 0;
    }

    // Update totalRecovered and totalRemaining
    totalRecovered += recoveredValue;
    totalRemaining += remainingValue;
  });

  // Update the total values in the corresponding elements
  $("#total_recovery").text(totalRecovered.toFixed(2));
  $("#total_remaining").text(totalRemaining.toFixed(2));
}
function extractDataAndStoreInArray() {
  var recovery_sheet_array = [];

  // Loop through each row in the table body, excluding the last one
  $("#recovery_table tbody tr:not(:last)").each(function () {
    var bill_id = $(this).find("td:eq(0)").text();
    var date = $(this).find("td:eq(1)").text();
    var days = parseInt($(this).find("td:eq(2)").text());
    var shop = $(this).find("td:eq(3)").text();
    var amount = parseFloat($(this).find("td:eq(4)").text());
    var recovered = parseFloat($(this).find(".btn-recovery").val()) || 0;
    var remaining = parseFloat($(this).find(".txt_remaining").text());
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
      Recovered: recovered != NaN ? recovered : 0,
      Remaining: remaining != NaN ? remaining : 0,
      Status: remaining === 0 ? "Nill" : status,
    };
    // console.log(rowData);
    recovery_sheet_array.push(rowData);
  });

  // Now, the recovery_sheet_array contains all the rows' data except the last one
  return recovery_sheet_array;
}

// Call the function to extract and store the data

$("#btn-process").click(() => {
  let reovery_sheet = extractDataAndStoreInArray();
  var currentURL = window.location.href;

  // Create a URL object
  var url = new URL(currentURL);

  // Get the value of the rec_id parameter
  let recId = url.searchParams.get("rec_id");
  $.each(reovery_sheet, function(index, element) {
    element.recID = recId; // Push the newItem to each element
  });
  console.log(reovery_sheet);

  let returns = [];
  let nill = [];
  let BF = [];

  for (const item of reovery_sheet) {
    if (item.Remaining < 0 || item.Status === "0") {
      give_bad_bills();
      return false;
    } else if (item.Remaining > 0 && item.Status === "Nill") {
      give_bad_bills();
      return false;
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

  if (nill.length > 0) {
    process_nill(nill);
    alert('Processed...')
  }
  if (returns.length > 0) {
    process_return(returns);
    alert('Processed...');
  }
  if (BF.length > 0) {
    process_forward(BF)
      .then(() => {
        return getSalemanID();
      })
      .then((saleman_id) => {
        console.log('Done 1...');
        return save_rec_sheet_wo_merger(saleman_id); // Return a Promise here
      })
      .then((response) => {
        let bills = [];
        // More logic
        for (const bill of BF) {
          let newBill = {
            "bill_id": bill.ID,
            "bill_amount": bill.Remaining
          };
          bills.push(newBill);
        }
        console.log('Done 2...');
        return save_rec_detail_wo_merger(response, bills); // Return a Promise here
      })
      .then((response) => {
        console.log("Done 3...:", response);
        let recId = response;
        // Final processing
        alert("Recovery Sheet Processed\n New Recovery Sheet for BF bills is:" + recId);
         // Include it here as the final step
      })
      .catch((error) => {
        // Handle errors here
      });
      
  }
  updateRecoverySheetDetails();
  //window.close();
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
  return mySuperSaver.performAjaxRequest(data).catch();
}
function process_return(returns) {
  let data = {
    data: {
      function_to_call: "process_return_bills",
      customer_bills: encodeURIComponent(JSON.stringify(returns)),
    },
    contentType: "application/x-www-form-urlencoded",
  };
  return mySuperSaver.performAjaxRequest(data).catch();
}
function process_nill(nill) {
  let data = {
    data: {
      function_to_call: "process_nill_bills",
      customer_bills: encodeURIComponent(JSON.stringify(nill)),
    },
    contentType: "application/x-www-form-urlencoded",
  };
  return mySuperSaver.performAjaxRequest(data).catch();
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
      data:{
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
  let id=saleman_id[0].recovery_sheet_saleman_id;
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
  var currentURL = window.location.href;

  // Create a URL object
  var url = new URL(currentURL);

  // Get the value of the rec_id parameter
  var recId = url.searchParams.get("rec_id");

  // Check if recId is not null or empty
  
    // recId contains the value of the rec_id parameter
    let data = {
      data:{
        function_to_call: "update_recovery_sheet_header",
        recID: recId,
      }
    };
  
  mySuperSaver.performAjaxRequest(data).then((response)=>{
    if(response){
      let bills=extractDataAndStoreInArray();
      let bill_ledger=[];
      let refID=recId;
      let bill_id='';
      let bill_amount=0;
      let recived_amount=0;
      let remaining_amount=0;
      let bill_status='';
      for(const bill of bills){
          bill_id=bill.ID;
          bill_amount=bill.Amount;
          recived_amount=bill.Recovered;
          remaining_amount=bill.Remaining;
          bill_status=bill.Status=='Return' ? 'INFILE':bill.Status;
          let new_data={
            "ref_id":refID,
            "bill_id":bill_id,
            "bill_amount":bill_amount,
            "recived_amount":recived_amount,
            "remaining_amount":remaining_amount,
            "bill_status":bill_status
          }
          bill_ledger.push(new_data);
      }
      let data = {
        data: {
          function_to_call: "save_bill_ledger",
          bill_ledger: encodeURIComponent(JSON.stringify(bill_ledger)),
        },
        contentType: "application/x-www-form-urlencoded",
      };
      mySuperSaver.performAjaxRequest(data).then((response)=>{
            if(response){
              console.log("Done 4...");
              
            }
      }).catch((error)=>{

      })
    }
    else{
      console.error(response);
    }
  })
}
