<?php
include "header.php";

$range = isset($_GET['range']) ? $_GET['range'] : 'month';
$validRanges = ['today','week','month','year','all'];
if (!in_array($range, $validRanges, true)) { $range = 'month'; }

switch ($range) {
    case 'today':
        $where = "DATE(p.date) = CURDATE()";
        $label = 'Today';
        break;
    case 'week':
        $where = "YEARWEEK(p.date, 1) = YEARWEEK(CURDATE(), 1)";
        $label = 'This Week';
        break;
    case 'year':
        $where = "YEAR(p.date) = YEAR(CURDATE())";
        $label = 'This Year';
        break;
    case 'all':
        $where = "1=1";
        $label = 'All Time';
        break;
    case 'month':
    default:
        $where = "YEAR(p.date) = YEAR(CURDATE()) AND MONTH(p.date) = MONTH(CURDATE())";
        $label = 'This Month';
        break;
}

$lbSql = "SELECT r.username, r.btc,
                 COALESCE(SUM(p.price), 0) AS revenue,
                 COUNT(p.id)               AS items
          FROM resseller r
          LEFT JOIN purchases p
                 ON p.resseller = r.username
                AND $where
          GROUP BY r.username, r.btc
          ORDER BY revenue DESC, items DESC, r.username ASC";
$lb = mysqli_query($dbcon, $lbSql);
?>
<div class="alert alert-danger fade in radius-bordered alert-shadowed"><b>Sellers Leaderboard</b></div>

<div style="margin:10px 0;">
  <div class="btn-group" role="group">
    <a href="resseller.php?range=today" class="btn btn-<?php echo $range==='today'?'primary':'default'; ?>">Today</a>
    <a href="resseller.php?range=week"  class="btn btn-<?php echo $range==='week' ?'primary':'default'; ?>">This Week</a>
    <a href="resseller.php?range=month" class="btn btn-<?php echo $range==='month'?'primary':'default'; ?>">This Month</a>
    <a href="resseller.php?range=year"  class="btn btn-<?php echo $range==='year' ?'primary':'default'; ?>">This Year</a>
    <a href="resseller.php?range=all"   class="btn btn-<?php echo $range==='all'  ?'primary':'default'; ?>">All Time</a>
  </div>
  <span style="margin-left:10px;"><b>Showing:</b> <?php echo $label; ?></span>
</div>

<?php
$totalRevenue = 0; $totalItems = 0; $rows = [];
if ($lb) {
    while ($ro = mysqli_fetch_assoc($lb)) {
        $totalRevenue += (float)$ro['revenue'];
        $totalItems   += (int)$ro['items'];
        $rows[] = $ro;
    }
}
?>

<div style="margin-bottom:10px;">
  <span class="label label-success">Total Revenue: $<?php echo number_format($totalRevenue, 2); ?></span>
  <span class="label label-info">Total Items Sold: <?php echo $totalItems; ?></span>
  <span class="label label-default">Sellers: <?php echo count($rows); ?></span>
</div>

<table class="table table-bordered table-striped">
  <thead>
    <tr>
      <th style="width:60px">Rank</th>
      <th>Seller</th>
      <th>Items Sold (<?php echo $label; ?>)</th>
      <th>Revenue (<?php echo $label; ?>)</th>
      <th>BTC Address</th>
      <th style="width:80px"></th>
    </tr>
  </thead>
  <tbody>
<?php
$rank = 0;
foreach ($rows as $ro) {
    $rank++;
    $medal = '';
    if ($rank === 1) { $medal = ' <span class="label label-warning">#1</span>'; }
    elseif ($rank === 2) { $medal = ' <span class="label label-default">#2</span>'; }
    elseif ($rank === 3) { $medal = ' <span class="label label-default">#3</span>'; }
    $btc = empty($ro['btc']) ? 'N/A' : htmlspecialchars($ro['btc']);
    echo '<tr>
        <td>'.$rank.$medal.'</td>
        <td><b>'.htmlspecialchars($ro['username']).'</b></td>
        <td>'.(int)$ro['items'].'</td>
        <td>$ '.number_format((float)$ro['revenue'], 2).'</td>
        <td>'.$btc.'</td>
        <td><a href="ress.php?id='.urlencode($ro['username']).'" class="btn btn-xs btn-primary">Edit</a></td>
    </tr>';
}
if (empty($rows)) {
    echo '<tr><td colspan="6" class="text-center">No sellers found.</td></tr>';
}
?>
  </tbody>
</table>
