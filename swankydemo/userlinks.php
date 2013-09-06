<?php

include 'adminchecker.php';

if (($_SESSION['usr'] ? $_SESSION['usr'] : 'Guest') == 'Guest') {
  	echo "<p><a href=\"profile.php\">Profile</a>";
	echo "</p>";
} elseif ($_SESSION['usr'] == $alias) {
		echo "<p><a href=\"profile.php\">Profile</a>";
	echo "<a href=\"admin.php\">Admin</a>";
	echo "</p>";
}
else {
  	echo "<p><a href=\"profile.php\">Profile</a>";
	echo "</p>";
  }
?>