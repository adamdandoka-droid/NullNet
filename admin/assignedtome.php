<?php
include "header.php";
$me = mysqli_real_escape_string($dbcon, $_SESSION['sname']);

$tq = mysqli_query($dbcon, "SELECT * FROM ticket WHERE assigned_to='$me' AND (status='1' OR status='2') ORDER BY status DESC");
$rq = mysqli_query($dbcon, "SELECT * FROM reports WHERE assigned_to='$me' AND (status='1' OR status='2') ORDER BY status DESC");
?>
<div class="alert alert-danger fade in radius-bordered alert-shadowed"><b>Assigned to Me</b></div>

<div class="panel panel-default">
  <div class="panel-heading no-collapse">My Tickets <span class="label label-primary">Total: <?php echo mysqli_num_rows($tq); ?></span></div>
  <table class="table table-bordered table-striped">
    <thead><tr><th>ID</th><th>User</th><th>Date</th><th>Title</th><th>State</th><th>Last Reply</th><th>Last Updated</th><th>View</th></tr></thead>
    <tbody>
    <?php while ($row = mysqli_fetch_assoc($tq)):
      $st = ($row['status']=='0') ? 'closed' : 'open';
      $lastup = empty($row['lastup']) ? 'n/a' : $row['lastup'];
    ?>
      <tr>
        <td><?php echo $row['id']; ?></td>
        <td><?php echo htmlspecialchars($row['uid']); ?></td>
        <td><?php echo htmlspecialchars($row['date']); ?></td>
        <td><?php echo htmlspecialchars($row['subject']); ?></td>
        <td><?php echo $st; ?></td>
        <td><?php echo htmlspecialchars($row['lastreply']); ?></td>
        <td><?php echo htmlspecialchars($lastup); ?></td>
        <td><a class="btn btn-primary btn-xs" href="viewt.php?id=<?php echo $row['id']; ?>"><span class="glyphicon glyphicon-eye-open"></span></a></td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>

<div class="panel panel-default">
  <div class="panel-heading no-collapse">My Reports <span class="label label-primary">Total: <?php echo mysqli_num_rows($rq); ?></span></div>
  <table class="table table-bordered table-striped">
    <thead><tr><th>ID</th><th>Buyer</th><th>Seller</th><th>Type</th><th>Date</th><th>Order ID</th><th>Price</th><th>State</th><th>Last Reply</th><th>Last Updated</th><th>View</th></tr></thead>
    <tbody>
    <?php while ($row = mysqli_fetch_assoc($rq)):
      $st = ($row['status']=='0') ? 'closed' : 'pending';
      $lastup = empty($row['lastup']) ? 'n/a' : $row['lastup'];
      $orderid = empty($row['orderid']) ? 'n/a' : $row['orderid'];
    ?>
      <tr>
        <td><?php echo $row['id']; ?></td>
        <td><?php echo strtolower(htmlspecialchars($row['uid'])); ?></td>
        <td><?php echo strtolower(htmlspecialchars($row['resseller'])); ?></td>
        <td><?php echo strtolower(htmlspecialchars($row['acctype'])); ?></td>
        <td><?php echo htmlspecialchars($row['date']); ?></td>
        <td><a href="viewr.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($orderid); ?></a></td>
        <td><?php echo (int)$row['price']; ?>$</td>
        <td><?php echo $st; ?></td>
        <td><?php echo htmlspecialchars($row['lastreply']); ?></td>
        <td><?php echo htmlspecialchars($lastup); ?></td>
        <td><a class="btn btn-primary btn-xs" href="viewr.php?id=<?php echo $row['id']; ?>"><span class="glyphicon glyphicon-eye-open"></span></a></td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>
