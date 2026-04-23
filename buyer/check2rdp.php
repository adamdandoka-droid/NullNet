<?php
session_start();
date_default_timezone_set('UTC');
include "../includes/config.php";

if (!isset($_SESSION['sname']) && !isset($_SESSION['spass']) && !isset($_SESSION['uname'])) {
    header("location: ../");
    exit();
}

$id  = (int)($_GET['id'] ?? 0);
$rs  = mysqli_query($dbcon, "SELECT url FROM rdps WHERE id='$id' LIMIT 1");
$row = $rs ? mysqli_fetch_assoc($rs) : null;
if (!$row) { echo "<span class='label label-danger'>Bad!</span>"; exit; }

// url stored as "host[:port]|login|pass"
$parts = explode("|", (string)$row['url']);
$hostRaw = trim((string)($parts[0] ?? ''));
if ($hostRaw === '') { echo "<span class='label label-danger'>Bad!</span>"; exit; }

// strip protocol if present, then split host:port
$hostRaw = preg_replace('#^[a-z]+://#i', '', $hostRaw);
$port = 3389;
if (strpos($hostRaw, ':') !== false) {
    list($hostOnly, $maybePort) = explode(':', $hostRaw, 2);
    $hostRaw = $hostOnly;
    if (ctype_digit($maybePort)) { $port = (int)$maybePort; }
}

// Try the parsed port first, then fall back to 3389/3390 in case the host string had no port.
$portsToTry = array_unique([$port, 3389, 3390]);
$ok = false;
foreach ($portsToTry as $p) {
    $errno = 0; $errstr = '';
    $fp = @fsockopen($hostRaw, $p, $errno, $errstr, 6);
    if ($fp) { fclose($fp); $ok = true; break; }
}

if ($ok) {
    echo "<span class='label label-success'>Working</span>";
} else {
    echo "<span class='label label-danger'>Bad!</span>";
    // auto-remove disabled: @mysqli_query($dbcon, "UPDATE rdps SET sold='2' WHERE id='$id'");
}
