<?php
include_once(__DIR__ . '/condb.php');
$conn=$conn;
function auth($username,$password,$tableName){
    $username=mysqli_escape_string($GLOBALS['conn'],$username);
    $password=mysqli_escape_string($GLOBALS['conn'],$password);
    $tableName=mysqli_escape_string($GLOBALS['conn'],$tableName);
    $query="SELECT COUNT(*) AS 'Auth'
    FROM (SELECT admin_id FROM {$tableName} WHERE admin_name = '{$username}' AND admin_password = '$password') AS SubqueryAlias;";
    $result=json_decode(read($query));
    return $result[0]->Auth ?? 'not fount';
}
function create_update_delete($query){
     $result=mysqli_query($GLOBALS['conn'],$query);
     // echo $query;
     if($result){
        return 1;
     }
     else{
        return 0;
     }
}
function read($query){
    $result=mysqli_query($GLOBALS['conn'],$query);
    $data=array();
    while($row=mysqli_fetch_assoc($result)){
        $data[]=$row;
    }
    return json_encode($data);
}
function ifexist($query) {
     $result = mysqli_query($GLOBALS['conn'], $query);
 
     if (!$result) {
         die("Query failed: " . mysqli_error($GLOBALS['conn']));
     }
 
     $row_count = mysqli_num_rows($result);
     
     if ($row_count > 0) {
         return true; // Record(s) exist
     } else {
         return false; // No record found
     }
 }
 function return_last_entered_record_id($query) {
     $result = mysqli_query($GLOBALS['conn'], $query);
 
     if ($result) {
         $last_id = mysqli_insert_id($GLOBALS['conn']);
         return $last_id;
     } else {
         echo mysqli_error($GLOBALS['conn']); // Print the MySQL error message for debugging
         return false; // Return false to indicate an error
     }
 }

function getData($key){
      return isset($_GET[$key]) ? mysqli_escape_string($GLOBALS['conn'],$_GET[$key]) : '';
}
function postData($key){
      return isset($_POST[$key]) ? mysqli_escape_string($GLOBALS['conn'],$_POST[$key]) : '';
}
if(isset($_POST['function_to_call'])){
     $function_to_call=mysqli_escape_string($conn,$_POST['function_to_call']);     
 }
 elseif (isset($_GET['function_to_call'])){
     $function_to_call=mysqli_escape_string($conn,$_GET['function_to_call']);
}

?>
