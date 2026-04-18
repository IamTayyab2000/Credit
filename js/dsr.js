import SuperSaver from "./SuperSaver.js";
// Needed Code Dont Delete
$("#messageBox").hide();
$("#close_messageBox").click(() => {
  $("#messageBox").hide();
});

// Picklist Header Selectors
var picklist_id = $("#picklist_id");
var picklist_saleman = $("#saleman_name_selector");
var picklist_amount = $("#picklist_amount");
var picklist_return = $("#picklist_return");
var picklist_scheme = $("#picklist_scheme");
var picklist_credit = $("#picklist_credit");
var picklist_recovery = $("#picklist_recovery");
var picklist_date = $("#picklist_date");

// Per-Bill Entry Selectors
var bill_total = $("#bill_total");
var bill_recovered = $("#bill_recovered");
var bill_returned = $("#bill_returned");
var bill_scheme = $("#bill_scheme");
var bill_credit_val = $("#credit_amount");
var bill_invoice_id = $("#customer_invoice_id");
var bill_customer_id = $("#customer_id");
var bill_customer_name = $("#customer_name");

// Auto-calculation for per-bill credit
function updateBillCredit() {
  const total = parseFloat(bill_total.val()) || 0;
  const recovered = parseFloat(bill_recovered.val()) || 0;
  const returned = parseFloat(bill_returned.val()) || 0;
  const scheme = parseFloat(bill_scheme.val()) || 0;

  const credit = total - (recovered + returned + scheme);
  bill_credit_val.val(credit);
}

// Add listeners to per-bill inputs
[bill_total, bill_recovered, bill_returned, bill_scheme].forEach(el => {
  el.on("input", updateBillCredit);
});

function calculatePicklistTotals() {
  let totalAmount = 0;
  let totalReturns = 0;
  let totalScheme = 0;
  let totalCredit = 0;
  let totalRecovery = 0;

  customer_json.forEach((bill) => {
    totalAmount += parseFloat(bill.bill_total || 0);
    totalReturns += parseFloat(bill.bill_returned || 0);
    totalScheme += parseFloat(bill.bill_scheme || 0);
    totalCredit += parseFloat(bill.credit_amount || 0);
    totalRecovery += parseFloat(bill.bill_recovered || 0);
  });

  picklist_amount.val(totalAmount);
  picklist_return.val(totalReturns);
  picklist_scheme.val(totalScheme);
  picklist_credit.val(totalCredit);
  picklist_recovery.val(totalRecovery);
}

const creditors_table = $("#creditors_table").DataTable({
  columns: [
    { data: "invoice_id", title: "Inv ID" },
    { data: "customer_id", title: "ID" },
    { data: "customer_name", title: "Name" },
    { data: "bill_total", title: "Total", className: "text-end" },
    { data: "bill_recovered", title: "Recovered", className: "text-end" },
    { data: "bill_returned", title: "Returned", className: "text-end" },
    { data: "bill_scheme", title: "Scheme", className: "text-end" },
    { data: "credit_amount", title: "Credit", className: "text-end font-weight-bold" },
    { data: "prev_balance", title: "Prev. Balance", className: "text-end text-danger" },
    {
      data: null,
      title: "Action",
      className: "text-center",
      render: function (data, type, row) {
        if (type === "display") {
          return '<button class="btn btn-sm btn-danger btn-remove"><i class="small">Remove</i></button>';
        }
        return data;
      },
    },
  ],
  rowCallback: function(row, data) {
    if (data.prev_balance && parseFloat(data.prev_balance) > 0) {
      $(row).addClass('table-danger');
    }
  }
});

let api = "functionality/allFunctionality.php";
var mySuperSaver = new SuperSaver(api);
var customer_list = "";
var customer_json = [];

// Load Salesman Data initially
LoadSalemanData();

function LoadSalemanData() {
  mySuperSaver
    .performAjaxRequest({
      data: { function_to_call: "load_saleman_data" },
    })
    .then((responce) => {
      mySuperSaver.addOptionsToSelect("saleman_name_selector", responce);
    })
    .catch((error) => {
      console.log(error);
    });
}

$("#saleman_name_selector").on("change", () => {
  let saleman_id = $("#saleman_name_selector").val();
  mySuperSaver
    .performAjaxRequest({
      data: {
        function_to_call: "Load_customer_table_by_saleman",
        saleman_id: saleman_id,
      },
    })
    .then((response) => {
      var customer_data_list = $("#customer_data_list");
      customer_data_list.empty();
      customer_list = response;

      $.each(response, function (index, item) {
        customer_data_list.append(
          $("<option>", {
            value: item.customer_id,
            text: item.customer_name,
          })
        );
      });
    });
});

$("#customer_id").on("input", () => {
  var inputValue = $("#customer_id").val();
  var selectedCustomer = customer_list.find(function (item) {
    return item.customer_id === inputValue;
  });

  if (selectedCustomer) {
    $("#customer_name").val(selectedCustomer.customer_name);
  } else {
    $("#customer_name").val("");
  }
});


let pendingAddBillData = null;

function getCurrentBillData() {
  return {
    picklist_id: picklist_id.val(),
    invoice_date: picklist_date.val(),
    invoice_id: bill_invoice_id.val(),
    customer_id: bill_customer_id.val(),
    customer_name: bill_customer_name.val(),
    bill_total: parseFloat(bill_total.val()) || 0,
    bill_recovered: parseFloat(bill_recovered.val()) || 0,
    bill_returned: parseFloat(bill_returned.val()) || 0,
    bill_scheme: parseFloat(bill_scheme.val()) || 0,
    credit_amount: parseFloat(bill_credit_val.val()) || 0,
  };
}

function tryAddCreditBill(billData) {
  let c_id = billData.customer_id;
  let c_name = billData.customer_name;
  let b_total = billData.bill_total;
  let b_recovered = billData.bill_recovered;
  let b_returned = billData.bill_returned;
  let b_scheme = billData.bill_scheme;
  let cr_amount = billData.credit_amount;
  let p_id = billData.picklist_id;
  let inv_id = billData.invoice_id;
  let inv_date = billData.invoice_date;

  // Only allow adding if customer is in the filtered list
  var selectedCustomer = customer_list.find(function (item) {
    return item.customer_id === c_id;
  });
  if (!selectedCustomer) {
    alert("Selected customer is not valid for the chosen salesman.");
    bill_customer_id.val("");
    bill_customer_name.val("");
    return;
  }

  if (customer_json.some((item) => item.invoice_id === inv_id)) {
    alert("Invoice ID already exists in the list.");
  } else if (
    p_id == "" ||
    inv_id == "" ||
    c_id == "" ||
    inv_date == "" ||
    b_total <= 0
  ) {
    alert("Please fill all mandatory fields (ID, Date, Invoice, Customer, Total)");
  } else {
    customer_json.push(billData);
    calculatePicklistTotals();

    // Clear per-bill input fields
    bill_invoice_id.val("");
    bill_customer_id.val("");
    bill_customer_name.val("");
    bill_total.val("");
    bill_recovered.val("");
    bill_returned.val("");
    bill_scheme.val("");
    bill_credit_val.val("");

    mySuperSaver.LoadDataTableFromJSON(creditors_table, customer_json);
  }
}


$("#btn_add_creditor_to_table").click(() => {
  let c_id = bill_customer_id.val();
  if (!c_id) {
    alert("Please enter/select a customer ID first.");
    return;
  }
  // Check for open credit bills via AJAX
  const billData = getCurrentBillData();
  $.get('check_open_credit.php', { customer_id: c_id }, function(response) {
    let prev_balance = 0;
    if (response.has_open_credit && response.open_bills.length > 0) {
      prev_balance = response.open_bills.reduce((sum, bill) => sum + parseFloat(bill.outstanding), 0);
    }
    billData.prev_balance = prev_balance > 0 ? prev_balance.toFixed(2) : '';
    tryAddCreditBill(billData);
  }, 'json');
});

// Modal proceed button
$("#proceedAddCreditBill").click(function() {
  $("#openCreditModal").modal('hide');
  if (pendingAddBillData) {
    tryAddCreditBill(pendingAddBillData);
    pendingAddBillData = null;
  }
});

$("#creditors_table").on("click", ".btn-remove", function () {
  const row = creditors_table.row($(this).closest("tr"));
  const rowData = row.data();

  const index = customer_json.findIndex(
    (item) => item.invoice_id === rowData.invoice_id
  );

  if (index !== -1) {
    customer_json.splice(index, 1);
    calculatePicklistTotals();
  }

  row.remove().draw();
});

$("#btn_process_picklist").click(() => {
  if (customer_json.length === 0) {
    alert("Add at least one bill to the list.");
    return;
  }
  store_data_picklist();
});

function store_data_picklist() {
  let data = {
    data: {
      function_to_call: 'store_data_picklist',
      picklist_id: picklist_id.val(),
      picklist_saleman: picklist_saleman.val(),
      picklist_date: picklist_date.val(),
      picklist_amount: picklist_amount.val(),
      picklist_recovery: picklist_recovery.val(),
      picklist_credit: picklist_credit.val(),
      picklist_scheme: picklist_scheme.val(),
      picklist_return: picklist_return.val()
    }
  };
  mySuperSaver.performAjaxRequest(data).then((responce) => {
    if (responce == 1) {
      store_data_bill();
    } else {
      alert('Picklist Error: ' + responce);
    }
  }).catch((error) => {
    console.log(error);
  });
}

function store_data_bill() {
  let data = {
    data: {
      function_to_call: 'store_bill_data',
      customer_bills: encodeURIComponent(JSON.stringify(customer_json))
    },
    contentType: 'application/x-www-form-urlencoded'
  };
  mySuperSaver.performAjaxRequest(data).then((responce) => {
    if (responce == 1) {
      store_bill_ledger();
    } else {
      alert('Bill Data Error: ' + responce);
    }
  });
}

function store_bill_ledger() {
  let data = {
    data: {
      function_to_call: 'store_bill_ledger',
      customer_bills: encodeURIComponent(JSON.stringify(customer_json))
    },
    contentType: 'application/x-www-form-urlencoded'
  };
  mySuperSaver.performAjaxRequest(data).then((responce) => {
    if (responce == 1) {
      alert('DSR Picklist Processed Successfully!');
      window.location.reload();
    } else {
      alert('Bill Ledger Error: ' + responce);
    }
  });
}