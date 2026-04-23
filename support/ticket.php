<?php

  include "header.php";
  $q = mysqli_query($dbcon, "SELECT * FROM ticket where status='1' or  status='2' ORDER BY status DESC")or die("error");
  $t = mysqli_num_rows($q);
  global $t,$q;

?>
<div class="alert alert-danger fade in radius-bordered alert-shadowed"> <i class="fa-fw fa fa-times"></i> <b>Tickets</b></div>

<ul class="nav nav-tabs">
    <li class="active"><a href="#mytickets" data-toggle="tab" aria-expanded="true"> <span class="glyphicon glyphicon-time"></span> Pending Tickets</a></li>
    <li class=""><a data-toggle="tab" aria-expanded="false"> <span class="glyphicon glyphicon-lock"></span> Insert Ticket</a></li>
</ul>
<div id="myTabContent" class="tab-content">
    <div class="tab-pane fade in" id="open"> 

</div>  
    <div class="tab-pane fade active in" id="mytickets"> 
    <?php
    echo '
    <div class="">
    <center>
        <div class="panel panel-default">
            <div class="panel-heading no-collapse">Tickets <span class="label label-primary">Total Pending : '.$t.'</span></div>
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Date Created</th>
                  <th>Title</th>
                  <th>Ticket State</th>
                  <th>Last Reply</th>
                  <th>Last Updated</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>';
              while($row = mysqli_fetch_assoc($q)){
                $st = $row['status'];

    switch ($st){
      case "0" :
       $st = "closed";
       break;
      case "1" :
       $st = "pending";
       break;
      case "2":
       $st = "pending";
       break;
    }
                if (empty($row['lastup'])) {
                $lastup = "n/a"; 
                } else { 
                $lastup = $row['lastup'];       
                }
                $staffBadge = !empty($row['assigned_to']) ? ' <span class="label label-warning" style="font-size:10px;">Staff: '.htmlspecialchars($row['assigned_to']).'</span>' : '';
              echo '<tr>
                  <td>'.$row['id'].'</td>
                  <td>'.$row['date'].'</td>
                  <td>'.htmlspecialchars($row['subject']).'<br><small><span class="label label-info" style="font-size:10px;">Buyer: '.htmlspecialchars($row['uid']).'</span>'.$staffBadge.'</small></td>
                  <td>'.$st.'</td>
                  <td>'.$row['lastreply'].'</td>
                  <td>'.$lastup.'</td>
                  <td><a class="btn btn-primary btn-xs" href="viewt.php?id='.$row['id'].'">View</a></td>
                  </tr>';
                  }
                  echo '

              </tbody>
            </table>
        </div>
    </div>';
    ?>

 </div>
 </div>
