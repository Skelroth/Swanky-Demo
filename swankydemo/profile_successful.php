<?php

include 'head-login.php';

include 'head.php';



?>

<body>

  <!--- HEADER -->

    <div class="header">

  </div>

  <div class="top-bar">

      <ul id="nav">

        <li id="user-panel">

          <img src="img/nav/usr-avatar.jpg" id="usr-avatar" alt="" />

          <div id="usr-info">

            <p id="usr-name">Welcome back, <?php echo $_SESSION['usr'] ? $_SESSION['usr'] : 'Guest';?>.</p>

                        <?php
            include 'userlinks.php';
            ?>


          </div>

        </li>

        <li>

        <ul id="top-nav">

         <li class="nav-item">

           <a href="index.php"><img src="img/nav/dash.png" alt="" /><p>Dashboard</p></a>

         </li>

         <li class="nav-item">

           <a href="tournaments.php"><img src="img/nav/tb.png" alt="" /><p>Tournaments</p></a>

         </li>

		 <li class="nav-item">

           <a href="schedule.php"><img src="img/nav/cal.png" alt="" /><p>Schedule</p></a>

         </li>

         <li class="nav-item">

           <a href="filemanager.php"><img src="img/nav/flm.png" alt="" /><p>File Manager</p></a>

        </li>

        <li class="nav-item">

           <a href="users.php"><img src="img/nav/typ.png" alt="" /><p>Users</p></a>

         </li>

         <li class="nav-item">

           <a href="servers.php"><img src="img/nav/err.png" alt="" /><p>Servers</p></a>

         </li>

                           <li class="nav-item">

           <a href="analytics.php"><img src="img/nav/grid.png" alt="" /><p>Support Ticket</p></a>

         </li>

       </ul>

      </li>

     </ul>

  </div>

  <input type='hidden' name='returnURL' value='/profile.php' />

  

  <?php

  $t=$_SESSION['usr'];

if (($_SESSION['usr'] ? $_SESSION['usr'] : 'Guest') == 'Guest')

  {

  }

else

  {

  include 'profile-settings_success.php';

  include 'footer.php';

  }

?>