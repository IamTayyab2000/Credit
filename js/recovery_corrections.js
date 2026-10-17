const endpoint = "functionality/allFunctionality.php";
const csrfToken = document.querySelector('meta[name="recovery-correction-csrf"]').content;
let sheets = [];
let selectedSheet = null;

const sheetList = document.getElementById("sheet-list");
const billList = document.getElementById("bill-list");
const selectedSheetLabel = document.getElementById("selected-sheet-label");
const passwordInput = document.getElementById("correction-password");
const reasonInput = document.getElementById("correction-reason");
const rollbackButton = document.getElementById("rollback-sheet");
const messageBox = document.getElementById("correction-message");
const auditList = document.getElementById("audit-list");

async function request(functionName, values = {}) {
  const body = new URLSearchParams({
    function_to_call: functionName,
    csrf_token: csrfToken,
    ...values,
  });
  const response = await fetch(endpoint, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" },
    body,
  });
  const result = await response.json();
  if (!response.ok) throw new Error(result?.message || "The request failed.");
  return result;
}

function showMessage(message, type = "danger") {
  messageBox.textContent = message;
  messageBox.className = `alert alert-${type}`;
}

function clearMessage() {
  messageBox.textContent = "";
  messageBox.className = "alert d-none";
}

function createCell(row, value, className = "") {
  const cell = document.createElement("td");
  cell.textContent = value ?? "";
  if (className) cell.className = className;
  row.appendChild(cell);
  return cell;
}

async function loadSheets() {
  clearMessage();
  sheetList.replaceChildren();
  const loadingRow = document.createElement("tr");
  createCell(loadingRow, "Loading sheets...", "text-center text-muted py-4").colSpan = 5;
  sheetList.appendChild(loadingRow);

  try {
    const result = await request("get_recovery_correction_sheets");
    if (!Array.isArray(result)) {
      throw new Error(result?.message || "Could not load recovery sheets. Apply the recovery-corrections database migration first.");
    }
    sheets = result;
    renderSheets();
    if (selectedSheet) {
      selectedSheet = sheets.find((sheet) => String(sheet.ID) === String(selectedSheet.ID)) || null;
      if (selectedSheet) await loadDetails(selectedSheet.ID);
    }
  } catch (error) {
    sheetList.replaceChildren();
    const row = document.createElement("tr");
    createCell(row, error.message, "text-danger py-3").colSpan = 5;
    sheetList.appendChild(row);
    showMessage(error.message);
  }
}

function renderSheets() {
  sheetList.replaceChildren();
  if (sheets.length === 0) {
    const row = document.createElement("tr");
    createCell(row, "No recovery sheets found.", "text-center text-muted py-4").colSpan = 5;
    sheetList.appendChild(row);
    return;
  }

  for (const sheet of sheets) {
    const row = document.createElement("tr");
    if (selectedSheet && String(selectedSheet.ID) === String(sheet.ID)) row.classList.add("table-active");
    createCell(row, `#${sheet.ID}`);
    createCell(row, sheet.Date);
    createCell(row, sheet.Saleman || "Unknown");
    const status = sheet.CorrectionStatus === "ROLLED_BACK"
      ? "Rolled back"
      : sheet.Status === "1" ? "Processed" : "Open";
    createCell(row, status);

    const actionCell = document.createElement("td");
    const viewButton = document.createElement("button");
    viewButton.type = "button";
    viewButton.className = "btn btn-outline-primary btn-sm";
    viewButton.textContent = "View";
    viewButton.addEventListener("click", () => loadDetails(sheet.ID));
    actionCell.appendChild(viewButton);
    row.appendChild(actionCell);
    sheetList.appendChild(row);
  }
}

async function loadAudit() {
  auditList.replaceChildren();
  try {
    const entries = await request("get_recovery_correction_audit");
    if (!Array.isArray(entries)) throw new Error(entries?.message || "Could not load correction history.");
    if (entries.length === 0) {
      const row = document.createElement("tr");
      createCell(row, "No corrections recorded.", "text-center text-muted py-4").colSpan = 7;
      auditList.appendChild(row);
      return;
    }
    for (const entry of entries) {
      const row = document.createElement("tr");
      createCell(row, entry.Created);
      createCell(row, entry.Admin);
      createCell(row, `#${entry.SheetID}`);
      createCell(row, entry.BillID || "All bills");
      createCell(row, entry.Action === "ROLLBACK_SHEET" ? "Sheet rollback" : "Bill edit");
      createCell(row, entry.Reason);
      const before = JSON.parse(entry.BeforeState);
      const after = JSON.parse(entry.AfterState);
      createCell(row, `${JSON.stringify(before)} -> ${JSON.stringify(after)}`, "small text-break");
      auditList.appendChild(row);
    }
  } catch (error) {
    const row = document.createElement("tr");
    createCell(row, error.message, "text-danger py-3").colSpan = 7;
    auditList.appendChild(row);
  }
}

async function loadDetails(sheetId) {
  clearMessage();
  selectedSheet = sheets.find((sheet) => String(sheet.ID) === String(sheetId));
  if (!selectedSheet) return;
  selectedSheetLabel.textContent = `Sheet #${selectedSheet.ID} · ${selectedSheet.Saleman || "Unknown"} · ${selectedSheet.Date}`;
  rollbackButton.disabled = selectedSheet.Status !== "1" || selectedSheet.CorrectionStatus !== "ACTIVE";
  billList.replaceChildren();
  const loadingRow = document.createElement("tr");
  createCell(loadingRow, "Loading bill details...", "text-center text-muted py-4").colSpan = 8;
  billList.appendChild(loadingRow);
  renderSheets();

  try {
    const result = await request("get_recovery_correction_details", { recID: selectedSheet.ID });
    if (!Array.isArray(result)) throw new Error(result?.message || "Could not load bill details.");
    renderBills(result);
  } catch (error) {
    billList.replaceChildren();
    const row = document.createElement("tr");
    createCell(row, error.message, "text-danger py-3").colSpan = 8;
    billList.appendChild(row);
    showMessage(error.message);
  }
}

function renderBills(bills) {
  billList.replaceChildren();
  if (bills.length === 0) {
    const row = document.createElement("tr");
    createCell(row, "This sheet has no bill details.", "text-center text-muted py-4").colSpan = 8;
    billList.appendChild(row);
    return;
  }

  const canEdit = selectedSheet.Status === "1" && selectedSheet.CorrectionStatus === "ACTIVE";
  for (const bill of bills) {
    const row = document.createElement("tr");
    createCell(row, bill.ID);
    createCell(row, bill.Shop || "Unknown");
    createCell(row, Number(bill.Amount).toLocaleString());

    const recoveredInput = document.createElement("input");
    recoveredInput.type = "number";
    recoveredInput.min = "0";
    recoveredInput.step = "1";
    recoveredInput.className = "form-control form-control-sm";
    recoveredInput.value = Number(bill.Recovered || 0);
    recoveredInput.setAttribute("aria-label", `Recovered for bill ${bill.ID}`);
    createCell(row, "").appendChild(recoveredInput);

    const returnedInput = document.createElement("input");
    returnedInput.type = "number";
    returnedInput.min = "0";
    returnedInput.step = "1";
    returnedInput.className = "form-control form-control-sm";
    returnedInput.value = Number(bill.Returned || 0);
    returnedInput.setAttribute("aria-label", `Returned for bill ${bill.ID}`);
    createCell(row, "").appendChild(returnedInput);

    const remainingCell = createCell(row, "");
    const updateRemaining = () => {
      remainingCell.textContent = (Number(bill.Amount) - Number(recoveredInput.value || 0) - Number(returnedInput.value || 0)).toLocaleString();
    };
    recoveredInput.addEventListener("input", updateRemaining);
    returnedInput.addEventListener("input", updateRemaining);
    updateRemaining();

    const statusSelect = document.createElement("select");
    statusSelect.className = "form-select form-select-sm";
    for (const [value, label] of [["Nill", "Nill"], ["Return", "Return"]]) {
      const option = document.createElement("option");
      option.value = value;
      option.textContent = label;
      statusSelect.appendChild(option);
    }
    const currentStatus = bill.RecoveryStatus === "NILL" ? "Nill" : bill.RecoveryStatus === "BF" ? "BF" : "Return";
    statusSelect.value = currentStatus;
    statusSelect.disabled = !canEdit || currentStatus === "BF";
    createCell(row, "").appendChild(statusSelect);

    const actionCell = document.createElement("td");
    const saveButton = document.createElement("button");
    saveButton.type = "button";
    saveButton.className = "btn btn-primary btn-sm";
    saveButton.textContent = currentStatus === "BF" ? "Roll back sheet" : "Save";
    saveButton.disabled = !canEdit || currentStatus === "BF";
    saveButton.addEventListener("click", () => saveBill(bill.ID, recoveredInput.value, returnedInput.value, statusSelect.value));
    actionCell.appendChild(saveButton);
    row.appendChild(actionCell);
    billList.appendChild(row);
  }
}

function getConfirmation() {
  const password = passwordInput.value;
  const reason = reasonInput.value.trim();
  if (!password) throw new Error("Enter the correction password to confirm this action.");
  if (reason.length < 8) throw new Error("Enter a correction reason of at least 8 characters.");
  return { correction_password: password, reason };
}

async function saveBill(billId, recovered, returned, status) {
  clearMessage();
  try {
    const confirmation = getConfirmation();
    const result = await request("correct_recovery_bill", {
      recID: selectedSheet.ID,
      billID: billId,
      recovered,
      returned,
      status,
      ...confirmation,
    });
    if (!result?.success) throw new Error(result?.message || "The bill could not be corrected.");
    passwordInput.value = "";
    reasonInput.value = "";
    showMessage(result.message, "success");
    await Promise.all([loadSheets(), loadAudit()]);
  } catch (error) {
    showMessage(error.message);
  }
}

rollbackButton.addEventListener("click", async () => {
  if (!selectedSheet || !window.confirm(`Roll back recovery sheet #${selectedSheet.ID} and restore its bills?`)) return;
  clearMessage();
  try {
    const confirmation = getConfirmation();
    const result = await request("rollback_recovery_sheet", {
      recID: selectedSheet.ID,
      ...confirmation,
    });
    if (!result?.success) throw new Error(result?.message || "The recovery sheet could not be rolled back.");
    passwordInput.value = "";
    reasonInput.value = "";
    showMessage(result.message, "success");
    await Promise.all([loadSheets(), loadAudit()]);
  } catch (error) {
    showMessage(error.message);
  }
});

document.getElementById("refresh-sheets").addEventListener("click", () => Promise.all([loadSheets(), loadAudit()]));
Promise.all([loadSheets(), loadAudit()]);