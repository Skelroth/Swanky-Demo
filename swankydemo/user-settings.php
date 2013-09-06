<div class="content container_12">

<div class="box grid_12">

        <div class="box-head"><h2>Users!</h2></div>

        <div class="box-content no-pad">
		  <table class="display">

          <thead>

            <tr>

              <th>Seat</th>

              <th>Alias</th>

              <th>First Name</th>

              <th>OS</th>

              <th>CPU</th>

              <th>GPU</th>

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

$result = mysqli_query($con,"SELECT * FROM SWANK_user_detail");

while($row = mysqli_fetch_array($result))

  {

  echo "<tr>";

  echo "<td>" . $row['seatnumber'] . "</td>";

  echo "<td>" . $row['alias'] . "</td>";

  echo "<td>" . $row['fname'] . "</td>";

  echo "<td>" . $row['operating_system'] . "</td>";

  echo "<td>" . $row['cpu'] . "</td>";

  echo "<td>" . $row['gpu'] . "</td>";

  echo "</tr>";

  }

echo "</table>";



mysqli_close($con);

?>

         </table>

        </div>

      </div>

	</div>