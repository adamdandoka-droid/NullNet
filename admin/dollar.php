<?php

include "header.php";
?>
<?php
  $date  = date("Y-m-d");
  $date2 = date("Y-m-");

  $qt   = mysqli_query($dbcon, "SELECT SUM(balance) as total FROM users");
  $qtf  = mysqli_fetch_assoc($qt);

  $qto  = mysqli_query($dbcon, "SELECT SUM(amountusd) as total FROM payment WHERE date LIKE '$date%'");
  $qtfo = mysqli_fetch_assoc($qto);

  $qtc  = mysqli_query($dbcon, "SELECT SUM(amountusd) as total FROM payment WHERE date LIKE '$date2%'");
  $qtfc = mysqli_fetch_assoc($qtc);

  $qtr  = mysqli_query($dbcon, "SELECT SUM(amountusd) as total FROM payment");
  $qtfr = mysqli_fetch_assoc($qtr);

  $qtpend = mysqli_query($dbcon, "SELECT SUM(amount) as total FROM seller_payments WHERE status='pending'");
  $qtfpend = mysqli_fetch_assoc($qtpend);
?>
<div class="alert alert-danger fade in radius-bordered alert-shadowed"><b>Financial Status</b></div>

<div class="form-group col-lg-3">
                <div class="teddy-text">
<center>        <b><font size="4" color="17C0FB">
<span class="glyphicon glyphicon-usd" style="font-size: 55px;"></span><br><h3><?php echo empty($qtfo['total']) ? "0.00" : htmlspecialchars($qtfo['total']); ?>$</h3>
</font> </CENTER></b>
                                        </div>
                            <div class="teddy-follow">
<center>        <b><font size="4" color="white">Total Deposit (Today)</font> </CENTER></b>
                            </div>
</div>

<div class="form-group col-lg-3">
                <div class="teddy-text">
<center>        <b><font size="4" color="17C0FB">
<span class="glyphicon glyphicon-usd" style="font-size: 55px;"></span><br><h3><?php echo empty($qtfc['total']) ? "0.00" : htmlspecialchars($qtfc['total']); ?>$</h3>
</font> </CENTER></b>
                                        </div>
                            <div class="teddy-follow">
<center>        <b><font size="4" color="white">Total Deposit (Month)</font> </CENTER></b>
                            </div>
</div>

<div class="form-group col-lg-3">
                <div class="teddy-text">
<center>        <b><font size="4" color="D41010">
<span class="glyphicon glyphicon-usd" style="font-size: 55px;"></span><br><h3><?php echo empty($qtfr['total']) ? "0.00" : htmlspecialchars($qtfr['total']); ?>$</h3>
</font> </CENTER></b>
                                        </div>
                            <div class="teddy-followred">
<center>        <b><font size="4" color="white">Total Deposit (All Time)</font> </CENTER></b>
                            </div>
</div>

<div class="form-group col-lg-3">
                <div class="teddy-text">
<center>        <b><font size="4" color="D41010">
<span class="glyphicon glyphicon-time" style="font-size: 55px;"></span><br><h3><?php echo empty($qtfpend['total']) ? "0.00" : htmlspecialchars($qtfpend['total']); ?>$</h3>
</font> </CENTER></b>
                                        </div>
                            <div class="teddy-followred">
<center>        <b><font size="4" color="white">Pending Seller Payouts</font> </CENTER></b>
                            </div>
</div>

<br>
<div class="form-group col-lg-8">
<h4>Last Deposits</h4>
<?php
$q = mysqli_query($dbcon, "SELECT * FROM payment ORDER BY id DESC LIMIT 5");
echo '
 <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Method</th>
              <th>Amount (USD)</th>
              <th>State</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>';
while($row = mysqli_fetch_assoc($q)){
    echo '<tr>
              <td>'.htmlspecialchars($row['id']).'</td>
              <td>'.htmlspecialchars($row['user']).'</td>
              <td>'.htmlspecialchars($row['method']).'</td>
              <td>'.htmlspecialchars($row['amountusd']).'$</td>
              <td>'.htmlspecialchars($row['state']).'</td>
              <td>'.htmlspecialchars($row['date']).'</td>
          </tr>';
}
echo '</tbody></table>';
?>
</div>

<div class="form-group col-lg-4">
<h4>Last Users</h4>
<?php
$q = mysqli_query($dbcon, "SELECT * FROM users ORDER BY id DESC LIMIT 5") or die("error");
echo '
 <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>';
while($row = mysqli_fetch_assoc($q)){
    echo '<tr>
              <td>'.htmlspecialchars($row['id']).'</td>
              <td>'.htmlspecialchars($row['username']).'</td>
              <td>'.htmlspecialchars($row['datereg']).'</td>
          </tr>';
}
echo '</tbody></table>';
?>
</div>
