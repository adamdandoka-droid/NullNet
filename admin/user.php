<?php
error_reporting(0);
include "header.php";
if ($role !== 'admin') {
    http_response_code(403);
    die('<div style="font-family:sans-serif;padding:40px;text-align:center"><h2>403 &mdash; Admins only</h2><p><a href="./users.php">Back to Users</a></p></div>');
}

$id = mysqli_real_escape_string($dbcon, $_GET['id']);
$q = mysqli_query($dbcon, "SELECT * FROM users WHERE id='$id' ");
$r = mysqli_fetch_assoc($q);


// user not found


global $r;
?>
<div class="alert alert-danger fade in radius-bordered alert-shadowed"><b>Profile</b></div>


<div class="row">
  <div class="col-md-4">
    <br>
    <div id="myTabContent" class="tab-content">
      <div class="tab-pane active in" id="home">
      <form id="tab" method="post">
        <div class="form-group">
        <label>Username</label>
        <input type="text" value="<?php echo $r['username'];?>" class="form-control" name="user">
        </div>
        <div class="form-group">
        <label>Email</label>
        <input type="text" value="<?php echo $r['email'];?>" class="form-control" name="email">
        </div>
        <div class="form-group">
        <label>Balance</label>
        <input type="text" value="<?php echo $r['balance'];?>" class="form-control" name="balance">
        </div>
        <div class="form-group">
        <label>ip</label>
        <input type="text" value="<?php echo $r['ip'];?>" class="form-control" name="ip">
        </div>
        <div class="form-group">
        <label>Date of Registartion</label>
        <input type="text" value="<?php echo $r['datereg'];?>" class="form-control" name="dor">
        </div>
        <div class="form-group">
        <label>Role</label>
        <select class="form-control" name="role">
            <?php $cur = $r['role'] ?? 'user'; foreach (['user'=>'User','support'=>'Support','admin'=>'Admin'] as $rv=>$rl): ?>
            <option value="<?php echo $rv; ?>" <?php echo ($cur===$rv?'selected':''); ?>><?php echo $rl; ?></option>
            <?php endforeach; ?>
        </select>
        </div>
        <input type="submit" class="btn btn-primary" name="op" value="Save" />
        <input type="submit" class="btn btn-danger" name="op" value="Delete" />
        <a href="users.php" class="btn btn-default"><span class="glyphicon glyphicon-arrow-left"></span> Back</a>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
//error_reporting(0);
$user = $_POST['user'];
$emal = $_POST['email'];
$balc = $_POST['balance'];
$ip = $_POST['ip'];
$id = mysqli_real_escape_string($dbcon, $_GET['id']);

if($_POST['op'] and $_POST['op'] == "Save"){
   $roleIn = $_POST['role'] ?? 'user';
   if (!in_array($roleIn, ['user','support','admin'], true)) { $roleIn = 'user'; }
   $roleEsc = mysqli_real_escape_string($dbcon, $roleIn);
   $qq = mysqli_query($dbcon, "UPDATE users SET username='$user',email='$emal',balance='$balc',role='$roleEsc' WHERE id='$id'");
   if($qq){
     echo "<b><font color='green'>Editing Done !!</font></b>";
   }else{
    echo "<b><font color='red'>Editing Error !!</font></b>";
   }
}else if($_POST['op'] and $_POST['op'] == "Delete"){
   $qq = mysqli_query($dbcon, "DELETE FROM users WHERE id='$id'");
   if($qq){
     $_SESSION['admin_flash'] = ['ok'=>true,  'msg'=>'User deleted'];
   }else{
     $_SESSION['admin_flash'] = ['ok'=>false, 'msg'=>'User not deleted'];
   }
   header("Location: users.php");
   exit();
}

?>