<div class="content container_12" align="center" style="float: center">
<div class="box grid_9" align="center" style="float: center">
        <div class="box-head" align="center" style="float: center"><h2>Send us a support ticket!</h2></div>
        <div class="box-content">

 <FORM action="support_create.php" method="post">


           <div class="form-row">
            <p class="form-label">Issue:</p>
            <div class="form-item"><input type="text" name="description" placeholder="Describe your issue here"></div>
           </div>

           <div class="form-row">
            <p class="form-label">Username/Seat Number</p>
            <div class="form-item"><input type="text" name="location"></div>
           </div>
<input type="hidden" value="<?php echo $_SESSION['usr'] ?>" name="submitted"><?php echo $_SESSION['usr'] ?>
        <div class="form-row">
             <label class="form-label">Urgency</label>
             <div class="form-item">
               <select name="urgency">
                 <option value="..Take your time..">Take your time</option>
                 <option value="..Soon, if you can..">Soon, if you can</option>
                 <option value="..It's pretty urgent..">It's pretty urgent</option>
                 <option value="..Right away please..">Right away please</option>
               </select>
             </div>
           </div>

   <INPUT type="submit" value="Send">
 </FORM>


</div></div>
           </div>
          <div class="clear"></div>
        </div>
      </div>
      </div>