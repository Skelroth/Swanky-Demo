<div class="content container_12">

<div class="box grid_12">

        <div class="box-head"><h2>Add A Tournament</h2></div>

        <div class="box-content no-pad" style="min-height: 0px; display: none;">

        <form action="tournaments_create.php" method="post">

        <table class="display" id="dt2">

        <thead>

          <tr>

            <th>Game</th>

            <th>Name/Type</th>

			<th>Teams/Players</th>

            <th>Stance</th>

            <th>Start Time</th>

            <th>Creator</th>
          </tr>

        </thead>

        <tbody>

          <tr>

            <td><input type="text" name="game" placeholder="Game"></td>

            <td><input type="text" name="name_type" placeholder="Name/Type"></td>

			<td><input type="text" name="teams_players" placeholder="Teams/Players"></td>

            <td><input type="text" name="stance" style="color:red" value="[UNOFFICIAL]" readonly></td>

            <td><input type="time" name="start"></td>

            <td><input type="hidden" value="<?php echo $_SESSION['usr'] ?>" name="creator"><?php echo $_SESSION['usr'] ?></td>

          </tr>

        </tbody>	
      </table>
          <th>Description</th>
          <td><input type="text" size="400" name="description" placeholder="Description"></td>
	          <ul class="table-toolbar">

          <img src="img/icons/basic/plus.png"/><input type="submit" value="Submit">

        </ul>

        </form>

        </div>

        

