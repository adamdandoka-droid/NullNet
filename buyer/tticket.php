<?php
ob_start();
session_start();
date_default_timezone_set('UTC');
 include "../includes/config.php";

if(!isset($_SESSION['sname']) and !isset($_SESSION['spass'])){
   header("location: ../");
   exit();
}

function secu($item){
    $k = base64_decode($item);
    $m = strip_tags($k);
    $f = $m;
    return $f;
}

$subject =  mysqli_real_escape_string($dbcon, secu($_GET['s']));
$message = base64_decode(mysqli_real_escape_string($dbcon, $_GET['m']));
$proipre =  "high";
if (empty($subject) OR empty($message) ) {
    echo '<div class="alert alert-warning" role="alert">Please complete all fields.</div>';
    exit();
}
$usrid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
function ticket_parse_date($s) {
    $formats = ['Y-m-d H:i:s','Y/m/d H:i:s','Y-m-d h:i:s a','d/m/Y h:i:s a','d/m/Y H:i:s','Y/m/d h:i:s'];
    foreach ($formats as $f) {
        $dt = DateTime::createFromFormat($f, trim((string)$s), new DateTimeZone("UTC"));
        if ($dt instanceof DateTime) { return $dt->getTimestamp(); }
    }
    $t = strtotime((string)$s);
    return $t !== false ? $t : false;
}
$cdq = mysqli_query($dbcon, "SELECT date FROM ticket WHERE uid='$usrid' ORDER BY id DESC LIMIT 1");
if ($cdq && ($last = mysqli_fetch_assoc($cdq))) {
    $lastTs = ticket_parse_date($last['date']);
    if ($lastTs !== false) {
        $elapsed = time() - $lastTs;
        $cooldown = 12 * 3600;
        if ($elapsed >= 0 && $elapsed < $cooldown) {
            $remain = $cooldown - $elapsed;
            $h = floor($remain / 3600);
            $m = floor(($remain % 3600) / 60);
            echo '<div class="alert alert-warning" role="alert">You can only open one ticket every 12 hours. Please wait <b>'.$h.'h '.$m.'m</b> before opening another.</div>';
            exit();
        }
    }
}
{
$tid = mysqli_real_escape_string($dbcon, $_GET['id']);
$q = mysqli_query($dbcon, "SELECT * FROM users WHERE username='$usrid'")or die(mysqli_error());
while($row = mysqli_fetch_assoc($q)){
  $stid = mysqli_real_escape_string($dbcon, $row['s_id']);
  $date = date("Y-m-d H:i:s");
  $memo = mysqli_real_escape_string($dbcon, $message);
  $subj = mysqli_real_escape_string($dbcon, $subject);
  $msg     = '
  <div class="panel panel-default">
  <div class="panel-body">
'.htmlspecialchars($memo).'
 </div>
  <div class="panel-footer"><div class="label label-info">' . $usrid . '</div> - '.date("d/m/Y h:i:s a").'</div>
  </div>
  ';  }
    //echo $stid." ".$memo." -".$subj;
   /* $que = mysqli_query($dbcon, ("INSERT INTO ticket
    (uid,status,priority,memo,date,subject,type,s_id,s_url,resseller,acctype,refunded,price,fmemo,admin_r)
    VALUES
    ('$usrid','1','$prio','$msg','$date','$subject','refunding','$stid','$surl','$ress','$type','Not Yet !','1','$memo',NULL)
  ")or die();   */


  $que = mysqli_query($dbcon, "
INSERT INTO `ticket`
(`uid`, `status`, `s_id`, `s_url`, `memo`, `acctype`, `admin_r`, `date`, `subject`, `type`, `resseller`, `price`, `refounded`, `fmemo`, `seen`, `lastreply`,`lastup`)
 VALUES
('$usrid', '1', '$stid', '$surl', '$msg', '$type','0', '$date', '$subject', 'refunding', '$ress', '1', 'Not Yet !', '$memo', '0', '$usrid','$date');
  ")or die(mysqli_error($dbcon));

  if($que){
    echo '<script>window.location.replace("./tickets.html"); </script>';
  }else{
   echo '<div class="alert alert-danger" role="alert">Your ticket Not sent something wrong !</div>';
} }



?>