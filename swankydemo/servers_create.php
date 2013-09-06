<?php

include '/connect.php';
$link = mysql_connect($db_host,$db_user,$db_pass) or die('Unable to establish a DB connection');

mysql_select_db($db_database,$link);



$type = $_POST['type'];

$name = $_POST['name'];

$purpose = $_POST['purpose'];

$ip = $_POST['ip'];

$listname = $_POST['listname'];

$owner = $_POST['owner'];




$query = "INSERT INTO SWANK_servers

SET type ='$type', name = '$name', purpose = '$purpose', ip= '$ip', listname = '$listname', owner = '$owner'";

 

 if(mysql_query($query)){

    header('Location: /servers_success.php');}

    else{ 

        echo "Update failed, please return to the previous page and try again.";

        echo $alias;} 

        

?>