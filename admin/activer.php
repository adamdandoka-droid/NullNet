<?php
include "header.php";

$ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || isset($_GET['ajax']);

function respond($ok, $msg, $ajax) {
    if ($ajax) {
        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: application/json');
        echo json_encode(['ok' => $ok, 'msg' => $msg]);
    } else {
        $cls = $ok ? 'alert-success' : 'alert-danger';
        echo '<br><div class="alert '.$cls.'" role="alert"><center>'.$msg.'</center></div>';
    }
    exit();
}

$uid = mysqli_real_escape_string($dbcon, $_GET["id"] ?? '');
if ($uid === '') { respond(false, 'Missing user id', $ajax); }

$q = mysqli_query($dbcon, "SELECT * FROM users WHERE id='$uid'");
$r = $q ? mysqli_fetch_assoc($q) : null;
if (!$r) { respond(false, 'User not found', $ajax); }

if ($r['resseller'] == "1") { respond(false, 'Already a seller', $ajax); }

$user = mysqli_real_escape_string($dbcon, $r['username']);
$date = date("Y/m/d h:i:s");

if (!mysqli_query($dbcon, "UPDATE users SET resseller='1' WHERE id='$uid'")) {
    respond(false, 'Could not activate seller: '.mysqli_error($dbcon), $ajax);
}

mysqli_query($dbcon, "INSERT INTO resseller
(username,unsoldb,soldb,isold,iunsold,activate,btc,withdrawal,allsales,lastweek)
VALUES ('$user','0','0','0','0','$date','','',null,null)");

respond(true, $r['username'].' is now a seller', $ajax);
