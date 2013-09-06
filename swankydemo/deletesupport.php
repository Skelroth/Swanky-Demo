<?php
$orderholder = $_POST['delete_order'];
include '/connect.php';
$dbh= "DELETE FROM `SWANK_support` WHERE `SWANK_support`.`order` = $orderholder LIMIT 1";
// Check connection
if (mysqli_connect_errno())
  {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }
mysqli_query($con,$dbh);

mysqli_close($con);
header ("location:/support.php");
?>