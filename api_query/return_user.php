<?php
require "../api_lib/lexAPI.php";
header("Content-Type: application/json");
if(isset($_POST["json"]))
{
    
    $json_data = rawurldecode($_POST["json"]);
    $json_data = json_decode($json_data,true);
    if(array_key_exists("user_id",$json_data) && array_key_exists("api_key",$json_data))
    {
    $user_id = $json_data["user_id"];
    $api_key = $json_data["api_key"];
    
    $exec_api  = new lexAPI();
    $exec_api = $exec_api->return_user($api_key,$user_id);
    
    echo json_encode($exec_api);
    }
    else
    {
        echo "You have missing params!";
    }
    
}
else
{
    echo "No JSON parameters posted!";
}

?>