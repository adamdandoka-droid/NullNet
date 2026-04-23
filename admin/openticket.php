<?php
include "header.php";
if ($role !== 'admin') {
    http_response_code(403);
    die('Admins only');
}
$id  = mysqli_real_escape_string($dbcon, $_GET['id'] ?? '');
$ret = $_GET['return'] ?? 'viewt.php?id='.$id;
if ($id === '') { die('Missing id'); }

$date = date("d/m/Y h:i:s a");
mysqli_query($dbcon, "UPDATE ticket SET status='1', lastreply='Admin', lastup='$date' WHERE id='$id'") or die(mysqli_error($dbcon));

$_SESSION['admin_flash'] = ['ok'=>true, 'msg'=>'Ticket #'.$id.' reopened'];
header("Location: ".$ret);
exit();
