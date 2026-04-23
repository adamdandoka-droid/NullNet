<?php
ob_start();
session_start();
date_default_timezone_set('UTC');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
include "../includes/config.php";

if (!isset($_SESSION['sname']) and !isset($_SESSION['spass'])) {
    header("location: ../");
    exit();
}
$usrid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
?>

    <ul class="nav nav-tabs">
        <li class="active"><a href="#filter" data-toggle="tab">Filter</a></li>
    </ul>
    <div id="myTabContent" class="tab-content">
        <div class="tab-pane active in" id="filter">
            <table class="table">
                <thead>
                    <tr>
                        <th>Scam Name</th>
                        <th>Description</th>
                        <th>Seller</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
  <td>
                            <input class='filterinput form-control input-sm' name="scam_name" size='3'>
                        </td>
                        <td>
                            <input class='filterinput form-control input-sm' name="scam_info" size='3'>
                        </td>
                        <td>
                            <select class='filterselect form-control input-sm' name="scam_seller">
                                <option value="">ALL</option>
                                <?php
$query = mysqli_query($dbcon, "SELECT DISTINCT(`resseller`) FROM `scampages` WHERE `sold` = '0' or `sold` = '1' ORDER BY resseller ASC");
        while($row = mysqli_fetch_assoc($query)){
                 $qer = mysqli_query($dbcon, "SELECT DISTINCT(`id`) FROM resseller WHERE username='".$row['resseller']."' ORDER BY id ASC")or die(mysql_error());
                   while($rpw = mysqli_fetch_assoc($qer))
                         $SellerNick = "seller".$rpw["id"]."";
        echo '<option value="'.$SellerNick.'">'.$SellerNick.'</option>';
        }
?>
                            </select>
                        </td>
                        <td>
                            <button id='filterbutton' class="btn btn-primary btn-sm" disabled>Filter <span class="glyphicon glyphicon-filter"></span></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <table width="100%" class="table table-striped table-bordered table-condensed sticky-header" id="table">
        <thead>
            <tr>
                <th scope="col">Scampage Name</th>
                <th scope="col">Description</th>
                <th scope="col">Seller</th>
                <th scope="col">Price</th>
                <th scope="col">Added on </th>
                <th scope="col">Buy</th>
            </tr>
        </thead>
        <tbody>

            <?php
$q = mysqli_query($dbcon, "SELECT * FROM scampages WHERE sold='0' ORDER BY RAND()")or die(mysqli_error());
 while($row = mysqli_fetch_assoc($q)){
            $qer = mysqli_query($dbcon, "SELECT * FROM resseller WHERE username='".$row['resseller']."'")or die(mysql_error());
                   while($rpw = mysqli_fetch_assoc($qer))
                         $SellerNick = "seller".$rpw["id"]."";
     echo "
 <tr>     
    <td id='scam_name'> ".htmlspecialchars($row['scamname'])." </td> 
        <td id='scam_info'> ".htmlspecialchars($row['infos'])." </td>
    <td id='scam_seller'> ".htmlspecialchars($SellerNick)."</td>
    <td> ".htmlspecialchars($row['price'])."</td>
            <td> ".$row['date']."</td>";
    echo '
    <td>
        <span id="scam'.$row['id'].'" title="buy" type="scam"><a onclick="javascript:buythistool('.$row['id'].')" class="btn btn-primary btn-xs"><font color=white>Buy</font></a></span> <a onclick="javascript:showProof('.$row['id'].',\'scampages\')" class="btn btn-info btn-xs"><font color=white>Show Proof</font></a><center>
    </td>
            </tr>
     ';
 }

 ?>
                <script type="text/javascript">
                    $('#filterbutton').click(function() {
                        $("#table tbody tr").each(function() {
                            var ck1 = $.trim($(this).find("#scam_name").text().toLowerCase());
                            var ck2 = $.trim($(this).find("#scam_info").text().toLowerCase());
                            var ck3 = $.trim($(this).find("#scam_seller").text().toLowerCase());
                            var val1 = $.trim($('input[name="scam_name"]').val().toLowerCase());
                            var val2 = $.trim($('input[name="scam_info"]').val().toLowerCase());
                            var val3 = $.trim($('select[name="scam_seller"]').val().toLowerCase());
                            if ((ck1 != val1 && val1 != '') || ck2.indexOf(val2) == -1 || (ck3 != val3 && val3 != '')) {
                                $(this).hide();
                            } else {
                                $(this).show();
                            }
                        });
                        $('#filterbutton').prop('disabled', true);
                    });
                    $('.filterselect').change(function() {
                        $('#filterbutton').prop('disabled', false);
                    });
                    $('.filterinput').keyup(function() {
                        $('#filterbutton').prop('disabled', false);
                    });

function buythistool(id){
  bootbox.confirm("Are you sure?", function(result) {
    if(result == true){
      $.ajax({
        method:"GET",
        url:"buyscam.php?id="+id+"&t=scampages",
        dataType:"json",
        success:function(data){
          if(data && data.status === 'ok'){
            var $row = $("#scam"+id).closest('tr');
            if($row.length){ $row.fadeOut(250, function(){ $(this).remove(); }); }
            else { $("#scam"+id).closest('div,li').fadeOut(250); }
            var price = parseFloat(data.price).toFixed(2);
            var item  = (data.item || '').toString();
            if(item.length > 80) item = item.substring(0,80)+'...';
            bootbox.dialog({
              title: '<span class="glyphicon glyphicon-ok-sign" style="color:#28a745"></span> Purchase Successful',
              message: '<div style="text-align:center;padding:10px"><h4>You successfully bought:</h4><p style="word-break:break-all"><b>'+ $('<div>').text(item).html() +'</b></p><h3 style="color:#28a745">$'+ price +'</h3><p class="text-muted">Order #'+ data.order_id +'</p></div>',
              buttons: {
                orders: { label:'<span class="glyphicon glyphicon-shopping-cart"></span> My Orders', className:'btn-primary', callback: function(){ if(typeof pageDiv==='function'){ pageDiv(15,'Orders - NullNet','orders.html',0); } else { window.location='orders.html'; } } },
                view:   { label:'<span class="glyphicon glyphicon-eye-open"></span> View Order', className:'btn-info', callback: function(){ openitem(data.order_id); } },
                close:  { label:'Close', className:'btn-default' }
              }
            });
          } else if(data && data.status === 'sold'){
            bootbox.alert('<center><h4>This item was already sold or removed.</h4></center>');
            $("#scam"+id).closest('tr').fadeOut(250);
          } else {
            bootbox.alert('<center><img src="files/img/balance.png"><h2><b>No enough balance !</b></h2><h4>Please refill your balance <a class="btn btn-primary btn-xs"  href="addBalance.html" onclick="window.open(this.href);return false;" >Add Balance <span class="glyphicon glyphicon-plus"></span></a></h4></center>');
          }
        },
        error:function(){ bootbox.alert('<center><h4>Network error. Please try again.</h4></center>'); }
      });
    }
  });
}
function openitem(order){
  $("#myModalLabel").text('Order #'+order);
  $('#myModal').modal('show');
  $.ajax({
    type:       'GET',
    url:        'showOrder'+order+'.html',
    success:    function(data)
    {
        $("#modelbody").html(data).show();
    }});

}

</script>
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel"></h4>
      </div>
      <div class="modal-body" id="modelbody">


      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
function showProof(id, t){
  $.ajax({method:'GET',url:'showProof.php?id='+id+'&t='+t,dataType:'json',success:function(d){
    var html='';
    if(d && d.status==='ok'){ html='<div style="text-align:center"><img src="'+d.url+'" style="max-width:100%;max-height:70vh;border:1px solid #ddd;border-radius:4px"></div>'; }
    else if(d && d.status==='noproof'){ html='<div style="text-align:center;padding:30px"><h4>No proof image uploaded by the seller for this item.</h4></div>'; }
    else { html='<div style="text-align:center;padding:30px"><h4>Proof not available.</h4></div>'; }
    bootbox.dialog({title:'<span class="glyphicon glyphicon-picture"></span> Item Proof',message:html,size:'large',buttons:{close:{label:'Close',className:'btn-default'}}});
  },error:function(){ bootbox.alert('Could not load proof image.'); }});
}
</script>
