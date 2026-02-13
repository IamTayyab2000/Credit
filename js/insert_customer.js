import SuperSaver from "./SuperSaver.js";
function btn_edit_customer(customerId) {
  console.log("Edit button clicked for customer ID:", customerId);
  // Rest of your code here
}
// Needed Code Dont Delete
let messageBox = $("#messageBox");
let messageBody = $("#messageBody");
messageBox.hide();
$("#close_messageBox").click(() => {
  messageBox.hide();
});
let api = "functionality/allFunctionality.php";
// ON LOAD
const mySuperSaver = new SuperSaver(api);
var customerTable = $("#customer_table").DataTable({
  columns: [
    { data: "customer_id" },
    { data: "customer_name" },
    { data: "sector_name" },
    { data: "saleman_name" },
    {
      data: null,
      render: function (data, type, row) {
        if (type === "display") {
          // Create the Edit button with cust_edit_id as a parameter
          const editButton =
            '<button class="btn btn-success btn-edit-customer" data-bs-toggle="modal" data-bs-target="#modal_edit_customers" data-id="' +
            data.customerId +
            '" data-name="' +
            data.customer_name +
            '" >Edit</button>';

          // Create the "See Ledger" button with cust_see_id as a parameter
          const seeLedgerButton =
            '<a href="see_customer_ledger.php?cust_id=' +
            data.customer_id +
            '" class="btn btn-success">See Ledger</a>';

          // Combine both buttons
          return editButton + " " + seeLedgerButton;
        }
        return data;
      },
    },
  ],
});
var salemanTable = $("#saleman_table").DataTable({
  columns: [{ data: "ID" }, { data: "NAME" }],
});
var sectorTable = $("#sector_table").DataTable({
  columns: [
    { data: "ID", title: "ID" },
    { data: "NAME", title: "Name" },
  ],
});
var routeTable = $("#route_table").DataTable({
  columns: [
    { data: "sector_name", title: "Sector" },
    { data: "saleman_name", title: "Sales Person" },
    { data: "day", title: "Day" },
  ],
});
mySuperSaver.LoadDataTable(routeTable, "load_route_data");
mySuperSaver.LoadDataTable(sectorTable, "load_sector_data");
mySuperSaver.LoadDataTable(salemanTable, "load_saleman_data");
mySuperSaver
  .performAjaxRequest({
    data: {
      function_to_call: "load_saleman_data",
    },
  })
  .then((responce) => {
    mySuperSaver.addOptionsToSelect("saleman_id_select", responce);
  })
  .catch((error) => {
    alert(error);
  });
function update_select_sector_instances() {
  mySuperSaver.LoadDataTable(routeTable, "load_route_data");
  mySuperSaver
    .performAjaxRequest({
      data: {
        function_to_call: "load_sector_data",
      },
    })
    .then((responce) => {
      mySuperSaver.addOptionsToSelect("to_update_sector_id_select", responce);
    })
    .catch((error) => {
      alert(error);
    });
  loadCustomerData();
  mySuperSaver
    .performAjaxRequest({
      data: {
        function_to_call: "load_sector_data",
      },
    })
    .then((responce) => {
      mySuperSaver.addOptionsToSelect("customer_sector_id_select", responce);
    })
    .catch((error) => {
      alert(error);
    });
  mySuperSaver
    .performAjaxRequest({
      data: {
        function_to_call: "load_sector_data",
      },
    })
    .then((responce) => {
      mySuperSaver.addOptionsToSelect("sector_id_select", responce);
    })
    .catch((error) => {
      alert(error);
    });
}
update_select_sector_instances();

$("#btn_add_route").click(() => {
  let sector_id = $("#sector_id_select").val();
  let saleman_id = $("#saleman_id_select").val();
  let day_value = $("#day_select").val();
  let data = {
    data: {
      function_to_call: "save_route_data",
      sector_id: sector_id,
      saleman_id: saleman_id,
      day: day_value,
    },
  };
  mySuperSaver
    .performAjaxRequest(data)
    .then((responce) => {
      if (responce == 1) {
        mySuperSaver.LoadDataTable(routeTable, "load_route_data");
      } else if (responce == 2) {
        mySuperSaver.LoadDataTable(routeTable, "load_route_data");
        console.log("Route Updated");
      } else {
        alert(responce);
      }
    })
    .catch((error) => {});
  loadCustomerData();
});
$("#btn_add_salesperson").click(() => {
  let saleman_name = $("#sales_person_name").val();
  console.log("Hello");
  let data = {
    data: {
      function_to_call: "save_saleman_data",
      saleman_name: saleman_name,
    },
  };
  mySuperSaver
    .performAjaxRequest(data)
    .then((responce) => {
      if (responce == 1) {
        let function_to_call = "load_saleman_data";
        mySuperSaver.LoadDataTable(salemanTable, function_to_call);
      }
    })
    .catch((error) => {
      alert(error);
    });
});
$("#customer_table").on("click", ".btn-edit-customer", function () {
  // Get the row containing the clicked button
  var row = $(this).closest("tr");

  // Extract data from the row as needed
  var id = row.find("td:eq(0)").text(); // Access the first column (index 0)
  var name = row.find("td:eq(1)").text(); // Access the second column (index 1)

  // Log the ID and Name (you can use these values for your modal)
  $("#edit_customer_id").val(id);
  $("#edit_customer_name").val(name);
});
$("#btn_update_customer").click(() => {
  var id = $("#edit_customer_id").val();
  var name = $("#edit_customer_name").val();
  mySuperSaver
    .performAjaxRequest({
      data: {
        function_to_call: "update_customer_info",
        id: id,
        name: name,
      },
    })
    .then((responce) => {
      if (responce == 1) {
        alert("Customer Updated");
        loadCustomerData();
      }
      else{
        messageBody.text(responce);
        messageBox.show();
      }
    })
    .catch((error) => {
      alert(error);
    });
});
$("#btn_add_sector").click(() => {
  let sector_name = $("#sector_name").val();
  console.log("Hello Sector");
  let data = {
    data: {
      function_to_call: "save_sector_data",
      sector_name: sector_name,
    },
  };
  mySuperSaver
    .performAjaxRequest(data)
    .then((responce) => {
      if (responce == 1) {
        let function_to_call = "load_sector_data";
        mySuperSaver.LoadDataTable(sectorTable, function_to_call);
        update_select_sector_instances();
      }
    })
    .catch((error) => {
      alert(error);
    });
});
$("#btn_update_sector").click(() => {
  let updated_sector_name = $("#update_sector_input").val();
  let selected_sector = $("#to_update_sector_id_select").val();
  let data = {
    data: {
      function_to_call: "update_sector",
      selected_sector: selected_sector,
      updated_sector_name: updated_sector_name,
    },
  };
  mySuperSaver
    .performAjaxRequest(data)
    .then((responce) => {
      if (responce == 1) {
        alert("Sector Updated");
        mySuperSaver.LoadDataTable(sectorTable, "load_sector_data");
        update_select_sector_instances();
      } else {
        alert(responce);
      }
    })
    .catch((error) => {});
});
$("#btn_add_customer").click(() => {
  let customer_id = $("#customer_id").val();
  let customer_name = $("#customer_name").val();
  let customer_route_id = $("#customer_sector_id_select").val();
  let data = {
    data: {
      function_to_call: "save_customer_data",
      customer_id: customer_id,
      customer_name: customer_name,
      customer_route_id: customer_route_id,
    },
  };
  mySuperSaver
    .performAjaxRequest(data)
    .then((responce) => {
      if (responce == 1) {
        loadCustomerData();
      } else {
        messageBody.html(responce);
      }
    })
    .catch((error) => {
      messageBox.text(error);
    });
});
loadCustomerData();
function loadCustomerData() {
  mySuperSaver.LoadDataTable(customerTable, "Load_customer_table");
}

// CSV Import Handler
$("#btn_import_csv").click(() => {
  let fileInput = $("#csv_file_input")[0];
  
  // Check if a file is selected
  if (!fileInput.files || fileInput.files.length === 0) {
    messageBody.text("Please select a CSV file");
    messageBox.removeClass("bg-success bg-danger").addClass("bg-danger");
    messageBox.show();
    return;
  }

  let file = fileInput.files[0];

  // Validate file size (max 5MB)
  if (file.size > 5 * 1024 * 1024) {
    messageBody.text("File size exceeds 5MB limit");
    messageBox.removeClass("bg-success bg-danger").addClass("bg-danger");
    messageBox.show();
    return;
  }

  // Show loading screen and hide content
  $("#csv_loading_screen").show();
  $("#csv_content").hide();

  // Create FormData object
  let formData = new FormData();
  formData.append('csv_file', file);

  // Send file to server
  $.ajax({
    url: 'process_csv_import.php',
    type: 'POST',
    data: formData,
    contentType: false,
    processData: false,
    dataType: 'json',
    success: function(response) {
      // Hide loading screen
      $("#csv_loading_screen").hide();
      $("#csv_content").show();

      if (response.status === 1) {
        let message = `<strong>Migration Completed Successfully!</strong><br/><br/>
                      <strong>Records Processed:</strong><br/>
                      Customers Inserted: ${response.inserted}<br/>
                      Failed: ${response.failed}<br/>
                      Total: ${response.total_processed}<br/><br/>
                      <strong>New Entities Created:</strong><br/>
                      Salesmen: ${response.salesmen_created}<br/>
                      Sectors/Areas: ${response.sectors_created}<br/>`;
        
        if (response.errors && response.errors.length > 0) {
          message += '<br/><strong>⚠️ Issues:</strong><br/>' + response.errors.join('<br/>');
        }
        
        messageBody.html(message);
        messageBox.removeClass("bg-success bg-danger").addClass("bg-success");
        messageBox.show();
        
        // Reset file input and reload customer table
        fileInput.value = '';
        loadCustomerData();
        
        // Close modal after 2 seconds
        setTimeout(() => {
          let modal = bootstrap.Modal.getInstance(document.getElementById('modal_import_from_excel'));
          if (modal) modal.hide();
          messageBox.hide();
        }, 2000);
      } else {
        messageBody.text('Migration failed: ' + response.message);
        messageBox.removeClass("bg-success bg-danger").addClass("bg-danger");
        messageBox.show();
      }
    },
    error: function(xhr, status, error) {
      // Hide loading screen
      $("#csv_loading_screen").hide();
      $("#csv_content").show();

      messageBody.text('Error: ' + error);
      messageBox.removeClass("bg-success bg-danger").addClass("bg-danger");
      messageBox.show();
    }
  });
});

// Reset modal content when it's hidden
document.getElementById('modal_import_from_excel').addEventListener('hidden.bs.modal', function () {
  $("#csv_loading_screen").hide();
  $("#csv_content").show();
  $("#csv_file_input")[0].value = '';
});
