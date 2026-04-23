<?php
include "header.php";

$now = date("Y-m-d H:i:s");

// Auto-release: release pending payments where 10 hours have passed and buyer hasn't reported
$autoQ = mysqli_query($dbcon, "SELECT sp.id, sp.seller, sp.amount FROM seller_payments sp LEFT JOIN purchases p ON p.id = sp.purchase_id WHERE sp.status='pending' AND sp.release_date <= '$now' AND (p.reported IS NULL OR p.reported = '')");
while ($arow = mysqli_fetch_assoc($autoQ)) {
    $apid    = (int)$arow['id'];
    $aseller = mysqli_real_escape_string($dbcon, $arow['seller']);
    $aamount = (float)$arow['amount'];
    mysqli_query($dbcon, "UPDATE seller_payments SET status='released', approved_at='$now' WHERE id='$apid'");
    mysqli_query($dbcon, "UPDATE resseller SET soldb=(soldb + $aamount) WHERE username='$aseller'");
}

$sql   = "SELECT * FROM purchases ORDER BY id DESC LIMIT 300";
$query = mysqli_query($dbcon, $sql);
if (!$query) { die('SQL Error: ' . mysqli_error($dbcon)); }

$allsales = 0;
$qer = mysqli_query($dbcon, "SELECT SUM(price) as total FROM purchases");
$qerr = mysqli_fetch_assoc($qer);
$allsales = $qerr['total'] ?? 0;
$totalcount = mysqli_num_rows(mysqli_query($dbcon, "SELECT id FROM purchases"));

$pendQ = mysqli_query($dbcon, "
    SELECT sp.*, p.url as item_url, p.infos as item_info, p.reported as p_reported,
           rs.id as seller_uid
    FROM seller_payments sp
    LEFT JOIN purchases p ON p.id = sp.purchase_id
    LEFT JOIN resseller rs ON rs.username = sp.seller
    WHERE sp.status='pending'
    ORDER BY sp.id DESC
");
$pendCount = mysqli_num_rows($pendQ);
?>
<div class="box-body">
<div class="alert alert-danger fade in radius-bordered alert-shadowed"><b>Orders &amp; Payments Management</b></div>

<ul class="nav nav-tabs">
    <li class="active"><a href="#stats" data-toggle="tab"><span class="glyphicon glyphicon-stats"></span> Stats</a></li>
    <li><a href="#myorders" data-toggle="tab"><span class="glyphicon glyphicon-list"></span> All Orders</a></li>
    <li><a href="#pendingpay" data-toggle="tab"><span class="glyphicon glyphicon-time"></span> Pending Payments <span class="badge" style="background:#e74c3c"><?php echo $pendCount; ?></span></a></li>
    <li><a href="#allpayments" data-toggle="tab"><span class="glyphicon glyphicon-usd"></span> All Payments</a></li>
</ul>

<div id="myTabContent" class="tab-content">

    <!-- STATS TAB -->
    <div class="tab-pane fade active in" id="stats">
        <div class="well well-sm" style="margin-top:15px">
            <h4>Sales Statistics</h4>
            <ul>
                <li>Total Orders: <b><?php echo (int)$totalcount; ?></b> ($<?php echo number_format($allsales,2); ?>)</li>
                <li>Pending Seller Payouts: <b><?php echo $pendCount; ?></b></li>
            </ul>
        </div>
    </div>

    <!-- ALL ORDERS TAB -->
    <div class="tab-pane fade in" id="myorders">
        <br>
        <table width="100%" class="table table-striped table-bordered table-condensed sort" id="ordersTable">
            <thead>
            <tr>
                <th>ID</th>
                <th>Buyer</th>
                <th>Seller</th>
                <th>Type</th>
                <th>Open</th>
                <th>Price</th>
                <th>Report ID</th>
                <th>Report State</th>
                <th>Date</th>
            </tr>
            </thead>
            <tbody>
        <?php while ($row = mysqli_fetch_assoc($query)):
            $qerSel = mysqli_query($dbcon, "SELECT id FROM resseller WHERE username='".mysqli_real_escape_string($dbcon,$row['resseller'])."'");
            $rpwSel = mysqli_fetch_assoc($qerSel);
            $SellerNick = $rpwSel ? "seller".$rpwSel['id'] : htmlspecialchars($row['resseller']);
            $reportnumber = empty($row['reportid']) ? 'n/a' : "<a href='viewr.php?id=".htmlspecialchars($row['reportid'])."'>".htmlspecialchars($row['reportid'])."</a>";
            $qurez = mysqli_query($dbcon, "SELECT state FROM reports WHERE orderid='".htmlspecialchars($row['id'])."'");
            $repState = '';
            while ($rowez = mysqli_fetch_assoc($qurez)) { $repState = htmlspecialchars($rowez['state']); }
        ?>
            <tr>
                <td><?php echo htmlspecialchars($row['id']); ?></td>
                <td><?php echo htmlspecialchars($row['buyer']); ?></td>
                <td><?php echo $SellerNick; ?></td>
                <td><?php echo strtoupper(htmlspecialchars($row['type'])); ?></td>
                <td><button onclick="openitem(<?php echo (int)$row['id']; ?>)" class="btn btn-primary btn-xs">OPEN</button></td>
                <td><?php echo htmlspecialchars($row['price']); ?>$</td>
                <td><?php echo $reportnumber; ?></td>
                <td><?php echo $repState ?: 'n/a'; ?></td>
                <td><?php echo htmlspecialchars($row['date']); ?></td>
            </tr>
        <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- PENDING PAYMENTS TAB -->
    <div class="tab-pane fade in" id="pendingpay">
        <br>
        <div class="alert alert-info">
            <strong>Info:</strong> Pending payments are held for <b>10 hours</b> from purchase time. They auto-release after 10 hours if no report is filed. You can approve (release early) any payment below. Payments held due to a buyer report will NOT auto-release.
        </div>
        <div id="approveResult"></div>
        <table width="100%" class="table table-striped table-bordered table-condensed" id="pendTable">
            <thead>
            <tr>
                <th>Pay #</th>
                <th>Order #</th>
                <th>Buyer</th>
                <th>Seller</th>
                <th>Item Type</th>
                <th>Item</th>
                <th>Amount</th>
                <th>Purchase Date</th>
                <th>Release Date</th>
                <th>Reported?</th>
                <th>Time Left</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
        <?php
        mysqli_data_seek($pendQ, 0);
        while ($pr = mysqli_fetch_assoc($pendQ)):
            $sellerLabel = $pr['seller_uid'] ? 'seller'.$pr['seller_uid'] : htmlspecialchars($pr['seller']);
            $reported = (!empty($pr['p_reported']) && $pr['p_reported'] !== '0') ? '<span class="label label-danger">YES</span>' : '<span class="label label-success">No</span>';
            $releaseTs = strtotime($pr['release_date']);
            $nowTs     = time();
            $diffSecs  = $releaseTs - $nowTs;
            if ($diffSecs > 0) {
                $hrs  = floor($diffSecs / 3600);
                $mins = floor(($diffSecs % 3600) / 60);
                $timeLeft = "{$hrs}h {$mins}m";
                $timeClass = 'warning';
            } else {
                $timeLeft = 'Overdue (pending report hold)';
                $timeClass = 'danger';
            }
            $itemDisplay = htmlspecialchars($pr['item_url'] ?: $pr['item_info'] ?: '-');
        ?>
            <tr>
                <td><?php echo (int)$pr['id']; ?></td>
                <td><a href="#" onclick="openitem(<?php echo (int)$pr['purchase_id']; ?>);return false;">#<?php echo (int)$pr['purchase_id']; ?></a></td>
                <td><?php echo htmlspecialchars($pr['buyer']); ?></td>
                <td><?php echo $sellerLabel; ?></td>
                <td><?php echo strtoupper(htmlspecialchars($pr['item_type'])); ?></td>
                <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo $itemDisplay; ?></td>
                <td><b><?php echo number_format($pr['amount'],2); ?>$</b></td>
                <td><?php echo htmlspecialchars($pr['purchase_date']); ?></td>
                <td><?php echo htmlspecialchars($pr['release_date']); ?></td>
                <td><?php echo $reported; ?></td>
                <td><span class="label label-<?php echo $timeClass; ?>"><?php echo $timeLeft; ?></span></td>
                <td>
                    <?php if (empty($pr['p_reported']) || $pr['p_reported'] === '0'): ?>
                    <button onclick="approvePay(<?php echo (int)$pr['id']; ?>)" class="btn btn-success btn-xs"><span class="glyphicon glyphicon-ok"></span> Approve</button>
                    <?php else: ?>
                    <span class="label label-warning">On Hold (Reported)</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- ALL PAYMENTS TAB -->
    <div class="tab-pane fade in" id="allpayments">
        <br>
        <?php
        $apQ = mysqli_query($dbcon, "SELECT sp.*, rs.id as seller_uid FROM seller_payments sp LEFT JOIN resseller rs ON rs.username=sp.seller ORDER BY sp.id DESC LIMIT 300");
        ?>
        <table width="100%" class="table table-striped table-bordered table-condensed sort" id="allPayTable">
            <thead>
            <tr>
                <th>Pay #</th>
                <th>Order #</th>
                <th>Buyer</th>
                <th>Seller</th>
                <th>Item Type</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Purchase Date</th>
                <th>Release Date</th>
                <th>Approved By</th>
                <th>Approved At</th>
            </tr>
            </thead>
            <tbody>
        <?php while ($ap = mysqli_fetch_assoc($apQ)):
            $apSellerLabel = $ap['seller_uid'] ? 'seller'.$ap['seller_uid'] : htmlspecialchars($ap['seller']);
            $statusBadge = [
                'pending'  => '<span class="label label-warning">Pending</span>',
                'released' => '<span class="label label-success">Released</span>',
                'approved' => '<span class="label label-info">Approved Early</span>',
            ][$ap['status']] ?? '<span class="label label-default">'.htmlspecialchars($ap['status']).'</span>';
        ?>
            <tr>
                <td><?php echo (int)$ap['id']; ?></td>
                <td>#<?php echo (int)$ap['purchase_id']; ?></td>
                <td><?php echo htmlspecialchars($ap['buyer']); ?></td>
                <td><?php echo $apSellerLabel; ?></td>
                <td><?php echo strtoupper(htmlspecialchars($ap['item_type'])); ?></td>
                <td><b><?php echo number_format($ap['amount'],2); ?>$</b></td>
                <td><?php echo $statusBadge; ?></td>
                <td><?php echo htmlspecialchars($ap['purchase_date']); ?></td>
                <td><?php echo htmlspecialchars($ap['release_date']); ?></td>
                <td><?php echo htmlspecialchars($ap['approved_by'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($ap['approved_at'] ?? '-'); ?></td>
            </tr>
        <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</div><!-- tab-content -->

<script type="text/javascript">
function openitem(order){
    $("#myModalHeader").text('Order #'+order);
    $("#modelbody").html('<div style="text-align:center;padding:30px;"><span class="glyphicon glyphicon-refresh"></span> Loading...</div>');
    $('#myModal').modal('show');
    $.ajax({
        type: 'GET',
        url: 'openorder.php?id='+order,
        success: function(data){
            if($.trim(data)===''){
                $("#modelbody").html('<div class="alert alert-warning">No data returned for this order.</div>');
            } else {
                $("#modelbody").html(data).show();
            }
        },
        error: function(xhr){
            $("#modelbody").html('<div class="alert alert-danger">Error loading order details. ('+xhr.status+')</div>');
        }
    });
}

function approvePay(pid){
    if(!confirm('Approve this payment and release funds to seller now?')) return;
    $.ajax({
        type: 'GET',
        url: 'approvepayment.php?id='+pid,
        dataType: 'json',
        success: function(data){
            if(data.status==='ok'){
                $('#approveResult').html('<div class="alert alert-success">'+data.msg+'</div>');
                setTimeout(function(){ location.reload(); }, 1500);
            } else {
                $('#approveResult').html('<div class="alert alert-danger">'+data.msg+'</div>');
            }
        },
        error: function(){
            $('#approveResult').html('<div class="alert alert-danger">Request failed.</div>');
        }
    });
}
</script>

<div class="modal fade" id="myModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="myModalHeader"></h4>
      </div>
      <div class="modal-body" id="modelbody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
