<div class="content container_12">

<div class="box grid_12">

        <div class="box-head"><h2>Table with Toolbar</h2></div>

        <div class="box-content no-pad">

    	  <table class="display">

          <form action="update_details.php" method="post">

          <thead>

            <tr>

              <th>First Name</th>

              <th>Last Name</th>

              <th>Email</th>

              <th>Seat Number</th>

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

$result = mysqli_query($con,"SELECT * FROM SWANK_user_detail WHERE alias = '$_SESSION[usr]'");

while($row = mysqli_fetch_array($result))

  {

  $alias = $row['alias'];

  $cpu = $row['cpu'];

  $gpu = $row['gpu'];

  $os = $row['operating_system'];

  $entered = $row['entered'];

  $fname = $row['fname'];

  $lname = $row['lname'];

  $email = $row['email'];

  $seatnumber = $row['seatnumber'];

  }

mysqli_close($con);

?>



<td><input type="text" name="fname" value="<?php echo $fname ?>"></td>

<td><input type="text" name="lname" value="<?php echo $lname ?>"></td>

<td><input type="text" name="email" value="<?php echo $email ?>"></td>

<td><input type="text" name="seatnumber" value="<?php echo $seatnumber ?>"></td>

          <thead>

            <tr>

              <th>Alias</th>

              <th>CPU</th>

              <th>GPU</th>

              <th>OS</th>

            </tr>

          </thead>

<td><input type="text" name="alias" value="<?php echo $alias ?>"></td>

<td><input type="text" name="cpu" value="<?php echo $cpu ?>"></td>

<td><input type="text" name="gpu" value="<?php echo $gpu ?>"></td>

<td><input type="text" name="os" value="<?php echo $os ?>"></td>

         </table>

        </div>

                 <input style="float: right;" type="submit" value="Submit">

         </form> 

      </div>

      <div class="ad-notif-info grid_12"><p style="float: left">Details have been successfully updated!</p><p style="float: right">(click to dismiss)</p></div>

	</div>