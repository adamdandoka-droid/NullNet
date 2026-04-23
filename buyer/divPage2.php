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
<div id="myTabContent" class="tab-content" >
  <div class="tab-pane active in" id="filter"><table class="table"><thead><tr><th>Country</th>
<th>Domain TLD</th>
<th>Detected Hosting</th>
<th>Seller</th>
<th></th></tr></thead><tbody><tr><td><select class='filterselect form-control input-sm' name="cpanel_country"><option value="">ALL</option>
<?php
$query = mysqli_query($dbcon, "SELECT DISTINCT(`country`) FROM `cpanels` WHERE `sold` = '0' ORDER BY country ASC");
	while($row = mysqli_fetch_assoc($query)){
	echo '<option value="'.$row['country'].'">'.$row['country'].'</option>';
	}
?>
</select></td><td><input class='filterinput form-control input-sm' name="cpanel_tld" size='3'></td><td><input class='filterinput form-control input-sm' name="cpanel_hosting" size='3'></td><td><select class='filterselect form-control input-sm' name="cpanel_seller"><option value="">ALL</option>
<?php
$query = mysqli_query($dbcon, "SELECT DISTINCT(`resseller`) FROM `cpanels` WHERE `sold` = '0' ORDER BY resseller ASC");
	while($row = mysqli_fetch_assoc($query)){
		 $qer = mysqli_query($dbcon, "SELECT DISTINCT(`id`) FROM resseller WHERE username='".$row['resseller']."' ORDER BY id ASC")or die(mysql_error());
		   while($rpw = mysqli_fetch_assoc($qer))
			 $SellerNick = "seller".$rpw["id"]."";
	echo '<option value="'.$SellerNick.'">'.$SellerNick.'</option>';
	}
?>
</select></td><td><button id='filterbutton'class="btn btn-primary btn-sm" disabled>Filter <span class="glyphicon glyphicon-filter"></span></button></td></tr></tbody></table></div>
</div>


<table width="100%"  class="table table-striped table-bordered table-condensed sticky-header" id="table">
<thead>
    <tr>
      <th scope="col" >Country</th>
      <th scope="col">TLD</th>
      <th scope="col">Detect Hosting</th>
      <th scope="col">Seller</th>
      <th scope="col">Check</th>
      <th scope="col">Price</th>
      <th scope="col">Added on </th>

      <th scope="col">Buy</th>
    </tr>
</thead>
  <tbody>
<?php
		include("cr.php");
	    $q = mysqli_query($dbcon, "SELECT * FROM cpanels WHERE sold='0' ORDER BY RAND()")or die(mysql_error());
	   	function srl($item)
		{
		$item0 = $item;
		$item1 = rtrim($item0);
		$item2 = ltrim($item1);
		return $item2;
		} 

 while($row = mysqli_fetch_assoc($q)){
	 	 $countryfullname = $row['country'];
	  $code = array_search("$countryfullname", $countrycodes);
	 $countrycode = strtolower($code);

	 $url = $row['url'];
	 	$d = explode("|", $url);
		$urled = srl($d[0]);

	 	  $_h = parse_url((string)$urled, PHP_URL_HOST);
   if (!$_h) { $_h = parse_url('http://'.(string)$urled, PHP_URL_HOST); }
   $_p = explode('.', (string)$_h);
   $tld = end($_p); 
    $qer = mysqli_query($dbcon, "SELECT * FROM resseller WHERE username='".$row['resseller']."'")or die(mysql_error());
		   while($rpw = mysqli_fetch_assoc($qer))
			 $SellerNick = "seller".$rpw["id"]."";
     echo "
 <tr>    
    <td id='cpanel_country'><i class='flag-icon flag-icon-$countrycode'></i>&nbsp;".htmlspecialchars($row['country'])." </td>
		    <td id='cpanel_tld'> .".$tld." </td>
    <td id='cpanel_hosting'> ".htmlspecialchars($row['infos'])." </td>
    <td id='cpanel_seller'> ".htmlspecialchars($SellerNick)."</td>
";
	 echo '<td><span id="shop'.$row["id"].'" type="cpanel"><a onclick="javascript:check('.$row["id"].');" class="btn btn-info btn-xs"><font color=white>Check</font></a></span><center></td>';
echo "
    <td> ".htmlspecialchars($row['price'])."</td>
	    <td> ".htmlspecialchars($row['date'])."</td>
    ";

    echo '
    <td>
	<span id="cpanel'.$row['id'].'" title="buy" type="cpanel"><a onclick="javascript:buythistool('.$row['id'].')" class="btn btn-primary btn-xs"><font color=white>Buy</font></a></span> <a onclick="javascript:showProof('.$row['id'].',\'cpanels\')" class="btn btn-info btn-xs"><font color=white>Show Proof</font></a><center>
    </td>
            </tr>
     ';
 }

 ?>

 </tbody>
 </table>

<script type="text/javascript">
$('#filterbutton').click(function () {$("#table tbody tr").each(function() {var ck1 = $.trim( $(this).find("#cpanel_country").text().toLowerCase() );var ck2 = $.trim( $(this).find("#cpanel_tld").text().toLowerCase() );var ck3 = $.trim( $(this).find("#cpanel_hosting").text().toLowerCase() );var ck4 = $.trim( $(this).find("#cpanel_seller").text().toLowerCase() ); var val1 = $.trim( $('select[name="cpanel_country"]').val().toLowerCase() );var val2 = $.trim( $('input[name="cpanel_tld"]').val().toLowerCase() );var val3 = $.trim( $('input[name="cpanel_hosting"]').val().toLowerCase() );var val4 = $.trim( $('select[name="cpanel_seller"]').val().toLowerCase() ); if((ck1 != val1 && val1 != '' ) || ck2.indexOf(val2)==-1 || ck3.indexOf(val3)==-1 || (ck4 != val4 && val4 != '' )){ $(this).hide();  }else{ $(this).show(); } });$('#filterbutton').prop('disabled', true);});$('.filterselect').change(function () {$('#filterbutton').prop('disabled', false);});$('.filterinput').keyup(function () {$('#filterbutton').prop('disabled', false);});
function buythistool(id){
  bootbox.confirm("Are you sure?", function(result) {
    if(result == true){
      $.ajax({
        method:"GET",
        url:"buytool.php?id="+id+"&t=cpanels",
        dataType:"json",
        success:function(data){
          if(data && data.status === 'ok'){
            var $row = $("#cpanel"+id).closest('tr');
            if($row.length){ $row.fadeOut(250, function(){ $(this).remove(); }); }
            else { $("#cpanel"+id).closest('div,li').fadeOut(250); }
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
            $("#cpanel"+id).closest('tr').fadeOut(250);
          } else {
            bootbox.alert('<center><img src="files/img/balance.png"><h2><b>No enough balance !</b></h2><h4>Please refill your balance <a class="btn btn-primary btn-xs"  href="addBalance.html" onclick="window.open(this.href);return false;" >Add Balance <span class="glyphicon glyphicon-plus"></span></a></h4></center>');
          }
        },
        error:function(){ bootbox.alert('<center><h4>Network error. Please try again.</h4></center>'); }
      });
    }
  });
}
g:xcheck=0;
function check(id){   
     if(xcheck > 1){
    bootbox.alert("<b>Wait</b> - Other checking operation is executed!");
  } else {
    xcheck++;
    var type = $("#shop"+id).attr('type')
	$("#shop"+id).html('Checking...').show();
	$.ajax({
	type: 		'GET',
	url: 		'CheckCpanel'+id+'.html',
	success:	function(data)
	{
		$("#shop"+id).html(data).show();
		xcheck--;
	}});
} }

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
