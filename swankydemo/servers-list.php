  <!---CSS Files-->

  <link rel="stylesheet" href="css/master.css">

  <link rel="stylesheet" href="css/tables.css">

  <!---jQuery Files-->

  <script src="js/jquery.dataTables.min.js"></script>

  <div class="content container_12">

<div class="box grid_12">

        <div class="box-head"><h2>Servers!</h2></div>

        <div class="box-content no-pad">

        <table class="display" id="dt2">

        <thead>

          <tr>

            <th>Type</th>

            <th>Name</th>

    		<th>Purpose</th>

            <th>IP</th>

            <th>PC-Listname</th>

            <th>Owner</th>

            <th>Option</th>

          </tr>

        </thead>

        <tbody>

<?php

$con=mysqli_connect("Swanky.db.11099833.hostedresource.com","Swanky","Tworlds123!","Swanky");

// Check connection

if (mysqli_connect_errno())

  {

  echo "Failed to connect to MySQL: " . mysqli_connect_error();

  }

$isitme = mysqli_query($con,"SELECT * FROM SWANK_servers WHERE owner = '$_SESSION[usr]'");

$result = mysqli_query($con,"SELECT * FROM SWANK_servers");

while($row = mysqli_fetch_array($result))  {

  echo "<tr>";

  echo "<td>" . $row['type'] . "</td>";

  echo "<td>" . $row['name'] . "</td>";

  echo "<td>" . $row['purpose'] . "</td>";

  echo "<td>" . $row['ip'] . "</td>";

  echo "<td>" . $row['listname'] . "</td>";

  echo "<td>" . $row['owner'] . "</td>";

echo $ID;

  if ($_SESSION['usr'] == $alias)  {

  echo "<td>" . "<form action=\"deleteserver.php\" method=\"post\">";
  echo "<input type=\"hidden\" name=\"delete_id\" value=\"" . $row['ID'] . "\"/>";
  echo "<input type=\"submit\" value=\"Delete\"/></form></td>"; 

} elseif ($row['owner']==$_SESSION[usr]) {

  echo "<td>" . "<form action=\"deleteserver.php\" method=\"post\">";
  echo "<input type=\"hidden\" name=\"delete_id\" value=\"" . $row['ID'] . "\"/>";
  echo "<input type=\"submit\" value=\"Delete\"/></form></td>"; 

} else {
}
echo "</tr>";
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