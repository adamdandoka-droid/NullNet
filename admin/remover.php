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
        $cls = $ok ? 'alert-success' : 'alert-warning';
        echo '<br><div class="alert '.$cls.'" role="alert"><center>'.$msg.'</center></div>';
    }
    exit();
}

$uid = mysqli_real_escape_string($dbcon, $_GET['id'] ?? '');
if ($uid === '') { respond(false, 'Missing user id', $ajax); }

$q = mysqli_query($dbcon, "SELECT * FROM users WHERE id='$uid'");
$r = $q ? mysqli_fetch_assoc($q) : null;
if (!$r) { respond(false, 'User not found', $ajax); }

if ($r['resseller'] == "0") { respond(false, 'Not a seller', $ajax); }

$user = mysqli_real_escape_string($dbcon, $r['username']);

if (!mysqli_query($dbcon, "UPDATE users SET resseller='0' WHERE id='$uid'")) {
    respond(false, 'Could not remove seller: '.mysqli_error($dbcon), $ajax);
}
mysqli_query($dbcon, "DELETE FROM resseller WHERE username='$user'");

respond(true, $r['username'].' is no longer a seller', $ajax);
