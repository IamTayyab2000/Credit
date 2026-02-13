import SuperSaver from "./SuperSaver.js";
// Needed Code Dont Delete
$("#messageBox").hide();
$("#close_messageBox").click(() => {
  $("#messageBox").hide();
});
var picklist_id=$("#picklist_id");
var picklist_saleman=$("#saleman_name_selector");
var picklist_amount = $("#picklist_amount");
var picklist_return = $("#picklist_return");
var picklist_scheme = $("#picklist_scheme");
var picklist_credit = $("#picklist_credit");
var picklist_recovery = $("#picklist_recovery");
var picklist_date=$("#picklist_date");
function calculateTotalRecovery() {
  const amount = parseFloat(picklist_amount.val()) || 0;
  const returns = parseFloat(picklist_return.val()) || 0;
  const credit = calculateTotalCredit() || 0;
  const scheme = parseFloat(picklist_scheme.val()) || 0;

  return amount - (returns + credit + scheme);
}

picklist_amount.on("input", () => {
  if (picklist_amount.val() === "") {
    picklist_amount.val(0);
  }
  picklist_recovery.val(calculateTotalRecovery());
});

picklist_return.on("input", () => {
  if (picklist_return.val() === "") {
    picklist_return.val(0);
  }
  picklist_recovery.val(calculateTotalRecovery());
});

picklist_scheme.on("input", () => {
  
  picklist_recovery.val(calculateTotalRecovery());
});

const creditors_table = $("#creditors_table").DataTable({
  columns: [
    { data: "invoice_id", title: "Inv ID" },
    { data: "customer_id", title: "ID" },
    { data: "customer_name", title: "Name" },
    { data: "credit_amount", title: "Amount" },
    {
      data: null,
      title: "Action",
      render: function (data, type, row) {
        if (type === "display") {
          // Create a button for removing the row
          return '<button class="btn btn-danger btn-remove">Remove</button>';
        }
        return data;
      },
    },
  ],
});
let api = "functionality/allFunctionality.php";
var mySuperSaver = new SuperSaver(api);
var customer_list = "";

var customer_json = [];
// Ok So now only functionality
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

      // Clear existing options
      customer_data_list.empty();

      $.each(response, function (index, item) {
        customer_list = response;
        customer_data_list.append(
          $("<option>", {
            value: item.customer_id,
            text: item.customer_name,
          })
        );
      });
      console.log(response);
    });
});

$("#customer_id").on("input", () => {
  var inputValue = $("#customer_id").val();
  var selectedCustomer = customer_list.find(function (item) {
    return item.customer_id === inputValue.toUpperCase();
  });

  if (selectedCustomer) {
    $("#customer_name").val(selectedCustomer.customer_name);
  } else {
    $("#customer_name").val("");
  }
});
$("#btn_add_creditor_to_table").click(() => {
  let customer_id = $("#customer_id").val();
  let customer_name = $("#customer_name").val();
  let credit_amount = $("#credit_amount").val();
  let picklist_id = $("#picklist_id").val();
  let invoice_id = $("#customer_invoice_id").val();
  let invoice_date = $("#picklist_date").val();

  // Check if invoice_id already exists in customer_json
  if (
    customer_json.some((item) => item.invoice_id === invoice_id)
  ) {
    alert("Invoice ID already exists in the list.");
  } else if (
    picklist_id == "" ||
    credit_amount == "" ||
    customer_name == "" ||
    customer_id == "" ||
    invoice_id == "" ||
    invoice_date == ""
  ) {
    alert("Fill All Important Fields");
  } else {
    let data = {
      picklist_id: picklist_id,
      invoice_date: invoice_date,
      invoice_id: invoice_id,
      customer_id: customer_id,
      customer_name: customer_name,
      credit_amount: credit_amount,
    };
    customer_json.push(data);
    picklist_credit.val(calculateTotalCredit());
    picklist_recovery.val(calculateTotalRecovery());
    // Clear input fields
    $("#customer_id").val("");
    $("#customer_name").val("");
    $("#credit_amount").val("");
    $("#customer_invoice_id").val("");
  }
  
  console.log(customer_json);
  mySuperSaver.LoadDataTableFromJSON(creditors_table, customer_json);
});

$("#creditors_table").on("click", ".btn-remove", function () {
  console.log("Hello");
  const row = creditors_table.row($(this).closest("tr"));
  const rowData = row.data();

  // Find the index of the row data in the JSON array
  const index = customer_json.findIndex(
    (item) => item.customer_id === rowData.customer_id
  );

  // Remove the row data from the JSON array
  if (index !== -1) {
    customer_json.splice(index, 1);
    picklist_credit.val(calculateTotalCredit());
    picklist_recovery.val(calculateTotalRecovery());
  }

  // Remove the row from the DataTable
  row.remove().draw();
  console.log(customer_json);
});
function calculateTotalCredit() {
  let totalCredit = 0;
  customer_json.forEach((customer) => {
    totalCredit += parseFloat(customer.credit_amount || 0);
  });
  return totalCredit;
}
$("#btn_process_picklist").click(()=>{
  store_data_picklist();
})
function store_data_picklist(){
   let data={
    data:{
      function_to_call:'store_data_picklist',
      picklist_id:picklist_id.val(),
      picklist_saleman:picklist_saleman.val(),
      picklist_date:picklist_date.val(),
      picklist_amount:picklist_amount.val(),
      picklist_recovery:picklist_recovery.val(),
      picklist_credit:picklist_credit.val(),
      picklist_scheme:picklist_scheme.val(),
      picklist_return:picklist_return.val()
    }
   }
   mySuperSaver.performAjaxRequest(data).then((responce)=>{
        if(responce==1){
          store_data_bill();
        }
        else{
          alert('Picklest:'+responce);
        }
   }).catch((error)=>{
        console.log(error);
   })
}

function store_data_bill(){
  let data = {
    data: {
      function_to_call:'store_bill_data',
      customer_bills: encodeURIComponent(JSON.stringify(customer_json))
    },
    contentType: 'application/x-www-form-urlencoded'
  };
  mySuperSaver.performAjaxRequest(data).then((responce)=>{
    if(responce==1){
      store_bill_ledger();
    }
    else{
      alert('Bill Data:'+responce);
    }
  })
  
}
function store_bill_ledger(){
  let data = {
    data: {
      function_to_call:'store_bill_ledger',
      customer_bills: encodeURIComponent(JSON.stringify(customer_json))
    },
    contentType: 'application/x-www-form-urlencoded'
  };
  mySuperSaver.performAjaxRequest(data).then((responce)=>{
    if(responce==1){
      alert('Picklist Processed...');
      window.location.reload();
    }
    else{
      alert('Bill Ledger:'+responce);
    }
  })
}