<div class="content container_12">

      <div class="ad-notif-info grid_12"><p>Swanky pop-up notification, Demo accounts: <br></br> Admin : 06116a  &nbsp; &nbsp; &nbsp; Regular : 7f1de1 </p></div>

      <div class="box grid_12">

        <div class="box-head"><h2>Welcome!</h2></div>

        <div class="box-content">

          <p>Welcome to Swanky, this box will be your guide.

          <br></br><br>

          Through this interface you will be able to check and add information relevent to the current LAN.

		  This can range from current game servers online, registering for a tournament or song requests.

		  <br> </br> <br>

          </p>

        </div>

      </div>


      <div class="box grid_12">

        <div class="box-head"><h2>Users Rigs</h2></div>

        <div class="box-content no-pad">

        <table class="display" id="example">

        <thead>

          <tr>

			<th>Alias</th>

            <th class="ui-state-default" role="columnheader" tabindex="0" aria-controls="example" rowspan="1" colspan="1" style="width: 223px;" aria-sort="ascending" aria-label="CSS grade: activate to sort column descending">CPU</th>

            <th>GPU</th>

            <th>Platform(s)</th>

            <th>W</th>

            <th>L</th>

            <th>Tournaments Entered</th>

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

  echo "<td>" . $row['alias'] . "</td>";

  echo "<td>" . $row['cpu'] . "</td>";

  echo "<td>" . $row['gpu'] . "</td>";

  echo "<td>" . $row['operating_system'] . "</td>";

  echo "<td>" . $row['wins'] . "</td>";

  echo "<td>" . $row['loss'] . "</td>";

  echo "<td>" . $row['entered'] . "</td>";

  echo "</tr>";

  }

echo "</table>";



mysqli_close($con);

?>

        </tbody>

      </table>

        </div>

      </div>

  </div>