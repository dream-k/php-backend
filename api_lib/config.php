<?php

$db_host = "localhost";
$db_user = "hoopty";
$db_pass = "sznE2ZdVJ4T1rvHk";
$db_name = "hoopty";

$con = mysqli_connect($db_host,$db_user,$db_pass,$db_name);

if(!$con)
{
    die("Incorect MYSQL credentials");
}

?>