<?php
ob_start();
session_start();
date_default_timezone_set('UTC');
include "../includes/config.php";

if (!isset($_SESSION['sname']) and !isset($_SESSION['spass'])) {
    header("location: ../");
    exit();
}
$usrid     = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
$ADMIN_BTC = '14ZcY3aUy4eKU535TpcHFB9QVnSxBqzMXx';
$ADMIN_ETH = '0x4e39301608688748d5951390bd5abe20f2f566c5';
$balQ      = mysqli_query($dbcon, "SELECT balance FROM users WHERE username='$usrid' LIMIT 1");
$balRow    = $balQ ? mysqli_fetch_assoc($balQ) : null;
$userBal   = $balRow ? number_format((float)$balRow['balance'], 2) : '0.00';
?>
<div class="container-fluid">
<div class="row">

<div class="col-lg-6">
<div class="well">
<h3><span class="glyphicon glyphicon-usd"></span> Add Balance</h3>
<div class="alert alert-info" style="padding:10px 15px;margin-bottom:14px">
  <span class="glyphicon glyphicon-piggy-bank"></span> <strong>Your current balance:</strong> $<?php echo htmlspecialchars($userBal); ?>
</div>
<p class="text-muted">Choose a payment method, send to the address shown, then submit your request. Balance is added after admin approval (usually within 24h).</p>

<!-- Method selector -->
<div class="form-group">
  <label>Payment Method</label>
  <div class="btn-group btn-group-justified" style="margin-bottom:10px" id="methodToggle">
    <div class="btn-group">
      <button type="button" class="btn btn-primary active" id="btnBTC" onclick="switchMethod('btc')">
        <img src="../files/img/btclogo.png" height="20" onerror="this.style.display='none'"> Bitcoin (BTC)
      </button>
    </div>
    <div class="btn-group">
      <button type="button" class="btn btn-default" id="btnETH" onclick="switchMethod('eth')">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 417" width="14" height="14" style="vertical-align:-2px;margin-right:3px"><g fill="none" fill-rule="evenodd"><path fill="#343434" d="M127.961 0l-2.795 9.5v275.668l2.795 2.79 127.962-75.638z"/><path fill="#8C8C8C" d="M127.962 0L0 212.32l127.962 75.639V154.158z"/><path fill="#3C3C3B" d="M127.961 312.187l-1.575 1.92v98.199l1.575 4.6L256 236.587z"/><path fill="#8C8C8C" d="M127.962 416.905v-104.72L0 236.585z"/><path fill="#141414" d="M127.961 287.958l127.96-75.637-127.96-58.162z"/><path fill="#393939" d="M0 212.32l127.96 75.638v-133.8z"/></g></svg> Ethereum (ETH)
      </button>
    </div>
  </div>
</div>

<!-- BTC address panel -->
<div id="panelBTC">
  <div class="panel panel-default">
    <div class="panel-heading"><strong><span class="glyphicon glyphicon-send"></span> Send BTC to this address:</strong></div>
    <div class="panel-body">
      <div class="input-group">
        <input type="text" class="form-control" id="btcAddrField" value="<?php echo htmlspecialchars($ADMIN_BTC); ?>" readonly style="font-family:monospace;font-size:12px">
        <span class="input-group-btn">
          <button class="btn btn-primary" id="copyBTC" onclick="copyAddr('btcAddrField')">
            <span class="glyphicon glyphicon-copy"></span> Copy
          </button>
        </span>
      </div>
      <small class="text-muted">Bitcoin (BTC) only. Do not send any other coin.</small>
    </div>
  </div>
</div>

<!-- ETH address panel -->
<div id="panelETH" style="display:none">
  <div class="panel panel-default" style="border-color:#627EEA">
    <div class="panel-heading" style="background:#627EEA;color:#fff"><strong><span class="glyphicon glyphicon-send"></span> Send ETH to this address:</strong></div>
    <div class="panel-body">
      <div class="input-group">
        <input type="text" class="form-control" id="ethAddrField" value="<?php echo htmlspecialchars($ADMIN_ETH); ?>" readonly style="font-family:monospace;font-size:12px">
        <span class="input-group-btn">
          <button class="btn btn-primary" id="copyETH" onclick="copyAddr('ethAddrField')">
            <span class="glyphicon glyphicon-copy"></span> Copy
          </button>
        </span>
      </div>
      <small class="text-muted">Ethereum (ETH) on the Ethereum mainnet only. ERC-20 tokens not accepted.</small>
    </div>
  </div>
</div>

<form id="formAddBalance">
  <input type="hidden" name="method" id="methodField" value="Bitcoin">
  <div class="form-group">
    <label>Amount (USD)</label>
    <div class="input-group">
      <span class="input-group-addon">$</span>
      <input type="number" name="amount" id="amountUsd" class="form-control" placeholder="Enter amount in USD (min $5)" min="5" step="1" required>
    </div>
    <small class="text-muted">Enter the USD value you are sending. You will pay the equivalent in crypto.</small>
    <div id="liveConvert" style="margin-top:8px; display:none">
      <div class="alert alert-info" style="padding:8px 12px;margin-bottom:0">
        <b>You need to send:</b>
        <span id="convertAmt" style="font-family:monospace;font-size:15px">--</span>
        <span id="convertSym">BTC</span>
        <small class="text-muted" style="margin-left:8px">
          (live rate: $<span id="convertRate">--</span> / <span id="convertSym2">BTC</span>,
          updated <span id="convertWhen">--</span>)
        </small>
      </div>
    </div>
  </div>
  <div class="form-group">
    <label>Transaction Hash / TxID <small class="text-muted">(optional but speeds up approval)</small></label>
    <input type="text" name="tx_hash" class="form-control" placeholder="Paste your transaction ID / hash here...">
  </div>
  <div class="form-group">
    <label>Note <small class="text-muted">(optional)</small></label>
    <input type="text" name="note" class="form-control" placeholder="Any extra info for the admin">
  </div>
  <button type="submit" class="btn btn-success btn-md" id="submitBtn">
    <span class="glyphicon glyphicon-send"></span> Submit Payment Request
  </button>
</form>
<div id="payResult" style="margin-top:12px;"></div>
</div>
</div>

<div class="col-lg-6">
<div class="well">
  <h4><span class="glyphicon glyphicon-info-sign"></span> How it works</h4>
  <ol>
    <li>Select your payment method: <b>Bitcoin</b> or <b>Ethereum</b>.</li>
    <li>Copy the address and send the equivalent amount in crypto.</li>
    <li>Optionally paste your <b>Transaction ID (TxID)</b> to speed up approval.</li>
    <li>Click <b>Submit Payment Request</b>.</li>
    <li>The admin will verify and approve — balance added within 24h.</li>
  </ol>
  <hr>
  <ul>
    <li>Minimum deposit: <b>$5 USD</b></li>
    <li>For delays, open a <a href="tickets.html">Support Ticket</a></li>
  </ul>
</div>

<h4>My Payment History</h4>
<?php
$pq = mysqli_query($dbcon, "SELECT * FROM payment WHERE user='$usrid' ORDER BY id DESC LIMIT 20");
if (!$pq || mysqli_num_rows($pq) === 0) {
    echo '<p class="text-muted">No payment requests yet.</p>';
} else {
    echo '<table class="table table-bordered table-condensed table-striped">
<thead><tr><th>#</th><th>Method</th><th>Amount</th><th>TxID</th><th>Status</th><th>Date</th></tr></thead><tbody>';
    while ($pr = mysqli_fetch_assoc($pq)) {
        $badges = [
            'awaiting' => '<span class="label label-warning">Awaiting</span>',
            'approved' => '<span class="label label-success">Approved</span>',
            'rejected' => '<span class="label label-danger">Rejected</span>',
            'pending'  => '<span class="label label-info">Pending</span>',
        ];
        $badge     = $badges[$pr['state'] ?? ''] ?? '<span class="label label-default">'.htmlspecialchars((string)($pr['state'] ?? '')).'</span>';
        $txh       = (string)($pr['tx_hash'] ?? '');
        $txDisplay = $txh ? '<code style="font-size:11px">'.htmlspecialchars(substr($txh,0,18)).'...</code>' : '<span class="text-muted">-</span>';
        $method    = htmlspecialchars((string)($pr['method'] ?? 'Bitcoin'));
        echo '<tr>'
            .'<td>'.htmlspecialchars((string)($pr['id'] ?? '')).'</td>'
            .'<td>'.$method.'</td>'
            .'<td>$'.htmlspecialchars((string)($pr['amountusd'] ?? '0')).'</td>'
            .'<td>'.$txDisplay.'</td>'
            .'<td>'.$badge.'</td>'
            .'<td>'.htmlspecialchars((string)($pr['date'] ?? '')).'</td>'
            .'</tr>';
    }
    echo '</tbody></table>';
}
?>
</div>

</div>
</div>

<script>
var liveRates = { btc: 0, eth: 0, btcAt: 0, ethAt: 0 };
var currentMethod = 'btc';

function switchMethod(m) {
    currentMethod = m;
    if (m === 'btc') {
        $('#panelBTC').show(); $('#panelETH').hide();
        $('#btnBTC').addClass('active btn-primary').removeClass('btn-default');
        $('#btnETH').addClass('btn-default').removeClass('active btn-primary');
        $('#methodField').val('Bitcoin');
    } else {
        $('#panelETH').show(); $('#panelBTC').hide();
        $('#btnETH').addClass('active btn-primary').removeClass('btn-default');
        $('#btnBTC').addClass('btn-default').removeClass('active btn-primary');
        $('#methodField').val('Ethereum');
    }
    fetchRate(true);
    updateConvert();
}

function fetchRate(force){
    var key = currentMethod;
    var now = Date.now();
    if (!force && liveRates[key] > 0 && (now - liveRates[key+'At']) < 60000) return;
    var ids = currentMethod === 'eth' ? 'ethereum' : 'bitcoin';
    $.ajax({
        url: 'https://api.coingecko.com/api/v3/simple/price?ids='+ids+'&vs_currencies=usd',
        dataType: 'json', timeout: 5000,
        success: function(d){
            var r = parseFloat((d[ids] || {}).usd || 0);
            if (r > 0) {
                liveRates[key] = r;
                liveRates[key+'At'] = Date.now();
                updateConvert();
            }
        }
    });
}

function updateConvert(){
    var usd = parseFloat($('#amountUsd').val() || 0);
    var sym = currentMethod.toUpperCase();
    $('#convertSym, #convertSym2').text(sym);
    var rate = liveRates[currentMethod];
    if (!usd || usd <= 0 || !rate) {
        $('#liveConvert').hide();
        return;
    }
    var amt = (usd / rate).toFixed(currentMethod === 'eth' ? 6 : 8);
    $('#convertAmt').text(amt);
    $('#convertRate').text(rate.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}));
    var d = new Date(liveRates[currentMethod+'At']);
    $('#convertWhen').text(d.toLocaleTimeString());
    $('#liveConvert').show();
}

$(document).on('input', '#amountUsd', updateConvert);
$(function(){
    fetchRate(true);
    setInterval(function(){ fetchRate(true); }, 60000);
});
function copyAddr(fieldId) {
    var el = document.getElementById(fieldId);
    el.select(); el.setSelectionRange(0, 99999);
    document.execCommand('copy');
    bootbox.alert('Address copied to clipboard!');
}
$("#formAddBalance").submit(function(e){
    e.preventDefault();
    var btn = $("#submitBtn");
    btn.prop('disabled', true).html('<span class="glyphicon glyphicon-refresh"></span> Submitting...');
    $("#payResult").html('');
    $.ajax({
        type: "POST",
        url: 'addBalanceAction.html',
        data: $(this).serialize(),
        dataType: 'text',
        success: function(data){
            btn.prop('disabled', false).html('<span class="glyphicon glyphicon-send"></span> Submit Payment Request');
            try {
                var res = (typeof data === 'string') ? JSON.parse(data) : data;
                if(res && res.status === 'ok'){
                    $("#payResult").html('<div class="alert alert-success"><strong>Request submitted!</strong> '+res.msg+'</div>');
                    $("#formAddBalance")[0].reset();
                    setTimeout(function(){ location.reload(); }, 2500);
                } else {
                    $("#payResult").html('<div class="alert alert-danger"><strong>Error:</strong> '+res.msg+'</div>');
                }
            } catch(ex){
                $("#payResult").html('<div class="alert alert-danger">Unexpected response. Please try again.</div>');
            }
        },
        error: function(){
            btn.prop('disabled', false).html('<span class="glyphicon glyphicon-send"></span> Submit Payment Request');
            $("#payResult").html('<div class="alert alert-danger">Request failed. Please try again.</div>');
        }
    });
});
</script>
