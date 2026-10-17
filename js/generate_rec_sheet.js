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

const rec_sheet_table=$("#rec_sheet_table").DataTable(
    {columns:[
        {data:'ID',title:'ID'},
        {data:'Saleman',title:'Saleman'},
        {data:'Date',title:'Date'},
        {data:'Amount',title:'Amount'},
        {data:'Recoverd',title:'Recoverd'},
        {data:'Status',
         title:'Status',
         render:function(data,type,row){
            if(type === 'display'){
                if(data==0){
                    return 'Not Processed';
                }else{
                    return 'Processed'
                }
            }
            return data;
         }
    },
    {
        data:'Status',
        title:'Action',
        render:(data,type,row)=>{
            if(type === 'display'){
                if(data==0){
                    return "<button class='btn btn-sm btn-success recovery-action-btn btn-download'>Print</button> <button class='btn btn-sm btn-outline-primary recovery-action-btn btn-sms-sheet'>SMS Sheet</button> <button class='btn btn-outline-primary btn-sm recovery-action-btn btn-update-pending'>Update</button> <button class='btn btn-info btn-sm recovery-action-btn btn-process'>Process</button>";
                }
                else{
                    return "<button class='btn btn-sm btn-primary recovery-action-btn btn-update'>Update</button> <button class='btn btn-sm btn-secondary recovery-action-btn btn-view'>View</button> <button class='btn btn-sm btn-outline-primary recovery-action-btn btn-sms-sheet'>SMS Sheet</button>";
                }
            }
            return data;
        }
    }
    ]}
);
function load_rec_tbl(){
    mySuperSaver.LoadDataTable(rec_sheet_table,'get_rec_sheet');
}
load_rec_tbl();
$('#rec_sheet_table tbody').on('click','.btn-download',function(){
    const rowData = rec_sheet_table.row($(this).closest('tr')).data();
    var rec_id=rowData.ID;
    window.open('recovery_sheet.php?rec_id='+rec_id,'_blank');
})
$('#rec_sheet_table tbody').on('click','.btn-process',function(){
    const rowData = rec_sheet_table.row($(this).closest('tr')).data();
    var rec_id=rowData.ID;
    window.location.href = 'process_recovery_sheet.php?rec_id='+encodeURIComponent(rec_id);
})
$('#rec_sheet_table tbody').on('click','.btn-update-pending',function(){
    const rowData = rec_sheet_table.row($(this).closest('tr')).data();
    window.location.href = 'process_recovery_sheet.php?rec_id='+encodeURIComponent(rowData.ID);
})
$('#rec_sheet_table tbody').on('click','.btn-view',function(){
    const rowData = rec_sheet_table.row($(this).closest('tr')).data();
    var rec_id=rowData.ID;
    window.open('view_processed_recovery_detail.php?rec_id='+rec_id,'_blank');
})
$('#rec_sheet_table tbody').on('click','.btn-sms-sheet',function(){
    const rowData = rec_sheet_table.row($(this).closest('tr')).data();
    window.location.href = 'download_sms_sheet.php?type=recovery&id=' + encodeURIComponent(rowData.ID);
})

const recoveryCsrf = document.querySelector('meta[name="recovery-correction-csrf"]')?.content || "";
const recoveryPanel = document.getElementById("recovery-update-panel");
const recoveryBillList = document.getElementById("recovery-update-bills");
const recoveryUpdateMessage = document.getElementById("recovery-update-message");
let selectedRecoverySheet = null;

async function recoveryRequest(functionName, values = {}) {
    const body = new URLSearchParams({ function_to_call: functionName, csrf_token: recoveryCsrf, ...values });
    const response = await fetch(api, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" },
        body,
    });
    let result;
    try {
        result = await response.json();
    } catch {
        throw new Error("The server returned an invalid response.");
    }
    if (!response.ok || result?.success === false) throw new Error(result?.message || "The request failed.");
    return result;
}

function showRecoveryUpdateMessage(message, type = "danger") {
    recoveryUpdateMessage.textContent = message;
    recoveryUpdateMessage.className = `alert alert-${type} mt-3 mb-0`;
}

function recoveryCell(row, value, className = "") {
    const cell = document.createElement("td");
    cell.textContent = value ?? "";
    if (className) cell.className = className;
    row.appendChild(cell);
    return cell;
}

async function openRecoveryEditor(sheet) {
    selectedRecoverySheet = sheet;
    recoveryPanel.classList.remove("d-none");
    document.getElementById("recovery-update-sheet-label").textContent =
        `Sheet #${sheet.ID} · ${sheet.Saleman || "Unknown"} · ${sheet.Date}`;
    recoveryBillList.replaceChildren();
    recoveryCell(recoveryBillList.insertRow(), "Loading bill details...", "text-center text-muted py-4").colSpan = 8;
    recoveryUpdateMessage.className = "alert d-none mt-3 mb-0";
    recoveryPanel.scrollIntoView({ behavior: "smooth", block: "start" });
    try {
        const sheets = await recoveryRequest("get_recovery_correction_sheets");
        if (!Array.isArray(sheets)) throw new Error(sheets?.message || "Could not verify the recovery sheet.");
        const current = sheets.find((item) => String(item.ID) === String(sheet.ID));
        if (!current || current.Status !== "1" || current.CorrectionStatus !== "ACTIVE") {
            throw new Error("Only an active, processed recovery sheet can be updated.");
        }
        const bills = await recoveryRequest("get_recovery_correction_details", { recID: sheet.ID });
        if (!Array.isArray(bills)) throw new Error(bills?.message || "Could not load recovery details.");
        renderRecoveryBills(bills);
        showRecoveryUpdateMessage("Changes require the correction password and a reason. Bills with later activity or BF status cannot be edited here.", "info");
    } catch (error) {
        recoveryBillList.replaceChildren();
        recoveryCell(recoveryBillList.insertRow(), error.message, "text-danger py-3").colSpan = 8;
        showRecoveryUpdateMessage(error.message);
    }
}

function renderRecoveryBills(bills) {
    recoveryBillList.replaceChildren();
    if (!bills.length) {
        recoveryCell(recoveryBillList.insertRow(), "This sheet has no bill details.", "text-center text-muted py-4").colSpan = 8;
        return;
    }
    for (const bill of bills) {
        const row = recoveryBillList.insertRow();
        recoveryCell(row, bill.ID);
        recoveryCell(row, bill.Shop || "Unknown");
        recoveryCell(row, Number(bill.Amount).toLocaleString());
        const recovered = document.createElement("input");
        recovered.type = "number";
        recovered.min = "0";
        recovered.step = "1";
        recovered.className = "form-control form-control-sm";
        recovered.value = Number(bill.Recovered || 0);
        recoveryCell(row, "").appendChild(recovered);
        const returned = document.createElement("input");
        returned.type = "number";
        returned.min = "0";
        returned.step = "1";
        returned.className = "form-control form-control-sm";
        returned.value = Number(bill.Returned || 0);
        recoveryCell(row, "").appendChild(returned);
        const remaining = recoveryCell(row, "");
        const updateRemaining = () => {
            remaining.textContent = (Number(bill.Amount) - Number(recovered.value || 0) - Number(returned.value || 0)).toLocaleString();
        };
        recovered.addEventListener("input", updateRemaining);
        returned.addEventListener("input", updateRemaining);
        updateRemaining();
        const status = document.createElement("select");
        status.className = "form-select form-select-sm";
        for (const [value, label] of [["Nill", "Nill"], ["Return", "Return"]]) {
            const option = document.createElement("option");
            option.value = value;
            option.textContent = label;
            status.appendChild(option);
        }
        const isForwarded = bill.RecoveryStatus === "BF";
        status.value = bill.RecoveryStatus === "NILL" ? "Nill" : "Return";
        status.disabled = isForwarded;
        recoveryCell(row, "").appendChild(status);
        const save = document.createElement("button");
        save.type = "button";
        save.className = "btn btn-primary btn-sm";
        save.textContent = isForwarded ? "BF locked" : "Save";
        save.disabled = isForwarded;
        save.addEventListener("click", async () => {
            save.disabled = true;
            try {
                const password = document.getElementById("recovery-correction-password").value;
                const reason = document.getElementById("recovery-correction-reason").value.trim();
                if (!password) throw new Error("Enter the correction password.");
                if (reason.length < 8) throw new Error("Enter a reason of at least 8 characters.");
                const result = await recoveryRequest("correct_recovery_bill", {
                    recID: selectedRecoverySheet.ID,
                    billID: bill.ID,
                    recovered: recovered.value,
                    returned: returned.value,
                    status: status.value,
                    correction_password: password,
                    reason,
                });
                showRecoveryUpdateMessage(result.message || "Recovery bill updated.", "success");
                await openRecoveryEditor(selectedRecoverySheet);
                load_rec_tbl();
            } catch (error) {
                showRecoveryUpdateMessage(error.message);
            } finally {
                save.disabled = false;
            }
        });
        recoveryCell(row, "").appendChild(save);
    }
}

$("#rec_sheet_table tbody").on("click", ".btn-update", function () {
    const sheet = rec_sheet_table.row($(this).closest("tr")).data();
    if (String(sheet.Status) === "0") {
        window.location.href = "process_recovery_sheet.php?rec_id=" + encodeURIComponent(sheet.ID);
        return;
    }
    openRecoveryEditor(sheet);
});

document.getElementById("close-recovery-update").addEventListener("click", () => {
    recoveryPanel.classList.add("d-none");
    selectedRecoverySheet = null;
});