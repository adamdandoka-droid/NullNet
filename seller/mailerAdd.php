<?php
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

$date  = date('Y-m-d H:i:s');
$site  = $_POST['mailer_host'] ?? '';
$price = $_POST['price'] ?? '';
$k     = $site;

if (isset($_POST['start']) && $_POST['start'] == "work") {
    $o = parse_url($k, PHP_URL_HOST);
    if ($price == 0) {
        echo htmlspecialchars($o) . " .. Price not valid! <br>";
    } else if (preg_match('/[^0-9]/', $price)) {
        echo htmlspecialchars($o) . " .. <b>Price not valid!</b> <br>";
    } else if (empty($k)) {
        echo "Complete all fields";
    } else {
        $kEsc = mysqli_real_escape_string($dbcon, $k);
        $check = "SELECT COUNT(*) FROM mailers WHERE url = '$kEsc'";
        $rs    = mysqli_query($dbcon, $check);
        $data  = mysqli_fetch_array($rs, MYSQLI_NUM);
        if ($data[0] >= 1) {
            echo htmlspecialchars($o) . " .. Already Added !<br/>";
        } else {
            $hostingg = 'Unknown';
            $country  = 'Unknown';
            @ini_set('default_socket_timeout', 3);
            if ($o) {
                $resp = @file_get_contents("https://ipwho.is/$o");
                if ($resp) {
                    $j = @json_decode($resp, true);
                    if (is_array($j) && !empty($j['success'])) {
                        $country  = $j['country'] ?? 'Unknown';
                        $hostingg = $j['connection']['isp'] ?? 'Unknown';
                    }
                }
            }
            // SELLER_COUNTRY_OVERRIDE: prefer explicit country from form
            $_postedCountry = isset($_POST['country']) ? trim($_POST['country']) : '';
            if ($_postedCountry !== '' && $_postedCountry !== '__auto__') { $country = $_postedCountry; }
            $countryEsc = mysqli_real_escape_string($dbcon, $country);
            $hostingEsc = mysqli_real_escape_string($dbcon, $hostingg);
            $priceEsc   = mysqli_real_escape_string($dbcon, $price);
            $sql = "INSERT INTO mailers
                (acctype,country,infos,url,price,resseller,sold,date,dateofsold,reported,sto)
                VALUES
                ('mailer','$countryEsc','$hostingEsc','$kEsc','$priceEsc','$uid','0','$date','$date','','')";
            if (mysqli_query($dbcon, $sql)) {
                saveProof($dbcon, 'mailers', mysqli_insert_id($dbcon));
                echo htmlspecialchars($k) . " .. [Added]<br>";
            } else {
                echo htmlspecialchars($k) . " .. Not Added: " . htmlspecialchars(mysqli_error($dbcon)) . "<br>";
            }
        }
    }
}
?>
