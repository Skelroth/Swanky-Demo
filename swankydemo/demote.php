<?php
$Alias = $_POST['Alias'];
include '/connect.php';
// Check connection
if (mysqli_connect_errno())
  {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }

mysqli_query($con,"UPDATE tz_members SET admin=0 
	WHERE usr='$Alias'");

mysqli_close($con);
header('Location: /admin.php');
?>