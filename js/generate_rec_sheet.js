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
            if(type='display'){
                if(data==0){
                    return 'Not Processed';
                }else{
                    return 'Processed'
                }
            }
         }
    },
    {
        data:'Status',
        title:'Action',
        render:(data,type,row)=>{
            if(type='display'){
                if(data==0){
                    return "<button class='btn btn-sm btn-success btn-download'>Download</button> <button class='btn btn-info btn-sm btn-process'>Process</button>";
                }
                else{
                    return "<button class='btn btn-sm btn-secondary btn-view'>View</button>";
                }
            }
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
    window.open('process_recovery_sheet.php?rec_id='+rec_id,'_blank');
})
$('#rec_sheet_table tbody').on('click','.btn-view',function(){
    const rowData = rec_sheet_table.row($(this).closest('tr')).data();
    var rec_id=rowData.ID;
    window.open('view_processed_recovery_detail.php?rec_id='+rec_id,'_blank');
})