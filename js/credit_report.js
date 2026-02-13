import SuperSaver from "./SuperSaver.js";
// Needed Code Dont Delete
$("#messageBox").hide();
$("#close_messageBox").click(() => {
  $("#messageBox").hide();
});
var api='functionality/allFunctionality.php';
const mySuperSaver=new SuperSaver(api);

const picklist_report=$('#picklistTable').DataTable({
        "columns": [
            { "data": "picklist_id" },
            { "data": "saleman_name" },
            { "data": "picklist_date" },
            { "data": "picklist_amount" },
            { "data": "Recovered Amount" },
            { "data": "Remaining Amount" },
            { "data": "Recovery %" },
            {
                "data": null,
                "render": function (data, type, row) {
                    return '<button class="btn btn-primary btn-get-id" data-id="' + data.picklist_id + '">See Bills</button>';
                }
            }
        ],
        "createdRow": function (row, data, dataIndex) {
            var recoveryPercent = data["Recovery %"];
            var picklistDate = new Date(data["picklist_date"]);
            var currentDate = new Date();
            var daysDifference = Math.floor((currentDate - picklistDate) / (1000 * 60 * 60 * 24));
            
            if (recoveryPercent < 50 && daysDifference >= 14) {
                $(row).addClass("table-danger");
            } else if (recoveryPercent >= 50 && recoveryPercent < 80 && daysDifference >= 7) {
                $(row).addClass("table-warning");
            } else if (recoveryPercent >= 80 && daysDifference >= 7) {
                $(row).addClass("table-success");
            }
        }
    });
mySuperSaver.LoadDataTable(picklist_report,'picklist_report');
$('#picklistTable').on('click', '.btn-get-id', function() {
    var picklistId = $(this).data('id');
    let url='bills.php?picklist_id='+picklistId;
    window.open(url,'_blank');
});
