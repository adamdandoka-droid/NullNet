<?php
session_start();
ob_start();
date_default_timezone_set('UTC');
include "../includes/config.php";

// Auth check
if (!isset($_SESSION['sname']) || !isset($_SESSION['spass'])) {
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['status'=>'error','msg'=>'Not authenticated']);
        exit();
    }
    header("location: ../login.html");
    exit();
}
$usrid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
$roleQ = mysqli_query($dbcon, "SELECT role FROM users WHERE username='$usrid'");
$roleR = $roleQ ? mysqli_fetch_assoc($roleQ) : null;
$role  = $roleR['role'] ?? 'user';
if ($role !== 'admin') {
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['status'=>'error','msg'=>'Access denied']);
        exit();
    }
    http_response_code(403); die('Access Denied');
}

// Fragment endpoint: return refreshed awaiting + history HTML
if (isset($_GET['fragment'])) {
    header('Content-Type: application/json');
    $aQ = mysqli_query($dbcon, "
        SELECT p.*, u.email, u.balance as user_balance, u.datereg, u.ipurchassed
        FROM payment p LEFT JOIN users u ON u.username = p.user
        WHERE p.state='awaiting' ORDER BY p.id DESC");
    $awaitCount = $aQ ? mysqli_num_rows($aQ) : 0;
    ob_start();
    if ($awaitCount === 0) {
        echo '<div class="alert alert-info">No pending payment requests right now.</div>';
    } else {
        echo '<table class="table table-bordered table-striped table-condensed sort"><thead><tr>'
            .'<th>Pay #</th><th>Username</th><th>Email</th><th>Method</th><th>Amount (USD)</th>'
            .'<th>TxID / Hash</th><th>Note</th><th>Current Balance</th><th>Total Purchases</th>'
            .'<th>Reg Date</th><th>Request Date</th><th>Action</th></tr></thead><tbody>';
        while ($row = mysqli_fetch_assoc($aQ)) {
            $isEth = strtolower($row['method'] ?? '') === 'ethereum';
            $txLink = $isEth ? 'https://etherscan.io/tx/' : 'https://www.blockchain.com/btc/tx/';
            $txSite = $isEth ? 'Etherscan' : 'blockchain.com';
            $txh = (string)($row['tx_hash'] ?? '');
            $rid = (int)$row['id'];
            $amtUsd = (int)($row['amountusd'] ?? 0);
            $userJson = htmlspecialchars(json_encode($row['user'] ?? ''), ENT_QUOTES);
            echo '<tr id="row-'.$rid.'">'
                .'<td><b>#'.$rid.'</b></td>'
                .'<td><b>'.htmlspecialchars($row['user'] ?? '').'</b></td>'
                .'<td>'.htmlspecialchars($row['email'] ?? '-').'</td>'
                .'<td>'.($isEth ? '<span class="label" style="background:#627EEA">ETH</span>' : '<span class="label label-warning">BTC</span>').'</td>'
                .'<td><b style="color:#27ae60;font-size:15px">$'.$amtUsd.'</b></td>'
                .'<td>'.($txh
                    ? '<a href="'.$txLink.htmlspecialchars($txh).'" target="_blank" title="View on '.$txSite.'" style="font-family:monospace;font-size:11px">'.htmlspecialchars(substr($txh,0,28)).'... <span class="glyphicon glyphicon-new-window"></span></a>'
                    : '<span class="text-muted">Not provided</span>').'</td>'
                .'<td>'.htmlspecialchars($row['note'] ?: '-').'</td>'
                .'<td>$'.(int)($row['user_balance'] ?? 0).'</td>'
                .'<td>'.htmlspecialchars($row['ipurchassed'] ?? '0').' orders</td>'
                .'<td>'.htmlspecialchars($row['datereg'] ?? '-').'</td>'
                .'<td>'.htmlspecialchars($row['date']).'</td>'
                .'<td>'
                .'<button onclick="payAction('.$rid.',\'approve\','.$amtUsd.','.$userJson.')" class="btn btn-success btn-xs"><span class="glyphicon glyphicon-ok"></span> Approve</button> '
                .'<button onclick="payAction('.$rid.',\'reject\','.$amtUsd.','.$userJson.')" class="btn btn-danger btn-xs" style="margin-top:3px"><span class="glyphicon glyphicon-remove"></span> Reject</button>'
                .'</td></tr>';
        }
        echo '</tbody></table>';
    }
    $awaitingHtml = ob_get_clean();

    $hQ = mysqli_query($dbcon, "
        SELECT p.*, u.email FROM payment p LEFT JOIN users u ON u.username = p.user
        WHERE p.state IN ('approved','rejected') ORDER BY p.id DESC LIMIT 50");
    ob_start();
    while ($hr = mysqli_fetch_assoc($hQ)) {
        $badge = $hr['state'] === 'approved'
            ? '<span class="label label-success">Approved</span>'
            : '<span class="label label-danger">Rejected</span>';
        $hIsEth = strtolower($hr['method'] ?? '') === 'ethereum';
        $hTxLink = $hIsEth ? 'https://etherscan.io/tx/' : 'https://www.blockchain.com/btc/tx/';
        $htxh = (string)($hr['tx_hash'] ?? '');
        echo '<tr>'
            .'<td>#'.(int)$hr['id'].'</td>'
            .'<td>'.htmlspecialchars($hr['user'] ?? '').'</td>'
            .'<td>'.htmlspecialchars($hr['email'] ?? '-').'</td>'
            .'<td>'.($hIsEth ? '<span class="label" style="background:#627EEA">ETH</span>' : '<span class="label label-warning">BTC</span>').'</td>'
            .'<td>$'.(int)($hr['amountusd'] ?? 0).'</td>'
            .'<td>'.($htxh
                ? '<a href="'.$hTxLink.htmlspecialchars($htxh).'" target="_blank" style="font-family:monospace;font-size:11px">'.htmlspecialchars(substr($htxh,0,28)).'...</a>'
                : '<span class="text-muted">-</span>').'</td>'
            .'<td>'.htmlspecialchars($hr['note'] ?: '-').'</td>'
            .'<td>'.$badge.'</td>'
            .'<td>'.htmlspecialchars($hr['date']).'</td>'
            .'</tr>';
    }
    $historyHtml = ob_get_clean();

    echo json_encode([
        'status'        => 'ok',
        'await_count'   => $awaitCount,
        'awaiting_html' => $awaitingHtml,
        'history_html'  => $historyHtml,
    ]);
    exit();
}

// Handle approve/reject AJAX actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $pid    = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        $payQ = mysqli_query($dbcon, "SELECT * FROM payment WHERE id='$pid' AND state='awaiting'");
        $pay  = mysqli_fetch_assoc($payQ);
        if (!$pay) {
            echo json_encode(['status'=>'error','msg'=>'Payment not found or already processed.']);
            exit();
        }
        $amt  = (int)$pay['amountusd'];
        $user = mysqli_real_escape_string($dbcon, $pay['user']);
        mysqli_query($dbcon, "UPDATE payment SET state='approved' WHERE id='$pid'");
        mysqli_query($dbcon, "UPDATE users SET balance = balance + $amt WHERE username='$user'");
        echo json_encode(['status'=>'ok','msg'=>'Payment approved. $'.$amt.' added to '.$user.'\'s balance.']);
    } elseif ($action === 'reject') {
        $payQ = mysqli_query($dbcon, "SELECT id FROM payment WHERE id='$pid' AND state='awaiting'");
        if (!mysqli_fetch_assoc($payQ)) {
            echo json_encode(['status'=>'error','msg'=>'Payment not found or already processed.']);
            exit();
        }
        mysqli_query($dbcon, "UPDATE payment SET state='rejected' WHERE id='$pid'");
        echo json_encode(['status'=>'ok','msg'=>'Payment rejected.']);
    } else {
        echo json_encode(['status'=>'error','msg'=>'Unknown action.']);
    }
    exit();
}

include "header.php";

// List all awaiting payments
$awaitQ = mysqli_query($dbcon, "
    SELECT p.*, u.email, u.balance as user_balance, u.datereg, u.ip as user_ip, u.ipurchassed
    FROM payment p
    LEFT JOIN users u ON u.username = p.user
    WHERE p.state = 'awaiting'
    ORDER BY p.id DESC
");
$awaitCount = mysqli_num_rows($awaitQ);

// Recent history
$histQ = mysqli_query($dbcon, "
    SELECT p.*, u.email
    FROM payment p
    LEFT JOIN users u ON u.username = p.user
    WHERE p.state IN ('approved','rejected')
    ORDER BY p.id DESC LIMIT 50
");
?>
<div class="alert alert-danger fade in radius-bordered alert-shadowed">
    <b><span class="glyphicon glyphicon-usd"></span> Payment Approval</b>
    <span class="badge" style="background:#e74c3c; margin-left:8px"><span id="awaitCountTop"><?php echo $awaitCount; ?></span> awaiting</span>
</div>

<div id="approvalResult" style="margin-bottom:12px"></div>

<ul class="nav nav-tabs">
    <li class="active"><a href="#awaiting" data-toggle="tab">
        <span class="glyphicon glyphicon-time"></span> Awaiting Approval
        <span class="badge" style="background:#e67e22" id="awaitCountTab"><?php echo $awaitCount; ?></span>
    </a></li>
    <li><a href="#history" data-toggle="tab"><span class="glyphicon glyphicon-list"></span> Approval History</a></li>
</ul>

<div class="tab-content" id="payTabContent">

    <!-- AWAITING TAB -->
    <div class="tab-pane fade in active" id="awaiting">
    <br>
    <div id="awaitingWrap">
    <?php if ($awaitCount === 0): ?>
        <div class="alert alert-info">No pending payment requests right now.</div>
    <?php else: ?>
    <table class="table table-bordered table-striped table-condensed sort">
        <thead>
        <tr>
            <th>Pay #</th>
            <th>Username</th>
            <th>Email</th>
            <th>Method</th>
            <th>Amount (USD)</th>
            <th>TxID / Hash</th>
            <th>Note</th>
            <th>Current Balance</th>
            <th>Total Purchases</th>
            <th>Reg Date</th>
            <th>Request Date</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($awaitQ)):
            $isEth = strtolower($row['method'] ?? '') === 'ethereum';
            $txLink = $isEth
                ? 'https://etherscan.io/tx/'
                : 'https://www.blockchain.com/btc/tx/';
            $txSite = $isEth ? 'Etherscan' : 'blockchain.com';
        ?>
        <tr id="row-<?php echo (int)$row['id']; ?>">
            <td><b>#<?php echo (int)$row['id']; ?></b></td>
            <td><b><?php echo htmlspecialchars($row['user'] ?? ''); ?></b></td>
            <td><?php echo htmlspecialchars($row['email'] ?? '-'); ?></td>
            <td>
                <?php if ($isEth): ?>
                    <span class="label" style="background:#627EEA">ETH</span>
                <?php else: ?>
                    <span class="label label-warning">BTC</span>
                <?php endif; ?>
            </td>
            <td><b style="color:#27ae60;font-size:15px">$<?php echo (int)($row['amountusd'] ?? 0); ?></b></td>
            <td>
                <?php $txh = (string)($row['tx_hash'] ?? ''); if ($txh): ?>
                    <a href="<?php echo $txLink.htmlspecialchars($txh); ?>" target="_blank" title="View on <?php echo $txSite; ?>" style="font-family:monospace;font-size:11px">
                        <?php echo htmlspecialchars(substr($txh,0,28)); ?>...
                        <span class="glyphicon glyphicon-new-window"></span>
                    </a>
                <?php else: ?>
                    <span class="text-muted">Not provided</span>
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($row['note'] ?: '-'); ?></td>
            <td>$<?php echo (int)($row['user_balance'] ?? 0); ?></td>
            <td><?php echo htmlspecialchars($row['ipurchassed'] ?? '0'); ?> orders</td>
            <td><?php echo htmlspecialchars($row['datereg'] ?? '-'); ?></td>
            <td><?php echo htmlspecialchars($row['date']); ?></td>
            <td>
                <button onclick="payAction(<?php echo (int)$row['id']; ?>,'approve',<?php echo (int)($row['amountusd'] ?? 0); ?>,<?php echo htmlspecialchars(json_encode($row['user'] ?? ''), ENT_QUOTES); ?>)" class="btn btn-success btn-xs">
                    <span class="glyphicon glyphicon-ok"></span> Approve
                </button>
                <button onclick="payAction(<?php echo (int)$row['id']; ?>,'reject',<?php echo (int)($row['amountusd'] ?? 0); ?>,<?php echo htmlspecialchars(json_encode($row['user'] ?? ''), ENT_QUOTES); ?>)" class="btn btn-danger btn-xs" style="margin-top:3px">
                    <span class="glyphicon glyphicon-remove"></span> Reject
                </button>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
    </div>
    </div>

    <!-- HISTORY TAB -->
    <div class="tab-pane fade in" id="history">
    <br>
    <table class="table table-bordered table-striped table-condensed sort">
        <thead>
        <tr>
            <th>Pay #</th>
            <th>Username</th>
            <th>Email</th>
            <th>Method</th>
            <th>Amount (USD)</th>
            <th>TxID / Hash</th>
            <th>Note</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
        </thead>
        <tbody id="historyBody">
        <?php while ($hr = mysqli_fetch_assoc($histQ)):
            $badge = $hr['state'] === 'approved'
                ? '<span class="label label-success">Approved</span>'
                : '<span class="label label-danger">Rejected</span>';
            $hIsEth  = strtolower($hr['method'] ?? '') === 'ethereum';
            $hTxLink = $hIsEth ? 'https://etherscan.io/tx/' : 'https://www.blockchain.com/btc/tx/';
        ?>
        <tr>
            <td>#<?php echo (int)$hr['id']; ?></td>
            <td><?php echo htmlspecialchars($hr['user'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($hr['email'] ?? '-'); ?></td>
            <td><?php echo $hIsEth ? '<span class="label" style="background:#627EEA">ETH</span>' : '<span class="label label-warning">BTC</span>'; ?></td>
            <td>$<?php echo (int)($hr['amountusd'] ?? 0); ?></td>
            <td>
                <?php $htxh = (string)($hr['tx_hash'] ?? ''); if ($htxh): ?>
                    <a href="<?php echo $hTxLink.htmlspecialchars($htxh); ?>" target="_blank" style="font-family:monospace;font-size:11px">
                        <?php echo htmlspecialchars(substr($htxh,0,28)); ?>...
                    </a>
                <?php else: ?>
                    <span class="text-muted">-</span>
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($hr['note'] ?: '-'); ?></td>
            <td><?php echo $badge; ?></td>
            <td><?php echo htmlspecialchars($hr['date']); ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>

</div>

<script>
function refreshPaymentTables(){
    $.ajax({
        url: 'paymentapproval.php?fragment=1',
        dataType: 'json',
        success: function(d){
            if(!d || d.status !== 'ok') return;
            $('#awaitingWrap').html(d.awaiting_html);
            $('#historyBody').html(d.history_html);
            $('#awaitCountTop').text(d.await_count);
            $('#awaitCountTab').text(d.await_count);
        }
    });
}
function payAction(pid, action, amount, username){
    var isApprove = action === 'approve';
    var amt = '$' + (amount || 0);
    var who = username ? ' to <b>'+username+'</b>' : ' to the user';
    bootbox.confirm({
        title: isApprove ? 'Approve payment' : 'Reject payment',
        message: isApprove
            ? 'Approve payment request <b>#'+pid+'</b> and add the balance ('+amt+')'+who+'?'
            : 'Reject payment request <b>#'+pid+'</b> ('+amt+')'+who+'? This cannot be undone.',
        buttons: {
            cancel:  { label: 'Cancel', className: 'btn-default' },
            confirm: {
                label: isApprove ? 'Approve' : 'Reject',
                className: isApprove ? 'btn-success' : 'btn-danger'
            }
        },
        callback: function(result){
            if(!result) return;
            $.ajax({
                type: 'GET',
                url:  'paymentapproval.php?action='+action+'&id='+pid,
                dataType: 'json',
                success: function(data){
                    if(data.status === 'ok'){
                        $('#approvalResult').html('<div class="alert alert-success"><strong>Done!</strong> '+data.msg+'</div>');
                        $('#row-'+pid).fadeOut(400, function(){ $(this).remove(); refreshPaymentTables(); });
                    } else {
                        $('#approvalResult').html('<div class="alert alert-danger">'+data.msg+'</div>');
                    }
                },
                error: function(){
                    $('#approvalResult').html('<div class="alert alert-danger">Request failed. Please try again.</div>');
                }
            });
        }
    });
}
</script>
