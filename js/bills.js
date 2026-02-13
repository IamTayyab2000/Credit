import SuperSaver from "./SuperSaver.js";
// Needed Code Dont Delete
$("#messageBox").hide();
$("#close_messageBox").click(() => {
  $("#messageBox").hide();
});
var api='functionality/allFunctionality.php';
const mySuperSaver=new SuperSaver(api);
let picklist_id=mySuperSaver.getParameterByName('picklist_id');
let data={
    data:{
        function_to_call:'get_bills_for_picklist',
        picklist_id:picklist_id
    }
}
const table_bills = $("#bills").DataTable({
    columns: [
        { "data": "bill_id" },
        { "data": "cutomer_id" },
        { "data": "customer_name" },
        { "data": "bill_date" },
        { "data": "bill_amount" },
        { "data": "Bill_status" },
        {
            "data": null,
            "render": function (data, type, row) {
                return '<button class="btn btn-primary btn-get-id" data-id="' + data.bill_id + '">See Ledger</button>';
            }
        }
    ]
});

mySuperSaver.performAjaxRequest(data).then((responce)=>{
    table_bills.clear().rows.add(responce).draw();
})
$('#bills').on('click', '.btn-get-id', function() {
    var billId = $(this).data('id');
    let url='ledger.php?bill_id='+billId;
    window.open(url,'_blank');
});
