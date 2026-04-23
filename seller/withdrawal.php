<?php
include "./header.php";

$uid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
$q = mysqli_query($dbcon, "SELECT * FROM users WHERE username='$uid'")or die();
$r = mysqli_fetch_assoc($q);

if($r['resseller'] != "1"){
  header("location: ../");
  exit ();
}

// Handle withdraw request submission
if (isset($_POST['start']) && $_POST['start'] === 'work') {
    // Server-side guard: refuse if released balance can't pay out at least $10 receive
    $chkF = mysqli_fetch_assoc(mysqli_query($dbcon, "SELECT soldb, withdrawal FROM resseller WHERE username='$uid'"));
    $chkSoldb   = (float)($chkF['soldb'] ?? 0);
    $chkReceive = round($chkSoldb * 65 / 100, 2);
    if ($chkSoldb <= 0 || $chkReceive < 10 || ($chkF['withdrawal'] ?? '') === 'requested') {
        echo "<meta http-equiv='refresh' content='0; url=withdrawal.php'>";
        exit();
    }
    $method = isset($_POST['method']) && in_array($_POST['method'], ['btc','eth']) ? $_POST['method'] : 'btc';
    mysqli_query($dbcon, "UPDATE resseller SET withdrawal='requested', withdraw_method='$method' WHERE username='$uid'");
    echo "<meta http-equiv='refresh' content='0; url=withdrawal.php'>";
    exit();
}

// Self-heal: never allow negative released balance to persist
mysqli_query($dbcon, "UPDATE resseller SET soldb=0 WHERE username='$uid' AND soldb<0");
mysqli_query($dbcon, "UPDATE resseller SET isold=0 WHERE username='$uid' AND isold<0");

$f = mysqli_fetch_assoc(mysqli_query($dbcon, "SELECT * FROM resseller WHERE username='$uid'"));
$SellerNick = $f['id'];

// Auto-release any expired non-reported pending payments for this seller
$nowTs = date('Y-m-d H:i:s');
$dueQ = mysqli_query($dbcon, "SELECT sp.id, sp.amount, sp.purchase_id FROM seller_payments sp WHERE sp.seller='$uid' AND sp.status='pending' AND sp.release_date <= '$nowTs'");
while ($d = mysqli_fetch_assoc($dueQ)) {
    $pid = (int)$d['purchase_id'];
    $rep = mysqli_query($dbcon, "SELECT id FROM purchases WHERE id='$pid' AND reported<>'' LIMIT 1");
    if ($rep && mysqli_num_rows($rep) > 0) continue;
    $amt = (float)$d['amount'];
    mysqli_query($dbcon, "UPDATE seller_payments SET status='released', released_at='$nowTs' WHERE id='".(int)$d['id']."'");
    mysqli_query($dbcon, "UPDATE resseller SET soldb = soldb + $amt WHERE username='$uid'");
}

// Refresh after sweep
$f = mysqli_fetch_assoc(mysqli_query($dbcon, "SELECT * FROM resseller WHERE username='$uid'"));

$soldb = (float)$f['soldb'];                    // released, withdrawable
$pendingHold = 0; $pendingCount = 0;
$pq = mysqli_query($dbcon, "SELECT amount FROM seller_payments WHERE seller='$uid' AND status='pending'");
while ($pr = mysqli_fetch_assoc($pq)) { $pendingHold += (float)$pr['amount']; $pendingCount++; }

$totalSales = 0;
$tq = mysqli_query($dbcon, "SELECT SUM(amount) AS s FROM seller_payments WHERE seller='$uid' AND status IN ('pending','released','approved')");
if ($tq) { $tr = mysqli_fetch_assoc($tq); $totalSales = (float)($tr['s'] ?? 0); }

$share = 65;
$receive = round($soldb * $share / 100, 2);
$canWithdraw = ($receive >= 10) && ($f['withdrawal'] !== 'requested');
$rejectedNote = ($f['withdrawal'] === 'rejected') ? trim($f['withdraw_note'] ?? '') : '';
$btcAddr = trim($f['btc'] ?? '');
$ethAddr = trim($f['eth'] ?? '');
$method  = $f['withdraw_method'] ?: 'btc';
?>
<script>
$(document).ready(function() {
    $("#updatebtc").click(function () {
        var btc = $("#addressbtcnew").val();
        $.ajax({ method:"GET", url:"./ajax/updatebtc.php?id="+encodeURIComponent(btc), dataType:"text",
            success:function(data){ $("#showresults").html(data).show(); setTimeout(function(){location.reload(true);}, 600); }
        });
    });
    $("#updateeth").click(function () {
        var eth = $("#addressethnew").val();
        $.ajax({ method:"GET", url:"./ajax/updateeth.php?id="+encodeURIComponent(eth), dataType:"text",
            success:function(data){ $("#showresults").html(data).show(); setTimeout(function(){location.reload(true);}, 600); }
        });
    });
    $('input[name=method]').on('change', function(){
        $('#chosenAddrBtc').toggle(this.value === 'btc');
        $('#chosenAddrEth').toggle(this.value === 'eth');
    });
});
</script>

<ul class="nav nav-tabs">
    <h2>Withdraw <small> for seller <?php echo htmlspecialchars($SellerNick); ?></small></h2>
    <li class="active"><a href="#allmysales" data-toggle="tab">Sales</a></li>
    <li><a href="#edit" data-toggle="tab">Edit Address</a></li>
    <li><a href="#phistory" data-toggle="tab">Payment History</a></li>
</ul>

<div id="myTabContent" class="tab-content">
    <div class="tab-pane fade active in" id="allmysales">
        <div class="row">
        <form role="form" method="post">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading"><h3 class="text-center"><strong>Seller <?php echo htmlspecialchars($SellerNick); ?> Invoice</strong></h3></div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-condensed">
                                <thead>
                                    <tr><td></td><td class="text-center"><strong>N</strong></td><td></td><td class="text-center"><strong>Price</strong></td></tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td></td><td></td>
                                        <td class="text-center"><strong>Total Sales</strong></td>
                                        <td class="text-center">$<?php echo number_format($totalSales, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td><b>Pending Hold <abbr title="Each sale is held for 10 hours so the buyer can report a bad item before the funds are released into your withdrawable balance.">[?]</abbr></b></td>
                                        <td class="text-center"><?php echo $pendingCount; ?></td>
                                        <td class="text-center"></td>
                                        <td class="text-center text-warning">$<?php echo number_format($pendingHold, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td></td><td></td>
                                        <td class="text-center"><strong>Released (Withdrawable)</strong></td>
                                        <td class="text-center"><b class="text-success">$<?php echo number_format($soldb, 2); ?></b></td>
                                    </tr>
                                    <tr>
                                        <td></td><td></td>
                                        <td class="text-center"><strong>Share</strong></td>
                                        <td class="text-center"><b><?php echo $share; ?>%</b></td>
                                    </tr>
                                    <tr>
                                        <td></td><td></td>
                                        <td class="text-center"><strong>You Receive</strong></td>
                                        <td class="text-center"><b>$<?php echo number_format($receive, 2); ?></b></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <?php
                        // Fetch detailed pending hold items
                        $holdQ = mysqli_query($dbcon, "
                            SELECT sp.id, sp.purchase_id, sp.amount, sp.release_date, sp.item_type,
                                   p.reported, p.url
                            FROM seller_payments sp
                            LEFT JOIN purchases p ON p.id = sp.purchase_id
                            WHERE sp.seller='$uid' AND sp.status='pending'
                            ORDER BY sp.release_date ASC
                        ");
                        $holdRows = [];
                        while ($hr = mysqli_fetch_assoc($holdQ)) { $holdRows[] = $hr; }
                        if (!empty($holdRows)):
                        ?>
                        <hr>
                        <h5><b>Pending Hold Details</b></h5>
                        <table class="table table-condensed table-bordered" style="font-size:13px">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Type</th>
                                    <th>Item</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Releases In</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($holdRows as $hr):
                                $isReported = !empty($hr['reported']) && $hr['reported'] !== '0';
                                $relTs = strtotime($hr['release_date']);
                                $nowTs2 = time();
                                $itemPreview = htmlspecialchars(mb_strimwidth($hr['url'] ?? '', 0, 40, '...'));
                                if ($isReported) {
                                    $statusBadge = '<span class="label label-warning">On Hold (Reported)</span>';
                                    $timeCell    = '<span class="label label-default">Frozen</span>';
                                } elseif ($relTs <= $nowTs2) {
                                    $statusBadge = '<span class="label label-info">Pending</span>';
                                    $timeCell    = '<span class="label label-success">Releasing soon&hellip;</span>';
                                } else {
                                    $diff = $relTs - $nowTs2;
                                    $h2   = floor($diff/3600);
                                    $m2   = floor(($diff%3600)/60);
                                    $statusBadge = '<span class="label label-warning">Pending</span>';
                                    $timeCell    = '<span class="profit-countdown" data-release="'.$relTs.'">'.$h2.'h '.str_pad($m2,2,'0',STR_PAD_LEFT).'m</span>';
                                }
                            ?>
                                <tr>
                                    <td>#<?php echo (int)$hr['purchase_id']; ?></td>
                                    <td><?php echo htmlspecialchars($hr['item_type']); ?></td>
                                    <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo $itemPreview; ?></td>
                                    <td><b>$<?php echo number_format((float)$hr['amount'],2); ?></b></td>
                                    <td><?php echo $statusBadge; ?></td>
                                    <td><?php echo $timeCell; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <script>
                        (function(){
                            function fmt(s){ var h=Math.floor(s/3600),m=Math.floor((s%3600)/60),sc=s%60; return h+'h '+(m<10?'0':'')+m+'m '+(sc<10?'0':'')+sc+'s'; }
                            function tick(){
                                var now=Math.floor(Date.now()/1000);
                                $('.profit-countdown').each(function(){
                                    var rel=parseInt($(this).attr('data-release'),10), diff=rel-now;
                                    if(diff<=0){ $(this).removeClass('label label-warning').addClass('label label-success').text('Releasing soon\u2026'); }
                                    else { if(!$(this).hasClass('label')){ $(this).addClass('label label-warning'); } $(this).text(fmt(diff)); }
                                });
                            }
                            tick(); setInterval(tick,1000);
                        })();
                        </script>
                        <?php endif; ?>

                        <div id="showresults"></div>

                        <?php if ($receive < 10): ?>
                            <div class="well well-sm">Your receive amount must be at least <b>$10</b> to request a withdraw. Released balance: $<?php echo number_format($soldb, 2); ?>.</div>
                        <?php elseif ($f['withdrawal'] === 'requested'): ?>
                            <div class="well well-sm">Your <b>withdraw</b> request has been submitted. The admin will process it soon.<br>
                            Method: <b><?php echo strtoupper($f['withdraw_method'] ?: 'BTC'); ?></b> &nbsp; Address:
                            <code><?php echo htmlspecialchars(($f['withdraw_method'] === 'eth') ? $ethAddr : $btcAddr); ?></code></div>
                        <?php elseif ($f['withdrawal'] === 'rejected'): ?>
                            <div class="alert alert-danger">
                                <b>Your last withdrawal request was rejected by the admin.</b>
                                <?php if ($rejectedNote !== ''): ?>
                                    <br>Reason: <i><?php echo nl2br(htmlspecialchars($rejectedNote)); ?></i>
                                <?php endif; ?>
                                <br><br>You can submit a new request below once the issue is resolved.
                            </div>
                        <?php endif; ?>
                        <?php if ($f['withdrawal'] !== 'requested'): ?>
                            <?php if (($method === 'btc' && $btcAddr === '') || ($method === 'eth' && $ethAddr === '')): ?>
                                <div class="alert alert-warning">Please set your <b><?php echo strtoupper($method); ?></b> address in the <b>Edit Address</b> tab before requesting a withdraw.</div>
                            <?php endif; ?>
                            <div class="panel panel-default">
                                <div class="panel-body">
                                    <label><b>Pay me with:</b></label>
                                    <label class="radio-inline"><input type="radio" name="method" value="btc" <?php echo $method==='btc'?'checked':''; ?>> Bitcoin (BTC)</label>
                                    <label class="radio-inline"><input type="radio" name="method" value="eth" <?php echo $method==='eth'?'checked':''; ?>> Ethereum (ETH)</label>
                                    <div id="chosenAddrBtc" style="margin-top:8px; <?php echo $method==='btc'?'':'display:none;'; ?>">
                                        BTC Address: <code><?php echo htmlspecialchars($btcAddr ?: '(not set)'); ?></code>
                                    </div>
                                    <div id="chosenAddrEth" style="margin-top:8px; <?php echo $method==='eth'?'':'display:none;'; ?>">
                                        ETH Address: <code><?php echo htmlspecialchars($ethAddr ?: '(not set)'); ?></code>
                                    </div>
                                    <input type="hidden" name="start" value="work" />
                                    <button type="submit" name="withdraw" class="btn btn-primary" style="margin-top:10px;"
                                        <?php echo (($method==='btc'&&!$btcAddr)||($method==='eth'&&!$ethAddr))?'disabled':''; ?>>
                                        Request Withdraw
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
        </div>
    </div>

    <div class="tab-pane fade" id="edit">
        <h4>Edit Payout Addresses</h4>
        <div class="row">
            <div class="form-group col-lg-6">
                <label for="addressbtcnew">Bitcoin (BTC) Address</label>
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="bc1q... / 1... / 3..." id="addressbtcnew" value="<?php echo htmlspecialchars($btcAddr); ?>" />
                    <span class="input-group-btn"><button id="updatebtc" type="button" class="btn btn-primary">Save BTC</button></span>
                </div>
            </div>
            <div class="form-group col-lg-6">
                <label for="addressethnew">Ethereum (ETH) Address</label>
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="0x..." id="addressethnew" value="<?php echo htmlspecialchars($ethAddr); ?>" />
                    <span class="input-group-btn"><button id="updateeth" type="button" class="btn btn-primary">Save ETH</button></span>
                </div>
            </div>
        </div>
        <div id="showresults2"></div>
        <p class="help-block">You can save both addresses, then choose which one to be paid with on the <b>Sales</b> tab when requesting a withdraw.</p>
    </div>

    <div class="tab-pane fade" id="phistory">
        <h3>Payment History</h3>
        <?php $sql = "SELECT * FROM rpayment WHERE username='$uid' ORDER BY id DESC"; $query = mysqli_query($dbcon, $sql); ?>
        <table width="100%" class="table table-striped table-bordered table-condensed">
            <thead><tr><th></th><th>Date</th><th>Sold</th><th>Percentage</th><th>BTC Rate</th><th>BTC</th><th>USD</th><th>Fee</th><th>Address</th></tr></thead>
            <tbody>
            <?php while ($row = mysqli_fetch_array($query)) {
                $sold = $row['amount']/65*100;
                $rating= $row['rate']; $rater = (empty($rating) || $rating === '0')?"N/A":$rating;
                $feee = empty($row['fee'])?"N/A":($rater * $row['fee']);
                echo '<tr><td></td><td>'.$row['date'].'</td><td>'.substr($sold,0,4).'$</td><td>65%</td>
                    <td>'.$rater.'</td><td>'.$row['abtc'].' <span class="glyphicon glyphicon-bitcoin"></span></td>
                    <td>'.$row['amount'].'$</td><td>'.substr($feee,0,4).'</td>
                    <td><a target="_blank" href="https://www.blockchain.com/btc/address/'.$row['adbtc'].'">'.$row['adbtc'].'</a></td></tr>';
            } ?>
            </tbody>
        </table>
    </div>
</div>
