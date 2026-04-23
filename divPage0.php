<?php
ob_start();
session_start();
error_reporting();
date_default_timezone_set('UTC');
include "includes/config.php";

if (!isset($_SESSION['sname']) and !isset($_SESSION['spass'])) {
    header("location: ../");
    exit();
}
$usrid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);

?>
<?php
 echo'
<div class="form-group col-lg-7 ">
<div class="well">
  Hello <a class="label label-primary">'.$usrid.'</a><br>
    If you have any <b>Question</b> ,<b>Problem</b>, <b>Suggestion</b> or <b>Request</b> Please feel free to <a class="label label-default " href="tickets.html"><span class="glyphicon glyphicon-pencil"></span> Open a Ticket</a><br>
    if you want to report an order , just go to <abbr title="Account - > My Orders or Click here" >My Orders  <span class="glyphicon glyphicon-shopping-cart"></span></abbr> 
    then click on <a class="label label-primary">Report #[Order Id]</a> button<br><br>
    Our Domains are <b>NullNet.shop</b>- Please Save them!

</div>

    <div class="list-group" id="div2">
        <h3><i class="glyphicon glyphicon-info-sign"></i> News</h3>'; 
                 $qq = @mysqli_query($dbcon, "SELECT * FROM news ORDER by id desc LIMIT 5") or die("error here"); 

                
while($r = mysqli_fetch_assoc($qq)){                            echo'<a class="list-group-item"><h5 class="list-group-item-heading"><b>'.htmlspecialchars($r['title']).'</b></h5><p class="list-group-item-text">'.nl2br(htmlspecialchars(stripcslashes($r['content']))).'</p><h6 class="list-group-item-text"><small class="text-muted">'.$r['date'].'</small></h6></a>'; 
}
 echo '

                                 </div>

</div>
<div class="form-group col-lg-4 ">
        <!-- <img src="files/img/eid.jpg" style="width: 70%; height: 70%" title="Eid Mubarak"> -->
<iframe src="static.html" style="border:none;" width="400" height="270" scrolling="no">Browser not compatible.</iframe>

    ';
        ?>
        <div class="well well-sm">    
                  <h4><b>Our Support team is here !</b></h4><a class="btn btn-default btn-sm" onclick="pageDiv(9,'Tickets - NullNet','tickets.html#open',0); return false;" href="tickets.html#open"><span class="glyphicon glyphicon-pencil"></span> Open a Ticket</a>
                  <h5><b>Interested in becoming a seller at  NullNet ?</b></h5><a class="btn btn-primary btn-xs" href="becomeseller.html" onclick="pageDiv(16,'Become A Seller - NullNet','becomeseller.html',0); return false;">Learn more</a>
                  <h5><b>Available Payment Methods </b></h5>

                  <img src="files/img/btclogo.png" height="48" width="49" title="Bitcoin" onclick="pageDiv(13,'Add Balance - NullNet','addBalance.html',0); return false;" href="addBalance.html" onmouseover="this.style.cursor='pointer'">
                  <span title="Ethereum" onclick="pageDiv(13,'Add Balance - NullNet','addBalance.html',0); return false;" onmouseover="this.style.cursor='pointer'" style="display:inline-block;vertical-align:middle;margin-left:8px">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 417" width="34" height="48"><g fill="none" fill-rule="evenodd"><path fill="#343434" d="M127.961 0l-2.795 9.5v275.668l2.795 2.79 127.962-75.638z"/><path fill="#8C8C8C" d="M127.962 0L0 212.32l127.962 75.639V154.158z"/><path fill="#3C3C3B" d="M127.961 312.187l-1.575 1.92v98.199l1.575 4.6L256 236.587z"/><path fill="#8C8C8C" d="M127.962 416.905v-104.72L0 236.585z"/><path fill="#141414" d="M127.961 287.958l127.96-75.637-127.96-58.162z"/><path fill="#393939" d="M0 212.32l127.96 75.638V154.159z"/></g></svg>
                  </span>
                 
      </div>
        <?php
        echo '
                 
      </div>
  </div>
'; ?>
