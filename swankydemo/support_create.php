<?php


include '/connect.php';



$link = mysql_connect($db_host,$db_user,$db_pass) or die('Unable to establish a DB connection');

mysql_select_db($db_database,$link);



$description = $_POST['description'];

$location = $_POST['location'];

$urgency = $_POST['urgency'];

$submitted = $_POST['submitted'];



$query = "INSERT INTO SWANK_support

SET description ='$description', location = '$location', urgency = '$urgency', submitted = '$submitted'";

 

 if(mysql_query($query)){

    header('Location: /support_success.php');}

    else{ 

        echo "Update failed, please return to the previous page and try again.";

        echo $alias;} 

        

?>