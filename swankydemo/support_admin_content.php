<!doctype html>
<html lang="en">
<head>
  <link rel="stylesheet" href="css/master.css">
  <script src="js/jquery-1.7.1.min.js"></script>
  <script src="js/jquery-ui-1.8.17.min.js"></script>
</head>
  <!--- CONTENT AREA -->

<div align="center" style="float: center" >
  <div class="content container_12" style="float: center">
     <div class="box grid_12" style="float: center">
     	<div id="accordion" class="ui-accordion ui-widget ui-helper-reset ui-accordion-icons" role="tablist">




<?php

include '/connect.php';

if (mysqli_connect_errno())
  {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }

$result = mysqli_query($con,"SELECT * FROM SWANK_support");

while($row = mysqli_fetch_array($result))
  {
      echo "<h3 class=\"ui-accordion-header ui-helper-reset ui-state-default><span class=\"ui-icon ui-icon-triangle-1-e\"></span><a href=#" . $row['order'] . ">". $row['order'];
      echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $row['submitted'] . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $row['completed'] . "</a></h3>";
      echo "<div>";
      echo "<p align=\"left\"><td><p>Urgency:</p>" . $row['urgency'] . "</td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <br><td><p>Location:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>" . $row['location'] . "</td><br><p>Description:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>" . $row['description'] . "</p>";
      echo "<td><form action=\"deletesupport.php\" method=\"post\">";
      echo "<input type=\"hidden\" name=\"delete_order\" value=\"" . $row['order'] . "\"/>";
      echo "<input type=\"submit\" value=\"Fixed! Delete.\"/></form></td>";  
      echo "</div>";
  }
?>
			</div>
        </div>
			</div>
        </div>

<script>
$(function() {
   $( "#accordion" ).accordion({ fillSpace: false });  
});
</script>

</body>
