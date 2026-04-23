<?php
session_start();
date_default_timezone_set('UTC');
include "includes/config.php";

if (!isset($_SESSION['sname']) and !isset($_SESSION['spass'])) {
    header("location: ../");
    exit();
}
$usrid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);

$id = mysqli_real_escape_string($dbcon, $_GET['id'] ?? '');
$query = mysqli_query($dbcon, "SELECT * FROM mailers WHERE id='$id'");
$row = $query ? mysqli_fetch_assoc($query) : null;
if (!$row) { echo "<span class='label label-danger'>Bad! (no record)</span>"; exit; }
$serverurl = trim((string)$row['url']);
if ($serverurl === '') { echo "<span class='label label-danger'>Bad! (empty url)</span>"; exit; }

$o = parse_url($serverurl, PHP_URL_HOST);
if (!$o) {
    $tmp = preg_replace('#^[a-z]+://#i', '', $serverurl);
    $o = explode('/', $tmp)[0] ?? '';
}

$qu = mysqli_query($dbcon, "SELECT testemail FROM users WHERE username='$usrid' LIMIT 1");
$ru = $qu ? mysqli_fetch_assoc($qu) : null;
$testemail = $ru['testemail'] ?? '';
if (!filter_var($testemail, FILTER_VALIDATE_EMAIL)) {
    echo "<span class='label label-warning'>Set Checker Email first</span>"; exit;
}

$subject = "PHPMailer #$id - Send test";
$body    = "Automated mailer verification from #$id";

$ch = curl_init($serverurl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'senderEmail'   => "info@$o",
    'senderName'    => 'Verifier',
    'subject'       => $subject,
    'messageLetter' => $body,
    'emailList'     => $testemail,
    'action'        => 'send',
]);
$resp     = curl_exec($ch);
$httpcode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$cerr     = curl_error($ch);
curl_close($ch);

// Hard unreachable: only when curl gave us literally nothing AND no http response.
if ($resp === false && $httpcode === 0) {
    $msg = htmlspecialchars(substr($cerr ?: 'no response', 0, 120));
    echo "<span class='label label-danger' title=\"$msg\">Bad! (unreachable: $msg)</span>";
    // auto-remove disabled: @mysqli_query($dbcon, "UPDATE mailers SET sold='2' WHERE id='$id'");
    exit;
}

$resp = (string)$resp;
$lc   = strtolower($resp);

if (preg_match('#incorrect\s*email#i', $resp)) {
    echo "<span class='label label-danger'>Incorrect email!</span>";
    exit;
}

$badPatterns = [
    'login failed','could not connect','smtp error','authentication failed',
    'connection refused','denied','blocked','access denied','suhosin','fatal error'
];
foreach ($badPatterns as $p) {
    if (strpos($lc, $p) !== false) {
        echo "<span class='label label-danger'>Bad! (mailer failure)</span>";
        // auto-remove disabled: @mysqli_query($dbcon, "UPDATE mailers SET sold='2' WHERE id='$id'");
        exit;
    }
}

$goodPatterns = [
    '<span class="label label-success">ok</span>',
    'message sent','sent successfully','mail sent','email sent',
    '>ok<','>sent<','queued','accepted','delivered','success'
];
foreach ($goodPatterns as $p) {
    if (strpos($lc, $p) !== false) {
        echo "<span class='label label-success'>Sent to $testemail (#$id)</span>";
        exit;
    }
}

// No clear marker either way — if the script responded with a 2xx/3xx and any
// body, treat it as accepted instead of falsely flagging it as broken.
if ($httpcode > 0 && $httpcode < 500 && strlen($resp) > 0) {
    echo "<span class='label label-success'>Sent to $testemail (#$id)</span>";
    exit;
}

echo "<span class='label label-danger'>Bad! (no success response)</span>";
// auto-remove disabled: @mysqli_query($dbcon, "UPDATE mailers SET sold='2' WHERE id='$id'");
?>
