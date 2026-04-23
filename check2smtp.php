<?php
ob_start();
session_start();
date_default_timezone_set('UTC');
include "includes/config.php";
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

if (!isset($_SESSION['sname']) and !isset($_SESSION['spass'])) {
    header("location: ../");
    exit();
}
$usrid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);

function srl($x) { return trim((string)$x); }

$id = mysqli_real_escape_string($dbcon, $_GET['id'] ?? '');
$query = mysqli_query($dbcon, "SELECT * FROM smtps WHERE id='$id'");
$serverurl = '';
while ($row = mysqli_fetch_array($query)) $serverurl = $row['url'];
if ($serverurl === '') { echo "<span class='label label-danger'>Bad! (no record)</span>"; exit; }

$d     = explode("|", (string)$serverurl);
$host  = srl($d[0] ?? '');
$login = srl($d[1] ?? '');
$pass  = srl($d[2] ?? '');
$port  = (int) srl($d[3] ?? '587');
if ($port <= 0) $port = 587;

$host = preg_replace('#^[a-z]+://#i', '', $host);
if (strpos($host, ':') !== false) {
    list($h, $maybePort) = explode(':', $host, 2);
    $host = $h;
    if (ctype_digit($maybePort)) { $port = (int)$maybePort; }
}

$qu = mysqli_query($dbcon, "SELECT testemail FROM users WHERE username='$usrid' LIMIT 1");
$ru = $qu ? mysqli_fetch_assoc($qu) : null;
$testemail = $ru['testemail'] ?? '';

if (!filter_var($testemail, FILTER_VALIDATE_EMAIL)) {
    echo "<span class='label label-warning'>Set Checker Email first</span>";
    exit;
}

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = $host;
    $mail->Port       = $port;
    $mail->SMTPAuth   = true;
    $mail->Username   = $login;
    $mail->Password   = $pass;
    $mail->Timeout    = 15;
    $mail->SMTPOptions = ['ssl' => ['verify_peer'=>false,'verify_peer_name'=>false,'allow_self_signed'=>true]];
    if ($port === 465)      { $mail->SMTPSecure = 'ssl'; }
    elseif ($port === 25)   { $mail->SMTPAutoTLS = false; $mail->SMTPSecure = ''; }
    else                    { $mail->SMTPSecure = 'tls'; }

    $fromAddr = filter_var($login, FILTER_VALIDATE_EMAIL) ? $login : ('test@' . preg_replace('/[^a-z0-9.\-]/i','',$host));
    $mail->setFrom($fromAddr, 'SMTP Verifier');
    $mail->addAddress($testemail);
    $mail->Subject = "SMTP test #$id";
    $mail->Body    = "Automated SMTP verification.\nHost: $host:$port\nLogin: $login\n";
    $mail->isHTML(false);

    $mail->send();
    echo "<span class='label label-success'>Sent to $testemail (#$id)</span>";
} catch (PHPMailerException $e) {
    $err = htmlspecialchars(substr($mail->ErrorInfo ?: $e->getMessage(), 0, 160));
    echo "<span class='label label-danger' title=\"$err\">Bad! ($err)</span>";
    // auto-remove disabled: @mysqli_query($dbcon, "UPDATE smtps SET sold='2' WHERE id='$id'");
} catch (\Throwable $e) {
    $err = htmlspecialchars(substr($e->getMessage(), 0, 160));
    echo "<span class='label label-danger' title=\"$err\">Bad! ($err)</span>";
    // auto-remove disabled: @mysqli_query($dbcon, "UPDATE smtps SET sold='2' WHERE id='$id'");
}
?>
