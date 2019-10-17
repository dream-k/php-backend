<?php
require "../api_lib/lexAPI.php";
header("Content-Type: application/json");
if(isset($_POST["json"]))
{
    
    $user_data = rawurldecode($_POST["json"]);
    $user_data = json_decode($user_data,true);
    if(array_key_exists("vehicle_id",$json_data) && array_key_exists("api_key",$json_data))
    {
    $vehicle_id = $user_data["vehicle_id"];
    $api_key = $user_data["api_key"];
    
    $exec_api  = new lexAPI();
    $exec_api = $exec_api->return_vehicle($api_key,$vehicle_id);
    
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