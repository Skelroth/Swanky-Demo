      <link rel="stylesheet" href="css/master.css">
      <script src="js/flot/jquery.flot.min.js"></script>
      <script src="js/flot/jquery.flot.pie.min.js"></script>
      <script src="js/flot/jquery.flot.resize.min.js"></script>

    <body>
<?php

$con=mysqli_connect("Swanky.db.11099833.hostedresource.com","Swanky","Tworlds123!","Swanky");

// Check connection

if (mysqli_connect_errno())

  {

  echo "Failed to connect to MySQL: " . mysqli_connect_error();

  }
$attendeesmax = 45;
$attendeesin = mysqli_query($con,"SELECT row 
FROM SWANK_tournament 
WHERE id=(
    SELECT max(id) FROM table
    )");

?>


      <div class="content container_12">
          <div class="sm-box grid_12">
      <span><h2>Attendees ($attendeesin/$attendeesmax)</h2>
       <div id="progressbar" class="ui-progressbar ui-widget ui-widget-content ui-corner-all" role="progressbar" aria-valuemin="0" aria-valuemax="<?php $attendeesin ?>" aria-valuenow="<?php $attendeesin ?>"><div class="ui-progressbar-value ui-widget-header ui-corner-left ui-corner-right" style="width: 20%;"></div></div>
      </span>
     </div>


          <div class="box grid_6">
            <div class="box-head"><h2>LAN Champion Overlord</h2></div>
            <div class="box-content">
              <div id="flot-bars" style="padding: 0px; position: relative;"><canvas class="base" width="859" height="180"></canvas><canvas class="overlay" width="859" height="180" style="position: absolute; left: 0px; top: 0px;"></canvas><div class="tickLabels" style="font-size:smaller"><div class="xAxis x1Axis" style="color:#545454"><div class="tickLabel" style="position:absolute;text-align:center;left:-23px;top:150px;width:85px">0.0</div><div class="tickLabel" style="position:absolute;text-align:center;left:76px;top:150px;width:85px">2.5</div><div class="tickLabel" style="position:absolute;text-align:center;left:176px;top:150px;width:85px">5.0</div><div class="tickLabel" style="position:absolute;text-align:center;left:275px;top:150px;width:85px">7.5</div><div class="tickLabel" style="position:absolute;text-align:center;left:375px;top:150px;width:85px">10.0</div><div class="tickLabel" style="position:absolute;text-align:center;left:474px;top:150px;width:85px">12.5</div><div class="tickLabel" style="position:absolute;text-align:center;left:574px;top:150px;width:85px">15.0</div><div class="tickLabel" style="position:absolute;text-align:center;left:673px;top:150px;width:85px">17.5</div><div class="tickLabel" style="position:absolute;text-align:center;left:773px;top:150px;width:85px">20.0</div></div><div class="yAxis y1Axis" style="color:#545454"><div class="tickLabel" style="position:absolute;text-align:right;top:138px;right:847px;width:12px">0</div><div class="tickLabel" style="position:absolute;text-align:right;top:115px;right:847px;width:12px">10</div><div class="tickLabel" style="position:absolute;text-align:right;top:92px;right:847px;width:12px">20</div><div class="tickLabel" style="position:absolute;text-align:right;top:69px;right:847px;width:12px">30</div><div class="tickLabel" style="position:absolute;text-align:right;top:45px;right:847px;width:12px">40</div><div class="tickLabel" style="position:absolute;text-align:right;top:22px;right:847px;width:12px">50</div><div class="tickLabel" style="position:absolute;text-align:right;top:-1px;right:847px;width:12px">60</div></div></div><div class="legend"><div style="position: absolute; width: 47px; height: 28px; top: 9px; right: 9px; background-color: rgb(255, 255, 255); opacity: 0.85;"> </div><table style="position:absolute;top:9px;right:9px;;font-size:smaller;color:#545454"><tbody><tr><td class="legendColorBox"><div style="border:1px solid #ccc;padding:1px"><div style="width:4px;height:0;border:5px solid #71a100;overflow:hidden"></div></div></td><td class="legendLabel">Green</td></tr><tr><td class="legendColorBox"><div style="border:1px solid #ccc;padding:1px"><div style="width:4px;height:0;border:5px solid #308eef;overflow:hidden"></div></div></td><td class="legendLabel">Blue</td></tr></tbody></table></div></div>
            </div>
          </div>
          </div> 
	          <div class="box grid_6">
	            <div class="box-head"><h2>Realtime Chart</h2></div>
		            <div class="box-content">
				              <div id="flot-realtime" style="padding: 0px; position: relative;">
				              <canvas class="base" width="859" height="180"></canvas>
				              <canvas class="overlay" width="859" height="180" style="position: absolute; left: 0px; top: 0px;"></canvas>
				              <div class="tickLabels" style="font-size:smaller">
					              <div class="yAxis y1Axis" style="color:#545454">
					              <div class="tickLabel" style="position:absolute;text-align:right;top:291px;right:841px;width:18px">0</div>
					              <div class="tickLabel" style="position:absolute;text-align:right;top:233px;right:841px;width:18px">20</div>
					              <div class="tickLabel" style="position:absolute;text-align:right;top:174px;right:841px;width:18px">40</div>
					              <div class="tickLabel" style="position:absolute;text-align:right;top:116px;right:841px;width:18px">60</div>
					              <div class="tickLabel" style="position:absolute;text-align:right;top:57px;right:841px;width:18px">80</div>
					              <div class="tickLabel" style="position:absolute;text-align:right;top:-1px;right:841px;width:18px">100</div>
					              </div>
				              </div>
		              </div>
	            </div>
          </div>
      </div>
    </body>