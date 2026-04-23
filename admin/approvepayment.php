<?php
session_start();
ob_start();
date_default_timezone_set('UTC');
include "../includes/config.php";

if(!isset($_SESSION['sname']) || !isset($_SESSION['spass'])){
    echo json_encode(['status'=>'error','msg'=>'Not authenticated']);
    exit();
}

$usrid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
$roleQ = mysqli_query($dbcon, "SELECT role FROM users WHERE username='$usrid'");
$roleR = $roleQ ? mysqli_fetch_assoc($roleQ) : null;
$role  = $roleR['role'] ?? 'user';
if ($role !== 'admin') {
    echo json_encode(['status'=>'error','msg'=>'Access denied']);
    exit();
}

$pid = (int)($_GET['id'] ?? 0);
if (!$pid) {
    echo json_encode(['status'=>'error','msg'=>'Invalid payment ID']);
    exit();
}

$now = date("Y-m-d H:i:s");

$q = mysqli_query($dbcon, "SELECT sp.*, p.reported FROM seller_payments sp LEFT JOIN purchases p ON p.id=sp.purchase_id WHERE sp.id='$pid' AND sp.status='pending'");
$row = mysqli_fetch_assoc($q);
if (!$row) {
    echo json_encode(['status'=>'error','msg'=>'Payment not found or already processed']);
    exit();
}

$seller = $row['seller'];
$amount = $row['amount'];
$approver = mysqli_real_escape_string($dbcon, $_SESSION['sname']);

mysqli_query($dbcon, "UPDATE seller_payments SET status='approved', approved_by='$approver', approved_at='$now' WHERE id='$pid'");
mysqli_query($dbcon, "UPDATE resseller SET soldb=(soldb + $amount) WHERE username='$seller'");

echo json_encode(['status'=>'ok','msg'=>'Payment approved and released to seller wallet']);
?>
