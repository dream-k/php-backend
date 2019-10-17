<?php
require "../api_lib/lexAPI.php";
header("Content-Type: application/json");
if(isset($_POST["json"]))
{
    
    $json_data = rawurldecode($_POST["json"]);
    $json_data = json_decode($json_data,true);
    if(array_key_exists("keyword",$json_data) && array_key_exists("api_key",$json_data) && array_key_exists("page",$json_data))
    {
    $keyword = $json_data["keyword"];
    $api_key = $json_data["api_key"];
    $page = $json_data["page"];
    
    $exec_api  = new lexAPI();
    $exec_api = $exec_api->find_vehicle($api_key,$page,$keyword);
    
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