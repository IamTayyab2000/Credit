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
const customerRoutesPromise = mySuperSaver
  .performAjaxRequest({
    data: {
      function_to_call: "load_customer_routes",
    },
  })
  .then((routes) => {
    mySuperSaver.addOptionsToSelect("edit_customer_route_id_select", routes);
  });
customerRoutesPromise.catch((error) => {
  messageBody.text(error.message);
  messageBox.show();
});
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
          const editButton =
            '<button class="btn btn-success btn-edit-customer" data-bs-toggle="modal" data-bs-target="#modal_edit_customers">Edit</button>';

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
  const customer = customerTable.row($(this).closest("tr")).data();
  if (!customer) {
    messageBody.text("Customer details could not be loaded. Refresh the page and try again.");
    messageBox.show();
    return;
  }

  $("#edit_customer_id").val(customer.customer_id);
  $("#edit_customer_name").val(customer.customer_name || "");
  $("#edit_customer_phone").val(customer.phone || "");
  customerRoutesPromise
    .then(() => {
      $("#edit_customer_route_id_select").val(String(customer.customer_route_id || "default"));
    })
    .catch((error) => {
      messageBody.text(error.message);
      messageBox.show();
    });
});
$("#btn_update_customer").click(() => {
  const button = $("#btn_update_customer");
  const id = $("#edit_customer_id").val();
  const name = $("#edit_customer_name").val().trim();
  const phone = $("#edit_customer_phone").val().trim();
  const route_id = $("#edit_customer_route_id_select").val();
  const correction_password = $("#edit_customer_correction_password").val();
  const csrf_token = document.querySelector('meta[name="recovery-correction-csrf"]')?.content || "";
  if (!name || !route_id || route_id === "default") {
    messageBody.text("Customer name and assigned route are required.");
    messageBox.show();
    return;
  }
  if (!correction_password) {
    messageBody.text("Enter the correction password to save customer changes.");
    messageBox.show();
    return;
  }
  if (!csrf_token) {
    messageBody.text("The request token is missing. Reload the customer page and try again.");
    messageBox.show();
    return;
  }

  button.prop("disabled", true).text("Saving...");
  mySuperSaver
    .performAjaxRequest({
      data: {
        function_to_call: "update_customer_info",
        id: id,
        name: name,
        phone: phone,
        route_id: route_id,
        correction_password: correction_password,
        csrf_token: csrf_token,
      },
    })
    .then((responce) => {
      if (responce == 1) {
        messageBox.hide();
        bootstrap.Modal.getOrCreateInstance(document.getElementById("modal_edit_customers")).hide();
        loadCustomerData();
      }
      else{
        messageBody.text(responce.message || responce);
        messageBox.show();
      }
    })
    .catch((error) => {
      messageBody.text(error.message || "Customer details could not be saved.");
      messageBox.show();
    })
    .finally(() => {
      $("#edit_customer_correction_password").val("");
      button.prop("disabled", false).text("Save Changes");
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
  let customer_phone = $("#customer_phone").val();
  let customer_route_id = $("#customer_sector_id_select").val();
  let data = {
    data: {
      function_to_call: "save_customer_data",
      customer_id: customer_id,
      customer_name: customer_name,
      customer_phone: customer_phone,
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

const legacyCsvFields = [
  { key: "bill_id", label: "Bill ID", required: true, aliases: ["billid", "legacybillid", "invoiceid", "invoiceno", "billno"] },
  { key: "customer_id", label: "Customer ID", required: true, aliases: ["customerid", "custid", "customercode", "shopid"] },
  { key: "customer_name", label: "Customer Name", required: true, aliases: ["customername", "name", "shopname", "storename"] },
  { key: "area_address", label: "Area", required: true, aliases: ["areaaddress", "area", "areaname", "sector", "sectorname", "address", "route"] },
  { key: "salesman_name", label: "Salesman", required: true, aliases: ["salesmanname", "salesman", "salemanname", "saleman", "salesperson"] },
  { key: "bill_amount", label: "Bill Amount", required: true, numeric: true, aliases: ["billamount", "amount", "billtotal", "total"] },
  { key: "remaining_credit", label: "Remaining Credit", required: true, numeric: true, aliases: ["remainingcredit", "remaining", "outstanding", "balance", "credit"] },
  { key: "bill_date", label: "Bill Date", required: true, aliases: ["billdate", "date", "invoicedate"] },
  { key: "phone", label: "Phone", aliases: ["phone", "phonenumber", "mobile", "mobilenumber", "contact"] }
];
let legacyCsvHeaders = [];
let legacyCsvRows = [];
let legacyCsvPreviewRows = [];

const bulkRenameCodeAliases = ["customerid", "customercode", "custid", "storecode", "shopcode", "id"];
const bulkRenameNameAliases = ["newcustomername", "newname", "customername", "shopname", "storename", "name"];
let bulkRenameWorkbook = null;
let bulkRenameHeaders = [];
let bulkRenameSourceRows = [];
let bulkRenamePreviewRows = [];

function normalizeBulkRenameHeader(value) {
  return String(value || "").trim().toLowerCase().replace(/[^a-z0-9]/g, "");
}

function setBulkRenameStage(stage) {
  $("#bulk_rename_upload_stage").toggleClass("d-none", stage !== "upload");
  $("#bulk_rename_mapping_stage").toggleClass("d-none", stage !== "mapping");
  $("#bulk_rename_preview_stage").toggleClass("d-none", stage !== "preview");
}

function showBulkRenameMessage(message, success = false) {
  $("#bulk_rename_message")
    .text(message)
    .removeClass("d-none alert-danger alert-success")
    .addClass(success ? "alert-success" : "alert-danger");
}

function loadBulkRenameWorksheet() {
  const sheet = bulkRenameWorkbook?.Sheets[$("#bulk_rename_sheet").val()];
  if (!sheet) {
    showBulkRenameMessage("Choose a worksheet first.");
    return;
  }
  const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: "", raw: true });
  if (rows.length < 2) {
    showBulkRenameMessage("The selected worksheet needs a header row and at least one customer row.");
    return;
  }
  bulkRenameHeaders = rows[0].map((header, index) => String(header || "").trim() || `Column ${index + 1}`);
  bulkRenameSourceRows = rows.slice(1)
    .filter((row) => row.some((value) => String(value ?? "").trim() !== ""))
    .map((row) => bulkRenameHeaders.map((_, index) => row[index] ?? ""));
  if (!bulkRenameSourceRows.length) {
    showBulkRenameMessage("The selected worksheet contains no customer rows.");
    return;
  }

  const populateColumnSelect = (selector, aliases) => {
    const select = $(selector).empty().append($("<option>", { value: "", text: "Select column" }));
    bulkRenameHeaders.forEach((header, index) => select.append($("<option>", { value: index, text: header })));
    const match = bulkRenameHeaders.findIndex((header) => aliases.includes(normalizeBulkRenameHeader(header)));
    if (match >= 0) select.val(String(match));
  };
  populateColumnSelect("#bulk_rename_code_column", bulkRenameCodeAliases);
  populateColumnSelect("#bulk_rename_name_column", bulkRenameNameAliases);
  $("#bulk_rename_column_mapping").removeClass("d-none");
  $("#bulk_rename_message").addClass("d-none");
}

function renderBulkRenamePreview(customers) {
  const codeColumn = $("#bulk_rename_code_column").val();
  const nameColumn = $("#bulk_rename_name_column").val();
  if (codeColumn === "" || nameColumn === "" || codeColumn === nameColumn) {
    showBulkRenameMessage("Select two different columns for store code and new customer name.");
    return;
  }

  const customerByCode = new Map(customers.map((customer) => [String(customer.customer_id).trim().toLowerCase(), customer]));
  const seenCodes = new Set();
  bulkRenamePreviewRows = bulkRenameSourceRows.map((row, index) => {
    const code = String(row[Number(codeColumn)] ?? "").trim();
    const newName = String(row[Number(nameColumn)] ?? "").trim();
    const normalizedCode = code.toLowerCase();
    const customer = code ? customerByCode.get(normalizedCode) : null;
    const duplicate = !!normalizedCode && seenCodes.has(normalizedCode);
    if (normalizedCode) seenCodes.add(normalizedCode);
    let status = "Ready";
    if (!code || !newName) status = "Store code and new name are required";
    else if (duplicate) status = "Duplicate store code";
    else if (!customer) status = "Store code not found";
    else if (String(customer.customer_name || "") === newName) status = "No change";
    return { sourceRow: index + 2, code, newName, customer, duplicate, status };
  });

  const body = $("#bulk_rename_rows").empty();
  const problems = [];
  bulkRenamePreviewRows.forEach((row) => {
    if (!row.code || !row.newName || row.duplicate || !row.customer) problems.push(`Row ${row.sourceRow}: ${row.status}`);
    body.append($("<tr>")
      .append($("<td>").text(row.code))
      .append($("<td>").text(row.customer?.customer_name || "Not found"))
      .append($("<td>").text(row.newName))
      .append($("<td>").text(row.status)));
  });
  const changes = bulkRenamePreviewRows.filter((row) => row.customer && row.newName && !row.duplicate && row.status === "Ready").length;
  const message = problems.length
    ? `${problems.length} row(s) need correction. No changes will be applied until every store code matches exactly.`
    : `${changes} customer name change(s) ready to apply.`;
  $("#bulk_rename_validation")
    .text(message)
    .removeClass("alert alert-danger alert-success")
    .addClass(`alert ${problems.length ? "alert-danger" : "alert-success"}`);
  $("#bulk_rename_confirm").prop("disabled", problems.length > 0 || changes === 0);
  $("#bulk_rename_message").addClass("d-none");
  setBulkRenameStage("preview");
}

$("#bulk_rename_template").on("click", () => {
  if (typeof XLSX === "undefined") {
    showBulkRenameMessage("Template download is unavailable because the spreadsheet parser did not load.");
    return;
  }
  const worksheet = XLSX.utils.aoa_to_sheet([["Store Code", "New Customer Name"], ["00123", "Updated Shop Name"]]);
  worksheet["!cols"] = [{ wch: 18 }, { wch: 36 }];
  const workbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(workbook, worksheet, "Name Updates");
  XLSX.writeFile(workbook, "Customer_Name_Update_Template.xlsx");
});

$("#bulk_rename_upload").on("click", () => {
  const file = $("#bulk_rename_file")[0].files[0];
  if (!file) {
    showBulkRenameMessage("Choose a CSV or Excel file first.");
    return;
  }
  if (file.size > 5 * 1024 * 1024) {
    showBulkRenameMessage("File size exceeds the 5MB limit.");
    return;
  }
  if (typeof XLSX === "undefined") {
    showBulkRenameMessage("Spreadsheet reader could not be loaded. Check your connection and try again.");
    return;
  }
  const reader = new FileReader();
  reader.onload = (event) => {
    try {
      bulkRenameWorkbook = XLSX.read(event.target.result, { type: "array" });
      if (!bulkRenameWorkbook.SheetNames.length) throw new Error("The file contains no worksheets.");
      const sheetSelect = $("#bulk_rename_sheet").empty();
      bulkRenameWorkbook.SheetNames.forEach((name) => sheetSelect.append($("<option>", { value: name, text: name })));
      $("#bulk_rename_column_mapping").addClass("d-none");
      $("#bulk_rename_message").addClass("d-none");
      setBulkRenameStage("mapping");
    } catch (error) {
      showBulkRenameMessage(`Could not read the file: ${error.message}`);
    }
  };
  reader.onerror = () => showBulkRenameMessage("Could not read the selected file.");
  reader.readAsArrayBuffer(file);
});

$("#bulk_rename_load_sheet").on("click", loadBulkRenameWorksheet);
$("#bulk_rename_preview").on("click", () => {
  $("#bulk_rename_preview").prop("disabled", true);
  mySuperSaver.performAjaxRequest({ data: { function_to_call: "Load_customer_table" } })
    .then((customers) => {
      if (!Array.isArray(customers)) throw new Error("Could not load the current customer directory.");
      customerTable.clear().rows.add(customers).draw();
      renderBulkRenamePreview(customers);
    })
    .catch((error) => showBulkRenameMessage(error.message || "Could not load the customer directory."))
    .finally(() => $("#bulk_rename_preview").prop("disabled", false));
});
$("#bulk_rename_back").on("click", () => setBulkRenameStage("mapping"));
$("#bulk_rename_confirm").on("click", () => {
  const updates = bulkRenamePreviewRows
    .filter((row) => row.customer && row.status === "Ready")
    .map((row) => ({ customer_id: row.code, customer_name: row.newName }));
  if (!updates.length) return;
  const correctionPassword = $("#bulk_rename_correction_password").val();
  const correctionCsrfToken = document.querySelector('meta[name="recovery-correction-csrf"]')?.content || "";
  if (!correctionPassword) {
    showBulkRenameMessage("Enter the correction password to apply customer name changes.");
    return;
  }
  if (!correctionCsrfToken) {
    showBulkRenameMessage("The request token is missing. Reload the customer page and try again.");
    return;
  }
  $("#bulk_rename_confirm").prop("disabled", true).text("Applying...");
  mySuperSaver.performAjaxRequest({
    data: {
      function_to_call: "bulk_update_customer_names",
      csrf_token: $("meta[name='bulk-rename-csrf']").attr("content") || "",
      correction_csrf_token: correctionCsrfToken,
      correction_password: correctionPassword,
      updates: JSON.stringify(updates)
    }
  }).then((response) => {
    if (!response || response.success !== true) throw new Error(response?.message || "The customer names could not be updated.");
    showBulkRenameMessage(`${response.updated} customer name(s) updated successfully.`, true);
    loadCustomerData();
    $("#bulk_rename_rows tr").each(function () { $(this).find("td:eq(1)").text($(this).find("td:eq(2)").text()); $(this).find("td:eq(3)").text("Updated"); });
    $("#bulk_rename_validation").addClass("d-none");
  }).catch((error) => {
    showBulkRenameMessage(error.message || "The customer names could not be updated.");
    $("#bulk_rename_confirm").prop("disabled", false);
  }).finally(() => {
    $("#bulk_rename_correction_password").val("");
    $("#bulk_rename_confirm").text("Apply Name Changes");
  });
});

document.getElementById("modal_bulk_rename_customers").addEventListener("hidden.bs.modal", () => {
  $("#bulk_rename_file").val("");
  $("#bulk_rename_message, #bulk_rename_validation").addClass("d-none").empty();
  $("#bulk_rename_rows").empty();
  $("#bulk_rename_correction_password").val("");
  $("#bulk_rename_column_mapping").addClass("d-none");
  $("#bulk_rename_confirm").prop("disabled", false).text("Apply Name Changes");
  bulkRenameWorkbook = null;
  bulkRenameHeaders = [];
  bulkRenameSourceRows = [];
  bulkRenamePreviewRows = [];
  setBulkRenameStage("upload");
});

function setLegacyCsvStage(stage) {
  $("#csv_content").toggleClass("d-none", stage !== "upload");
  $("#legacy_csv_mapping_stage").toggleClass("d-none", stage !== "mapping");
  $("#legacy_csv_preview_stage").toggleClass("d-none", stage !== "preview");
}

function normalizeLegacyCsvHeader(value) {
  return String(value || "").trim().toLowerCase().replace(/[^a-z0-9]/g, "");
}

function showLegacyCsvMessage(message, success = false) {
  $("#legacy_csv_message")
    .removeClass("d-none alert-danger alert-success")
    .addClass(success ? "alert-success" : "alert-danger")
    .text(message);
}

function readLegacyCsvFile(file) {
  if (typeof XLSX === "undefined") {
    showLegacyCsvMessage("CSV preview is unavailable because the spreadsheet parser did not load.");
    return;
  }
  if (file.size > 5 * 1024 * 1024) {
    showLegacyCsvMessage("File size exceeds 5MB limit.");
    return;
  }
  const reader = new FileReader();
  reader.onload = (event) => {
    try {
      const workbook = XLSX.read(event.target.result, { type: "array", raw: true });
      const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
      const rows = XLSX.utils.sheet_to_json(firstSheet, { header: 1, defval: "", raw: true });
      if (rows.length < 2) throw new Error("The CSV needs a heading row and at least one data row.");
      legacyCsvHeaders = rows[0].map((header, index) => String(header || "").trim() || `Column ${index + 1}`);
      legacyCsvRows = rows.slice(1)
        .filter((row) => row.some((value) => String(value ?? "").trim() !== ""))
        .map((row) => legacyCsvHeaders.map((_, index) => row[index] ?? ""));
      legacyCsvPreviewRows = legacyCsvRows.map((row) => row.slice());
      if (!legacyCsvRows.length) throw new Error("The CSV has no non-empty data rows.");

      const table = $("#legacy_csv_mapping_table").empty();
      table.append($("<thead>").append($("<tr>").append("<th>Migration Field</th><th>CSV Heading</th>")));
      const body = $("<tbody>");
      const mapped = new Set();
      legacyCsvFields.forEach((field) => {
        const columnIndex = legacyCsvHeaders.findIndex((header, index) => {
          const normalized = normalizeLegacyCsvHeader(header);
          return !mapped.has(index) && field.aliases.includes(normalized);
        });
        if (columnIndex !== -1) mapped.add(columnIndex);
        const select = $("<select>", { class: "form-select form-select-sm legacy-csv-map" }).data("field", field.key);
        select.append($("<option>", { value: "", text: "Do Not Import" }));
        legacyCsvHeaders.forEach((header, index) => select.append($("<option>", { value: index, text: header })));
        if (columnIndex !== -1) select.val(String(columnIndex));
        body.append($("<tr>").append($("<td>").text(field.label)).append($("<td>").append(select)));
      });
      table.append(body);
      $("#legacy_csv_message").addClass("d-none").empty();
      setLegacyCsvStage("mapping");
    } catch (error) {
      showLegacyCsvMessage(`Could not read the CSV: ${error.message}`);
    }
  };
  reader.onerror = () => showLegacyCsvMessage("Could not read the selected CSV file.");
  reader.readAsArrayBuffer(file);
}

function getLegacyCsvMapping() {
  const mapping = {};
  $(".legacy-csv-map").each(function () {
    if ($(this).val() !== "") mapping[$(this).data("field")] = Number($(this).val());
  });
  return mapping;
}

function validateLegacyCsvMapping(mapping) {
  const missing = legacyCsvFields.filter((field) => field.required && mapping[field.key] === undefined).map((field) => field.label);
  $("#legacy_csv_mapping_message").html(missing.length
    ? `<div class="alert alert-danger">Map all required fields: ${missing.join(", ")}</div>`
    : "");
  return missing.length === 0;
}

function validateLegacyCsvPreview() {
  const mapping = getLegacyCsvMapping();
  const issues = [];
  const seenBillIds = new Set();
  legacyCsvPreviewRows.forEach((row, rowIndex) => {
    legacyCsvFields.filter((field) => field.required).forEach((field) => {
      const value = String(row[mapping[field.key]] ?? "").trim();
      if (!value) issues.push(`Row ${rowIndex + 2}: ${field.label} is missing`);
      if (value && field.numeric && !Number.isFinite(Number(value.replace(/,/g, "")))) {
        issues.push(`Row ${rowIndex + 2}: ${field.label} must be numeric`);
      }
    });
    const billId = String(row[mapping.bill_id] ?? "").trim().toLowerCase();
    if (billId && seenBillIds.has(billId)) issues.push(`Row ${rowIndex + 2}: duplicate Bill ID ${billId}`);
    if (billId) seenBillIds.add(billId);
  });
  const uniqueIssues = [...new Set(issues)];
  $("#legacy_csv_preview_validation").html(uniqueIssues.length
    ? `<div class="alert alert-danger">${uniqueIssues.slice(0, 10).map((issue) => $('<span>').text(issue).html()).join("<br>")}${uniqueIssues.length > 10 ? "<br>Additional rows have validation issues." : ""}</div>`
    : `<div class="alert alert-success">${legacyCsvPreviewRows.length} row(s) ready to migrate. Review and confirm.</div>`);
  $("#legacy_csv_confirm").prop("disabled", uniqueIssues.length > 0);
  return uniqueIssues.length === 0;
}

function renderLegacyCsvPreview() {
  const mapping = getLegacyCsvMapping();
  const fields = legacyCsvFields.filter((field) => mapping[field.key] !== undefined);
  const table = $("#legacy_csv_preview_table").empty();
  const head = $("<tr>");
  fields.forEach((field) => head.append($("<th>").text(field.label)));
  table.append($("<thead>").append(head));
  const body = $("<tbody>");
  legacyCsvPreviewRows.forEach((row, rowIndex) => {
    const tr = $("<tr>").attr("data-row", rowIndex);
    fields.forEach((field) => {
      const input = $("<input>", { type: "text", class: "form-control form-control-sm legacy-csv-value" })
        .attr("data-field", field.key).val(row[mapping[field.key]]);
      tr.append($("<td>").append(input));
    });
    body.append(tr);
  });
  table.append(body);
  validateLegacyCsvPreview();
}

function submitLegacyCsvImport() {
  if (!validateLegacyCsvPreview()) return;
  const mapping = getLegacyCsvMapping();
  const canonicalKeys = legacyCsvFields.map((field) => field.key);
  const output = [canonicalKeys];
  legacyCsvPreviewRows.forEach((row, rowIndex) => {
    const editedValues = {};
    $("#legacy_csv_preview_table tbody tr").eq(rowIndex).find(".legacy-csv-value").each(function () {
      editedValues[$(this).data("field")] = $(this).val();
    });
    output.push(canonicalKeys.map((key) => mapping[key] === undefined ? "" : (editedValues[key] ?? row[mapping[key]] ?? "")));
  });
  const csvBlob = new Blob([XLSX.utils.sheet_to_csv(XLSX.utils.aoa_to_sheet(output))], { type: "text/csv;charset=utf-8" });
  const formData = new FormData();
  formData.append("csv_file", csvBlob, "mapped_migration.csv");
  $("#csv_loading_screen").show();
  $("#legacy_csv_mapping_stage, #legacy_csv_preview_stage").addClass("d-none");
  $("#legacy_csv_message").addClass("d-none").empty();
  $.ajax({
    url: "process_csv_import.php",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    dataType: "json",
    success: (response) => {
      $("#csv_loading_screen").hide();
      if (response.status === 1) {
        const details = `Migration completed. Customers inserted: ${response.inserted}; failed: ${response.failed}; total: ${response.total_processed}; salesmen: ${response.salesmen_created}; areas: ${response.sectors_created}.`;
        showLegacyCsvMessage(response.errors?.length ? `${details} Issues: ${response.errors.join("; ")}` : details, true);
        $("#csv_content").removeClass("d-none").show();
        $("#csv_file_input").val("");
        loadCustomerData();
        setTimeout(() => bootstrap.Modal.getInstance(document.getElementById("modal_import_from_excel"))?.hide(), 2500);
      } else {
        showLegacyCsvMessage(`Migration failed: ${response.message || "Unknown error"}`);
        setLegacyCsvStage("preview");
      }
    },
    error: (xhr, status, error) => {
      $("#csv_loading_screen").hide();
      showLegacyCsvMessage(`Migration failed: ${xhr.responseJSON?.message || error}`);
      setLegacyCsvStage("preview");
    }
  });
}

$("#btn_import_csv").on("click", () => {
  const fileInput = $("#csv_file_input")[0];
  if (!fileInput.files || !fileInput.files.length) {
    showLegacyCsvMessage("Please select a CSV file.");
    return;
  }
  readLegacyCsvFile(fileInput.files[0]);
});
$("#legacy_csv_mapping_table").on("change", ".legacy-csv-map", function () {
  const selected = $(this).val();
  if (selected && $(".legacy-csv-map").not(this).filter(function () { return $(this).val() === selected; }).length) {
    showLegacyCsvMessage("Each CSV heading can only be mapped once.");
    $(this).val("");
  }
  validateLegacyCsvMapping(getLegacyCsvMapping());
});
$("#legacy_csv_preview").on("click", () => {
  if (!validateLegacyCsvMapping(getLegacyCsvMapping())) return;
  renderLegacyCsvPreview();
  setLegacyCsvStage("preview");
});
$("#legacy_csv_preview_table").on("input", ".legacy-csv-value", function () {
  const rowIndex = Number($(this).closest("tr").attr("data-row"));
  const columnIndex = getLegacyCsvMapping()[$(this).data("field")];
  legacyCsvPreviewRows[rowIndex][columnIndex] = $(this).val();
  validateLegacyCsvPreview();
});
$("#legacy_csv_back_to_mapping").on("click", () => setLegacyCsvStage("mapping"));
$("#legacy_csv_confirm").on("click", submitLegacyCsvImport);

const customerImportFields = [
  { key: "customer_id", label: "Customer ID", required: true, aliases: ["customerid", "custid", "id", "customercode"] },
  { key: "name", label: "Name", required: true, aliases: ["name", "customername", "shopname", "storename"] },
  { key: "area", label: "Area", required: true, aliases: ["area", "areaname", "sector", "sectorname", "route"] },
  { key: "salesman", label: "Salesman", required: true, aliases: ["salesman", "salesmanname", "saleman", "salemanname", "salesperson", "salespersonname"] },
  { key: "day", label: "Day", required: true, aliases: ["day", "deliveryday", "routeday"] },
  { key: "phone", label: "Phone", aliases: ["phone", "phonenumber", "mobile", "mobilenumber", "contact"] }
];
let customerImportWorkbook = null;
let customerImportHeaders = [];
let customerImportRows = [];
let customerImportPreviewRows = [];

function normalizeCustomerImportHeader(value) {
  return String(value || "").trim().toLowerCase().replace(/[^a-z0-9]/g, "");
}

function setCustomerImportStage(stage) {
  $("#customer_content").toggleClass("d-none", stage !== "upload");
  $("#customer_mapping_stage").toggleClass("d-none", stage !== "mapping");
  $("#customer_preview_stage").toggleClass("d-none", stage !== "preview");
  $("#customer_import_mapping_area").toggleClass("d-none", !customerImportHeaders.length);
}

function showCustomerImportMessage(message, success = false) {
  $("#customer_messageBody").text(message);
  $("#customer_messageBox .alert")
    .removeClass("alert-danger alert-success")
    .addClass(success ? "alert-success" : "alert-danger");
  $("#customer_messageBox").show();
}

function prepareCustomerImport(file) {
  if (typeof XLSX === "undefined") {
    showCustomerImportMessage("Spreadsheet reader could not be loaded. Check your connection and try again.");
    return;
  }
  if (file.size > 5 * 1024 * 1024) {
    showCustomerImportMessage("File size exceeds 5MB limit.");
    return;
  }

  const reader = new FileReader();
  reader.onload = (event) => {
    try {
      customerImportWorkbook = XLSX.read(event.target.result, { type: "array", cellDates: true });
      if (!customerImportWorkbook.SheetNames.length) throw new Error("The file contains no worksheets.");
      const sheetSelect = $("#customer_import_sheet").empty();
      customerImportWorkbook.SheetNames.forEach((name) => sheetSelect.append($("<option>", { value: name, text: name })));
      customerImportHeaders = [];
      $("#customer_import_mapping_area").addClass("d-none");
      $("#customer_import_mapping_message").empty();
      $("#customer_messageBox").hide();
      setCustomerImportStage("mapping");
    } catch (error) {
      showCustomerImportMessage(`Could not read the file: ${error.message}`);
    }
  };
  reader.onerror = () => showCustomerImportMessage("Could not read the selected file.");
  reader.readAsArrayBuffer(file);
}

function loadCustomerImportSheet() {
  const sheet = customerImportWorkbook.Sheets[$("#customer_import_sheet").val()];
  const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: "", raw: true });
  if (rows.length < 2) {
    showCustomerImportMessage("The selected sheet needs a heading row and at least one customer row.");
    return;
  }
  customerImportHeaders = rows[0].map((header, index) => String(header || "").trim() || `Column ${index + 1}`);
  customerImportRows = rows.slice(1)
    .filter((row) => row.some((value) => String(value ?? "").trim() !== ""))
    .map((row) => customerImportHeaders.map((_, index) => row[index] ?? ""));
  customerImportPreviewRows = customerImportRows.map((row) => row.slice());
  if (!customerImportRows.length) {
    showCustomerImportMessage("The selected sheet contains no customer rows.");
    return;
  }

  const mappingTable = $("#customer_import_mapping_table").empty();
  mappingTable.append($("<thead>").append($("<tr>").append("<th>Customer Field</th><th>Document Heading</th>")));
  const body = $("<tbody>");
  const alreadyMapped = new Set();
  customerImportFields.forEach((field) => {
    const columnIndex = customerImportHeaders.findIndex((header, index) => {
      const normalized = normalizeCustomerImportHeader(header);
      return !alreadyMapped.has(index) && field.aliases.includes(normalized);
    });
    if (columnIndex !== -1) alreadyMapped.add(columnIndex);
    const select = $("<select>", { class: "form-select form-select-sm customer-import-map" }).data("field", field.key);
    select.append($("<option>", { value: "", text: "Do Not Import" }));
    customerImportHeaders.forEach((header, index) => select.append($("<option>", { value: index, text: header })));
    if (columnIndex !== -1) select.val(String(columnIndex));
    body.append($("<tr>").append($("<td>").text(field.label)).append($("<td>").append(select)));
  });
  mappingTable.append(body);
  $("#customer_import_mapping_area").removeClass("d-none");
  $("#customer_messageBox").hide();
}

function getCustomerImportMapping() {
  const mapping = {};
  $(".customer-import-map").each(function () {
    if ($(this).val() !== "") mapping[$(this).data("field")] = Number($(this).val());
  });
  return mapping;
}

function validateCustomerImportMapping(mapping) {
  const missing = customerImportFields.filter((field) => field.required && mapping[field.key] === undefined).map((field) => field.label);
  $("#customer_import_mapping_message").html(missing.length
    ? `<div class="alert alert-danger">Map the required fields: ${missing.join(", ")}</div>`
    : "");
  return missing.length === 0;
}

function renderCustomerImportPreview() {
  const mapping = getCustomerImportMapping();
  const fields = customerImportFields.filter((field) => mapping[field.key] !== undefined);
  const table = $("#customer_import_preview_table").empty();
  const head = $("<tr>");
  fields.forEach((field) => head.append($("<th>").text(field.label)));
  table.append($("<thead>").append(head));
  const body = $("<tbody>");
  const problems = [];
  customerImportPreviewRows.forEach((row, rowIndex) => {
    const tr = $("<tr>").attr("data-row", rowIndex);
    fields.forEach((field) => {
      const input = $("<input>", { type: "text", class: "form-control form-control-sm customer-import-value" })
        .attr("data-field", field.key)
        .val(row[mapping[field.key]] instanceof Date ? row[mapping[field.key]].toISOString().slice(0, 10) : row[mapping[field.key]]);
      tr.append($("<td>").append(input));
      if (field.required && !String(row[mapping[field.key]] ?? "").trim()) problems.push(`Row ${rowIndex + 2}: ${field.label} is missing`);
    });
    body.append(tr);
  });
  table.append(body);
  const uniqueProblems = [...new Set(problems)];
  $("#customer_preview_validation").html(uniqueProblems.length
    ? `<div class="alert alert-danger">${uniqueProblems.slice(0, 8).map((problem) => $('<span>').text(problem).html()).join("<br>")}${uniqueProblems.length > 8 ? "<br>More rows have missing required values." : ""}</div>`
    : `<div class="alert alert-success">${customerImportRows.length} row(s) ready to import. Edit cells as needed, then confirm.</div>`);
  $("#customer_import_confirm").prop("disabled", uniqueProblems.length > 0);
}

function buildMappedCustomerCsv() {
  const mapping = getCustomerImportMapping();
  const output = [["Customer ID", "Name", "Area", "Salesman", "Day", "Phone"]];
  customerImportRows.forEach((row) => {
    const values = {};
    $("#customer_import_preview_table tbody tr").eq(output.length - 1).find(".customer-import-value").each(function () {
      values[$(this).data("field")] = $(this).val();
    });
    output.push(["customer_id", "name", "area", "salesman", "day", "phone"].map((key) =>
      mapping[key] === undefined ? "" : values[key] ?? row[mapping[key]] ?? ""
    ));
  });
  const sheet = XLSX.utils.aoa_to_sheet(output);
  return new Blob([XLSX.utils.sheet_to_csv(sheet)], { type: "text/csv;charset=utf-8" });
}

function submitCustomerImport() {
  const formData = new FormData();
  formData.append("customer_file", buildMappedCustomerCsv(), "customer_import_mapped.csv");
  $("#customer_loading_screen").show();
  $("#customer_mapping_stage, #customer_preview_stage").addClass("d-none");
  $("#customer_messageBox").hide();
  $.ajax({
    url: "process_customer_import.php",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    dataType: "json",
    success: (response) => {
      $("#customer_loading_screen").hide();
      if (response.status === 1) {
        let message = `Customer import completed. Inserted: ${response.inserted}; failed: ${response.failed}; total: ${response.total_processed}. Salesmen: ${response.salesmen_created}; Areas: ${response.sectors_created}; Routes: ${response.routes_created}.`;
        if (response.errors && response.errors.length) message += ` Issues: ${response.errors.join("; ")}`;
        showCustomerImportMessage(message, true);
        $("#customer_content").addClass("d-none");
        loadCustomerData();
        setTimeout(() => bootstrap.Modal.getInstance(document.getElementById("modal_import_customers"))?.hide(), 3000);
      } else {
        showCustomerImportMessage(`Import failed: ${response.message || "Unknown error"}`);
        setCustomerImportStage("preview");
      }
    },
    error: (xhr, status, error) => {
      $("#customer_loading_screen").hide();
      showCustomerImportMessage(`Import failed: ${xhr.responseJSON?.message || error}`);
      setCustomerImportStage("preview");
    }
  });
}

$("#btn_import_customers").on("click", () => {
  const fileInput = $("#customer_file_input")[0];
  if (!fileInput.files || !fileInput.files.length) {
    showCustomerImportMessage("Please select a file.");
    return;
  }
  prepareCustomerImport(fileInput.files[0]);
});

$("#customer_import_load_sheet").on("click", loadCustomerImportSheet);
$("#customer_import_mapping_table").on("change", ".customer-import-map", function () {
  const selected = $(this).val();
  if (selected && $(".customer-import-map").not(this).filter(function () { return $(this).val() === selected; }).length) {
    showCustomerImportMessage("Each document heading can only be mapped once.");
    $(this).val("");
  }
  validateCustomerImportMapping(getCustomerImportMapping());
});
$("#customer_import_preview").on("click", () => {
  if (!validateCustomerImportMapping(getCustomerImportMapping())) return;
  renderCustomerImportPreview();
  setCustomerImportStage("preview");
});
$("#customer_import_preview_table").on("input", ".customer-import-value", function () {
  const rowIndex = Number($(this).closest("tr").attr("data-row"));
  const columnIndex = getCustomerImportMapping()[$(this).data("field")];
  customerImportPreviewRows[rowIndex][columnIndex] = $(this).val();
  const mapping = getCustomerImportMapping();
  const problems = [];
  customerImportPreviewRows.forEach((row, index) => {
    customerImportFields.filter((field) => field.required).forEach((field) => {
      if (!String(row[mapping[field.key]] ?? "").trim()) problems.push(`Row ${index + 2}: ${field.label} is missing`);
    });
  });
  const uniqueProblems = [...new Set(problems)];
  $("#customer_preview_validation").html(uniqueProblems.length
    ? `<div class="alert alert-danger">${uniqueProblems.slice(0, 8).map((problem) => $('<span>').text(problem).html()).join("<br>")}${uniqueProblems.length > 8 ? "<br>More rows have missing required values." : ""}</div>`
    : `<div class="alert alert-success">${customerImportPreviewRows.length} row(s) ready to import. Edit cells as needed, then confirm.</div>`);
  $("#customer_import_confirm").prop("disabled", uniqueProblems.length > 0);
});
$("#customer_import_back_to_mapping").on("click", () => setCustomerImportStage("mapping"));
$("#customer_import_confirm").on("click", submitCustomerImport);

// Reset customer modal content when it's hidden
document.getElementById('modal_import_customers').addEventListener('hidden.bs.modal', function () {
  $("#customer_loading_screen").hide();
  $("#customer_content").show();
  $("#customer_content").removeClass("d-none");
  $("#customer_mapping_stage, #customer_preview_stage").addClass("d-none");
  $("#customer_import_mapping_area").addClass("d-none");
  $("#customer_messageBox").hide();
  $("#customer_file_input")[0].value = '';
  customerImportWorkbook = null;
  customerImportHeaders = [];
  customerImportRows = [];
  customerImportPreviewRows = [];
});

// Reset modal content when it's hidden
document.getElementById('modal_import_from_excel').addEventListener('hidden.bs.modal', function () {
  $("#csv_loading_screen").hide();
  $("#csv_content").removeClass("d-none").show();
  $("#legacy_csv_mapping_stage, #legacy_csv_preview_stage").addClass("d-none");
  $("#legacy_csv_message").addClass("d-none").empty();
  $("#csv_file_input")[0].value = '';
  legacyCsvHeaders = [];
  legacyCsvRows = [];
  legacyCsvPreviewRows = [];
});
