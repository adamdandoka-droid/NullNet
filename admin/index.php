<?php


include "../includes/config.php";
include "header.php";
?>
        <?php
  $date = date("m-d-y");
  $qt = mysqli_query($dbcon, "SELECT * FROM users");
  $qtf = mysqli_num_rows($qt);
    $qtc = mysqli_query($dbcon, "SELECT * FROM ticket WHERE status='1' or status='2'");
  $qtfc = mysqli_num_rows($qtc);
    $qtcz = mysqli_query($dbcon, "SELECT * FROM reports WHERE status='1' or status='2'");
  $qtfec = mysqli_num_rows($qtcz);
     $qtczb = mysqli_query($dbcon, "SELECT * FROM resseller");
  $qtfec8 = mysqli_num_rows($qtczb);
  $qpay = mysqli_query($dbcon, "SELECT COALESCE(SUM(amount),0) AS total, COUNT(*) AS cnt FROM seller_payments WHERE status IN ('approved','released','paid','completed')");
  $qpayrow = $qpay ? mysqli_fetch_assoc($qpay) : array('total'=>0,'cnt'=>0);
  $payoutTotal = number_format((float)$qpayrow['total'], 2);
  $payoutCount = (int)$qpayrow['cnt'];
?>      
<div class="alert alert-danger fade in radius-bordered alert-shadowed"><b>NullNet</b></div>
<div class="form-group col-md-6 col-lg-2">
                <div class="teddy-text">
<center>        <b><font size="4" color="17C0FB">
<span class="glyphicon glyphicon-time" style="font-size: 55px;"></span><br><h3><?php echo $qtfc;?></h3>
</font> </CENTER></b>
                                        </div>
                            <div class="teddy-follow">
<center>        <b><font size="4" color="white">Pending Tickets</font> </CENTER></b>
                            </div>

</div>
<div class="form-group col-md-6 col-lg-2">
                <div class="teddy-text">
<center>        <b><font size="4" color="17C0FB">
<span class="glyphicon glyphicon-time" style="font-size: 55px;"></span><br><h3><?php echo $qtfec; ?></h3>
</font> </CENTER></b>
                                        </div>
                            <div class="teddy-follow">
<center>        <b><font size="4" color="white">Pending Reports</font> </CENTER></b>
                            </div>

</div>
<div class="form-group col-md-6 col-lg-2">
                <div class="teddy-text">
<center>        <b><font size="4" color="D41010">
<span class="glyphicon glyphicon-user" style="font-size: 55px;"></span><br><h3><?php echo $qtf;?>+</h3>
</font> </CENTER></b>
                                        </div>
                            <div class="teddy-followred">
<center>        <b><font size="4" color="white">Users</font> </CENTER></b>
                            </div>

</div>
<div class="form-group col-md-6 col-lg-2">
                <div class="teddy-text">
<center>        <b><font size="4" color="D41010">
<span class="glyphicon glyphicon-fire" style="font-size: 55px;"></span><br><h3><?php echo $qtfec8; ?></h3>
</font> </CENTER></b>
                                        </div>
                            <div class="teddy-followred">
<center>        <b><font size="4" color="white">Sellers</font> </CENTER></b>
                            </div>

</div>
<div class="form-group col-md-6 col-lg-2">
                <div class="teddy-text">
<center>        <b><font size="4" color="17C0FB">
<span class="glyphicon glyphicon-usd" style="font-size: 55px;"></span><br><h3>$<?php echo $payoutTotal; ?></h3>
</font> </CENTER></b>
                                        </div>
                            <div class="teddy-follow">
<center>        <b><font size="4" color="white">Seller Payouts (<?php echo $payoutCount; ?>)</font> </CENTER></b>
                            </div>

</div>
<br>
<div class="form-group col-lg-8">
<h4>Last Tickets </h4>
<?php
  $q = mysqli_query($dbcon, "SELECT * FROM ticket order by id desc Limit 5")or die("error");

    echo '
     <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>User</th>
                  <th>Title</th>
                  <th>Date Created</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>';
              while($row = mysqli_fetch_assoc($q)){
                $st = $row['status'];

    switch ($st){
      case "0" :
       $st = "closed";
       break;
      case "1" :
       $st = "pending";
       break;
      case "2":
       $st = "pending";
       break;
    }
                if (empty($row['lastup'])) {
                $lastup = "n/a"; 
                } else { 
                $lastup = $row['lastup'];       
                }
              echo '<tr>
                  <td>'.$row['id'].'</td>
                  <td>'.$row['uid'].'</td>
                  <td>'.$row['subject'].'</td>
                                  <td>'.$row['date'].'</td>
                  <td><a class="btn btn-primary btn-xs" href="viewt.php?id='.$row['id'].'">View</a></td>
                  </tr>';
                  }
                  echo '

              </tbody>
            </table>
      ';
    ?>
</div>
<div class="form-group col-lg-4">
<h4>Last Users </h4>
<?php
  $q = mysqli_query($dbcon, "SELECT * FROM users order by id desc Limit 5")or die("error");

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
                  <td>'.$row['id'].'</td>
                  <td>'.$row['username'].'</td>
                  <td>'.$row['datereg'].'</td>
                  </tr>';
                  }
                  echo '

              </tbody>
            </table>
      ';
    ?>
</div>
</div>
<!------------------------------------------------------------------------>
<div class="form-group col-lg-6 ">

<?php


include "../includes/config.php";
?>

<center>
<!------------------------------------------------------------------------>

<!----------------------------------------------------------------------------------->
<?php
$bbbyesterday = date('Y-m-d', strtotime('-3 days'));
$bbyesterday  = date('Y-m-d', strtotime('-2 days'));
$byesterday   = date('Y-m-d', strtotime('-1 days'));
$yesterday    = date('Y-m-d', strtotime('-1 days'));
$today        = date('Y-m-d');

function _sumSalesForDay($dbcon, $day) {
    $sum = 0;
    $rs = mysqli_query($dbcon, "SELECT price FROM purchases WHERE date BETWEEN '$day 00:00:00' AND '$day 23:59:59'");
    if ($rs) { while ($r = mysqli_fetch_array($rs)) { $sum += (float)$r['price']; } }
    return $sum;
}
$saleToday   = _sumSalesForDay($dbcon, $today);
$saleYest    = _sumSalesForDay($dbcon, date('Y-m-d', strtotime('-1 days')));
$saleByest   = _sumSalesForDay($dbcon, date('Y-m-d', strtotime('-2 days')));
$saleBByest  = _sumSalesForDay($dbcon, date('Y-m-d', strtotime('-3 days')));
$saleBBByest = $saleBByest; // legacy var kept for safety
?>
<html>
  <head>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Day', 'Sales $'],
          ['<?php echo date('l', strtotime('-3 days')); ?>',  <?php echo $saleBByest;?>],
          ['<?php echo date('l', strtotime('-2 days')); ?>',  <?php echo $saleByest;?>],
          ['Yesterday',  <?php echo $saleYest;?>],
          ['Today',      <?php echo $saleToday;?>],
        ]);

        var options = {
          title: 'NullNet Sales $',
          vAxis: {minValue: 0},
                  colors: ['navy','#001f3f'],
                    animation:{
        startup: 'True',
    duration: 1000,
    easing: 'out',
  }
        };

        var chart = new google.visualization.AreaChart(document.getElementById('chart_div'));
        chart.draw(data, options);
                
      }
    </script>
  </head>
  <body>
    <div id="chart_div" style="width: 100%; height: 500px;"></div>
  </body>
</html></div>
<div class="form-group col-lg-6 ">
<?php
include "../includes/config.php";
$bbyesterday = date('Y-m-d', strtotime('-3 days'));
$byesterday  = date('Y-m-d', strtotime('-2 days'));
$yesterday   = date('Y-m-d', strtotime('-1 days'));
$today       = date('Y-m-d');
  $qtoday = mysqli_query($dbcon, "SELECT * FROM users WHERE datereg='$today'");
  $qtfotoday = mysqli_num_rows($qtoday);

  $qyester = mysqli_query($dbcon, "SELECT * FROM users WHERE datereg='$yesterday'");
  $qtfoyester = mysqli_num_rows($qyester);

  $qbyest = mysqli_query($dbcon, "SELECT * FROM users WHERE datereg='$byesterday'");
  $qbyeste = mysqli_num_rows($qbyest);

  $qbbyest = mysqli_query($dbcon, "SELECT * FROM users WHERE datereg='$bbyesterday'");
  $qtfobbyest = mysqli_num_rows($qbbyest);
  

?>
<html>
  <head>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Day', 'New users'],
          ['<?php echo date('l', strtotime('-3 days')); ?>',  <?php echo $qtfobbyest;?>],
          ['<?php echo date('l', strtotime('-2 days')); ?>',  <?php echo $qbyeste;?>],
          ['Yesterday',  <?php echo $qtfoyester;?>],
          ['Today',  <?php echo $qtfotoday;?>],
        ]);

        var options = {
          title: 'Registered users',
          vAxis: {minValue: 0},
                  colors: ['orange','#FFA500'],
                    animation:{
        startup: 'True',
    duration: 1000,
    easing: 'out',
  }
        };

        var chart = new google.visualization.AreaChart(document.getElementById('chart_dive'));
        chart.draw(data, options);
                
      }
    </script>
  </head>
  <body>
    <div id="chart_dive" style="width: 100%; height: 500px;"></div>
  </body>
</html>

</div>
