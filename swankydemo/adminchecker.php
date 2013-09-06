    <?php
  $t=$_SESSION['usr'];
  include 'connect.php';
// Check connection
if (mysqli_connect_errno())
  {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }
$result = mysqli_query($con,"SELECT * FROM tz_members WHERE usr ='$t' AND admin= 1");
while($row = mysqli_fetch_array($result))  {
$alias = $row['usr'];
  }

?>