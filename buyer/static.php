<?php
ob_start();
session_start();
date_default_timezone_set('UTC');
include "../includes/config.php";

if(!isset($_SESSION['sname']) and !isset($_SESSION['spass'])){
   header("location: ../");
   exit();
}
$usrid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
echo "
<!doctype html>
<html>
  <head>
";
  $countleads = mysqli_query($dbcon,"SELECT * FROM leads WHERE sold='0'");$countr1=mysqli_num_rows($countleads); 
$countcpanels = mysqli_query($dbcon,"SELECT * FROM cpanels WHERE sold='0'");$countr2=mysqli_num_rows($countcpanels);
$countshells = mysqli_query($dbcon,"SELECT * FROM stufs WHERE sold='0'");$countr3=mysqli_num_rows($countshells);
$countrdps = mysqli_query($dbcon,"SELECT * FROM rdps WHERE sold='0'");$countr4=mysqli_num_rows($countrdps);
 $countmailers = mysqli_query($dbcon,"SELECT * FROM mailers WHERE sold='0'");$countr5=mysqli_num_rows($countmailers);
$countsmtps = mysqli_query($dbcon,"SELECT * FROM smtps WHERE sold='0'");$countr66=mysqli_num_rows($countsmtps); 
$countscams = mysqli_query($dbcon,"SELECT * FROM scampages");$countr6=mysqli_num_rows($countscams); 
$counttutos = mysqli_query($dbcon,"SELECT * FROM tutorials");$countr7=mysqli_num_rows($counttutos); 
echo'
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">';
	echo "
      google.charts.load('current', {'packages':['corechart']});
          google.charts.setOnLoadCallback(drawChart2);
      function drawChart2() {
        var data = google.visualization.arrayToDataTable([
          ['Tools', 'Number'],
          ['Leads',  $countr1],
          ['cPanels',     $countr2],
          ['Shells',     $countr3],
          ['Rdps',      $countr4],
          ['Mailers',      $countr5],
          ['Smtps',      $countr66],
          ['Scampages',      $countr6],
          ['Tutorials',      $countr7],
                            ]);

        var options = {
          title: 'Available Tools',  
          titleTextStyle: {color: '#910606'},
          backgroundColor: 'transparent',

          legend: 'right',
          chartArea: {'width': '100%', 'height': '80%' },

          pieSliceText: 'label',
          pieHole: 0.4,
          colors:['#910606','#011e3d','#011b36','#011932','#011428','#021426','#032546']

        };

        var chart = new google.visualization.PieChart(document.getElementById('donutchart'));
        chart.draw(data, options);

        google.visualization.events.addListener(chart, 'select', function() {
          var sel = chart.getSelection();
          if (!sel.length) return;
          var label = data.getValue(sel[0].row, 0);
          var map = {
            'Leads':                ['leads.html',     6,  'Leads - NullNet'],
            'cPanels':              ['cPanel.html',    2,  'cPanel - NullNet'],
            'Shells':               ['shell.html',     3,  'Shell - NullNet'],
            'Rdps':                 ['rdp.html',       1,  'RDP - NullNet'],
            'Mailers':              ['mailer.html',    4,  'PHP Mailer - NullNet'],
            'Smtps':                ['smtp.html',      5,  'SMTP - NullNet'],
            'Scampages':            ['scampage.html',  9,  'Scampages - NullNet'],
            'Tutorials':            ['tutorial.html',  10, 'Tutorials - NullNet'],
            'Premium/Dating/Shop':  ['premium.html',   7,  'Premium/Dating/Shop - NullNet'],
            'Banks':                ['banks.html',     8,  'Banks - NullNet']
          };
          var t = map[label]; if (!t) return;
          try { (window.parent && window.parent.pageDiv) ? window.parent.pageDiv(t[1], t[2], t[0], 0) : (window.top.location.href = t[0]); }
          catch(e) { window.top.location.href = t[0]; }
        });

      }
    </script>";
	echo '
    <style type="text/css">
      div.google-visualization-tooltip { pointer-events: none }
      svg > g > g:last-child { pointer-events: none }
    </style>
  </head>
  <body>
    <!--<div id="chart_div" style="width: 450px; height: 200px;"></div>-->
    <div id="donutchart" title="Click a slice to open the category" style="width: 450px; height: 250px;; cursor:pointer">
      
    </div>

  </body>
</html>';
?>