  <!---CSS Files-->

  <link rel="stylesheet" href="css/master.css">

  <link rel="stylesheet" href="css/tables.css">

  <!---jQuery Files-->

  <script src="js/jquery.dataTables.min.js"></script>

  <div class="content container_12">

<div class="box grid_12">

        <div class="box-head"><h2>Tournaments</h2></div>

        <div class="box-content no-pad">

        <table class="display" id="dt2">

        <thead>

          <tr>

            <th>Game</th>

            <th>Name/Type</th>

    		<th>Teams/Players</th>

            <th>Stance</th>

            <th>Start Time</th>

            <th>Creator</th>

            <th>Option</th>

          </tr>

        </thead>

        <tbody>

<?php

include '/connect.php';

// Check connection

if (mysqli_connect_errno())

  {

  echo "Failed to connect to MySQL: " . mysqli_connect_error();

  }

$isitme = mysqli_query($con,"SELECT * FROM SWANK_tournament WHERE creator = '$_SESSION[usr]'");

$result = mysqli_query($con,"SELECT * FROM SWANK_tournament");

while($row = mysqli_fetch_array($result))  {

  echo "<tr>";

  echo "<td>" . $row['game'] . "</td>";

  echo "<td>" . $row['name_type'] . "</td>";

  echo "<td>" . $row['teams_players'] . "</td>";

  echo "<td>" . $row['stance'] . "</td>";

  echo "<td>" . $row['start'] . "</td>";

  echo "<td>" . $row['creator'] . "</td>";

  if ($_SESSION['usr'] == $alias)  {

  echo "<td>" . "<form action=\"deletetournament.php\" method=\"post\">";
  echo "<input type=\"hidden\" name=\"delete_id\" value=\"" . $row['ID'] . "\"/>";
  echo "<input type=\"submit\" value=\"Delete\"/></form></td>";  

} elseif ($row['creator'] == $_SESSION['usr']) {

  echo "<td>" . "<form action=\"deletetournament.php\" method=\"post\">";
  echo "<input type=\"hidden\" name=\"delete_id\" value=\"" . $row['ID'] . "\"/>";
  echo "<input type=\"submit\" value=\"Delete\"/></form></td>";  
  
} else {
}

  }

mysqli_close($con);

?>

        </td>

        </tr>

        </tbody>

      </table>



        </div>

      </div>

	  </div>

      </div>

	  </div>