$("#messageBox").hide();
$("#btn_sign_in").click(()=>{
    let admin_username=$("#admin_username").val();
    let admin_password=$("#admin_password").val();
    let function_to_call="login";
    console.log(admin_username,admin_password);
    $.ajax({
        url:'functionality/allfunctionality.php',
        type:'POST',
        data:{
            function_to_call:function_to_call,
            username:admin_username,
            username:admin_username,
            password:admin_password
        },
        success:(responce)=>{
            if(responce==1){
                $(location).attr('href','adminpanel.php');
            }
            else{
                $("#messageBox").show();
                $("#messageBody").html(responce);
            }
        }
    });
})
$("#close_messageBox").click(()=>{
    $("#messageBox").hide();
})