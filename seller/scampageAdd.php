<?php
error_reporting(0);
 session_start();
date_default_timezone_set('UTC');
include "../includes/config.php";
include_once __DIR__ . "/proofHelper.php";

if(!isset($_SESSION['sname']) and !isset($_SESSION['spass'])){
   header("location: ../");
   exit();
}
$usrid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
$uid = mysqli_real_escape_string($dbcon, $_SESSION['sname']);
?>
<?php
 $infos = mysqli_real_escape_string($dbcon, $_POST['infos']);
  $link = mysqli_real_escape_string($dbcon, $_POST['link']);
  $price = mysqli_real_escape_string($dbcon, $_POST['price']);
   $namescm = mysqli_real_escape_string($dbcon, $_POST['scamname']);
   $date = date("Y-m-d H:i:s");

  if(isset($_POST['start']) and $_POST['start'] == "work"){
      if ($price == 0)
{
        echo "<br><b>Price not valid!</b> <br>";
} 
      else if (empty($link))
{
        echo "Complete all fields <br>";
} 
 else if (preg_match('/[^0-9]/', $price)) {
        echo "<b>Price not valid!</b> <br>";
} else {
  $qq = @mysqli_query($dbcon, "SELECT * FROM scampages") or die("DB error: ".mysqli_error($dbcon));
  $check="SELECT id FROM scampages WHERE url = '$link' LIMIT 1";
$rs = mysqli_query($dbcon,$check);
if($rs && mysqli_num_rows($rs) > 0) {
while($row = mysqli_fetch_assoc($qq)){
     $st = $row['url'];
         
                                $oddd = parse_url($link, PHP_URL_HOST);
                         if (preg_match("#$oddd#", $st))  {     
                         } 
    }
    echo "<b>Already added</b><br/>";
} else {


    $query = mysqli_query($dbcon, "
  INSERT INTO scampages
  (acctype,country,infos,url,price,resseller,sold,sto,dateofsold,date,scamname)
  VALUES
  ('scampage','-','$infos','$link','$price','$uid','0','','','$date','$namescm')
  ")or die(mysqli_error($dbcon));

  if($query){
    saveProof($dbcon, 'scampages', mysqli_insert_id($dbcon));
    echo "Added successfully!";

  }else{
    echo '<div class="alert alert-danger" role="alert">Not Added Contact Support</div>';
} }
  } }
?>