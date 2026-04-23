<?php
session_start();
date_default_timezone_set('UTC');
include "../includes/config.php";
header('Content-Type: application/json');

if (!isset($_SESSION['sname']) || !isset($_SESSION['spass'])) {
    echo json_encode(['status'=>'error','msg'=>'Not authenticated']); exit();
}
$me = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
$rQ = mysqli_query($dbcon, "SELECT role FROM users WHERE username='$me'");
$rR = $rQ ? mysqli_fetch_assoc($rQ) : null;
$role = $rR['role'] ?? 'user';
if ($role !== 'admin' && $role !== 'support') {
    echo json_encode(['status'=>'error','msg'=>'Access denied']); exit();
}

$user = trim($_GET['user'] ?? '');
if ($user === '') { echo json_encode(['status'=>'error','msg'=>'Missing user']); exit(); }
$userEsc = mysqli_real_escape_string($dbcon, $user);

$uQ = mysqli_query($dbcon, "SELECT username, email, balance, ipurchassed, datereg FROM users WHERE username='$userEsc' LIMIT 1");
$uInfo = $uQ ? mysqli_fetch_assoc($uQ) : null;
if (!$uInfo) { echo json_encode(['status'=>'error','msg'=>'User not found']); exit(); }

// Payments
ob_start();
echo '<h4><span class="glyphicon glyphicon-usd"></span> Payment History</h4>';
$pQ = mysqli_query($dbcon, "SELECT id, method, amountusd, tx_hash, state, date, note FROM payment WHERE user='$userEsc' ORDER BY id DESC");
$pTotalApproved = 0; $pCount = $pQ ? mysqli_num_rows($pQ) : 0;
if ($pCount === 0) {
    echo '<div class="alert alert-info">No payment requests on record.</div>';
} else {
    echo '<table class="table table-bordered table-condensed table-striped"><thead><tr>'
        .'<th>#</th><th>Method</th><th>Amount</th><th>TxID</th><th>Status</th><th>Date</th><th>Note</th></tr></thead><tbody>';
    while ($pr = mysqli_fetch_assoc($pQ)) {
        $st = $pr['state'] ?? '';
        $cls = $st === 'approved' ? 'label-success' : ($st === 'rejected' ? 'label-danger' : ($st === 'awaiting' ? 'label-warning' : 'label-default'));
        if ($st === 'approved') $pTotalApproved += (int)$pr['amountusd'];
        $txh = (string)($pr['tx_hash'] ?? '');
        echo '<tr>'
            .'<td>#'.(int)$pr['id'].'</td>'
            .'<td>'.htmlspecialchars($pr['method'] ?? '').'</td>'
            .'<td>$'.(int)($pr['amountusd'] ?? 0).'</td>'
            .'<td>'.($txh ? '<code style="font-size:11px">'.htmlspecialchars(substr($txh,0,18)).'...</code>' : '<span class="text-muted">-</span>').'</td>'
            .'<td><span class="label '.$cls.'">'.htmlspecialchars(ucfirst($st)).'</span></td>'
            .'<td>'.htmlspecialchars($pr['date'] ?? '').'</td>'
            .'<td>'.htmlspecialchars($pr['note'] ?: '-').'</td>'
            .'</tr>';
    }
    echo '</tbody></table>';
}

// Purchases
echo '<h4 style="margin-top:18px"><span class="glyphicon glyphicon-shopping-cart"></span> Purchase History</h4>';
$bQ = mysqli_query($dbcon, "SELECT id, type, country, price, resseller, date, reported FROM purchases WHERE buyer='$userEsc' ORDER BY id DESC");
$bCount = $bQ ? mysqli_num_rows($bQ) : 0;
$bTotal = 0;
if ($bCount === 0) {
    echo '<div class="alert alert-info">No purchases on record.</div>';
} else {
    echo '<table class="table table-bordered table-condensed table-striped"><thead><tr>'
        .'<th>#</th><th>Type</th><th>Country</th><th>Price</th><th>Seller</th><th>Date</th><th>Reported</th></tr></thead><tbody>';
    while ($br = mysqli_fetch_assoc($bQ)) {
        $bTotal += (int)$br['price'];
        $rep = trim((string)($br['reported'] ?? ''));
        echo '<tr>'
            .'<td>#'.(int)$br['id'].'</td>'
            .'<td>'.htmlspecialchars($br['type'] ?? '').'</td>'
            .'<td>'.htmlspecialchars($br['country'] ?? '-').'</td>'
            .'<td>$'.(int)($br['price'] ?? 0).'</td>'
            .'<td>'.htmlspecialchars($br['resseller'] ?? '-').'</td>'
            .'<td>'.htmlspecialchars($br['date'] ?? '').'</td>'
            .'<td>'.($rep !== '' ? '<span class="label label-warning">'.htmlspecialchars($rep).'</span>' : '<span class="text-muted">-</span>').'</td>'
            .'</tr>';
    }
    echo '</tbody></table>';
}

$body = ob_get_clean();

$summary = '<div class="well well-sm" style="margin-bottom:12px">'
    .'<b>'.htmlspecialchars($uInfo['username']).'</b> &middot; '.htmlspecialchars($uInfo['email'] ?? '-').'<br>'
    .'Current Balance: <b>$'.(int)($uInfo['balance'] ?? 0).'</b> &nbsp;|&nbsp; '
    .'Items Purchased: <b>'.htmlspecialchars($uInfo['ipurchassed'] ?? '0').'</b> &nbsp;|&nbsp; '
    .'Registered: <b>'.htmlspecialchars($uInfo['datereg'] ?? '-').'</b><br>'
    .'Total Approved Payments: <b style="color:#27ae60">$'.$pTotalApproved.'</b> ('.$pCount.' requests) &nbsp;|&nbsp; '
    .'Total Spent on Purchases: <b style="color:#c0392b">$'.$bTotal.'</b> ('.$bCount.' items)'
    .'</div>';

echo json_encode(['status'=>'ok','html'=>$summary.$body,'username'=>$uInfo['username']]);
