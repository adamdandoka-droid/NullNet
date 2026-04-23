<?php
ob_start();
session_start();
date_default_timezone_set('UTC');
include "includes/config.php";
error_reporting(0);

if (!isset($_SESSION['sname']) and !isset($_SESSION['spass'])) {
    header("location: ./");
    exit();
}

function secu($item){
    return strip_tags(base64_decode($item));
}

$subjectRaw = isset($_GET['s']) ? secu($_GET['s']) : '';
$messageRaw = isset($_GET['m']) ? base64_decode($_GET['m']) : '';
$prioRaw    = isset($_GET['p']) ? secu($_GET['p']) : '';
$tid        = isset($_GET['id']) ? mysqli_real_escape_string($dbcon, $_GET['id']) : '';

if (empty($messageRaw)) {
    echo '<script>alert("Please explain why you want to refund this tool"); history.back();</script>';
    exit();
}
if (empty($tid)) {
    echo '<script>alert("Invalid order"); history.back();</script>';
    exit();
}

$usrid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
$q = mysqli_query($dbcon, "SELECT * FROM purchases WHERE buyer='$usrid' AND id='$tid'") or die(mysqli_error($dbcon));
$row = mysqli_fetch_assoc($q);
if (!$row) {
    echo '<script>alert("Order not found"); history.back();</script>';
    exit();
}

$orderid = (int)$row['id'];
$check = mysqli_query($dbcon, "SELECT COUNT(*) AS c FROM reports WHERE orderid='$orderid'");
$cnt   = mysqli_fetch_assoc($check);
if ($cnt && (int)$cnt['c'] > 0) {
    echo '<script>alert("This order is already reported!"); history.back();</script>';
    exit();
}

$stid    = mysqli_real_escape_string($dbcon, $row['s_id']);
$surl    = mysqli_real_escape_string($dbcon, $row['url']);
$infos   = mysqli_real_escape_string($dbcon, $row['infos']);
$ress    = mysqli_real_escape_string($dbcon, $row['resseller']);
$type    = mysqli_real_escape_string($dbcon, $row['type']);
$price   = (int)$row['price'];
$date    = date("Y-m-d");
$datestr = date("d/m/Y H:i:s a");

$subject = mysqli_real_escape_string($dbcon, $subjectRaw);
$memo    = mysqli_real_escape_string($dbcon, $messageRaw);

$msg = '
<div class="panel panel-default">
  <div class="panel-body"><div class="ticket">'.htmlspecialchars($messageRaw).'</div></div>
  <div class="panel-footer"><div class="label label-info">Buyer</div> - '.$datestr.'</div>
</div>';
$msgEsc = mysqli_real_escape_string($dbcon, $msg);

$que = "INSERT INTO `reports`
(`uid`, `status`, `s_id`, `s_url`, `memo`, `acctype`, `admin_r`, `date`, `subject`, `type`, `resseller`, `price`, `refunded`, `fmemo`, `lastreply`, `s_info`, `seen`, `orderid`, `lastup`, `state`)
VALUES
('$usrid', '1', '$stid', '$surl', '$msgEsc', '$type', 0, '$date', '$subject', 'request', '$ress', $price, 'Not Yet !', '$memo', 'buyer', '$infos', 0, $orderid, '$datestr', 'onHold')";

if (mysqli_query($dbcon, $que)) {
    $last_id = mysqli_insert_id($dbcon);
    mysqli_query($dbcon, "UPDATE `purchases` SET reported='1', reportid='$last_id' WHERE id='$tid'");
    echo '
<div class="panel panel-success">
  <div class="panel-heading"><h3 class="panel-title">Report Added #'.$last_id.'</h3></div>
  <div class="panel-body">
    Your report of order #'.$orderid.' has been successfully added!<br>
    To check the report state go to <b>Tickets</b> &gt; <b>Reports</b>.
  </div>
</div>';
} else {
    echo '<div class="alert alert-danger">Could not create report: '.htmlspecialchars(mysqli_error($dbcon)).'</div>';
}
