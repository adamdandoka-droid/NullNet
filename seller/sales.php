<?php
include "./header.php";
date_default_timezone_set('UTC');

$uid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
$q = mysqli_query($dbcon, "SELECT * FROM users WHERE username='$uid'") or die();
$r = mysqli_fetch_assoc($q);

if ($r['resseller'] != "1") {
    header("location: ../");
    exit();
}
$s = mysqli_query($dbcon, "SELECT * FROM resseller WHERE username='$uid'") or die(mysqli_error($dbcon));
$f = mysqli_fetch_assoc($s);

// Auto-release sweep: any pending seller_payments past release_date with no report
$now = date("Y-m-d H:i:s");
$autoQ = mysqli_query($dbcon, "
    SELECT sp.id, sp.seller, sp.amount
    FROM seller_payments sp
    LEFT JOIN purchases p ON p.id = sp.purchase_id
    WHERE sp.status='pending' AND sp.seller='$uid'
      AND sp.release_date <= '$now'
      AND (p.reported IS NULL OR p.reported = '' OR p.reported = '0')
");
while ($arow = mysqli_fetch_assoc($autoQ)) {
    $apid = (int)$arow['id'];
    $aamt = (float)$arow['amount'];
    mysqli_query($dbcon, "UPDATE seller_payments SET status='released', approved_at='$now' WHERE id='$apid'");
    mysqli_query($dbcon, "UPDATE resseller SET soldb=(soldb + $aamt) WHERE username='$uid'");
}

// Re-fetch resseller after sweep
$s = mysqli_query($dbcon, "SELECT * FROM resseller WHERE username='$uid'");
$f = mysqli_fetch_assoc($s);

// Aggregate stats
$totalSalesQ = mysqli_query($dbcon, "SELECT SUM(price) AS t, COUNT(*) AS c FROM purchases WHERE resseller='$uid'");
$totalSales  = mysqli_fetch_assoc($totalSalesQ);
$allsales    = (float)($totalSales['t'] ?? 0);
$allcount    = (int)($totalSales['c'] ?? 0);

$pendQ = mysqli_query($dbcon, "SELECT SUM(amount) AS t, COUNT(*) AS c FROM seller_payments WHERE seller='$uid' AND status='pending'");
$pendR = mysqli_fetch_assoc($pendQ);
$pendingTotal = (float)($pendR['t'] ?? 0);
$pendingCount = (int)($pendR['c'] ?? 0);

$relQ  = mysqli_query($dbcon, "SELECT SUM(amount) AS t FROM seller_payments WHERE seller='$uid' AND status IN ('released','approved')");
$relR  = mysqli_fetch_assoc($relQ);
$releasedTotal = (float)($relR['t'] ?? 0);

$paidOutQ = mysqli_query($dbcon, "SELECT SUM(amount) AS t FROM rpayment WHERE username='$uid'");
$paidOutR = mysqli_fetch_assoc($paidOutQ);
$totalPaidOut = (float)($paidOutR['t'] ?? 0);

// Build map of payment status by purchase_id
$pmap = [];
$pmapQ = mysqli_query($dbcon, "SELECT purchase_id, status, release_date, approved_at, approved_by FROM seller_payments WHERE seller='$uid'");
while ($pr = mysqli_fetch_assoc($pmapQ)) {
    $pmap[(int)$pr['purchase_id']] = $pr;
}

$sql   = "SELECT * FROM purchases WHERE resseller='$uid' ORDER BY id DESC";
$query = mysqli_query($dbcon, $sql);
?>

<div class="box-body">
<h2 class="box-title">My Orders</h2>

<ul class="nav nav-tabs">
    <li class="active"><a href="#stats" data-toggle="tab">Stats</a></li>
    <li><a href="#myorders" data-toggle="tab">My Orders</a></li>
</ul>

<div id="myTabContent" class="tab-content">
    <div class="tab-pane fade active in" id="stats">
        <div class="well well-sm" style="margin-top:15px">
            <p class="text-info">Each tool you sell is held for <b>10 hours</b> before profit is credited to your withdrawable wallet, so the buyer has time to report a bad item. Admin can release it earlier.</p>
            <h4>Your Sales</h4>
            <ul>
                <li>All Sales: <b><?php echo $allcount; ?></b> ($<?php echo number_format($allsales,2); ?>)</li>
                <li>Pending Profit (held 10h): <b style="color:#e67e22">$<?php echo number_format($pendingTotal,2); ?></b> across <?php echo $pendingCount; ?> order(s)</li>
                <li>Released to Wallet: <b style="color:#27ae60">$<?php echo number_format($releasedTotal,2); ?></b></li>
                <li>Withdrawable Balance (soldb): <b>$<?php echo number_format((float)($f['soldb'] ?? 0),2); ?></b></li>
                <li>Total Paid Out (after 65% cut): <b style="color:#2980b9">$<?php echo number_format($totalPaidOut,2); ?></b></li>
            </ul>
        </div>
    </div>

    <div class="tab-pane fade in" id="myorders">
        <br>
        <table width="100%" class="table table-striped table-bordered table-condensed sort" id="table">
            <thead>
                <tr>
                    <th></th>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Item</th>
                    <th>Open</th>
                    <th>Price</th>
                    <th>Profit Status</th>
                    <th>Time Left</th>
                    <th>Report</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
<?php
while ($row = mysqli_fetch_array($query)) {
    $orderid = (int)$row['id'];
    $reported = $row['reported'] ?? '';
    $repNum = empty($row['reportid']) ? 'n/a' : "<a href='vr-".htmlspecialchars($row['reportid']).".html'>".htmlspecialchars($row['reportid'])."</a>";

    $repStateQ = mysqli_query($dbcon, "SELECT state FROM reports WHERE orderid='$orderid'");
    $repState = '';
    while ($rs = mysqli_fetch_assoc($repStateQ)) { $repState = htmlspecialchars($rs['state']); }

    // Profit status based on seller_payments
    $pp = $pmap[$orderid] ?? null;
    $statusBadge = '<span class="label label-default">n/a</span>';
    $timeCellHtml = '<span class="text-muted">-</span>';

    if ($pp) {
        $st = $pp['status'];
        $relTs = strtotime($pp['release_date']);
        $isReported = (!empty($reported) && $reported !== '0');

        if ($st === 'pending') {
            if ($isReported) {
                $statusBadge = '<span class="label label-warning">On Hold (Reported)</span>';
                $timeCellHtml = '<span class="label label-warning">Frozen</span>';
            } else {
                $statusBadge = '<span class="label label-warning">Pending</span>';
                $timeCellHtml = '<span class="profit-countdown" data-release="'.(int)$relTs.'">--</span>';
            }
        } elseif ($st === 'released') {
            $statusBadge = '<span class="label label-success">Released (Auto)</span>';
            $timeCellHtml = '<span class="text-success">'.htmlspecialchars($pp['approved_at'] ?? '').'</span>';
        } elseif ($st === 'approved') {
            $statusBadge = '<span class="label label-success">Approved Early</span>';
            $timeCellHtml = '<span class="text-success">by '.htmlspecialchars($pp['approved_by'] ?? '-').'</span>';
        } else {
            $statusBadge = '<span class="label label-default">'.htmlspecialchars($st).'</span>';
        }
    }

    echo '<tr>
            <td></td>
            <td>'.$orderid.'</td>
            <td>'.htmlspecialchars(strtolower($row['type'])).'</td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">'.htmlspecialchars($row['url']).'</td>
            <td><button onclick="openitem('.$orderid.')" class="btn btn-primary btn-xs">OPEN</button></td>
            <td>$'.htmlspecialchars($row['price']).'</td>
            <td>'.$statusBadge.'</td>
            <td>'.$timeCellHtml.'</td>
            <td>'.$repNum.($repState ? ' <small>('.$repState.')</small>' : '').'</td>
            <td>'.htmlspecialchars($row['date']).'</td>
        </tr>';
}
?>
            </tbody>
        </table>
    </div>
</div>

<script type="text/javascript">
function openitem(order){
    $("#myModalHeader").text('Order #'+order);
    $('#myModal').modal('show');
    $.ajax({type:'GET', url:'showOrder'+order+'.html', success:function(data){ $("#modelbody").html(data).show(); }});
}

(function(){
    function fmt(s){
        if (s <= 0) return 'Releasing...';
        var h = Math.floor(s/3600);
        var m = Math.floor((s%3600)/60);
        var sec = s%60;
        return h+'h '+(m<10?'0':'')+m+'m '+(sec<10?'0':'')+sec+'s';
    }
    function tick(){
        var now = Math.floor(Date.now()/1000);
        $('.profit-countdown').each(function(){
            var rel = parseInt($(this).attr('data-release'),10);
            var diff = rel - now;
            if (diff <= 0){
                $(this).removeClass('label label-warning').addClass('label label-success').text('Auto-releasing on next view');
            } else {
                if (!$(this).hasClass('label')){ $(this).addClass('label label-warning'); }
                $(this).text(fmt(diff));
            }
        });
    }
    tick();
    setInterval(tick, 1000);
})();
</script>

<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalHeader"></h4>
      </div>
      <div class="modal-body" id="modelbody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
</div>
