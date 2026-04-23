<?php
include "header.php";

$onlyMine = isset($_GET['mine']) && $_GET['mine']==='1';
$me = mysqli_real_escape_string($dbcon, $_SESSION['sname']);

// staff list (for assign dropdowns)
$staffQ = mysqli_query($dbcon, "SELECT username FROM users WHERE role='admin' OR role='support' ORDER BY username");
$staff = [];
while ($staffQ && $sr = mysqli_fetch_assoc($staffQ)) { $staff[] = $sr['username']; }

function status_label($st) {
    switch ((string)$st) {
        case '0': return '<span class="label label-default">closed</span>';
        case '1': return '<span class="label label-warning">open</span>';
        case '2': return '<span class="label label-info">replied</span>';
    }
    return htmlspecialchars($st);
}

function assign_form($type, $id, $current, $staff) {
    $h = '<form method="post" action="assign.php" style="margin:0;display:inline-flex;gap:4px">'
       . '<input type="hidden" name="type" value="'.$type.'">'
       . '<input type="hidden" name="id" value="'.$id.'">'
       . '<select name="assignee" class="form-control input-sm" style="display:inline-block;width:auto">'
       . '<option value="">-- Unassigned --</option>';
    foreach ($staff as $su) {
        $sel = ($su === ($current ?? '')) ? ' selected' : '';
        $h .= '<option value="'.htmlspecialchars($su).'"'.$sel.'>'.htmlspecialchars($su).'</option>';
    }
    $h .= '</select><button type="submit" class="btn btn-xs btn-primary">Assign</button></form>';
    return $h;
}

// Insert ticket POST handler
$insertNotice = '';
if (isset($_POST['start']) && $_POST['start'] === 'work') {
    $subject = mysqli_real_escape_string($dbcon, $_POST['subject'] ?? '');
    $user    = mysqli_real_escape_string($dbcon, $_POST['user'] ?? '');
    $date    = date("Y/m/d h:i:s");
    if ($subject !== '' && $user !== '') {
        $ok = mysqli_query($dbcon, "
INSERT INTO `ticket`
(`uid`, `status`, `s_id`, `s_url`, `memo`, `acctype`, `admin_r`, `date`, `subject`, `type`, `resseller`, `price`, `refounded`, `fmemo`, `seen`, `lastreply`, `lastup`)
VALUES
('$user', 1, 0, '', '', 0, 0, '$date', '$subject', 'request', 0, 0, 'Not Yet !', '', 1, 'Admin', '$date')
        ");
        $insertNotice = $ok
            ? '<div class="alert alert-success" role="alert">Ticket added</div>'
            : '<div class="alert alert-danger" role="alert">Insert error: '.htmlspecialchars(mysqli_error($dbcon)).'</div>';
    } else {
        $insertNotice = '<div class="alert alert-warning" role="alert">User and subject are required</div>';
    }
}

// Pending Tickets
$wT = "(status='1' or status='2')" . ($onlyMine ? " AND assigned_to='$me'" : '');
$qT = mysqli_query($dbcon, "SELECT * FROM ticket WHERE $wT ORDER BY status DESC, id DESC") or die("error");
$tT = mysqli_num_rows($qT);

// Pending Reports
$wR = "status='1'" . ($onlyMine ? " AND assigned_to='$me'" : '');
$qR = mysqli_query($dbcon, "SELECT * FROM reports WHERE $wR ORDER BY id DESC") or die("error");
$tR = mysqli_num_rows($qR);

// All Tickets
$qA = mysqli_query($dbcon, "SELECT * FROM ticket ORDER BY id DESC") or die("error");
$tA = mysqli_num_rows($qA);

// Optional initial tab via #anchor (handled by JS) or ?tab=
$initialTab = $_GET['tab'] ?? 'pending';
?>
<div class="alert alert-danger fade in radius-bordered alert-shadowed"><b>Tickets &amp; Reports</b></div>

<ul class="nav nav-tabs" id="ticketTabs">
  <li class="<?php echo $initialTab==='pending'?'active':''; ?>"><a href="#pending" data-toggle="tab">Pending Tickets <span class="badge"><?php echo $tT; ?></span></a></li>
  <li class="<?php echo $initialTab==='reports'?'active':''; ?>"><a href="#reports" data-toggle="tab">Pending Reports <span class="badge"><?php echo $tR; ?></span></a></li>
  <li class="<?php echo $initialTab==='all'?'active':''; ?>"><a href="#all" data-toggle="tab">All Tickets <span class="badge"><?php echo $tA; ?></span></a></li>
  <li class="<?php echo $initialTab==='insert'?'active':''; ?>"><a href="#insert" data-toggle="tab"><span class="glyphicon glyphicon-plus"></span> Insert Ticket</a></li>
</ul>

<div id="ticketTabContent" class="tab-content" style="padding-top:15px">

  <!-- Pending Tickets -->
  <div class="tab-pane fade <?php echo $initialTab==='pending'?'in active':''; ?>" id="pending">
    <div class="panel panel-default">
      <div class="panel-heading">Tickets <span class="label label-primary">Total Pending: <?php echo $tT; ?></span></div>
      <table class="table table-bordered table-striped">
        <thead><tr><th>ID</th><th>Date</th><th>Title</th><th>State</th><th>Last Reply</th><th>Last Updated</th><th>Assigned To</th><th>View</th></tr></thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($qT)): ?>
          <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['date']); ?></td>
            <td>
              <?php echo htmlspecialchars($row['subject']); ?>
              <br><small>
                <span class="label label-info" style="font-size:10px;">Buyer: <?php echo htmlspecialchars($row['uid']); ?></span>
                <?php if (!empty($row['assigned_to'])): ?>
                <span class="label label-warning" style="font-size:10px;">Staff: <?php echo htmlspecialchars($row['assigned_to']); ?></span>
                <?php endif; ?>
              </small>
            </td>
            <td><?php echo status_label($row['status']); ?></td>
            <td><?php echo htmlspecialchars($row['lastreply']); ?></td>
            <td><?php echo htmlspecialchars($row['lastup'] ?: 'n/a'); ?></td>
            <td><?php echo assign_form('ticket', $row['id'], $row['assigned_to'] ?? '', $staff); ?></td>
            <td><a class="btn btn-xs btn-primary" href="viewt.php?id=<?php echo $row['id']; ?>"><span class="glyphicon glyphicon-eye-open"></span></a></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pending Reports -->
  <div class="tab-pane fade <?php echo $initialTab==='reports'?'in active':''; ?>" id="reports">
    <div class="panel panel-default">
      <div class="panel-heading">Reports <span class="label label-primary">Total Pending: <?php echo $tR; ?></span></div>
      <table class="table table-bordered table-striped">
        <thead><tr><th>ID</th><th>Buyer</th><th>Seller</th><th>Type</th><th>Date</th><th>Order</th><th>Price</th><th>State</th><th>Last Reply</th><th>Last Updated</th><th>Assigned To</th></tr></thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($qR)):
          $st = $row['status'];
          $stTxt = $st==='0' ? 'closed' : 'pending';
          $orderid = !empty($row['orderid']) ? $row['orderid'] : 'n/a';
        ?>
          <tr>
            <td><a href="viewr.php?id=<?php echo $row['id']; ?>"><?php echo $row['id']; ?></a></td>
            <td><?php echo htmlspecialchars(strtolower($row['uid'])); ?></td>
            <td><?php echo htmlspecialchars(strtolower($row['resseller'])); ?></td>
            <td><?php echo htmlspecialchars(strtolower($row['acctype'])); ?></td>
            <td><?php echo htmlspecialchars($row['date']); ?></td>
            <td><a href="viewr.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($orderid); ?></a></td>
            <td><?php echo (int)$row['price']; ?>$</td>
            <td><?php echo htmlspecialchars($stTxt); ?></td>
            <td><?php echo htmlspecialchars($row['lastreply']); ?></td>
            <td><?php echo htmlspecialchars($row['lastup'] ?: 'n/a'); ?></td>
            <td><?php echo assign_form('report', $row['id'], $row['assigned_to'] ?? '', $staff); ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- All Tickets -->
  <div class="tab-pane fade <?php echo $initialTab==='all'?'in active':''; ?>" id="all">
    <div class="panel panel-default">
      <div class="panel-heading">All Tickets <span class="label label-primary">Total: <?php echo $tA; ?></span></div>
      <table class="table table-bordered table-striped">
        <thead><tr><th>ID</th><th>Date</th><th>Title</th><th>State</th><th>Last Reply</th><th>Last Updated</th><th>Assigned To</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($qA)): ?>
          <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['date']); ?></td>
            <td>
              <?php echo htmlspecialchars($row['subject']); ?>
              <br><small>
                <span class="label label-info" style="font-size:10px;">Buyer: <?php echo htmlspecialchars($row['uid']); ?></span>
                <?php if (!empty($row['assigned_to'])): ?>
                <span class="label label-warning" style="font-size:10px;">Staff: <?php echo htmlspecialchars($row['assigned_to']); ?></span>
                <?php endif; ?>
              </small>
            </td>
            <td><?php echo status_label($row['status']); ?></td>
            <td><?php echo htmlspecialchars($row['lastreply']); ?></td>
            <td><?php echo htmlspecialchars($row['lastup'] ?: 'n/a'); ?></td>
            <td><?php echo assign_form('ticket', $row['id'], $row['assigned_to'] ?? '', $staff); ?></td>
            <td>
              <a class="btn btn-xs btn-primary" href="viewt.php?id=<?php echo $row['id']; ?>"><span class="glyphicon glyphicon-eye-open"></span> View</a>
              <?php if ($row['status'] == '0' && $role === 'admin'): ?>
                <a class="btn btn-xs btn-success" href="openticket.php?id=<?php echo $row['id']; ?>&return=ticket.php?tab=all"><span class="glyphicon glyphicon-folder-open"></span> Open</a>
              <?php elseif (($row['status']=='1'||$row['status']=='2') && $role === 'admin'): ?>
                <a class="btn btn-xs btn-danger" href="closeticket.php?id=<?php echo $row['id']; ?>"><span class="glyphicon glyphicon-remove"></span> Close</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Insert Ticket -->
  <div class="tab-pane fade <?php echo $initialTab==='insert'?'in active':''; ?>" id="insert">
    <?php echo $insertNotice; ?>
    <div class="col-md-5">
      <form method="post"><br>
        Title : <input placeholder="subject" type="text" name="subject" class="form-control input-sm" required><br>
        User  : <input placeholder="username" type="text" name="user" class="form-control input-sm" required><br>
        <button type="submit" name="submit" class="btn btn-primary btn-md">Add <span class="glyphicon glyphicon-plus"></span></button>
        <input type="hidden" name="start" value="work" />
      </form>
    </div>
  </div>

</div>

<script>
// activate tab from URL hash if present (e.g. ticket.php#reports)
$(function(){
  var h = window.location.hash;
  if (h && $('#ticketTabs a[href="'+h+'"]').length) {
    $('#ticketTabs a[href="'+h+'"]').tab('show');
  }
});
</script>
