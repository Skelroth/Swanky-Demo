<?php


include 'connect.php';


$link = mysql_connect($db_host,$db_user,$db_pass) or die('Unable to establish a DB connection');

mysql_select_db($db_database,$link);

$fname = $_POST['fname'];

$lname = $_POST['lname'];

$alias = $_POST['alias'];

$cpu = $_POST['cpu'];

$gpu = $_POST['gpu'];

$os = $_POST['os'];

$email = $_POST['email'];

$seatnumber = $_POST['seatnumber'];

$ID = $_POST['ID'];



$query = "INSERT INTO SWANK_user_detail (fname, lname, cpu, gpu, operating_system, seatnumber, email, ID, alias)
			VALUES ('$fname', '$lname', '$cpu', '$gpu', '$os', '$seatnumber', '$email', '$ID', '$alias')
			ON DUPLICATE KEY
				UPDATE fname ='$fname', lname = '$lname', cpu = '$cpu', gpu = '$gpu', operating_system = '$os', seatnumber = '$seatnumber', email = '$email'";


 if(mysql_query($query)){
 header('Location: /profile.php');}
else{ 
        echo "Update failed, please return to the previous page and try again.";
} 
?>