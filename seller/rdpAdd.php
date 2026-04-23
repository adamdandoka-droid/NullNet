<?php
error_reporting(0);
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

$host          = mysqli_real_escape_string($dbcon, $_POST['rdp_host'] ?? '');
$login         = mysqli_real_escape_string($dbcon, $_POST['rdp_login'] ?? '');
$pass          = mysqli_real_escape_string($dbcon, $_POST['rdp_pass'] ?? '');
$access        = mysqli_real_escape_string($dbcon, $_POST['access'] ?? '');
$windows       = mysqli_real_escape_string($dbcon, $_POST['windows'] ?? '');
$ram           = mysqli_real_escape_string($dbcon, $_POST['ram'] ?? '');
$price         = mysqli_real_escape_string($dbcon, $_POST['price'] ?? '');
$countryIn     = trim($_POST['rdp_country'] ?? '');
$stateIn       = trim($_POST['rdp_state'] ?? '');
$hostingTypeIn = trim($_POST['rdp_hosting_type'] ?? '');
$createdAtIn   = trim($_POST['rdp_created_at'] ?? '');
$allowedHostingTypes = ['Hacked','Created'];
$createdAtTs = strtotime(str_replace('T', ' ', $createdAtIn));
$createdAt   = $createdAtTs ? date('Y-m-d H:i:s', $createdAtTs) : '';
$date    = $createdAt !== '' ? $createdAt : date("Y-m-d H:i:s");
$link    = "$host|$login|$pass";

if (isset($_POST['start']) && $_POST['start'] == "work") {
    if ($price == 0) {
        echo "Price is not valid !";
    } else if (empty($host) || empty($login) || empty($pass) || empty($access) || empty($windows) || empty($ram)) {
        echo "Complete all fields <br>";
    } else if ($countryIn === '' || $hostingTypeIn === '' || $createdAt === '') {
        echo "Country, Hosting Type and Created At are required <br>";
    } else if (!in_array($hostingTypeIn, $allowedHostingTypes, true)) {
        echo "Invalid Hosting Type <br>";
    } else if (preg_match('/[^0-9]/', $price)) {
        echo "Price is not valid !";
    } else {
        $check = "SELECT COUNT(*) FROM rdps WHERE url = '$link'";
        $rs    = mysqli_query($dbcon, $check);
        $data  = mysqli_fetch_array($rs, MYSQLI_NUM);
        if ($data[0] >= 1) {
            echo "Already Added !";
        } else {
            $hostingg = trim($_POST['rdp_hosting'] ?? '');
            if ($hostingg === '') {
                @ini_set('default_socket_timeout', 3);
                $resp = @file_get_contents("https://ipwho.is/$host");
                if ($resp) {
                    $j = @json_decode($resp, true);
                    if (is_array($j) && !empty($j['success'])) {
                        $hostingg = isset($j['connection']['isp']) ? $j['connection']['isp'] : '';
                    }
                }
            }
            if ($hostingg === '') { $hostingg = 'Unknown'; }

            $countryy     = mysqli_real_escape_string($dbcon, $countryIn);
            $cityy        = mysqli_real_escape_string($dbcon, $stateIn);
            $hostingTypeE = mysqli_real_escape_string($dbcon, $hostingTypeIn);
            $hostingg     = mysqli_real_escape_string($dbcon, $hostingg);

            $query = mysqli_query($dbcon, "
                INSERT INTO rdps
                (acctype,country,city,hosting,hosting_type,price,url,sold,sto,dateofsold,date,access,windows,ram,resseller,reported)
                VALUES
                ('rdp','$countryy','$cityy','$hostingg','$hostingTypeE','$price','$link','0','','','$date','$access','$windows','$ram','$uid','')
            ") or die(mysqli_error($dbcon));

            if ($query) {
                saveProof($dbcon, 'rdps', mysqli_insert_id($dbcon));
                echo "Successfully Added .. (" . htmlspecialchars($host) . ") with " . htmlspecialchars($login) . "/" . htmlspecialchars($pass);
            } else {
                echo '<div class="alert alert-danger" role="alert">Not Added Contact Support</div>';
            }
        }
    }
}
?>
