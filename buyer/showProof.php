<?php
ob_start();
session_start();
date_default_timezone_set('UTC');
include "../includes/config.php";
header('Content-Type: application/json');

if (!isset($_SESSION['sname']) and !isset($_SESSION['spass'])) {
    echo json_encode(['status' => 'auth']);
    exit();
}

$allowed = [
    'rdps','cpanels','stufs','mailers','smtps',
    'leads','accounts','banks','scampages','tutorials'
];
$type = isset($_GET['t']) ? strtolower(preg_replace('/[^a-z0-9_]/i','', $_GET['t'])) : '';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!in_array($type, $allowed, true) || $id <= 0) {
    echo json_encode(['status' => 'invalid']);
    exit();
}

$q = mysqli_query($dbcon, "SELECT proof FROM `$type` WHERE id='$id' LIMIT 1");
if (!$q || !($r = mysqli_fetch_assoc($q))) {
    echo json_encode(['status' => 'notfound']);
    exit();
}
$proof = trim($r['proof'] ?? '');
if ($proof === '') {
    echo json_encode(['status' => 'noproof']);
    exit();
}
echo json_encode(['status' => 'ok', 'url' => $proof]);
