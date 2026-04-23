<?php
ob_start();
session_start();
date_default_timezone_set('UTC');
include "includes/config.php";
error_reporting(0);

if (!isset($_SESSION['sname']) and !isset($_SESSION['spass'])) {
    header("location: login.html");
    exit();
}

function flash_redirect($html) {
    $_SESSION['ticket_msg'] = $html;
    header("Location: tickets.html");
    exit();
}

// Accept POST (preferred) or legacy base64 GET
$subjectRaw = '';
$messageRaw = '';
if (isset($_POST['subject'])) { $subjectRaw = strip_tags($_POST['subject']); }
elseif (isset($_GET['s']))    { $subjectRaw = strip_tags(base64_decode($_GET['s'])); }

if (isset($_POST['message'])) { $messageRaw = $_POST['message']; }
elseif (isset($_GET['m']))    { $messageRaw = base64_decode($_GET['m']); }

if (empty($subjectRaw) || empty($messageRaw)) {
    flash_redirect('<div class="alert alert-warning" role="alert">Please complete all fields.</div>');
}

$usrid   = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
$subject = mysqli_real_escape_string($dbcon, $subjectRaw);
$message = mysqli_real_escape_string($dbcon, $messageRaw);

// 12-hour cooldown between tickets per user
function ticket_parse_date($s) {
    $formats = ['Y-m-d H:i:s','Y/m/d H:i:s','Y-m-d h:i:s a','d/m/Y h:i:s a','d/m/Y H:i:s','Y/m/d h:i:s'];
    foreach ($formats as $f) {
        $dt = DateTime::createFromFormat($f, trim((string)$s), new DateTimeZone('UTC'));
        if ($dt instanceof DateTime) { return $dt->getTimestamp(); }
    }
    $t = strtotime((string)$s);
    return $t !== false ? $t : false;
}
$cdq = mysqli_query($dbcon, "SELECT date FROM ticket WHERE uid='$usrid' ORDER BY id DESC LIMIT 1");
if ($cdq && ($last = mysqli_fetch_assoc($cdq))) {
    $lastTs = ticket_parse_date($last['date']);
    if ($lastTs !== false) {
        $elapsed  = time() - $lastTs;
        $cooldown = 12 * 3600;
        if ($elapsed >= 0 && $elapsed < $cooldown) {
            $remain = $cooldown - $elapsed;
            $h = floor($remain / 3600);
            $m = floor(($remain % 3600) / 60);
            flash_redirect('<div class="alert alert-warning" role="alert">You can only open one ticket every 12 hours. Please wait <b>'.$h.'h '.$m.'m</b> before opening another.</div>');
        }
    }
}

$ress = 0;
$uq = mysqli_query($dbcon, "SELECT resseller FROM users WHERE username='$usrid'");
if ($uq && ($row = mysqli_fetch_assoc($uq))) {
    $ress = (int)($row['resseller'] ?? 0);
}

$date = date("Y-m-d H:i:s");
$msg  = '
<div class="panel panel-default">
  <div class="panel-body">
'.htmlspecialchars($messageRaw).'
  </div>
  <div class="panel-footer"><div class="label label-info">'.$usrid.'</div> - '.date("d/m/Y h:i:s a").'</div>
</div>';
$msgEsc = mysqli_real_escape_string($dbcon, $msg);

$que = mysqli_query($dbcon, "
INSERT INTO `ticket`
(`uid`, `status`, `s_id`, `s_url`, `memo`, `acctype`, `admin_r`, `date`, `subject`, `type`, `resseller`, `price`, `refounded`, `fmemo`, `seen`, `lastreply`, `lastup`)
VALUES
('$usrid', 1, 0, '', '$msgEsc', 0, 0, '$date', '$subject', 'request', $ress, 0, 'Not Yet !', '$message', 0, '$usrid', '$date')
");

if ($que) {
    flash_redirect('<div class="alert alert-success" role="alert">Your ticket has been created.</div>');
} else {
    flash_redirect('<div class="alert alert-danger" role="alert">Your ticket was not sent: '.htmlspecialchars(mysqli_error($dbcon)).'</div>');
}
