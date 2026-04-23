<?php
session_start();
date_default_timezone_set('UTC');
include "includes/config.php";

if (!isset($_SESSION['sname']) && !isset($_SESSION['spass']) && !isset($_SESSION['uname'])) {
    header("location: ../");
    exit();
}

function check_host_exists($host) {
    if ($host === '' || $host === null) return false;
    $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : @gethostbyname($host);
    if (!$ip || $ip === $host && !filter_var($ip, FILTER_VALIDATE_IP)) return false;
    $ch = curl_init("http://ip-api.com/json/" . urlencode($ip) . "?fields=status");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $resp = curl_exec($ch);
    curl_close($ch);
    if (!$resp) return true; // API unreachable: don't false-flag the host
    $j = json_decode($resp, true);
    return isset($j['status']) && $j['status'] === 'success';
}

function check_port_open($host, $port, $timeout = 6) {
    $errno = 0; $errstr = '';
    $fp = @fsockopen($host, (int)$port, $errno, $errstr, $timeout);
    if ($fp) { fclose($fp); return true; }
    return false;
}

$id  = (int)($_GET['id'] ?? 0);
$rs  = mysqli_query($dbcon, "SELECT url FROM rdps WHERE id='$id' LIMIT 1");
$row = $rs ? mysqli_fetch_assoc($rs) : null;
if (!$row) { echo "<span class='label label-danger'>Bad!</span>"; exit; }

$parts   = explode("|", (string)$row['url']);
$hostRaw = trim((string)($parts[0] ?? ''));
if ($hostRaw === '') { echo "<span class='label label-danger'>Bad!</span>"; exit; }

$hostRaw = preg_replace('#^[a-z]+://#i', '', $hostRaw);
$port = 3389;
if (strpos($hostRaw, ':') !== false) {
    list($hostOnly, $maybePort) = explode(':', $hostRaw, 2);
    $hostRaw = $hostOnly;
    if (ctype_digit($maybePort)) { $port = (int)$maybePort; }
}

$ok = false;
if (check_host_exists($hostRaw)) {
    foreach (array_unique([$port, 3389, 3390]) as $p) {
        if (check_port_open($hostRaw, $p)) { $ok = true; break; }
    }
}

if ($ok) {
    echo "<span class='label label-success'>Working</span>";
} else {
    echo "<span class='label label-danger'>Bad!</span>";
    // auto-remove disabled: @mysqli_query($dbcon, "UPDATE rdps SET sold='2' WHERE id='$id'");
}
