<?php
include "header.php";

$type = $_POST['type'] ?? '';
$id   = (int)($_POST['id'] ?? 0);
$ass  = trim($_POST['assignee'] ?? '');

if (!in_array($type, ['ticket','report'], true) || $id <= 0) {
    header("Location: ./index.php"); exit();
}

$assEsc = mysqli_real_escape_string($dbcon, $ass);
$assSql = $ass === '' ? "NULL" : "'$assEsc'";

if ($ass !== '') {
    $chk = mysqli_query($dbcon, "SELECT role FROM users WHERE username='$assEsc'");
    $cr  = $chk ? mysqli_fetch_assoc($chk) : null;
    if (!$cr || ($cr['role'] !== 'admin' && $cr['role'] !== 'support')) {
        die('<div style="font-family:sans-serif;padding:40px;text-align:center"><h2>Invalid assignee</h2><p>Only admin or support users may be assigned.</p><p><a href="javascript:history.back()">Back</a></p></div>');
    }
}

if ($type === 'ticket') {
    mysqli_query($dbcon, "UPDATE ticket SET assigned_to=$assSql WHERE id='$id'");
    header("Location: ./ticket.php"); exit();
} else {
    mysqli_query($dbcon, "UPDATE reports SET assigned_to=$assSql WHERE id='$id'");
    header("Location: ./reports.php"); exit();
}
