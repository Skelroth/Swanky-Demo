<div class="content container_12">

<div class="box grid_12">

        <div class="box-head"><h2>Add your own server</h2></div>

        <div class="box-content no-pad" style="min-height: 0px; display: none;">

        <form action="servers_create.php" method="post">

        <table class="display" id="dt2">

        <thead>

          <tr>


            <th>Type</th>

            <th>Name</th>

            <th>Purpose</th>

            <th>IP</th>

            <th>PC-Listname</th>


          </tr>

        </thead>

        <tbody>

          <tr>

            <td><input type="text" name="type" placeholder="Type"></td>

            <td><input type="text" name="name" placeholder="Name"></td>

			<td><input type="text" name="purpose" placeholder="Purpose/Description"></td>

            <td><input type="text" name="ip" placeholder="0.0.0.0"></td>

            <td><input type="text" name="listname" placeholder="PC Share Name"></td>

            <td><input type="hidden" value="<?php echo $_SESSION['usr'] ?>" name="owner"><?php echo $_SESSION['usr'] ?></td>

          </tr>

        </tbody>	
      </table>
	          <ul class="table-toolbar">

          <img src="img/icons/basic/plus.png"/><input type="submit" value="Submit">

        </ul>

        </form>

        </div>

        

