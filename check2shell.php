<?php
session_start();
date_default_timezone_set('UTC');
include "includes/config.php";

if (!isset($_SESSION['sname']) and !isset($_SESSION['spass'])) {
    header("location: ../");
    exit();
}
$usrid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);

function check_host_exists($host) {
    if ($host === '' || $host === null) return false;
    $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : @gethostbyname($host);
    if (!$ip || ($ip === $host && !filter_var($ip, FILTER_VALIDATE_IP))) return false;
    $ch = curl_init("http://ip-api.com/json/" . urlencode($ip) . "?fields=status");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $resp = curl_exec($ch);
    curl_close($ch);
    if (!$resp) return true;
    $j = json_decode($resp, true);
    return isset($j['status']) && $j['status'] === 'success';
}

$id = mysqli_real_escape_string($dbcon, $_GET['id'] ?? '');
$query = mysqli_query($dbcon, "SELECT * FROM stufs WHERE id='$id'");
$serverurl = '';
while ($row = mysqli_fetch_array($query)) $serverurl = $row['url'];

$host = parse_url((string)$serverurl, PHP_URL_HOST);
if (!$host) {
    $tmp = preg_replace('#^[a-z]+://#i', '', (string)$serverurl);
    $host = explode('/', $tmp)[0] ?? '';
    if (strpos($host, ':') !== false) { $host = explode(':', $host, 2)[0]; }
}

$ok = false;
if (check_host_exists($host)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $serverurl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:23.0) Gecko/20100101 Firefox/23.0');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $output   = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($output !== false && preg_match('#Uname:|Safe mode: OFF|Client IP:|Server IP:|Your IP:|Last Modified#si', $output)) {
        $ok = true;
    } elseif ($httpcode >= 200 && $httpcode < 500 && $output !== false && strlen($output) > 0) {
        $ok = true;
    }
}

if ($ok) {
    echo "<span class='label label-success'>Working</span>";
} else {
    echo "<span class='label label-danger'>Bad!</span>";
    // auto-remove disabled: @mysqli_query($dbcon, "UPDATE stufs SET sold='2' WHERE id='$id'");
}
?>
