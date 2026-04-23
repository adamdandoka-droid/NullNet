<?php
ob_start();
session_start();
error_reporting();
date_default_timezone_set('UTC');
include "includes/config.php";

if (!isset($_SESSION['sname']) and !isset($_SESSION['spass'])) {
    header("location: ../");
    exit();
}
?>
<script>
function sendt(id){

    var sub = $("#subject"+id).val();
    var msg = $("#msg"+id).val();
    var pr = $("#proi"+id).val();
     $.ajax({
     method:"GET",
     url:"treport.html?id="+id+"&m="+btoa(msg),
     dataType:"text",
     success:function(data){
     $("#resulta"+id).html(data).show();
     },
   });
}

    </script>
<div class="well well">
<h2><center><small><font color="#080C39"><span class="glyphicon glyphicon-shopping-cart"></span></small></font> My Orders       </h2>
<p align="center">You can only report a bad tool within <b>10 hours</b> by clicking on <a class="btn btn-primary btn-xs"><font color=white>Report #[Order Id]</a></font> , Otherwise we can't give you refund or replacement!</p>
                    </div>

<div class="input-group" style="margin-bottom:15px; max-width:400px;">
  <span class="input-group-addon"><span class="glyphicon glyphicon-search"></span></span>
  <input type="text" id="orderSearch" class="form-control" placeholder="Search orders by ID, type, item, seller or date...">
</div>

<table width="100%" class="table table-striped table-bordered table-condensed" id="table">
                                                
                                        <thead>
            <tr>
  <th scope="col">ID</th>
  <th scope="col">Type</th>
  <th scope="col">Item</th>
  <th scope="col">View</th>
  <th scope="col">Price</th>
  <th scope="col">Seller</th>
  <th scope="col">Report</th>
   <th scope="col">Date</th>
            </tr>
        </thead>
 <tbody id='tbody2'>
 <?php
$real_data = date("Y-m-d H:i:s");
$usrid     = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
$q = mysqli_query($dbcon, "SELECT * FROM purchases WHERE buyer='$usrid' ORDER BY id DESC") or die(mysqli_error($dbcon));

while ($row = mysqli_fetch_assoc($q)) {
    $idorder   = $row['id'];
    $toollink1 = $row['url'];
    $sidd      = $row['s_id'];
    $type      = $row['type'];
    $info      = $row['url'];
    $desc      = $row['infos'];
    echo "<tr>
            <td> " . $row['id'] . " </td>
    <td> " . strtoupper($row['type']) . " </td>
    <td> " . $row['url'] . " </td>";
?>
    <td> 
<button onclick="openitem(<?php echo $idorder; ?>)" class="btn btn-primary btn-xs"> View #<?php echo $idorder; ?></button>

    <?php
                            $qer = mysqli_query($dbcon, "SELECT * FROM resseller WHERE username='".$row['resseller']."'")or die(mysqli_error($dbcon));
                   while($rpw = mysqli_fetch_assoc($qer))
                         $SellerNick = "seller".$rpw["id"]."";
    echo "
    <td> " . $row['price'] . "</td>
            <td> " . $SellerNick . "</td>
    <td> ";
        $pending= 0;
    $date_purchased = $row['date'];
    $endTime        = strtotime("+600 minutes", strtotime($date_purchased));
    $data_plus      = date('Y-m-d H:i:s', $endTime);
    if (($real_data > $data_plus) && ($row['reported'] == "")) {
        echo 'Time expired';
    } else {
        if ($row['reported'] == "1") {
            $qrrr = mysqli_query($dbcon, "SELECT * FROM reports WHERE s_id='$sidd' and uid='$usrid'") or die(mysqli_error());
            while ($rowe = mysqli_fetch_assoc($qrrr)) {
                $idreport = $rowe['id'];
                echo "<font color='green'><a href='showReport$idreport.html' onclick=\"pageDiv('report$idreport','Report #$idreport - NullNet','showReport$idreport.html',0); return false;\"><u>#$idreport</u></a></font>";
            }
        } else {
            echo '<a data-toggle="modal" class="btn btn-primary btn-xs" data-target="#myModald' . $row["id"] . '" >
<font color=white>Report #[' . $idorder . '] </a></center>';
        }
    }
    echo "</td>
                    <td> " . $row['date'] . "</td>
    </tr>";
    
    echo '
 
<div class="modal fade" id="myModald' . $row['id'] . '" >
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h4 class="modal-title" id="myModalLabel">
                                              Report Form
                                            </h4>
                                        </div>
                                        <div class="modal-body">
<div class="well well-sm">
  <h4><b>Report Of Order #' . $row['id'] . ' </b></h4>
  <p>Please write clearly what is wrong with this <b>'.$row['type'].'</b> and why you want to refund it</p>
</div>
<div id="resulta' . $row['id'] . '">
<div class="input-group">
    <textarea id="msg' . $row['id'] . '"  class="form-control custom-control" rows="3" name="memo" style="resize:none" required=""></textarea>     
    <span id="xreport" class="input-group-addon btn btn-primary" onclick="this.disabled=true;javascript:sendt(' . $row['id'] . ');">Submit</span>
</div>
</div>
</div>
<div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
      </div>
';
    
    
}
?>

 </tbody>
 </table>
</div>


<script type="text/javascript">
function openitem(order){
  $("#myModalHeader").text('Order #'+order);
  $("#modelbody").html('<div style="text-align:center;padding:30px;"><span class="glyphicon glyphicon-refresh" style="font-size:24px;"></span><br><br>Loading...</div>');
  $('#myModal').modal('show');
  $.ajax({
    type:    'GET',
    url:     'showOrder'+order+'.html',
    success: function(data){
      if($.trim(data) === ''){
        $("#modelbody").html('<div class="alert alert-warning">No data returned for this order. The item may have been removed.</div>');
      } else {
        $("#modelbody").html(data).show();
      }
    },
    error: function(xhr, status, error){
      $("#modelbody").html('<div class="alert alert-danger"><strong>Error:</strong> Could not load order details. Please try again. ('+xhr.status+')</div>');
    }
  });
}

$(document).ready(function(){
  $("#orderSearch").on("keyup", function(){
    var value = $(this).val().toLowerCase();
    $("#table tbody tr").filter(function(){
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
  });
});
</script>
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h4 class="modal-title" id="myModalHeader"></h4>
      </div>
      <div class="modal-body" id="modelbody">


      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>