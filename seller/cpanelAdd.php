<?php
ob_start();
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

$url   = $_POST['cpanel_host'] ?? '';
$login = $_POST['cpanel_login'] ?? '';
$pass  = $_POST['cpanel_pass'] ?? '';
$price = mysqli_real_escape_string($dbcon, $_POST['price'] ?? '');
$k     = "$url|$login|$pass";
$o     = parse_url($url, PHP_URL_HOST);
$date  = date('Y-m-d H:i:s');

if (isset($_POST['start']) && $_POST['start'] == "work") {
    if ($price == 0) {
        echo htmlspecialchars($o) . " .. Price is not valid!";
    } else if (preg_match('/[^0-9]/', $price)) {
        echo htmlspecialchars($o) . " .. Price is not valid!";
    } else if (empty($url) || empty($login) || empty($pass)) {
        echo "Complete all fields";
    } else {
        $kEsc = mysqli_real_escape_string($dbcon, $k);
        $check = "SELECT COUNT(*) FROM cpanels WHERE url = '$kEsc'";
        $rs   = mysqli_query($dbcon, $check);
        $data = mysqli_fetch_array($rs, MYSQLI_NUM);
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
            $countryEsc  = mysqli_real_escape_string($dbcon, $country);
            $hostingEsc  = mysqli_real_escape_string($dbcon, $hostingg);

            $q = mysqli_query($dbcon, "INSERT INTO cpanels
                (acctype,country,infos,url,price,sold,sto,dateofsold,resseller,date,reported)
                VALUES
                ('cpanel','$countryEsc','$hostingEsc','$kEsc','$price','0','','$date','$uid','$date','')")
                or die(mysqli_error($dbcon));
            if ($q) {
                saveProof($dbcon, 'cpanels', mysqli_insert_id($dbcon));
                echo htmlspecialchars($o) . " .. Added Successfully";
            } else {
                echo "Not Added";
            }
        }
    }
}
?>
