<?php
require "../api_lib/lexAPI.php";
header("Content-Type: application/json");
if(isset($_POST["json"]))
{
    
    $user_data = rawurldecode($_POST["json"]);
    $user_data = json_decode($user_data,true);
    if(array_key_exists("limit",$json_data) && array_key_exists("api_key",$json_data) && array_key_exists("page",$json_data))
    {
    $limit = $user_data["limit"];
    $page = $user_data["page"];
    $api_key = $user_data["api_key"];
    
    $exec_api  = new lexAPI();
    $exec_api = $exec_api->return_vehicles($limit,$page,$api_key);
    
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