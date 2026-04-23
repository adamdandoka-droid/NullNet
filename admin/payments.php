<?php
include "header.php";
date_default_timezone_set('UTC');

$now = date("Y-m-d H:i:s");

// ===== Auto-release seller payments past 10h with no report =====
$autoQ = mysqli_query($dbcon, "
    SELECT sp.id, sp.seller, sp.amount
    FROM seller_payments sp
    LEFT JOIN purchases p ON p.id = sp.purchase_id
    WHERE sp.status='pending' AND sp.release_date <= '$now'
      AND (p.reported IS NULL OR p.reported = '' OR p.reported = '0')
");
while ($arow = mysqli_fetch_assoc($autoQ)) {
    $apid    = (int)$arow['id'];
    $aseller = mysqli_real_escape_string($dbcon, $arow['seller']);
    $aamt    = (float)$arow['amount'];
    mysqli_query($dbcon, "UPDATE seller_payments SET status='released', approved_at='$now' WHERE id='$apid'");
    mysqli_query($dbcon, "UPDATE resseller SET soldb=(soldb + $aamt) WHERE username='$aseller'");
}

// Counts
$wQ   = mysqli_query($dbcon, "SELECT COUNT(*) AS c FROM resseller WHERE withdrawal='requested'");
$wR   = mysqli_fetch_assoc($wQ);
$wCount = (int)$wR['c'];

$paQ  = mysqli_query($dbcon, "SELECT COUNT(*) AS c FROM payment WHERE state='awaiting'");
$paR  = mysqli_fetch_assoc($paQ);
$paCount = (int)$paR['c'];

$spQ  = mysqli_query($dbcon, "SELECT COUNT(*) AS c FROM seller_payments WHERE status='pending'");
$spR  = mysqli_fetch_assoc($spQ);
$spCount = (int)$spR['c'];
?>
<div class="box-body">
<div class="alert alert-danger fade in radius-bordered alert-shadowed"><b>Payments Management</b></div>

<div class="form-group" style="margin-bottom:12px">
    <div class="input-group">
        <span class="input-group-addon"><span class="glyphicon glyphicon-search"></span></span>
        <input type="text" id="paymentsSearch" class="form-control"
            placeholder="Search by seller, buyer, email, method, address, status, amount, txid, order # ..." />
        <span class="input-group-btn">
            <button class="btn btn-default" type="button" onclick="document.getElementById('paymentsSearch').value='';filterPaymentsTables();">Clear</button>
        </span>
    </div>
    <small class="text-muted">Search filters every table below in the four payment tabs.</small>
</div>

<div id="withdrawActionResult" style="margin-bottom:10px"></div>

<ul class="nav nav-tabs" id="paymentsTabs">
    <li class="active"><a href="#withdraw" data-toggle="tab"><span class="glyphicon glyphicon-credit-card"></span> Withdraw Approval <?php if($wCount>0) echo '<span class="label label-danger">'.$wCount.'</span>'; ?></a></li>
    <li><a href="#payapproval" data-toggle="tab"><span class="glyphicon glyphicon-usd"></span> Payment Approval <?php if($paCount>0) echo '<span class="label label-danger">'.$paCount.'</span>'; ?></a></li>
    <li><a href="#sellerhold" data-toggle="tab"><span class="glyphicon glyphicon-time"></span> Seller Profit Hold <?php if($spCount>0) echo '<span class="label label-warning">'.$spCount.'</span>'; ?></a></li>
    <li><a href="#allpayments" data-toggle="tab"><span class="glyphicon glyphicon-list"></span> All Payments History</a></li>
</ul>

<div class="tab-content">

<!-- ============ TAB 1: WITHDRAW APPROVAL ============ -->
<div class="tab-pane fade active in" id="withdraw">
    <br>
    <div class="alert alert-info">Sellers who have requested a withdrawal. <b>Pending</b> column reflects profit still held by the 10h rule. <b>Total</b> = released balance you can pay out now.</div>
<?php
$wlQ = mysqli_query($dbcon, "SELECT * FROM resseller WHERE withdrawal='requested'");
$wlCount = mysqli_num_rows($wlQ);
$totalNet = 0; $totalSeller = 0;
echo '<div class="panel panel-default">
        <div class="panel-heading">Withdrawal requests <span class="label label-warning">Total: '.$wlCount.'</span></div>
        <table class="table table-bordered table-striped">
            <thead><tr>
                <th>Seller</th>
                <th>Released USD</th>
                <th>Pending USD (10h hold)</th>
                <th>Reported USD (frozen)</th>
                <th>Receive USD (65%)</th>
                <th>Receive BTC</th>
                <th>Method</th>
                <th>Payout Address</th>
                <th>NullNet 35%</th>
                <th>Action</th>
            </tr></thead><tbody>';

if ($wlCount === 0) {
    echo '<tr><td colspan="9" class="text-center text-muted">No pending withdrawal requests.</td></tr>';
} else {
    $btcRate = 0;
    $statsTxt = @file_get_contents("https://blockchain.info/stats?format=json");
    if ($statsTxt) {
        $stats = @json_decode($statsTxt, true);
        $btcRate = (float)($stats['market_price_usd'] ?? 0);
    }

    while ($row = mysqli_fetch_assoc($wlQ)) {
        $uid = mysqli_real_escape_string($dbcon, $row['username']);
        $released = (float)$row['soldb'];

        $p1 = mysqli_query($dbcon, "SELECT SUM(amount) AS t FROM seller_payments WHERE seller='$uid' AND status='pending'");
        $p1r = mysqli_fetch_assoc($p1);
        $pending = (float)($p1r['t'] ?? 0);

        $p2 = mysqli_query($dbcon, "
            SELECT SUM(sp.amount) AS t FROM seller_payments sp
            LEFT JOIN purchases p ON p.id = sp.purchase_id
            WHERE sp.seller='$uid' AND sp.status='pending'
              AND p.reported IS NOT NULL AND p.reported <> '' AND p.reported <> '0'
        ");
        $p2r = mysqli_fetch_assoc($p2);
        $reportedAmt = (float)($p2r['t'] ?? 0);

        $receive = $released * 0.65;
        $netcut  = $released * 0.35;
        $totalSeller += $receive;
        $totalNet    += $netcut;
        $btcAmount = $btcRate > 0 ? round($receive / $btcRate, 8) : 0;

        echo '<tr>
            <td><b>'.htmlspecialchars($row['username']).'</b></td>
            <td>$'.number_format($released,2).'</td>
            <td><span style="color:#e67e22">$'.number_format($pending,2).'</span></td>
            <td><span style="color:#c0392b">$'.number_format($reportedAmt,2).'</span></td>
            <td><b>$'.number_format($receive,2).'</b></td>
            <td>'.$btcAmount.'</td>
            <td><span class="label '.($row['withdraw_method']==='eth'?'label-info':'label-warning').'">'.strtoupper($row['withdraw_method'] ?: 'btc').'</span></td>
            <td><small style="font-family:monospace">'.htmlspecialchars(($row['withdraw_method']==='eth')?($row['eth'] ?? ''):$row['btc']).'</small></td>
            <td>$'.number_format($netcut,2).'</td>
            <td id="wrow-act-'.htmlspecialchars($row['username']).'">
                <button onclick="payWithdraw(\''.htmlspecialchars($row['username'], ENT_QUOTES).'\','.$receive.')" class="btn btn-success btn-xs"><span class="glyphicon glyphicon-ok"></span> Pay</button>
                <button onclick="rejectWithdraw(\''.htmlspecialchars($row['username'], ENT_QUOTES).'\')" class="btn btn-danger btn-xs"><span class="glyphicon glyphicon-remove"></span> Reject</button>
            </td>
        </tr>';
    }
    echo '<tr style="background:#f5f5f5"><td><b>TOTAL</b></td><td colspan="3"></td><td><b>$'.number_format($totalSeller,2).'</b></td><td colspan="3"></td><td><b>$'.number_format($totalNet,2).'</b></td><td></td></tr>';
}
echo '</tbody></table></div>';
?>
</div>

<!-- ============ TAB 2: PAYMENT APPROVAL (Balance Top-ups) ============ -->
<div class="tab-pane fade in" id="payapproval">
    <br>
    <div class="alert alert-info">User balance top-up requests (BTC / Ethereum). Approve to credit user balance, reject to deny.</div>
    <div id="payApprovalArea">
<?php
$aQ = mysqli_query($dbcon, "
    SELECT p.*, u.email, u.balance as user_balance, u.datereg, u.ipurchassed
    FROM payment p LEFT JOIN users u ON u.username = p.user
    WHERE p.state='awaiting' ORDER BY p.id DESC");
$awc = $aQ ? mysqli_num_rows($aQ) : 0;
if ($awc === 0) {
    echo '<div class="alert alert-info">No pending payment requests.</div>';
} else {
    echo '<table class="table table-bordered table-striped table-condensed sort"><thead><tr>'
        .'<th>Pay #</th><th>User</th><th>Email</th><th>Method</th><th>Amount USD</th>'
        .'<th>TxID</th><th>Note</th><th>Balance</th><th>Reg</th><th>Date</th><th>Action</th></tr></thead><tbody>';
    while ($row = mysqli_fetch_assoc($aQ)) {
        $isEth = strtolower($row['method'] ?? '') === 'ethereum';
        $txLink = $isEth ? 'https://etherscan.io/tx/' : 'https://www.blockchain.com/btc/tx/';
        $txh = (string)($row['tx_hash'] ?? '');
        $rid = (int)$row['id'];
        $amt = (int)($row['amountusd'] ?? 0);
        $userJson = htmlspecialchars(json_encode($row['user'] ?? ''), ENT_QUOTES);
        echo '<tr id="row-'.$rid.'">'
            .'<td><b>#'.$rid.'</b></td>'
            .'<td><b>'.htmlspecialchars($row['user'] ?? '').'</b></td>'
            .'<td>'.htmlspecialchars($row['email'] ?? '-').'</td>'
            .'<td>'.($isEth ? '<span class="label" style="background:#627EEA">ETH</span>' : '<span class="label label-warning">BTC</span>').'</td>'
            .'<td><b style="color:#27ae60">$'.$amt.'</b></td>'
            .'<td>'.($txh ? '<a href="'.$txLink.htmlspecialchars($txh).'" target="_blank" style="font-family:monospace;font-size:11px">'.htmlspecialchars(substr($txh,0,28)).'...</a>' : '-').'</td>'
            .'<td>'.htmlspecialchars($row['note'] ?: '-').'</td>'
            .'<td>$'.(int)($row['user_balance'] ?? 0).'</td>'
            .'<td>'.htmlspecialchars($row['datereg'] ?? '-').'</td>'
            .'<td>'.htmlspecialchars($row['date']).'</td>'
            .'<td>'
            .'<button onclick="payAction('.$rid.',\'approve\','.$amt.','.$userJson.')" class="btn btn-success btn-xs">Approve</button> '
            .'<button onclick="payAction('.$rid.',\'reject\','.$amt.','.$userJson.')" class="btn btn-danger btn-xs">Reject</button>'
            .'</td></tr>';
    }
    echo '</tbody></table>';
}
?>
    </div>
</div>

<!-- ============ TAB 3: SELLER PROFIT HOLD (Early Approval) ============ -->
<div class="tab-pane fade in" id="sellerhold">
    <br>
    <div class="alert alert-info">Seller profits are held <b>10 hours</b> after each sale. Approve here to release to a seller's wallet immediately. Items reported by buyers stay frozen until the report is resolved.</div>
    <div id="approveResult"></div>
<?php
$pendQ = mysqli_query($dbcon, "
    SELECT sp.*, p.url as item_url, p.infos as item_info, p.reported as p_reported
    FROM seller_payments sp
    LEFT JOIN purchases p ON p.id = sp.purchase_id
    WHERE sp.status='pending'
    ORDER BY sp.id DESC
");
?>
    <table class="table table-bordered table-striped table-condensed">
        <thead><tr>
            <th>Pay #</th><th>Order #</th><th>Buyer</th><th>Seller</th><th>Type</th>
            <th>Item</th><th>Amount</th><th>Purchase Date</th><th>Release Date</th>
            <th>Reported?</th><th>Time Left</th><th>Action</th>
        </tr></thead>
        <tbody>
<?php while ($pr = mysqli_fetch_assoc($pendQ)):
    $reported = (!empty($pr['p_reported']) && $pr['p_reported'] !== '0');
    $relTs = strtotime($pr['release_date']);
    $diff = $relTs - time();
    $itemDisplay = htmlspecialchars($pr['item_url'] ?: $pr['item_info'] ?: '-');
?>
        <tr>
            <td>#<?php echo (int)$pr['id']; ?></td>
            <td>#<?php echo (int)$pr['purchase_id']; ?></td>
            <td><?php echo htmlspecialchars($pr['buyer']); ?></td>
            <td><?php echo htmlspecialchars($pr['seller']); ?></td>
            <td><?php echo strtoupper(htmlspecialchars($pr['item_type'])); ?></td>
            <td style="max-width:170px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo $itemDisplay; ?></td>
            <td><b>$<?php echo number_format($pr['amount'],2); ?></b></td>
            <td><?php echo htmlspecialchars($pr['purchase_date']); ?></td>
            <td><?php echo htmlspecialchars($pr['release_date']); ?></td>
            <td><?php echo $reported ? '<span class="label label-danger">YES</span>' : '<span class="label label-success">No</span>'; ?></td>
            <td><span class="profit-cd label label-<?php echo $diff>0?'warning':'default'; ?>" data-release="<?php echo (int)$relTs; ?>"><?php echo $diff>0 ? floor($diff/3600).'h '.floor(($diff%3600)/60).'m' : 'Overdue'; ?></span></td>
            <td>
            <?php if ($reported): ?>
                <span class="label label-warning">On Hold (Reported)</span>
            <?php else: ?>
                <button onclick="approvePay(<?php echo (int)$pr['id']; ?>)" class="btn btn-success btn-xs">Approve Now</button>
            <?php endif; ?>
            </td>
        </tr>
<?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- ============ TAB 4: ALL PAYMENTS HISTORY ============ -->
<div class="tab-pane fade in" id="allpayments">
    <br>
    <div class="alert alert-info">Combined history: balance top-ups, item purchases, and seller withdrawals.</div>
    <table class="table table-bordered table-striped table-condensed sort">
        <thead><tr>
            <th>Date</th>
            <th>Kind</th>
            <th>User / Seller</th>
            <th>Amount USD</th>
            <th>Status</th>
            <th>Reference</th>
        </tr></thead>
        <tbody>
<?php
// Build unified rows in PHP (limit to last 300 of each)
$rows = [];

// Balance top-ups
$qBal = mysqli_query($dbcon, "SELECT id, user, amountusd, state, date, method FROM payment ORDER BY id DESC LIMIT 300");
while ($b = mysqli_fetch_assoc($qBal)) {
    $rows[] = [
        'date'   => $b['date'],
        'kind'   => '<span class="label label-info">Balance Add</span>',
        'who'    => htmlspecialchars($b['user']),
        'amount' => '+$'.(int)$b['amountusd'],
        'amt_color' => '#27ae60',
        'status' => $b['state']==='approved' ? '<span class="label label-success">Approved</span>'
                  : ($b['state']==='rejected' ? '<span class="label label-danger">Rejected</span>'
                  : '<span class="label label-warning">Awaiting</span>'),
        'ref'    => 'pay#'.(int)$b['id'].' ('.htmlspecialchars($b['method'] ?? '-').')',
    ];
}
// Purchases
$qPur = mysqli_query($dbcon, "SELECT id, buyer, resseller, type, price, date, reported FROM purchases ORDER BY id DESC LIMIT 300");
while ($p = mysqli_fetch_assoc($qPur)) {
    $isRep = (!empty($p['reported']) && $p['reported'] !== '0');
    $rows[] = [
        'date'   => $p['date'],
        'kind'   => '<span class="label label-primary">Purchase</span>',
        'who'    => htmlspecialchars($p['buyer']).' &rarr; '.htmlspecialchars($p['resseller']),
        'amount' => '-$'.htmlspecialchars($p['price']),
        'amt_color' => '#c0392b',
        'status' => $isRep ? '<span class="label label-danger">Reported</span>' : '<span class="label label-success">Completed</span>',
        'ref'    => 'order#'.(int)$p['id'].' ('.strtoupper(htmlspecialchars($p['type'])).')',
    ];
}
// Seller payouts (when withdrawal request gets paid -> withdrawal becomes 'done' and soldb resets via rpay.php)
// We surface seller_payments releases here as profit-credit events
$qSP = mysqli_query($dbcon, "SELECT id, purchase_id, seller, amount, status, purchase_date, approved_at, approved_by FROM seller_payments ORDER BY id DESC LIMIT 300");
while ($sp = mysqli_fetch_assoc($qSP)) {
    $statusLabel = [
        'pending'  => '<span class="label label-warning">Pending 10h</span>',
        'released' => '<span class="label label-success">Released (auto)</span>',
        'approved' => '<span class="label label-success">Released (admin)</span>',
    ][$sp['status']] ?? '<span class="label label-default">'.htmlspecialchars($sp['status']).'</span>';
    $when = !empty($sp['approved_at']) ? $sp['approved_at'] : $sp['purchase_date'];
    $rows[] = [
        'date'   => $when,
        'kind'   => '<span class="label" style="background:#16a085;color:#fff">Seller Profit</span>',
        'who'    => htmlspecialchars($sp['seller']),
        'amount' => '+$'.number_format($sp['amount'],2),
        'amt_color' => '#16a085',
        'status' => $statusLabel,
        'ref'    => 'pay#'.(int)$sp['id'].' / order#'.(int)$sp['purchase_id'].(empty($sp['approved_by'])?'':' by '.htmlspecialchars($sp['approved_by'])),
    ];
}

// Sort by date desc
usort($rows, function($a,$b){ return strcmp($b['date'],$a['date']); });
$rows = array_slice($rows, 0, 500);

foreach ($rows as $r) {
    echo '<tr>
        <td>'.htmlspecialchars($r['date']).'</td>
        <td>'.$r['kind'].'</td>
        <td>'.$r['who'].'</td>
        <td><b style="color:'.$r['amt_color'].'">'.$r['amount'].'</b></td>
        <td>'.$r['status'].'</td>
        <td><small>'.$r['ref'].'</small></td>
    </tr>';
}
?>
        </tbody>
    </table>
</div>

</div><!-- /tab-content -->
</div><!-- /box-body -->

<script type="text/javascript">
function approvePay(pid){
    if(!confirm('Release this payment to the seller wallet now?')) return;
    $.ajax({
        type:'GET', url:'approvepayment.php?id='+pid, dataType:'json',
        success:function(d){
            if(d.status==='ok'){
                $('#approveResult').html('<div class="alert alert-success">'+d.msg+'</div>');
                setTimeout(function(){ location.reload(); }, 1200);
            } else {
                $('#approveResult').html('<div class="alert alert-danger">'+d.msg+'</div>');
            }
        },
        error:function(){ $('#approveResult').html('<div class="alert alert-danger">Request failed.</div>'); }
    });
}

function payAction(rid, act, amt, user){
    var verb = act==='approve' ? 'APPROVE & credit $'+amt+' to '+user+'?' : 'REJECT this request?';
    if(!confirm(verb)) return;
    $.ajax({
        type:'GET', url:'paymentapproval.php', data:{action:act, id:rid}, dataType:'json',
        success:function(d){
            if(d.status==='ok'){
                $('#row-'+rid).fadeOut(300, function(){ $(this).remove(); });
            } else {
                alert(d.msg || 'Failed');
            }
        },
        error:function(){ alert('Request failed.'); }
    });
}

function showWithdrawMsg(html, kind){
    var cls = kind === 'ok' ? 'alert-success' : 'alert-danger';
    $('#withdrawActionResult').html('<div class="alert '+cls+'">'+html+'</div>');
    setTimeout(function(){ $('#withdrawActionResult .alert').fadeOut(400, function(){ $(this).remove(); }); }, 5000);
}

function payWithdraw(seller, receive){
    bootbox.confirm({
        title: 'Approve withdrawal',
        message: 'Mark withdrawal for <b>'+seller+'</b> as paid ($'+receive+')? This will reset their released balance and record a successful payout.',
        buttons: {
            cancel:  { label: 'Cancel', className: 'btn-default' },
            confirm: { label: 'Pay & Approve', className: 'btn-success' }
        },
        callback: function(ok){
            if(!ok) return;
            $.ajax({
                type:'POST', url:'withdrawAction.php', dataType:'json',
                data:{ action:'pay', seller:seller },
                success:function(d){
                    if(d.status==='ok'){
                        showWithdrawMsg(d.msg,'ok');
                        $('#wrow-act-'+seller).closest('tr').fadeOut(400, function(){ $(this).remove(); });
                    } else { showWithdrawMsg(d.msg,'err'); }
                },
                error:function(){ showWithdrawMsg('Request failed.','err'); }
            });
        }
    });
}

function rejectWithdraw(seller){
    bootbox.prompt({
        title: 'Reject withdrawal for <b>'+seller+'</b>',
        inputType: 'textarea',
        placeholder: 'Explain why this withdrawal is being rejected (visible to the seller).',
        buttons: {
            cancel:  { label: 'Cancel', className: 'btn-default' },
            confirm: { label: 'Reject Withdrawal', className: 'btn-danger' }
        },
        callback: function(note){
            if(note === null) return;
            note = (note || '').trim();
            if(!note){ showWithdrawMsg('A rejection note is required.','err'); return; }
            $.ajax({
                type:'POST', url:'withdrawAction.php', dataType:'json',
                data:{ action:'reject', seller:seller, note:note },
                success:function(d){
                    if(d.status==='ok'){
                        showWithdrawMsg(d.msg,'ok');
                        $('#wrow-act-'+seller).closest('tr').fadeOut(400, function(){ $(this).remove(); });
                    } else { showWithdrawMsg(d.msg,'err'); }
                },
                error:function(){ showWithdrawMsg('Request failed.','err'); }
            });
        }
    });
}

// Search filter across every table inside the payments tabs
function filterPaymentsTables(){
    var q = ($('#paymentsSearch').val() || '').toLowerCase().trim();
    $('.tab-content table tbody tr').each(function(){
        var $tr = $(this);
        if(!q){ $tr.show(); return; }
        var txt = $tr.text().toLowerCase();
        $tr.toggle(txt.indexOf(q) !== -1);
    });
}
$(document).on('input', '#paymentsSearch', filterPaymentsTables);

// Live countdown for seller hold tab
(function(){
    function fmt(s){
        if(s<=0) return 'Overdue';
        var h=Math.floor(s/3600), m=Math.floor((s%3600)/60), sec=s%60;
        return h+'h '+(m<10?'0':'')+m+'m '+(sec<10?'0':'')+sec+'s';
    }
    setInterval(function(){
        var now=Math.floor(Date.now()/1000);
        $('.profit-cd').each(function(){
            var rel=parseInt($(this).attr('data-release'),10);
            $(this).text(fmt(rel-now));
        });
    }, 1000);
})();
</script>
