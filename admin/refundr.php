<?php
 ob_start();
session_start();
include "../includes/config.php";

if(!isset($_SESSION['sname']) || !isset($_SESSION['spass'])){
   header("location: ../login.php");
   exit();
}
$adminUser = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
$roleQ = mysqli_query($dbcon, "SELECT role FROM users WHERE username='$adminUser'");
$roleR = $roleQ ? mysqli_fetch_assoc($roleQ) : null;
$adminRole = $roleR['role'] ?? 'user';
if ($adminRole !== 'admin' && $adminRole !== 'support') {
   header("location: ../login.php");
   exit();
}

$id = $_GET['id'];
$myid = mysqli_real_escape_string($dbcon, $id);


$get = mysqli_query($dbcon, "SELECT * FROM reports WHERE id='$myid'");
$row = mysqli_fetch_assoc($get);
if (!$row) { echo "report not found"; exit(); }
if (strcasecmp(trim($row['refunded']), 'Refunded') === 0) {
    header("location: viewr.php?id=$myid");
    exit();
}

$date = date("m/d/Y h:i:s a");
$resseller = $row['resseller'];
$buyer = $row['uid'];
$acctype = $row['acctype'];
$sid = $row['s_id'];
$surl = $row['s_url'];
$price = (float)$row['price'];
$d = $row['date'];

if (true) {
$qq = mysqli_query($dbcon, "INSERT INTO refund
    (ids,type,url,price,buyer,sdate,rdate,resseller)
    VALUES
    ('$sid','$acctype','$surl','$price','$buyer','$d','$date','$resseller')
    ")or die(mysqli_error($dbcon));
if($qq){
  $b = ($price * 55) / 100 ;
  $refund = mysqli_query($dbcon, "UPDATE users SET balance=(balance +$price) WHERE username='$buyer'");
  $refund = mysqli_query($dbcon, "UPDATE reports SET refunded='Refunded' WHERE id='$myid'");
  // Profit-hold aware refund: if seller_payment is still pending, just cancel it (no soldb change)
$orderid_ref = (int)($row["orderid"] ?? 0);
$spQ_ref = mysqli_query($dbcon, "SELECT id, status FROM seller_payments WHERE purchase_id='$orderid_ref' AND seller='$resseller' LIMIT 1");
$spR_ref = $spQ_ref ? mysqli_fetch_assoc($spQ_ref) : null;
if ($spR_ref && $spR_ref["status"] === "pending") {
    mysqli_query($dbcon, "UPDATE seller_payments SET status='refunded', approved_at='".date("Y-m-d H:i:s")."' WHERE id='".(int)$spR_ref["id"]."'");
    $backto = mysqli_query($dbcon, "UPDATE resseller SET isold=GREATEST(isold - 1, 0) WHERE username='$resseller'");
} else {
    if ($spR_ref) { mysqli_query($dbcon, "UPDATE seller_payments SET status='refunded' WHERE id='".(int)$spR_ref["id"]."'"); }
    $backto = mysqli_query($dbcon, "UPDATE resseller SET isold=GREATEST(isold - 1, 0),soldb=GREATEST(soldb - $price, 0) WHERE username='$resseller'");
}
   $priceFmt = number_format($price, 2);
   $roleLabel = ucfirst($adminRole);
   $labelCls  = ($adminRole === 'support') ? 'label-warning' : 'label-danger';
   $msg     = '
  <div class="panel panel-default">
  <div class="panel-body">
<b>Refunded $'.$priceFmt.' successfully. Thank you for contacting us.</b>
 </div>
  <div class="panel-footer"><div class="label '.$labelCls.'">'.$roleLabel.':'.htmlspecialchars($adminUser).'</div> - '.date("d/m/Y h:i:s a").'</div>
  </div>
  ';
  $date = date("d/m/Y h:i:s a");
  $qq = mysqli_query($dbcon, "UPDATE reports SET memo = CONCAT(memo,'$msg'),status='0',seen='1',lastreply='$roleLabel',lastup='$date',state='accepted' WHERE id='$myid'")or die("mysql error");
  if($refund){
     header("location: viewr.php?id=$myid");
     exit();
  }else{
    echo "problem";
  }
} }

if(isset($_GET['action']) and $_GET['action'] == 'nr' ){
 $nrefund = mysqli_query($dbcon, "UPDATE reports SET refunded='not Refunded' WHERE id='$myid'");
 if($nrefund){
  header("location: viewr.php?id=$myid");
 }else{
    echo "problem in not refund";

}
}
?>