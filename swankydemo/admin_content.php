<div class="content container_12">

<div class="box grid_12">

        <div class="box-head"><h2>Admins</h2></div>

        <div class="box-content no-pad">
      <table class="display">

          <thead>

            <tr>

              <th>User</th>

              <th>Email</th>

              <th>Registration IP</th>

              <th>Date-Time</th>

              <th>Stance</th>

              <th>Remove</th>

            </tr>

          </thead>

      <tbody align="center">

      <?php

$con=mysqli_connect("Swanky.db.11099833.hostedresource.com","Swanky","Tworlds123!","Swanky");

// Check connection

if (mysqli_connect_errno())

  {

  echo "Failed to connect to MySQL: " . mysqli_connect_error();

  }

$result = mysqli_query($con,"SELECT * FROM tz_members WHERE admin = 1");

while($row = mysqli_fetch_array($result))

  {

  echo "<tr>";

  echo "<td>" . $row['usr'] . "</td>";

  echo "<td>" . $row['email'] . "</td>";

  echo "<td>" . $row['regIP'] . "</td>";

  echo "<td>" . $row['dt'] . "</td>";

  echo "<td>" . "Admin" . "</td>";

  echo "<td>" . "<form action=\"demote.php\" method=\"post\"><input type=\"hidden\" name=\"Alias\" value=\"" . $row['usr'] . "\"><input type=\"submit\" class=\"button red\" value=\"Demote!\">" . "</form>" . "</td>";

  echo "</tr>";

  }

echo "</table>";



mysqli_close($con);

?>

         </table>

        </div>

      </div>




















<div class="box grid_12">

        <div class="box-head"><h2>Regular Users</h2></div>

        <div class="box-content no-pad">
      <table class="display">

          <thead>

            <tr>

              <th>User</th>

              <th>Email</th>

              <th>Registration IP</th>

              <th>Date-Time</th>

              <th>Stance</th>

              <th>Remove</th>

            </tr>

          </thead>

      <tbody align="center">

      <?php

$con=mysqli_connect("Swanky.db.11099833.hostedresource.com","Swanky","Tworlds123!","Swanky");

// Check connection

if (mysqli_connect_errno())

  {

  echo "Failed to connect to MySQL: " . mysqli_connect_error();

  }

$result = mysqli_query($con,"SELECT * FROM tz_members WHERE admin = 0");

while($row = mysqli_fetch_array($result))

  {

  echo "<tr>";

  echo "<td>" . $row['usr'] . "</td>";

  echo "<td>" . $row['email'] . "</td>";

  echo "<td>" . $row['regIP'] . "</td>";

  echo "<td>" . $row['dt'] . "</td>";

  echo "<td>" . "Regular" . "</td>";

  echo "<td>" . "<form action=\"promote.php\" method=\"post\"><input type=\"hidden\" name=\"Alias\" value=\"" . $row['usr'] . "\"><input type=\"submit\" class=\"button green\" value=\"Promote!\">" . "</td>";

  echo "</tr>";

  }

echo "</table>";



mysqli_close($con);

?>

         </table>

        </div>

      </div>

  </div>