<?php
session_start();
ob_start();
date_default_timezone_set('UTC');
include "../includes/config.php";

header('Content-Type: application/json');

if (!isset($_SESSION['sname']) || !isset($_SESSION['spass'])) {
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

// Ensure withdraw_note column exists (idempotent)
$colCheck = mysqli_query($dbcon, "SHOW COLUMNS FROM resseller LIKE 'withdraw_note'");
if (!$colCheck || mysqli_num_rows($colCheck) === 0) {
    @mysqli_query($dbcon, "ALTER TABLE resseller ADD COLUMN withdraw_note TEXT NULL");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$seller = trim($_POST['seller'] ?? $_GET['seller'] ?? '');
$seller = mysqli_real_escape_string($dbcon, $seller);
$note   = trim($_POST['note'] ?? '');
$note   = mysqli_real_escape_string($dbcon, $note);
$now    = date('Y-m-d H:i:s');

if ($seller === '') {
    echo json_encode(['status'=>'error','msg'=>'Missing seller']);
    exit();
}

$rQ = mysqli_query($dbcon, "SELECT * FROM resseller WHERE username='$seller'");
$row = $rQ ? mysqli_fetch_assoc($rQ) : null;
if (!$row) {
    echo json_encode(['status'=>'error','msg'=>'Seller not found']);
    exit();
}
if ($row['withdrawal'] !== 'requested') {
    echo json_encode(['status'=>'error','msg'=>'No active withdrawal request for this seller']);
    exit();
}

if ($action === 'pay') {
    $released  = (float)$row['soldb'];
    $receive   = round($released * 0.65, 2);
    $method    = $row['withdraw_method'] ?? 'btc';
    $address   = ($method === 'eth') ? ($row['eth'] ?? '') : ($row['btc'] ?? '');
    $methodEsc = mysqli_real_escape_string($dbcon, $method);
    $addrEsc   = mysqli_real_escape_string($dbcon, $address);

    // Live BTC/USD rate at moment of payout
    $rate = 0; $cryptoAmt = 0;
    $ctx = stream_context_create(['http'=>['timeout'=>4]]);
    if ($method === 'eth') {
        $j = @file_get_contents("https://api.coingecko.com/api/v3/simple/price?ids=ethereum&vs_currencies=usd", false, $ctx);
        if ($j) { $d = @json_decode($j, true); $rate = (float)($d['ethereum']['usd'] ?? 0); }
    } else {
        $j = @file_get_contents("https://blockchain.info/stats?format=json", false, $ctx);
        if ($j) { $d = @json_decode($j, true); $rate = (float)($d['market_price_usd'] ?? 0); }
        if ($rate <= 0) {
            $j = @file_get_contents("https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd", false, $ctx);
            if ($j) { $d = @json_decode($j, true); $rate = (float)($d['bitcoin']['usd'] ?? 0); }
        }
    }
    if ($rate > 0) { $cryptoAmt = round($receive / $rate, 8); }
    $rateEsc  = mysqli_real_escape_string($dbcon, (string)$rate);
    $cryptoEsc= mysqli_real_escape_string($dbcon, (string)$cryptoAmt);

    // Record manual payout in rpayment (if table exists)
    @mysqli_query($dbcon, "INSERT INTO rpayment
        (username, amount, abtc, adbtc, method, date, url, urid, rate, fee)
        VALUES
        ('$seller', '$receive', '$cryptoEsc', '$addrEsc', '$methodEsc', '$now', 'manual-approval', '0', '$rateEsc', '0')");

    // Mark as done and reset released balance
    mysqli_query($dbcon, "UPDATE resseller
        SET withdrawal='done', soldb=0, withdraw_note=NULL
        WHERE username='$seller'");

    // Mark the released seller_payments records as 'paid' so Total Sales resets
    mysqli_query($dbcon, "UPDATE seller_payments
        SET status='paid', approved_by='$usrid', approved_at='$now'
        WHERE seller='$seller' AND status='released'");

    $rateMsg = $rate > 0 ? sprintf(" — rate $%s/%s, sent %s %s", number_format($rate,2), strtoupper($method), $cryptoAmt, strtoupper($method)) : "";
    echo json_encode([
        'status'=>'ok',
        'msg'=>"Withdrawal for $seller approved successfully (\$$receive ".strtoupper($method).")".$rateMsg."."
    ]);
    exit();
}

if ($action === 'reject') {
    if ($note === '') {
        echo json_encode(['status'=>'error','msg'=>'A rejection note is required.']);
        exit();
    }
    mysqli_query($dbcon, "UPDATE resseller
        SET withdrawal='rejected', withdraw_note='$note'
        WHERE username='$seller'");
    echo json_encode([
        'status'=>'ok',
        'msg'=>"Withdrawal for $seller rejected."
    ]);
    exit();
}

echo json_encode(['status'=>'error','msg'=>'Unknown action']);
