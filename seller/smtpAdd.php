<?php
error_reporting(0);
session_start();
date_default_timezone_set('UTC');
include "../includes/config.php";
include_once __DIR__ . "/proofHelper.php";

if (!isset($_SESSION['sname']) and !isset($_SESSION['spass'])) {
    header("location: ../");
    exit();
}
$uid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);

function ambilKata($param, $kata1, $kata2){
    if(strpos($param, $kata1) === FALSE) return FALSE;
    if(strpos($param, $kata2) === FALSE) return FALSE;
    $start = strpos($param, $kata1) + strlen($kata1);
    $end = strpos($param, $kata2, $start);
    return substr($param, $start, $end - $start);
}

$host  = mysqli_real_escape_string($dbcon, $_POST['host'] ?? '');
$login = mysqli_real_escape_string($dbcon, $_POST['login'] ?? '');
$pass  = mysqli_real_escape_string($dbcon, $_POST['pass'] ?? '');
$port  = mysqli_real_escape_string($dbcon, $_POST['port'] ?? '');
$price = mysqli_real_escape_string($dbcon, $_POST['price'] ?? '');
$date  = date("Y-m-d H:i:s");

if (isset($_POST['start']) && $_POST['start'] == "work") {
    if ($price == 0) {
        echo "<br><b>" . htmlspecialchars($host) . "</b> .... <b>Price not valid!</b> <br>";
    } else if (empty($host) || empty($login) || empty($pass) || empty($port)) {
        echo "Complete all fields <br>";
    } else if (preg_match('/[^0-9]/', $price)) {
        echo "<br><b>" . htmlspecialchars($host) . "</b> .... <b>Price not valid!</b> <br>";
    } else {
        if (preg_match('/http/', $host)) {
            $host = parse_url($host, PHP_URL_HOST);
        }
        $link    = "$host|$login|$pass|$port";
        $linkEsc = mysqli_real_escape_string($dbcon, $link);
        $check   = "SELECT COUNT(*) FROM smtps WHERE url = '$linkEsc'";
        $rs      = mysqli_query($dbcon, $check);
        $data    = mysqli_fetch_array($rs, MYSQLI_NUM);
        if ($data[0] >= 1) {
            echo "<br><b>" . htmlspecialchars($host) . "</b> .... <b>Already Added</b> <br>";
        } else {
            $hostingg = 'Unknown';
            $countryy = 'Unknown';
            @ini_set('default_socket_timeout', 3);
            $resp = @file_get_contents("https://ipwho.is/$host");
            if ($resp) {
                $j = @json_decode($resp, true);
                if (is_array($j) && !empty($j['success'])) {
                    $countryy = $j['country'] ?? 'Unknown';
                    $hostingg = $j['connection']['isp'] ?? 'Unknown';
                }
            }
            // SELLER_COUNTRY_OVERRIDE: prefer explicit country from form
            $_postedCountry = isset($_POST['country']) ? trim($_POST['country']) : '';
            if ($_postedCountry !== '' && $_postedCountry !== '__auto__') { $countryy = $_postedCountry; }
            $countryEsc = mysqli_real_escape_string($dbcon, $countryy);
            $hostingEsc = mysqli_real_escape_string($dbcon, $hostingg);

            $query = mysqli_query($dbcon, "INSERT INTO smtps
                (acctype,country,infos,price,url,sold,sto,dateofsold,date,resseller,reported)
                VALUES
                ('smtp','$countryEsc','$hostingEsc','$price','$linkEsc','0','','$date','$date','$uid','')")
                or die(mysqli_error($dbcon));
            if ($query) {
                saveProof($dbcon, 'smtps', mysqli_insert_id($dbcon));
                echo "<b>" . htmlspecialchars($host) . "</b> ........ <b><font color=green>Added!</font></b>";
            } else {
                echo '<div class="alert alert-danger" role="alert">Not Added Contact Support</div>';
            }
        }
    }
}
?>
