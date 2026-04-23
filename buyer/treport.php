<?php
ob_start();
session_start();
date_default_timezone_set('UTC');

if(!isset($_SESSION['sname']) and !isset($_SESSION['spass'])){
   header("location: ../");
   exit();
}
  include "../includes/config.php";
function secu($item){
    $k = base64_decode((string)$item);
    $m = strip_tags($k);
    $f = $m;
    return $f;
}

$subject =  mysqli_real_escape_string($dbcon, secu($_GET['s'] ?? ''));
$message =  base64_decode(mysqli_real_escape_string($dbcon, $_GET['m'] ?? ''));
$proipre =  mysqli_real_escape_string($dbcon, secu($_GET['p'] ?? ''));
if(empty($message)) { 
echo '<script>alert("Please explain why you want to refund this tool");</script>';
} else {
$tid = mysqli_real_escape_string($dbcon, $_GET['id'] ?? '');

$usrid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
$q = mysqli_query($dbcon, "SELECT * FROM purchases WHERE buyer='$usrid' AND id='$tid'")or die(mysqli_error($dbcon));
while($row = mysqli_fetch_assoc($q)){
  $stid = mysqli_real_escape_string($dbcon, $row['s_id']);
  $orderid = mysqli_real_escape_string($dbcon, $row["id"]);

  $purchase_ts = strtotime($row['date']);
  if ($purchase_ts !== false && (time() - $purchase_ts) > 10 * 3600) {
    echo '<script>alert("Report window has expired. You can only report an order within 10 hours of purchase.");</script>';
    continue;
  }

  // Block reporting if the seller payout has already been released (prevents refund-after-payout exploit)
  $payoutChk = mysqli_query($dbcon, "SELECT status FROM seller_payments WHERE purchase_id='$orderid' LIMIT 1");
  if ($payoutChk && ($payoutRow = mysqli_fetch_assoc($payoutChk))) {
    if ($payoutRow['status'] === 'released') {
      echo '<script>alert("This order has already been paid out to the seller and can no longer be reported.");</script>';
      continue;
    }
    if ($payoutRow['status'] === 'refunded') {
      echo '<script>alert("This order has already been refunded.");</script>';
      continue;
    }
  }

$check="SELECT COUNT(*) FROM reports WHERE orderid = '$orderid'";
$rs = mysqli_query($dbcon,$check);
$data = mysqli_fetch_array($rs, MYSQLI_NUM);
if($data[0] >= 1) {
echo '<script>alert("This order is already reported!");</script>';
} else {
  $surl = mysqli_real_escape_string($dbcon, $row["url"]);
  $infos = mysqli_real_escape_string($dbcon, $row["infos"]);
  $ress = mysqli_real_escape_string($dbcon, $row['resseller']);
  $date = date("Y-m-d H:i:s");
  $memo = mysqli_real_escape_string($dbcon, $message);
  $subj = mysqli_real_escape_string($dbcon, $subject);
  $prio = mysqli_real_escape_string($dbcon, $proipre);
  $type = mysqli_real_escape_string($dbcon, $row['type']);
  $price = mysqli_real_escape_string($dbcon, $row['price']);
   $msg     = '
  <div class="panel panel-default">
  <div class="panel-body"><div class="ticket">'.htmlspecialchars($memo).'</div></div>
  <div class="panel-footer"><div class="label label-info">Buyer</div> - '.date("d/m/Y h:i:s a").'</div>
  </div>
  '; 
  $que = "
INSERT INTO `reports`
(`uid`, `status`, `s_id`, `s_url`, `memo`, `acctype`, `admin_r`, `date`, `subject`, `type`, `resseller`, `price`, `refunded`, `fmemo`, `lastreply`, `s_info`, `seen`, `orderid`, `lastup`, `state`)
 VALUES
('$usrid', '1', '$stid', '$surl', '$msg', '$type','0', '$date', '$subject', 'request', '$ress', '$price', 'Not Yet !', '$memo', 'buyer', '$infos', '0', '$orderid', '$date', 'onHold');
  ";
if (mysqli_query($dbcon, $que)) {
    $last_id = mysqli_insert_id($dbcon);
		    echo '
<div class="panel panel-success">
  <div class="panel-heading">
    <h3 class="panel-title">Report Added #'.$last_id.'</h3>
  </div>
  <div class="panel-body">
Your report of order #'.$orderid.' has been successfully added !<br>In order to check the report state go to <b>Tickets</b> &gt; <b>Reports</b>. <br>  </div>
</div>			
			';
			  $updater = mysqli_query($dbcon, "
UPDATE `purchases` SET reported='1',reportid='$last_id' where id='$tid'
  ");
} } }
}
?>