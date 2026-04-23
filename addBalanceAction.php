<?php
ob_start();
session_start();
date_default_timezone_set('UTC');
include "includes/config.php";
header('Content-Type: application/json');

if (!isset($_SESSION['sname']) or !isset($_SESSION['spass'])) {
    echo json_encode(['status'=>'error','msg'=>'Not authenticated']);
    exit();
}

$usrid  = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
$amount = (int)($_POST['amount'] ?? 0);
$txhash = mysqli_real_escape_string($dbcon, trim($_POST['tx_hash'] ?? ''));
$note   = mysqli_real_escape_string($dbcon, trim($_POST['note']    ?? ''));
$method = trim($_POST['method'] ?? 'Bitcoin');

if (!in_array($method, ['Bitcoin', 'Ethereum'])) {
    $method = 'Bitcoin';
}

if ($amount < 5) {
    echo json_encode(['status'=>'error','msg'=>'Minimum deposit is $5 USD.']);
    exit();
}

$cooldownCheck = mysqli_query($dbcon,
    "SELECT date FROM payment WHERE user='$usrid' ORDER BY id DESC LIMIT 1"
);
if ($cooldownCheck && mysqli_num_rows($cooldownCheck) > 0) {
    $lastRow  = mysqli_fetch_assoc($cooldownCheck);
    $lastTime = strtotime($lastRow['date']);
    $elapsed  = time() - $lastTime;
    if ($elapsed < 600) {
        $remaining = 600 - $elapsed;
        $mins = floor($remaining / 60);
        $secs = $remaining % 60;
        $waitMsg = $mins > 0
            ? "Please wait {$mins} minute(s) and {$secs} second(s) before submitting another payment request."
            : "Please wait {$secs} second(s) before submitting another payment request.";
        echo json_encode(['status'=>'error','msg'=>$waitMsg]);
        exit();
    }
}

$address = $method === 'Ethereum'
    ? '0x4e39301608688748d5951390bd5abe20f2f566c5'
    : '14ZcY3aUy4eKU535TpcHFB9QVnSxBqzMXx';

$methodDb = mysqli_real_escape_string($dbcon, $method);
$addressDb = mysqli_real_escape_string($dbcon, $address);
$date = date('Y-m-d H:i:s');

mysqli_query($dbcon,
    "INSERT INTO payment (user, method, amount, amountusd, address, p_data, state, date, tx_hash, note)
     VALUES ('$usrid', '$methodDb', '0', '$amount', '$addressDb', '', 'awaiting', '$date', '$txhash', '$note')"
) or die(json_encode(['status'=>'error','msg'=>'Database error: '.mysqli_error($dbcon)]));

echo json_encode(['status'=>'ok','msg'=>'Your payment request has been submitted. Balance will be added once the admin approves it.']);
?>
