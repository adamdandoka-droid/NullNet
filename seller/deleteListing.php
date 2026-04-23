<?php
error_reporting(0);
session_start();
header('Content-Type: application/json');
include "../includes/config.php";

if (!isset($_SESSION['sname']) || !isset($_SESSION['spass'])) {
    echo json_encode(['status'=>'error','message'=>'Not authenticated']);
    exit;
}

$allowed = ['rdps','cpanels','stufs','mailers','smtps','leads','scampages','tutorials','accounts','banks'];
$cat = (string)($_POST['cat'] ?? '');
$id  = (int)($_POST['id'] ?? 0);
$uid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);

if (!in_array($cat, $allowed, true) || $id <= 0) {
    echo json_encode(['status'=>'error','message'=>'Invalid request']);
    exit;
}

$check = mysqli_query($dbcon, "SELECT sold FROM `$cat` WHERE id='$id' AND resseller='$uid' LIMIT 1");
$row = $check ? mysqli_fetch_assoc($check) : null;
if (!$row) {
    echo json_encode(['status'=>'error','message'=>'Listing not found or not yours']);
    exit;
}
if ((int)$row['sold'] >= 1) {
    echo json_encode(['status'=>'error','message'=>'Cannot delete a sold item']);
    exit;
}

$ok = mysqli_query($dbcon, "DELETE FROM `$cat` WHERE id='$id' AND resseller='$uid' AND sold='0' LIMIT 1");
if ($ok && mysqli_affected_rows($dbcon) > 0) {
    echo json_encode(['status'=>'ok']);
} else {
    echo json_encode(['status'=>'error','message'=>'Delete failed: '.mysqli_error($dbcon)]);
}
