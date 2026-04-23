<?php
 ob_start();
 date_default_timezone_set('GMT');

session_start();
include "../includes/config.php";


$id = $_GET['id'];
$myid = mysqli_real_escape_string($dbcon, $id);


$get = mysqli_query($dbcon, "SELECT * FROM reports WHERE id='$myid'");
$row = mysqli_fetch_assoc($get);

$date      = date("Y-m-d H:i:s");
$resseller = $row['resseller'];
$buyer     = $row['uid'];
$acctype   = $row['acctype'];
$sid       = $row['s_id'];
$surl      = $row['s_url'];
$price     = $row['price'];
$orderid   = !empty($row['orderid']) ? (int)$row['orderid'] : 0;

$meU = mysqli_real_escape_string($dbcon, $_SESSION['sname'] ?? '');

$msg = '
  <div class="panel panel-default">
  <div class="panel-body">
<b>Refund request has been rejected.<br>Thank you for using NullNet.</b>
 </div>
  <div class="panel-footer"><div class="label label-danger">Admin:'.htmlspecialchars($meU).'</div> - '.date("d/m/Y h:i:s a").'</div>
  </div>
  ';
$date = date("d/m/Y h:i:s a");
$qq = mysqli_query($dbcon, "UPDATE reports SET memo = CONCAT(memo,'$msg'),status='0',seen='1',lastreply='Admin',lastup='$date',state='rejected' WHERE id='$myid'") or die("mysql error");

// Release the frozen profit back to the seller when closing without a refund
if($orderid > 0 && !empty($resseller)){
    $sellerE = mysqli_real_escape_string($dbcon, $resseller);
    $now     = date("Y-m-d H:i:s");

    // Find the pending seller_payment for this order
    $spQ = mysqli_query($dbcon, "SELECT id, amount FROM seller_payments WHERE purchase_id='$orderid' AND seller='$sellerE' AND status='pending'");
    if($spQ && $spR = mysqli_fetch_assoc($spQ)){
        $spId  = (int)$spR['id'];
        $spAmt = (float)$spR['amount'];
        // Mark payment as released and credit seller wallet
        mysqli_query($dbcon, "UPDATE seller_payments SET status='released', approved_at='$now', approved_by='$meU' WHERE id='$spId'");
        mysqli_query($dbcon, "UPDATE resseller SET soldb=(soldb + $spAmt) WHERE username='$sellerE'");
    }

    // Clear the reported flag on the purchase
    mysqli_query($dbcon, "UPDATE purchases SET reported='' WHERE id='$orderid'");
}

header("location: viewr.php?id=$myid");


?>