<?php
$idholder = $_POST['delete_id'];
include '/connect.php';
// Check connection
if (mysqli_connect_errno())
  {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }
mysqli_query($con,"DELETE FROM SWANK_servers WHERE ID=$idholder");
header ("location:/servers.php");
?>