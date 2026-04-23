<?php
ob_start();
session_start();
date_default_timezone_set('UTC');
include "includes/config.php";
error_reporting(0);
if (!isset($_SESSION['sname']) and !isset($_SESSION['spass'])) {
    header("location: login.html");
    exit();
}
$usrid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
?>
<?php
$flash = '';
if (isset($_SESSION['ticket_msg'])) {
  $flash = $_SESSION['ticket_msg'];
  unset($_SESSION['ticket_msg']);
}
?>
<ul class="nav nav-tabs">
    <li class="<?php echo $flash ? '' : 'active'; ?>"><a href="#mytickets" data-toggle="tab" aria-expanded="true">My Tickets</a></li>
    <li class="<?php echo $flash ? 'active' : ''; ?>"><a href="#open" data-toggle="tab" aria-expanded="false"> <span class="glyphicon glyphicon-pencil"></span> New Ticket</a></li>
</ul>

<div id="myTabContent" class="tab-content">
    <div class="tab-pane fade in <?php echo $flash ? 'active' : ''; ?>" id="open">
        <div class="form-group col-lg-5 ">
            <h3>Support Tickets</h3>
            <?php if ($flash) echo $flash; ?>
            <form method="post" action="tticket.html">
                <b>Title</b><br>
                <input type="text" placeholder="Seller request.." class="form-control" name="subject" required><br>
                <b>Message</b><br>
                <textarea class="form-control" name="message" style="width: 100%; height: 100px;" placeholder="Message Here" required></textarea>
                <br>
                <center><button type="submit" class="btn btn-primary">Submit <span class="glyphicon glyphicon-chevron-right"></span></button></center>
            </form>
        </div><br><br>
        <div class="col-lg-7">
            <div class="bs-component">
                <div class="well well">
                    <ul>
                        <li>In order to refund ticket go to <b>Account</b> -&gt; <b>My Orders</b> and choose the tool and click on <b>Report</b> button</li>
                        <li>Do not create double-tickets , create just one ticket and include all your problems then wait for your ticket to be replied</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php

 $uid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);

  $qq = @mysqli_query($dbcon, "SELECT * FROM ticket WHERE uid='$uid' ORDER by id desc") or die("error here");
echo '                  <br>
 
    <div class="tab-pane fade in '.($flash ? '' : 'active').'" id="mytickets"> 

<table width="100%" class="table table-striped table-bordered table-condensed" id="table">
      <thead>
        <tr>
              <th scope="col"></th>
          <th scope="col">ID</th>
          <th scope="col">Date Created</th>     
          <th scope="col">Title</th>      
          <th scope="col">Status</th>
          <th scope="col">Last Reply</th>
          <th scope="col">Last Updated</th>
        </tr>
      </thead>
 <tbody id="tbody2">';
 while($row = mysqli_fetch_assoc($qq)){
     $st = $row['status'];
    switch ($st){
      case "0" :
       $st = "closed";
       break;
      case "1" :
       $st = "open";
       break;
      case "2":
       $st = "open";
       break;
    }
    $idticket = $row['id'];
        if (empty($row['lastup'])) {
                $lastup = "n/a"; 
                } else { 
                $lastup = $row['lastup'];       
                }
?>
<tr onmouseover="this.style.cursor='pointer'" onclick="pageDiv('ticket<?php echo $idticket; ?>','Ticket #<?php echo $idticket; ?> - NULLNET','showTicket<?php echo $idticket; ?>.html',0);" style="cursor: pointer;">   
<?php
if ($row['seen'] == "1") {
        echo "
     <td>  </td>
 <td> <b>".htmlspecialchars($row['id'])." </b></td>
    <td><b> ".htmlspecialchars($row['date'])." </b></td>
    <td><b> ".htmlspecialchars($row['subject'])." </b></td>
    <td> <b>".$st." </b></td>
            <td><b> ".htmlspecialchars($row['lastreply'])." </b></td>
            <td><b> ".htmlspecialchars($lastup)." </b></td>
            </tr>
"; } else {
echo "
     <td>  </td>
 <td> ".htmlspecialchars($row['id'])." </td>
    <td> ".htmlspecialchars($row['date'])." </td>
    <td> ".htmlspecialchars($row['subject'])." </td>
    <td> ".$st."</td>
            <td> ".htmlspecialchars($row['lastreply'])." </td>
            <td> ".htmlspecialchars($lastup)." </td>
            </tr>
     ";
 } }

 echo '

 </tbody>
 </table>'; 
                                ?>
