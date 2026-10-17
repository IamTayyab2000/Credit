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
var customer_list = [];
var customer_json = [];
var customerListRequestId = 0;
var customerListLoadedForSaleman = null;

function normalizeCustomerList(response) {
  if (Array.isArray(response)) return response;
  if (response && Array.isArray(response.data)) return response.data;
  if (response && typeof response === "object") {
    return Object.values(response).filter((item) => item && item.customer_id);
  }
  return [];
}

function normalizeCustomerId(customerId) {
  return String(customerId ?? "").replace(/[\s\u200B-\u200D\uFEFF]/g, "").toLowerCase();
}

function normalizeCustomerName(customerName) {
  return String(customerName ?? "")
    .normalize("NFKD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, " ")
    .trim()
    .replace(/\s+/g, " ");
}

function findCustomerById(customerId) {
  const normalizedId = normalizeCustomerId(customerId);
  return customer_list.find((item) =>
    normalizeCustomerId(item.customer_id) === normalizedId
  );
}

function findExactCustomersByName(customerName) {
  const normalizedName = normalizeCustomerName(customerName);
  if (!normalizedName) return [];
  return customer_list.filter((item) =>
    normalizeCustomerName(item.customer_name) === normalizedName
  );
}

function findCustomersByName(customerName) {
  const normalizedName = normalizeCustomerName(customerName);
  if (!normalizedName) return [];
  const exactMatches = findExactCustomersByName(normalizedName);
  if (exactMatches.length) return exactMatches;

  const searchTerms = normalizedName.split(" ");
  return customer_list.filter((item) => {
    const candidateName = normalizeCustomerName(item.customer_name);
    return searchTerms.every((term) => candidateName.includes(term));
  });
}

function checkExistingBillIds(billIds) {
  return mySuperSaver.performAjaxRequest({
    data: {
      function_to_call: "check_dsr_bill_ids",
      bill_ids: JSON.stringify(billIds),
    },
  }).then((response) => {
    if (!Array.isArray(response?.existing_bill_ids)) {
      throw new Error(response?.message || "Bill IDs could not be checked.");
    }
    return response.existing_bill_ids;
  });
}

// Load Salesman Data initially
LoadSalemanData();

function LoadSalemanData() {
  mySuperSaver
    .performAjaxRequest({
      data: { function_to_call: "load_saleman_data" },
    })
    .then((responce) => {
      mySuperSaver.addOptionsToSelect("saleman_name_selector", responce);
      if (picklist_saleman.val()) picklist_saleman.trigger("change");
    })
    .catch((error) => {
      console.log(error);
    });
}

$("#btn_import_dsr").prop("disabled", true);
$("#saleman_name_selector").on("change", () => {
  const saleman_id = picklist_saleman.val();
  const requestId = ++customerListRequestId;
  customerListLoadedForSaleman = null;
  customer_list = [];
  $("#customer_data_list").empty();
  $("#btn_import_dsr").prop("disabled", true);
  if (!saleman_id) return;

  mySuperSaver
    .performAjaxRequest({
      data: {
        function_to_call: "Load_customer_table_by_saleman",
        saleman_id: saleman_id,
      },
    })
    .then((response) => {
      if (requestId !== customerListRequestId || saleman_id !== picklist_saleman.val()) return;
      var customer_data_list = $("#customer_data_list");
      customer_list = normalizeCustomerList(response);
      customerListLoadedForSaleman = saleman_id;

      $.each(customer_list, function (index, item) {
        customer_data_list.append(
          $("<option>", {
            value: item.customer_id,
            text: item.customer_name,
          })
        );
      });
      $("#btn_import_dsr").prop("disabled", false);
    })
    .catch((error) => {
      if (requestId !== customerListRequestId) return;
      console.error("Could not load DSR customers:", error);
      alert("Could not load customers for the selected salesman. Please select the salesman again and retry.");
    });
});

$("#customer_id").on("input", () => {
  var inputValue = $("#customer_id").val();
  var selectedCustomer = findCustomerById(inputValue);

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

async function tryAddCreditBill(billData) {
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

  if (customer_json.some((item) => String(item.invoice_id).trim().toLowerCase() === String(inv_id).trim().toLowerCase())) {
    alert("Invoice ID already exists in the list.");
    return;
  }
  if (
    p_id == "" ||
    inv_id == "" ||
    c_id == "" ||
    inv_date == "" ||
    b_total <= 0
  ) {
    alert("Please fill all mandatory fields (ID, Date, Invoice, Customer, Total)");
    return;
  }

  try {
    const existingBillIds = await checkExistingBillIds([inv_id]);
    if (existingBillIds.length) {
      alert(`Bill ID ${existingBillIds.join(", ")} already exists in the database. This bill cannot be added.`);
      return;
    }
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
  } catch (error) {
    alert(`Could not verify the bill ID. This bill cannot be added. ${error.message || error}`);
  }
}

function normalizeImportHeader(header) {
  return String(header || "")
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]/g, "");
}

const dsrImportFields = [
  { key: "invoice_id", label: "Invoice ID", required: true, aliases: ["invoiceid", "invoice", "invid"] },
  { key: "customer_id", label: "Customer Number", aliases: ["customerid", "customernumber", "customerno", "customernum", "custid", "storecode", "storeid", "customercode"] },
  { key: "customer_name", label: "Customer / Shop Name", aliases: ["customername", "shopname", "shop", "storename", "name"] },
  { key: "bill_total", label: "Bill Total", required: true, aliases: ["billtotal", "total", "amount"] },
  { key: "bill_recovered", label: "Recovered", aliases: ["billrecovered", "recovered", "recovery"] },
  { key: "bill_returned", label: "Returned", aliases: ["billreturned", "returned", "return"] },
  { key: "bill_scheme", label: "Scheme / Discount", aliases: ["billscheme", "scheme", "discount"] },
  { key: "invoice_date", label: "Invoice Date", aliases: ["invoicedate", "date"] }
];
const dsrImportAliases = {
  invoice_id: ["invoiceid", "invoice", "invid"],
  customer_id: ["customerid", "customernumber", "customerno", "customernum", "custid", "storecode", "storeid", "customercode"],
  customer_name: ["customername", "shopname", "shop", "storename", "name"],
  bill_total: ["billtotal", "total", "amount"],
  bill_recovered: ["billrecovered", "recovered", "recovery"],
  bill_returned: ["billreturned", "returned", "return"],
  bill_scheme: ["billscheme", "scheme", "discount"],
  invoice_date: ["invoicedate", "date"]
};
let dsrImportWorkbook = null;
let dsrImportHeaders = [];
let dsrImportRows = [];
let dsrImportConfirmed = false;
let dsrImportExistingBillIds = new Set();
let dsrImportCheckRequestId = 0;
let dsrImportCheckPending = false;
let dsrImportCheckFailed = false;

function parseImportedNumber(value) {
  const text = String(value ?? "").replace(/,/g, "").trim();
  if (!text) return { value: 0, valid: true };
  const number = Number(text);
  return { value: number, valid: Number.isFinite(number) };
}

function normalizeImportedDate(value) {
  if (value instanceof Date && !Number.isNaN(value.getTime())) {
    const year = value.getFullYear();
    const month = String(value.getMonth() + 1).padStart(2, "0");
    const day = String(value.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  }

  if (typeof value === "number" && value > 0) {
    const excelDate = new Date(Date.UTC(1899, 11, 30) + value * 86400000);
    return normalizeImportedDate(excelDate);
  }

  const text = String(value ?? "").trim();
  if (/^\d{4}-\d{2}-\d{2}$/.test(text)) return text;

  const parts = text.split(/[\/-]/);
  if (parts.length === 3 && parts[0].length <= 2 && parts[2].length === 4) {
    return `${parts[2]}-${parts[1].padStart(2, "0")}-${parts[0].padStart(2, "0")}`;
  }
  return text;
}

function setDsrImportStage(stage) {
  $("#dsrImportSheetStage").toggleClass("d-none", stage !== "sheets");
  $("#dsrImportMappingStage").toggleClass("d-none", stage !== "mapping");
  $("#dsrImportPreviewStage").toggleClass("d-none", stage !== "preview");
  $("#dsrImportConfirm").toggleClass("d-none", stage !== "preview" || dsrImportConfirmed);
}

function openDsrImportWorkbook(workbook) {
  dsrImportWorkbook = workbook;
  dsrImportConfirmed = false;
  dsrImportExistingBillIds = new Set();
  dsrImportCheckPending = false;
  dsrImportCheckFailed = false;
  const sheetSelect = $("#dsrImportSheet").empty();
  workbook.SheetNames.forEach((name) => sheetSelect.append($("<option>", { value: name, text: name })));
  $("#dsrImportValidation").empty();
  $("#dsrImportPreviewStatus").text("");
  $("#dsrImportConfirm").prop("disabled", false).text("Confirm Import");
  setDsrImportStage("sheets");
  bootstrap.Modal.getOrCreateInstance(document.getElementById("dsrImportModal")).show();
}

function loadDsrImportSheet() {
  const sheet = dsrImportWorkbook.Sheets[$("#dsrImportSheet").val()];
  const rawRows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: "", raw: true });
  if (rawRows.length < 2) {
    alert("The selected worksheet needs a header row and at least one data row.");
    return false;
  }
  dsrImportHeaders = rawRows[0].map((header, index) => String(header || "").trim() || `Column ${index + 1}`);
  dsrImportRows = rawRows.slice(1).filter((values) => values.some((value) => String(value ?? "").trim() !== "")).map((values, index) => ({
    sourceRow: index + 2,
    values: dsrImportHeaders.map((_, columnIndex) => values[columnIndex] ?? ""),
    customerSelection: ""
  }));
  if (!dsrImportRows.length) {
    alert("The selected worksheet contains no bill rows.");
    return false;
  }
  renderDsrImportMapping();
  return true;
}

function renderDsrImportMapping() {
  const table = $("#dsrImportMappingTable").empty();
  table.append($("<thead>").append($("<tr>").append("<th>Excel Column</th><th>System Column</th>")));
  const body = $("<tbody>");
  const usedFields = new Set();
  dsrImportHeaders.forEach((header, columnIndex) => {
    const normalized = normalizeImportHeader(header);
    const matched = dsrImportFields.find((field) => !usedFields.has(field.key) && field.aliases.includes(normalized));
    if (matched) usedFields.add(matched.key);
    const select = $("<select>", { class: "form-select form-select-sm dsr-import-map" }).data("column", columnIndex);
    select.append($("<option>", { value: "", text: "Do Not Import" }));
    dsrImportFields.forEach((field) => select.append($("<option>", { value: field.key, text: field.label })));
    if (matched) select.val(matched.key);
    body.append($("<tr>").append($("<td>").text(header)).append($("<td>").append(select)));
  });
  table.append(body);
  setDsrImportStage("mapping");
}

function getDsrImportMapping() {
  const mapping = {};
  $(".dsr-import-map").each(function () {
    const key = $(this).val();
    if (key) mapping[key] = Number($(this).data("column"));
  });
  return mapping;
}

function validateDsrImportMapping(mapping) {
  const missing = getDsrImportMissingMappings(mapping);
  if (missing.length) {
    $("#dsrImportMappingMessage").html(`<div class="alert alert-danger mb-0">Required Excel columns are not mapped: ${missing.join(", ")}</div>`);
    return false;
  }
  $("#dsrImportMappingMessage").empty();
  return true;
}

function getDsrImportMissingMappings(mapping) {
  const missing = [];
  if (mapping.invoice_id === undefined) missing.push("Invoice ID");
  if (mapping.bill_total === undefined) missing.push("Bill Total");
  if (mapping.customer_id === undefined && mapping.customer_name === undefined) missing.push("Customer Number or Customer / Shop Name");
  return missing;
}

function getDsrImportValue(row, mapping, key) {
  return mapping[key] === undefined ? "" : row.values[mapping[key]];
}

function resolveDsrImportCustomer(row, mapping) {
  const customerId = String(getDsrImportValue(row, mapping, "customer_id") || "").trim();
  const customerName = String(getDsrImportValue(row, mapping, "customer_name") || "").trim();
  if (row.customerSelection) return findCustomerById(row.customerSelection) || null;
  if (customerId) {
    const customerById = findCustomerById(customerId);
    if (customerById) return customerById;
  }
  const matches = findExactCustomersByName(customerName);
  return matches.length === 1 ? matches[0] : null;
}

function renderDsrImportPreview() {
  const mapping = getDsrImportMapping();
  const table = $("#dsrImportPreviewTable").empty();
  const fields = dsrImportFields.filter((field) => mapping[field.key] !== undefined);
  const headRow = $("<tr>");
  fields.forEach((field) => headRow.append($("<th>").text(field.label)));
  headRow.append("<th>Store Match</th><th>Action</th>");
  table.append($("<thead>").append(headRow));
  const body = $("<tbody>");
  dsrImportRows.forEach((row, rowIndex) => {
    const tr = $("<tr>").attr("data-row", rowIndex);
    fields.forEach((field) => {
      const value = getDsrImportValue(row, mapping, field.key);
      const input = $("<input>", { class: "form-control form-control-sm dsr-import-cell", type: "text" })
        .attr("data-field", field.key).val(value);
      input.prop("disabled", dsrImportConfirmed);
      tr.append($("<td>").append(input));
    });
    const matches = findCustomersByName(String(getDsrImportValue(row, mapping, "customer_name") || "").trim());
    const customerById = normalizeCustomerId(getDsrImportValue(row, mapping, "customer_id"))
      ? findCustomerById(getDsrImportValue(row, mapping, "customer_id")) : null;
    const selected = resolveDsrImportCustomer(row, mapping);
    let matchLabel = selected ? `Matched: ${selected.customer_name} (${selected.customer_id})`
      : matches.length === 1 ? `Possible match: ${matches[0].customer_name} (${matches[0].customer_id})`
        : matches.length > 1 ? "Multiple Matches" : customerById ? "Matched" : "Store Code Not Found";
    const storeCell = $("<td>").append($("<span>").text(matchLabel));
    if (!selected && !dsrImportConfirmed && (matches.length || String(getDsrImportValue(row, mapping, "customer_id") || "").trim())) {
      const select = $("<select>", { class: "form-select form-select-sm mt-1 dsr-import-customer" }).attr("aria-label", "Select Store");
      select.append($("<option>", { value: "", text: "Select Store" }));
      (matches.length ? matches : customer_list).forEach((customer) => select.append($("<option>", { value: customer.customer_id, text: `${customer.customer_name} (${customer.customer_id})` })));
      select.val(row.customerSelection);
      storeCell.append(select);
    }
    tr.append(storeCell);
    const remove = $("<button>", { type: "button", class: "btn btn-outline-danger btn-sm dsr-import-delete", text: "Delete" }).prop("disabled", dsrImportConfirmed);
    tr.append($("<td>").append(remove));
    body.append(tr);
  });
  table.append(body);
  $("#dsrImportAddRow").prop("disabled", dsrImportConfirmed).toggleClass("d-none", dsrImportConfirmed);
  validateDsrImportRows(mapping);
  if (!dsrImportConfirmed) checkDsrImportBillIds(mapping);
}

function validateDsrImportRows(mapping) {
  const issues = { invalid: [], unmapped: [], missingStores: [], multipleStores: [], duplicates: [], existingBills: [] };
  const invoiceIds = new Set(dsrImportConfirmed ? [] : customer_json.map((bill) => String(bill.invoice_id).trim().toLowerCase()));
  dsrImportRows.forEach((row) => {
    const label = `Row ${row.sourceRow}`;
    const invoice = String(getDsrImportValue(row, mapping, "invoice_id") || "").trim();
    const totalText = getDsrImportValue(row, mapping, "bill_total");
    const total = parseImportedNumber(totalText);
    const customerName = String(getDsrImportValue(row, mapping, "customer_name") || "").trim();
    const customerId = String(getDsrImportValue(row, mapping, "customer_id") || "").trim();
    const matches = findCustomersByName(customerName);
    const customer = resolveDsrImportCustomer(row, mapping);
    const badValues = [];
    if (!invoice) badValues.push("missing invoice ID");
    if (!total.valid || total.value <= 0) badValues.push("invalid bill total");
    ["bill_recovered", "bill_returned", "bill_scheme"].forEach((key) => {
      if (mapping[key] !== undefined && !parseImportedNumber(getDsrImportValue(row, mapping, key)).valid) badValues.push(`invalid ${dsrImportFields.find((field) => field.key === key).label}`);
    });
    const invoiceDate = normalizeImportedDate(getDsrImportValue(row, mapping, "invoice_date")) || picklist_date.val();
    if (!invoiceDate || Number.isNaN(Date.parse(invoiceDate))) badValues.push("invalid/missing date");
    if (badValues.length) issues.invalid.push(`${label}: ${badValues.join(", ")}`);
    if (!customerId && !customerName) issues.invalid.push(`${label}: missing customer ID/name`);
    if (!customer) {
      if (customerName && matches.length) issues.multipleStores.push(`${label}: ${customerName}`);
      else issues.missingStores.push(`${label}: ${customerName || customerId || "customer not found"}`);
    }
    if (invoice) {
      const normalizedInvoice = invoice.toLowerCase();
      if (invoiceIds.has(normalizedInvoice)) issues.duplicates.push(`${label}: duplicate invoice ID ${invoice}`);
      invoiceIds.add(normalizedInvoice);
      if (dsrImportExistingBillIds.has(normalizedInvoice)) issues.existingBills.push(`${label}: bill ID ${invoice} already exists in the database`);
    }
  });
  if (Object.keys(mapping).length < dsrImportHeaders.length) {
    const ignored = dsrImportHeaders.filter((_, index) => !Object.values(mapping).includes(index));
    if (ignored.length) issues.unmapped = ignored.map((header) => `${header} (Do Not Import)`);
  }
  const categories = [
    ["Invalid/missing values", issues.invalid], ["Unmapped columns", issues.unmapped],
    ["Missing Store Codes", issues.missingStores], ["Store Matches Needing Confirmation", issues.multipleStores],
    ["Duplicate/invalid records", issues.duplicates], ["Bill IDs Already in Database", issues.existingBills]
  ];
  const blocking = issues.invalid.length + issues.missingStores.length + issues.multipleStores.length + issues.duplicates.length
    + issues.existingBills.length > 0 || dsrImportCheckPending || dsrImportCheckFailed;
  const summary = categories.filter(([, items]) => items.length).map(([title, items]) =>
    `<div><strong>${title} (${items.length})</strong>: ${items.slice(0, 5).map((item) => $('<span>').text(item).html()).join("; ")}${items.length > 5 ? "; ..." : ""}</div>`
  );
  const missingMappings = getDsrImportMissingMappings(mapping);
  if (missingMappings.length) summary.unshift(`<div><strong>Missing required columns</strong>: ${missingMappings.join(", ")}</div>`);
  if (dsrImportCheckPending) summary.unshift("Checking bill IDs against the database...");
  if (dsrImportCheckFailed) summary.unshift("Bill IDs could not be checked. Import is blocked until verification succeeds.");
  if (!summary.length) summary.push(`<div>${dsrImportRows.length} row(s) validated with no blocking errors.</div>`);
  if (!picklist_id.val()) summary.unshift("Picklist ID is required before import.");
  if (!picklist_saleman.val()) summary.unshift("Select a salesman before import.");
  const hasHeaderErrors = getDsrImportMissingMappings(mapping).length > 0;
  $("#dsrImportValidation").html(`<div class="alert ${blocking || !picklist_id.val() || !picklist_saleman.val() || hasHeaderErrors ? "alert-danger" : "alert-success"} mb-0">${summary.join("")}</div>`);
  $("#dsrImportConfirm").prop("disabled", blocking || !picklist_id.val() || !picklist_saleman.val() || hasHeaderErrors || dsrImportConfirmed);
  return !blocking && !!picklist_id.val() && !!picklist_saleman.val() && !hasHeaderErrors;
}

function updateDsrImportExistingBillWarnings(mapping) {
  $("#dsrImportPreviewTable tbody tr").each(function () {
    const row = dsrImportRows[Number($(this).attr("data-row"))];
    if (!row) return;
    const invoiceId = String(getDsrImportValue(row, mapping, "invoice_id") || "").trim().toLowerCase();
    const exists = dsrImportExistingBillIds.has(invoiceId);
    const invoiceCell = $(this).children("td").first();
    $(this).toggleClass("table-danger", exists);
    invoiceCell.find(".dsr-import-existing-warning").remove();
    if (exists) {
      invoiceCell.append($("<div>", {
        class: "dsr-import-existing-warning text-danger small",
        text: "Invoice ID already exists in the database; this row cannot be imported.",
      }));
    }
  });
}

async function checkDsrImportBillIds(mapping) {
  const requestId = ++dsrImportCheckRequestId;
  const invoiceIds = dsrImportRows
    .map((row) => String(getDsrImportValue(row, mapping, "invoice_id") || "").trim())
    .filter(Boolean);
  dsrImportCheckPending = true;
  dsrImportCheckFailed = false;
  validateDsrImportRows(mapping);
  try {
    const existingBillIds = await checkExistingBillIds(invoiceIds);
    if (requestId !== dsrImportCheckRequestId) return false;
    dsrImportExistingBillIds = new Set(existingBillIds.map((billId) => String(billId).trim().toLowerCase()));
    return true;
  } catch (error) {
    if (requestId !== dsrImportCheckRequestId) return false;
    dsrImportCheckFailed = true;
    console.error("Could not verify imported DSR bill IDs:", error);
    return false;
  } finally {
    if (requestId === dsrImportCheckRequestId) {
      dsrImportCheckPending = false;
      validateDsrImportRows(mapping);
      updateDsrImportExistingBillWarnings(mapping);
    }
  }
}

$("#btn_import_dsr").click(() => {
  if (!picklist_saleman.val() || customerListLoadedForSaleman !== picklist_saleman.val()) {
    alert("Wait for the selected salesman's customers to finish loading before importing.");
    return;
  }
  $("#dsr_import_file").val("").trigger("click");
});

$("#btn_download_dsr_sample").click(() => {
  if (typeof XLSX === "undefined") {
    alert("Sample download is unavailable because the spreadsheet parser did not load.");
    return;
  }

  const sampleRows = [
    ["InvoiceID", "Customer Number", "CustomerName", "BillTotal", "Recovered", "Returned", "Scheme", "InvoiceDate"],
    ["INV-001", "", "Exact customer name when Customer Number is unavailable", 1000, 0, 0, 0, picklist_date.val() || "YYYY-MM-DD"]
  ];
  const worksheet = XLSX.utils.aoa_to_sheet(sampleRows);
  worksheet["!cols"] = [
    { wch: 16 }, { wch: 16 }, { wch: 48 }, { wch: 12 },
    { wch: 12 }, { wch: 12 }, { wch: 10 }, { wch: 16 }
  ];
  const workbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(workbook, worksheet, "DSR Import");
  XLSX.writeFile(workbook, "DSR_Import_Sample.xlsx");
});

$("#dsr_import_file").on("change", function () {
  const file = this.files[0];
  if (!file) return;
  if (typeof XLSX === "undefined") {
    alert("Excel import is unavailable because the spreadsheet parser did not load.");
    return;
  }

  const reader = new FileReader();
  reader.onload = (event) => {
    try {
      const workbook = XLSX.read(event.target.result, { type: "array", cellDates: true });
      if (!workbook.SheetNames || workbook.SheetNames.length === 0) {
        throw new Error("The workbook has no worksheets.");
      }
      openDsrImportWorkbook(workbook);
    } catch (error) {
      console.error("DSR import failed:", error);
      alert(`Could not read the DSR file. ${error.message || "Check that it has a header row and valid bill data."}`);
    }
  };
  reader.readAsArrayBuffer(file);
});

$("#dsrImportChooseSheet").on("click", () => {
  if (loadDsrImportSheet()) setDsrImportStage("mapping");
});
$("#dsrImportBackToSheets").on("click", () => setDsrImportStage("sheets"));
$("#dsrImportShowPreview").on("click", () => {
  const mapping = getDsrImportMapping();
  if (!validateDsrImportMapping(mapping)) return;
  renderDsrImportPreview();
  setDsrImportStage("preview");
});
$("#dsrImportMappingTable").on("change", ".dsr-import-map", function () {
  const selected = $(this).val();
  if (selected && $(".dsr-import-map").not(this).filter(function () { return $(this).val() === selected; }).length) {
    alert("Each DSR field can only be mapped once.");
    $(this).val("");
  }
});
$("#dsrImportPreviewTable").on("input", ".dsr-import-cell", function () {
  const row = dsrImportRows[Number($(this).closest("tr").attr("data-row"))];
  const columnIndex = getDsrImportMapping()[$(this).data("field")];
  row.values[columnIndex] = $(this).val();
  validateDsrImportRows(getDsrImportMapping());
});
$("#dsrImportPreviewTable").on("change", ".dsr-import-cell", function () {
  renderDsrImportPreview();
});
$("#dsrImportPreviewTable").on("change", ".dsr-import-customer", function () {
  dsrImportRows[Number($(this).closest("tr").attr("data-row"))].customerSelection = $(this).val();
  renderDsrImportPreview();
});
$("#dsrImportPreviewTable").on("click", ".dsr-import-delete", function () {
  dsrImportRows.splice(Number($(this).closest("tr").attr("data-row")), 1);
  renderDsrImportPreview();
});
$("#dsrImportAddRow").on("click", () => {
  dsrImportRows.push({ sourceRow: `new ${dsrImportRows.length + 1}`, values: dsrImportHeaders.map(() => ""), customerSelection: "" });
  renderDsrImportPreview();
});
$("#dsrImportConfirm").on("click", async () => {
  const mapping = getDsrImportMapping();
  if (!validateDsrImportRows(mapping)) return;
  const confirmButton = $("#dsrImportConfirm").prop("disabled", true);
  const verified = await checkDsrImportBillIds(mapping);
  if (!verified || !validateDsrImportRows(mapping)) {
    confirmButton.prop("disabled", true);
    return;
  }
  const importedBills = dsrImportRows.map((row) => {
    const customer = resolveDsrImportCustomer(row, mapping);
    const total = parseImportedNumber(getDsrImportValue(row, mapping, "bill_total")).value;
    const recovered = parseImportedNumber(getDsrImportValue(row, mapping, "bill_recovered")).value;
    const returned = parseImportedNumber(getDsrImportValue(row, mapping, "bill_returned")).value;
    const scheme = parseImportedNumber(getDsrImportValue(row, mapping, "bill_scheme")).value;
    return {
      picklist_id: picklist_id.val(),
      invoice_date: normalizeImportedDate(getDsrImportValue(row, mapping, "invoice_date")) || picklist_date.val(),
      invoice_id: String(getDsrImportValue(row, mapping, "invoice_id")).trim(),
      customer_id: customer.customer_id,
      customer_name: customer.customer_name,
      bill_total: total,
      bill_recovered: recovered,
      bill_returned: returned,
      bill_scheme: scheme,
      credit_amount: total - recovered - returned - scheme,
      prev_balance: ""
    };
  });
  customer_json.push(...importedBills);
  calculatePicklistTotals();
  creditors_table.clear().rows.add(customer_json).draw();
  creditors_table.page("last").draw("page");
  dsrImportConfirmed = true;
  $("#dsrImportPreviewStatus").text(`${importedBills.length} bill(s) imported into this DSR. Final imported data is shown below.`);
  renderDsrImportPreview();
  $("#dsrImportConfirm").addClass("d-none");
  bootstrap.Modal.getInstance(document.getElementById("dsrImportModal"))?.hide();
  document.getElementById("creditors_table").scrollIntoView({ behavior: "smooth", block: "center" });
});


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
  processDsr();
});

async function processDsr() {
  const $button = $("#btn_process_picklist").prop("disabled", true);
  try {
    const response = await mySuperSaver.performAjaxRequest({
      data: {
        function_to_call: "process_dsr",
        picklist_id: picklist_id.val(),
        picklist_saleman: picklist_saleman.val(),
        picklist_date: picklist_date.val(),
        customer_bills: JSON.stringify(customer_json),
      },
    });
    if (!response?.success) {
      throw new Error(response?.message || "The DSR could not be saved.");
    }

    const download = "download_sms_sheet.php?type=dsr&id=" + encodeURIComponent(picklist_id.val());
    if (confirm("DSR processed successfully. Download the SMS sheet now?")) {
      window.location.href = download;
    } else {
      window.location.reload();
    }
  } catch (error) {
    alert(`DSR processing failed: ${error.message || error}`);
  } finally {
    $button.prop("disabled", false);
  }
}

const dsrEditCsrf = document.querySelector('meta[name="dsr-edit-csrf"]')?.content || "";
let dsrEditBills = [];

function showDsrUpdateMessage(message, type = "danger") {
  $("#dsr_update_message").removeClass("d-none alert-danger alert-success alert-warning")
    .addClass(`alert-${type}`).text(message);
}

function requestDsrEdit(functionName, values = {}) {
  return mySuperSaver.performAjaxRequest({
    data: {
      function_to_call: functionName,
      csrf_token: dsrEditCsrf,
      ...values,
    },
  });
}

$("#load_dsr_updates").on("click", async function () {
  const button = $(this).prop("disabled", true);
  showDsrUpdateMessage("Loading DSRs...", "info");
  try {
    const sheets = await requestDsrEdit("get_editable_dsr_picklists");
    if (!Array.isArray(sheets)) throw new Error(sheets?.message || "Could not load DSRs.");
    const selector = $("#dsr_update_picklist").empty()
      .append($("<option>", { value: "", text: "Choose a DSR" }));
    for (const sheet of sheets) {
      const locked = Number(sheet.LockedCount) > 0;
      const label = `${sheet.Date} · ${sheet.Saleman} · ${sheet.ID} (${sheet.BillCount} bills${locked ? ", contains locked bills" : ""})`;
      selector.append($("<option>", { value: sheet.ID, text: label }));
    }
    $("#show_dsr_update").prop("disabled", sheets.length === 0);
    showDsrUpdateMessage(sheets.length ? "Choose a DSR to load its bills." : "No DSR sheets were found.", sheets.length ? "success" : "warning");
  } catch (error) {
    showDsrUpdateMessage(error.message || "Could not load DSRs.");
  } finally {
    button.prop("disabled", false);
  }
});

$("#dsr_update_picklist").on("change", function () {
  $("#show_dsr_update").prop("disabled", !this.value);
});

$("#show_dsr_update").on("click", async function () {
  const picklistId = $("#dsr_update_picklist").val();
  if (!picklistId) return;
  const button = $(this).prop("disabled", true);
  $("#dsr_update_editor").addClass("d-none");
  showDsrUpdateMessage("Loading DSR bills...", "info");
  try {
    const rows = await requestDsrEdit("get_dsr_edit_details", { picklist_id: picklistId });
    if (!Array.isArray(rows)) throw new Error(rows?.message || "Could not load DSR bills.");
    dsrEditBills = rows;
    const tbody = $("#dsr_update_bill_list").empty();
    for (const bill of rows) {
      const row = $("<tr>");
      $("<td>").text(bill.ID).appendTo(row);
      $("<td>").text(bill.CustomerID).appendTo(row);
      $("<td>").text(bill.CustomerName || "").appendTo(row);
      $("<td>").text(Number(bill.Total).toLocaleString()).appendTo(row);
      const locked = Number(bill.HasRecovery) > 0 || !["INFILE", "NILL"].includes(bill.BillStatus);
      for (const [key, value] of [["recovered", bill.Recovered], ["returned", bill.Returned], ["scheme", bill.Scheme]]) {
        const input = $("<input>", {
          type: "number", min: "0", step: "1", class: "form-control form-control-sm dsr-edit-amount",
          "data-field": key, value: Number(value || 0), disabled: locked, "aria-label": `${key} for bill ${bill.ID}`,
        });
        $("<td>").append(input).appendTo(row);
      }
      const remaining = Number(bill.Remaining);
      $("<td>", { class: locked ? "text-danger" : "dsr-edit-remaining" })
        .text(remaining.toLocaleString() + (locked ? " · locked by recovery activity" : ""))
        .appendTo(row);
      row.attr("data-bill-id", bill.ID).attr("data-total", Number(bill.Total));
      tbody.append(row);
    }
    $("#dsr_update_editor").removeClass("d-none");
    const editableCount = rows.filter((bill) => Number(bill.HasRecovery) === 0 && ["INFILE", "NILL"].includes(bill.BillStatus)).length;
    $("#save_dsr_update").prop("disabled", editableCount === 0);
    showDsrUpdateMessage(editableCount
      ? `Edit recovered, returned, and scheme values, then save. ${rows.length - editableCount} bill(s) with recovery activity are read-only.`
      : "All bills have recovery activity and are read-only.", editableCount ? "success" : "warning");
  } catch (error) {
    showDsrUpdateMessage(error.message || "Could not load DSR bills.");
  } finally {
    button.prop("disabled", false);
  }
});

$("#dsr_update_bill_list").on("input", ".dsr-edit-amount", function () {
  const row = $(this).closest("tr");
  const used = row.find(".dsr-edit-amount").toArray()
    .reduce((sum, input) => sum + (Number(input.value) || 0), 0);
  const remaining = Number(row.attr("data-total")) - used;
  row.find(".dsr-edit-remaining").text(remaining.toLocaleString());
  row.toggleClass("table-danger", remaining < 0);
});

$("#save_dsr_update").on("click", async function () {
  const button = $(this).prop("disabled", true);
  try {
    const updates = $("#dsr_update_bill_list tr").filter(function () {
      return !$(this).find(".dsr-edit-amount").first().prop("disabled");
    }).map(function () {
      const row = $(this);
      return {
        bill_id: row.attr("data-bill-id"),
        recovered: row.find('[data-field="recovered"]').val(),
        returned: row.find('[data-field="returned"]').val(),
        scheme: row.find('[data-field="scheme"]').val(),
      };
    }).get();
    const response = await requestDsrEdit("update_dsr_amounts", {
      picklist_id: $("#dsr_update_picklist").val(),
      bills: JSON.stringify(updates),
    });
    if (!response?.success) throw new Error(response?.message || "The DSR update failed.");
    showDsrUpdateMessage(response.message || "DSR updated.", "success");
    $("#show_dsr_update").trigger("click");
  } catch (error) {
    showDsrUpdateMessage(error.message || "The DSR update failed.");
  } finally {
    button.prop("disabled", false);
  }
});