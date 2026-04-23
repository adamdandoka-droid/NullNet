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
<div class="form-group col-lg-7 col-lg-offset-2">
  <div class="well">
    <h2><span class="glyphicon glyphicon-star"></span> Interested in becoming a seller at NullNet ?</h2>
    Okay then let us explain your Features and tell you some rules.<br>

    <h4><span class="glyphicon glyphicon-globe"></span> Your Features</h4>
    <ul>
      <li>You get <b>65%</b> of your sales via bitcoin <span class="glyphicon glyphicon-bitcoin"></span></li>
      <li>You get paid any time you like, no delay!</li>
      <li>You can sell any tool you like with no limit and choose your price!</li>
      <li>You will have your own Panel with tons of options</li>
    </ul>

    <h4><span class="glyphicon glyphicon-exclamation-sign"></span> Our Rules</h4>
    <ul>
      <li>All the tools have to be <b>Fresh</b> and not used or sold before.</li>
      <li>Don't try XSS / SQL Injection / add (<abbr title="Under 0!">minus</abbr>) price and buy it &mdash; it will not work</li>
      <li>Do not put links of other stores (e-mails) or any ads</li>
    </ul>

    <h4><i class="glyphicon glyphicon-list-alt"></i> How to Apply ?</h4>
    <ul>
      <li><a class="label label-default" href="tickets.html" onclick="pageDiv(11,'Tickets - NullNet','tickets.html',0); return false;"><span class="glyphicon glyphicon-pencil"></span> Open a Ticket</a> with a title of <abbr title="in order to reply faster!">seller request</abbr></li>
      <li>Make sure to mention what tools you have and include a <abbr title="We don't accept sellers with 20 cpanels and etc.">mass example</abbr> for each.</li>
      <li>Make sure all of your tools work before sending them to us.</li>
      <li>We should reply to you as soon as we see the request!</li>
    </ul>
  </div>
</div>
