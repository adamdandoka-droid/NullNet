<?php
error_reporting(0);
ob_start();
session_start();
date_default_timezone_set('UTC');
include "../includes/config.php";
include "./header.php";

if (!isset($_SESSION['sname']) || !isset($_SESSION['spass'])) {
    header("location: ../");
    exit();
}
$uid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);

$cats = [
    ['key'=>'rdps',      'label'=>'RDP',          'detail_cols'=>['country','city','hosting']],
    ['key'=>'cpanels',   'label'=>'cPanel',       'detail_cols'=>['country','infos']],
    ['key'=>'stufs',     'label'=>'Shell',        'detail_cols'=>['country','infos']],
    ['key'=>'mailers',   'label'=>'PHP Mailer',   'detail_cols'=>['country','infos']],
    ['key'=>'smtps',     'label'=>'SMTP',         'detail_cols'=>['country','infos']],
    ['key'=>'leads',     'label'=>'Leads',        'detail_cols'=>['country','infos']],
    ['key'=>'scampages', 'label'=>'Scampage',     'detail_cols'=>['scamname','country']],
    ['key'=>'tutorials', 'label'=>'Tutorial',     'detail_cols'=>['acctype','infos']],
    ['key'=>'accounts',  'label'=>'Premium/Shop', 'detail_cols'=>['sitename','country']],
    ['key'=>'banks',     'label'=>'Bank',         'detail_cols'=>['bankname','country']],
];

$totalListed = 0; $totalSold = 0; $totalRevenue = 0;
$rows = [];
foreach ($cats as $c) {
    $tbl = $c['key'];
    $q = @mysqli_query($dbcon, "SELECT * FROM `$tbl` WHERE resseller='$uid' ORDER BY id DESC");
    if (!$q) { continue; }
    while ($r = mysqli_fetch_assoc($q)) {
        $detail = [];
        foreach ($c['detail_cols'] as $col) {
            if (isset($r[$col]) && $r[$col] !== '') $detail[] = $r[$col];
        }
        $sold = (int)($r['sold'] ?? 0);
        $totalListed++;
        if ($sold >= 1) { $totalSold++; $totalRevenue += (int)($r['price'] ?? 0); }
        $rows[] = [
            'cat'    => $tbl,
            'label'  => $c['label'],
            'id'     => (int)$r['id'],
            'detail' => implode(' &middot; ', array_map('htmlspecialchars', $detail)),
            'price'  => (int)($r['price'] ?? 0),
            'sold'   => $sold,
            'date'   => $r['date'] ?? '',
            'buyer'  => $r['sto'] ?? '',
        ];
    }
}
?>
<div style="padding:18px 28px">
  <h2 style="margin-top:0"><span class="glyphicon glyphicon-list-alt"></span> My Listings</h2>
  <p class="text-muted">All items you have put on sale. You can delete unsold items at any time.</p>

  <div class="row" style="margin-bottom:15px">
    <div class="col-sm-3">
      <div class="panel panel-default"><div class="panel-body" style="text-align:center">
        <h3 style="margin:0;color:#3498db"><?php echo $totalListed; ?></h3>
        <small class="text-muted">Total Listings</small>
      </div></div>
    </div>
    <div class="col-sm-3">
      <div class="panel panel-default"><div class="panel-body" style="text-align:center">
        <h3 style="margin:0;color:#27ae60"><?php echo $totalSold; ?></h3>
        <small class="text-muted">Sold</small>
      </div></div>
    </div>
    <div class="col-sm-3">
      <div class="panel panel-default"><div class="panel-body" style="text-align:center">
        <h3 style="margin:0;color:#e67e22"><?php echo max(0, $totalListed-$totalSold); ?></h3>
        <small class="text-muted">Available</small>
      </div></div>
    </div>
    <div class="col-sm-3">
      <div class="panel panel-default"><div class="panel-body" style="text-align:center">
        <h3 style="margin:0;color:#9b59b6">$<?php echo $totalRevenue; ?></h3>
        <small class="text-muted">Gross Revenue</small>
      </div></div>
    </div>
  </div>

  <?php if (!$rows): ?>
    <div class="alert alert-info">You haven't listed any items yet. Use the side menu (RDP, Shell, cPanel, ...) to add your first listing.</div>
  <?php else: ?>
  <table id="myListingsTable" class="table table-striped table-bordered table-condensed">
    <thead>
      <tr>
        <th>ID</th>
        <th>Category</th>
        <th>Details</th>
        <th>Price</th>
        <th>Status</th>
        <th>Date Listed</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr id="listing-<?php echo $r['cat']; ?>-<?php echo $r['id']; ?>">
        <td>#<?php echo $r['id']; ?></td>
        <td><span class="label label-primary"><?php echo htmlspecialchars($r['label']); ?></span></td>
        <td><?php echo $r['detail'] ?: '<span class="text-muted">-</span>'; ?></td>
        <td><b style="color:#27ae60">$<?php echo $r['price']; ?></b></td>
        <td>
          <?php if ($r['sold'] >= 1): ?>
            <span class="label label-success">Sold</span>
            <?php if ($r['buyer']): ?> <small class="text-muted">to <?php echo htmlspecialchars($r['buyer']); ?></small><?php endif; ?>
          <?php else: ?>
            <span class="label label-default">Available</span>
          <?php endif; ?>
        </td>
        <td><small><?php echo htmlspecialchars($r['date']); ?></small></td>
        <td>
          <?php if ($r['sold'] >= 1): ?>
            <button class="btn btn-default btn-xs" disabled title="Cannot delete sold items">
              <span class="glyphicon glyphicon-lock"></span> Locked
            </button>
          <?php else: ?>
            <button class="btn btn-danger btn-xs"
                    onclick="deleteListing('<?php echo $r['cat']; ?>',<?php echo $r['id']; ?>,this)">
              <span class="glyphicon glyphicon-trash"></span> Delete
            </button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<script>
function deleteListing(cat, id, btn){
  bootbox.confirm({
    title: 'Delete Listing',
    message: 'Permanently delete listing <b>#'+id+'</b> from <b>'+cat+'</b>? This cannot be undone.',
    buttons: { confirm: { label: 'Delete', className: 'btn-danger' }, cancel: { label: 'Cancel', className: 'btn-default' } },
    callback: function(ok){
      if (!ok) return;
      $(btn).prop('disabled', true).html('<span class="glyphicon glyphicon-hourglass"></span> ...');
      $.post('deleteListing.html', { cat: cat, id: id }, function(d){
        if (d && d.status === 'ok') {
          $('#listing-'+cat+'-'+id).fadeOut(300, function(){ $(this).remove(); });
        } else {
          bootbox.alert((d && d.message) ? d.message : 'Failed to delete.');
          $(btn).prop('disabled', false).html('<span class="glyphicon glyphicon-trash"></span> Delete');
        }
      }, 'json').fail(function(){
        bootbox.alert('Network error.');
        $(btn).prop('disabled', false).html('<span class="glyphicon glyphicon-trash"></span> Delete');
      });
    }
  });
}
</script>
<?php include "./footer.php"; ?>
