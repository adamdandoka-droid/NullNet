<?php
ob_start();
session_start();
date_default_timezone_set('UTC');

if(!isset($_SESSION['sname']) and !isset($_SESSION['spass'])){
   header("location: ../");
   exit();
}
include "includes/config.php";

$date = date("Y-m-d H:i:s");
$uid  = mysqli_real_escape_string($dbcon, $_GET['id'] ?? '');
$tbl  = mysqli_real_escape_string($dbcon, $_GET['t']  ?? '');

$qqs  = @mysqli_query($dbcon, "SELECT * FROM $tbl WHERE id='$uid'");
$rows = $qqs ? mysqli_fetch_assoc($qqs) : null;

header('Content-Type: application/json');
if (!$rows) { echo json_encode(['status'=>'sold']); exit; }

$price     = mysqli_real_escape_string($dbcon, $rows['price']);
$type      = mysqli_real_escape_string($dbcon, $rows['acctype']);
$fb        = mysqli_real_escape_string($dbcon, $rows['country']);
$infos     = mysqli_real_escape_string($dbcon, $rows['infos']);
$url       = mysqli_real_escape_string($dbcon, $rows['url']);
$login     = mysqli_real_escape_string($dbcon, $rows['login'] ?? '');
$pa        = mysqli_real_escape_string($dbcon, $rows['pass']  ?? '');
$sid       = mysqli_real_escape_string($dbcon, $rows['id']);
$resseller = mysqli_real_escape_string($dbcon, $rows['resseller']);

$usrid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
$qqs2  = @mysqli_query($dbcon, "SELECT * FROM users WHERE username='$usrid'");
$rows2 = mysqli_fetch_assoc($qqs2);
$balance = $rows2['balance'];
$ipur    = $rows2['ipurchassed'];

if ($balance >= $price) {
    $newb  = $balance - $price;
    $newb2 = mysqli_real_escape_string($dbcon, $newb);
    $re    = mysqli_query($dbcon, "SELECT sold FROM $tbl WHERE id='$uid'");
    $ree   = mysqli_fetch_assoc($re);
    if ($ree['sold'] == '0') {
        $npur = $ipur + 1;
        mysqli_query($dbcon, "UPDATE $tbl SET sold='1', sto='$usrid', dateofsold='$date', resseller='$resseller' WHERE id='$uid'");
        mysqli_query($dbcon, "UPDATE users SET balance='$newb2', ipurchassed='$npur' WHERE username='$usrid'");
        mysqli_query($dbcon, "INSERT INTO purchases
            (s_id,buyer,type,date,country,infos,url,login,pass,price,resseller,reported,reportid)
            VALUES
            ('$sid','$usrid','$type','$date','$fb','$infos','$url','$login','$pa','$price','$resseller','',null)");
        $last_id = mysqli_insert_id($dbcon);
        $b = $price;
        $release_date = date("Y-m-d H:i:s", strtotime("+10 hours"));
        mysqli_query($dbcon, "INSERT INTO seller_payments (purchase_id,seller,buyer,amount,status,item_type,s_id,purchase_date,release_date) VALUES ('$last_id','$resseller','$usrid','$price','pending','$type','$sid','$date','$release_date')");
        mysqli_query($dbcon, "UPDATE resseller SET allsales=(allsales + $b) WHERE username='$resseller'");
        echo json_encode([
            'status'=>'ok','order_id'=>$last_id,'type'=>$type,
            'price'=>$price,'item'=>($rows['infos'] ?: $rows['url']),'remaining'=>$newb
        ]);
    } else {
        echo json_encode(['status'=>'sold']);
    }
} else {
    echo json_encode(['status'=>'insufficient']);
}
?>
