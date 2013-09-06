<?php


include '/connect.php';


$link = mysql_connect($db_host,$db_user,$db_pass) or die('Unable to establish a DB connection');

mysql_select_db($db_database,$link);



$game = $_POST['game'];

$name_type = $_POST['name_type'];

$teams_players = $_POST['teams_players'];

$stance = $_POST['stance'];

$start = $_POST['start'];

$winner = $_POST['winner'];

$creator = $_POST['creator'];

$description = $_POST['description'];



$query = "INSERT INTO SWANK_tournament

SET game ='$game', name_type = '$name_type', teams_players = '$teams_players', stance = '$stance', start = '$start', winner = '$winner', description = '$description', creator = '$creator'";

 

 if(mysql_query($query)){

    header('Location: /tournaments.php');}

    else{ 

        echo "Update failed, please return to the previous page and try again.";

        echo $alias;} 

        

?>