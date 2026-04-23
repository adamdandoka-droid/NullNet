<?php
ob_start();
session_start();
error_reporting(0);
date_default_timezone_set('UTC');
include "includes/config.php";

if(!isset($_SESSION['sname']) and !isset($_SESSION['spass'])){
   header("location: ../");
   exit();
}
$usrid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);

// Verify user still exists in DB — if not, return logout signal
$s3 = mysqli_query($dbcon, "SELECT balance, resseller FROM users WHERE username='$usrid'");
$userRow = mysqli_fetch_assoc($s3);
if(!$userRow){
    echo '01';
    exit();
}

$r3 = $userRow['balance'] ?? 0;

$s1 = mysqli_query($dbcon, "SELECT * FROM ticket where status='1' and uid='$usrid'");
$r1=mysqli_num_rows($s1);
$s2 = mysqli_query($dbcon, "SELECT * FROM reports where status='1' and uid='$usrid'");
$r2=mysqli_num_rows($s2);
$s4 = mysqli_query($dbcon, "SELECT * FROM rdps WHERE sold='0'");
$r4=mysqli_num_rows($s4);
$s5 = mysqli_query($dbcon, "SELECT * FROM stufs WHERE sold='0'");
$r5=mysqli_num_rows($s5);
$s6 = mysqli_query($dbcon, "SELECT * FROM cpanels WHERE sold='0'");
$r6=mysqli_num_rows($s6);
$s7 = mysqli_query($dbcon, "SELECT * FROM mailers WHERE sold='0'");
$r7=mysqli_num_rows($s7);
$s8 = mysqli_query($dbcon, "SELECT * FROM smtps WHERE sold='0'");
$r8=mysqli_num_rows($s8);
$s9 = mysqli_query($dbcon, "SELECT * FROM leads WHERE sold='0'");
$r9=mysqli_num_rows($s9);
$s10= mysqli_query($dbcon, "SELECT * FROM accounts WHERE sold='0'");
$r10=mysqli_num_rows($s10);
$s11 = mysqli_query($dbcon, "SELECT * FROM banks WHERE sold='0'");
$r11=mysqli_num_rows($s11);
$s12 = mysqli_query($dbcon, "SELECT * FROM scampages");
$r12=mysqli_num_rows($s12);
$s13 = mysqli_query($dbcon, "SELECT * FROM tutorials");
$r13=mysqli_num_rows($s13);


$myObj =new stdClass();
$myObj->tickets = "$r1";
$myObj->reports = "$r2";
$myObj->balance = "$".$r3;
$myObj->rdp = "$r4";
$myObj->shell = "$r5";
$myObj->cpanel = "$r6";
$myObj->mailer = "$r7";
$myObj->smtp = "$r8";
$myObj->leads = "$r9";
$myObj->premium = "$r10";
$myObj->banks = "$r11";
$myObj->scams = "$r12";
$myObj->tutorials = "$r13";

$reselerif = $userRow['resseller'] ?? '0';
if ($reselerif == "1") {
    $q = mysqli_query($dbcon, "SELECT soldb FROM resseller WHERE username='$usrid'");
    $r = mysqli_fetch_assoc($q);
    $seller = $r['soldb'] ?? '0';
    $myObj->seller = "$$seller";
}

$myJSON = json_encode($myObj);
echo $myJSON;
?>
