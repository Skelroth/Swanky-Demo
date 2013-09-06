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

include 'connect.php';

$result = mysqli_query($con,"SELECT * FROM SWANK_tournament");

while($row = mysqli_fetch_array($result))
  {
     echo "<h3 class=\"ui-accordion-header ui-helper-reset ui-state-default><span class=\"ui-icon ui-icon-triangle-1-e\"></span><a href=#" . $row['ID'] . ">". $row['game'] . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $row['start'] . "</a></h3>";
     echo "<div>";
     echo "<p align=\"left\"><td>" . $row['stance'] . "</td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; (Teams/Players) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <td>" . $row['teams_players'] . "</td><br>" . $row['description'] . "</p>";
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
