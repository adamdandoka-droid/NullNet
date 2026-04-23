<?php
ob_start();
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
$site  = $_POST['shell_host'] ?? '';
$price = $_POST['price'] ?? '';
$k     = $site;

if (isset($_POST['start']) && $_POST['start'] == "work") {
    if ($price == 0) {
        echo "Price is not valid !";
    } else if (preg_match('/[^0-9]/', $price)) {
        echo "Price is not valid!";
    } else if (empty($k)) {
        echo "Complete all fields";
    } else {
        $o      = parse_url($k, PHP_URL_HOST);
        if (!$o) { $o = parse_url('http://' . ltrim($k, '/'), PHP_URL_HOST); }
        $o      = (string)$o;
        $oEsc   = mysqli_real_escape_string($dbcon, $o);
        $kEsc   = mysqli_real_escape_string($dbcon, $k);
        $check  = "SELECT COUNT(*) FROM stufs WHERE domain = '$oEsc'";
        $rs     = mysqli_query($dbcon, $check);
        $data   = mysqli_fetch_array($rs, MYSQLI_NUM);
        if ($data[0] >= 1) {
            echo "Already Added !";
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
            $sql = "INSERT INTO stufs
                (acctype,country,infos,url,price,resseller,sold,date,dateofsold,reported,sto,domain)
                VALUES
                ('shell','$countryEsc','$hostingEsc','$kEsc','$priceEsc','$uid','0','$date','$date','','','$oEsc')";
            if (mysqli_query($dbcon, $sql)) {
                saveProof($dbcon, 'stufs', mysqli_insert_id($dbcon));
                echo "Successfully Added !";
            } else {
                echo "Not Added: " . htmlspecialchars(mysqli_error($dbcon));
            }
        }
    }
}
?>
