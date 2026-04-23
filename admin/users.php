<?php




include "header.php";
?>
<div class="alert alert-danger fade in radius-bordered alert-shadowed"><b>Users List</b></div>
<?php
$searchRaw = trim($_GET['q'] ?? '');
$searchEsc = mysqli_real_escape_string($dbcon, $searchRaw);
$searchSafe = htmlspecialchars($searchRaw, ENT_QUOTES);
$where = '';
if ($searchRaw !== '') {
    $where = "WHERE username LIKE '%$searchEsc%' OR email LIKE '%$searchEsc%' OR id='$searchEsc'";
}
?>
<form method="get" action="users.php" class="form-inline" style="margin-bottom:12px">
  <div class="input-group" style="width:100%;max-width:480px">
    <input type="text" name="q" value="<?php echo $searchSafe; ?>" class="form-control" placeholder="Search by username, email, or id...">
    <span class="input-group-btn">
      <button type="submit" class="btn btn-primary">Search</button>
      <?php if ($searchRaw !== ''): ?>
      <a href="users.php" class="btn btn-default">Clear</a>
      <?php endif; ?>
    </span>
  </div>
</form>
<?php
  $q = mysqli_query($dbcon, "SELECT COUNT(*) AS c FROM users $where") or die("error");
  $tRow = mysqli_fetch_assoc($q);
  $t = (int)$tRow['c'];

if(!isset($_GET['page'])){
  $page = 1;
  $ka = $page;
}else{
  $page = max(1, intval($_GET['page']));
  $ka = $page;
}
$record_at_page = 20;
$record_count = $t;

$pages_count = (int)ceil($record_count / $record_at_page);
if ($pages_count < 1) { $pages_count = 1; }
if ($page > $pages_count) { $page = $pages_count; $ka = $page; }
$start = ($ka - 1) * $record_at_page;
$end = $record_at_page;

if($record_count != 0){
  $qq = mysqli_query($dbcon, "SELECT * FROM users $where ORDER BY id DESC LIMIT $start,$end") or die("error");
echo '
    <div class="">
        <div class="panel panel-default">
          <div class="panel-heading no-collapse">  <center>Total users <span class="label label-warning">'.$t.'  </center></span></div>
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Balance</th>
                  <th>items purch</th>
                  <th>Last login</th>
                  <th>Action</th>
                  <th>Seller</th>
                </tr>
              </thead>
              <tbody>';
  while($row = mysqli_fetch_assoc($qq)){
              echo '<tr>
                  <td>'.$row['username'].'</td>
                  <td>'.$row['email'].'</td>
                  <td>'.$row['balance'].'</td>
                  <td>'.$row['ipurchassed'].'</td>
                  <td>'.$row['lastlogin'].'</td>
                  <td>';
      if ($role === 'admin') {
          echo '<a href="user.php?id='.$row['id'].'"><center><span class="btn label-danger"><font color=white>Edit/Delete</font></center></span></a>';
      } else {
          echo '<center><span class="label label-default">View only</span></center>';
      }
      $unameJ = htmlspecialchars($row['username'], ENT_QUOTES);
      echo '<center style="margin-top:4px"><a href="#" class="btn btn-info btn-xs view-history-btn" data-username="'.$unameJ.'"><span class="glyphicon glyphicon-list-alt"></span> History</a></center>';
      echo '</td><td>';

      $uname = htmlspecialchars($row["username"]);
      if($row["resseller"] == "0" or empty($row["resseller"])){
          echo '<a href="activer.php?id='.$row["id"].'" class="btn label-primary make-seller-btn" data-id="'.$row["id"].'" data-username="'.$uname.'"><font color=white>Make Seller</font></a>';
      }else{
          echo '<a href="remover.php?id='.$row["id"].'" class="btn label-danger remove-seller-btn" data-id="'.$row["id"].'" data-username="'.$uname.'"><font color=white>Remove Seller</font></a>';
      }

                  echo '</td></tr>';
  }
                  echo '

              </tbody>
            </table>
        </div>
    </div>';

}
?>
<center>
<nav>
  <ul class="pagination">
    <?php
      for($i=1;$i<=$pages_count;$i++){

  if($page == $i){
     echo $page;
  }else{
    $qParam = $searchRaw !== '' ? '&q='.urlencode($searchRaw) : '';
    echo '<li><a href="users.php?page='.$i.$qParam.'">'.$i.'</a></li>';
  }
}
ob_end_flush();
mysqli_close($dbcon);

    ?>

</ul>
</nav>
</center>
<div id="adminFlash" style="position:fixed;top:70px;right:20px;z-index:9999;min-width:280px;display:none"></div>
<?php if (!empty($_SESSION['admin_flash'])):
    $f = $_SESSION['admin_flash']; unset($_SESSION['admin_flash']); ?>
<script>
$(function(){ adminFlash(<?php echo $f['ok']?'true':'false'; ?>, <?php echo json_encode($f['msg']); ?>); });
</script>
<?php endif; ?>
<script>
function adminFlash(ok, msg){
    var cls = ok ? 'alert-success' : 'alert-danger';
    var $f = $('#adminFlash');
    $f.html('<div class="alert '+cls+'" role="alert" style="margin:0">'+msg+'</div>').fadeIn(150);
    clearTimeout(window.__adminFlashT);
    window.__adminFlashT = setTimeout(function(){ $f.fadeOut(300); }, 3000);
}
function swapSellerBtn($btn, makeSeller){
    var id = $btn.data('id');
    var user = $btn.data('username');
    if (makeSeller) {
        $btn.attr('class','btn label-danger remove-seller-btn')
            .attr('href','remover.php?id='+id)
            .html('<font color=white>Remove Seller</font>');
    } else {
        $btn.attr('class','btn label-primary make-seller-btn')
            .attr('href','activer.php?id='+id)
            .html('<font color=white>Make Seller</font>');
    }
}
$(document).on('click', '.view-history-btn', function(e){
    e.preventDefault();
    var uname = $(this).data('username');
    var dlg = bootbox.dialog({
        title: '<span class="glyphicon glyphicon-list-alt"></span> History &mdash; ' + uname,
        message: '<div class="text-center" style="padding:20px"><span class="glyphicon glyphicon-refresh"></span> Loading...</div>',
        size: 'large',
        buttons: {
            close: { label: 'Close', className: 'btn-default' }
        }
    });
    $.ajax({
        url: 'userhistory.php?user=' + encodeURIComponent(uname),
        dataType: 'json',
        success: function(d){
            if (d && d.status === 'ok') {
                dlg.find('.bootbox-body').html(d.html);
            } else {
                dlg.find('.bootbox-body').html('<div class="alert alert-danger">'+(d && d.msg ? d.msg : 'Failed to load history')+'</div>');
            }
        },
        error: function(){
            dlg.find('.bootbox-body').html('<div class="alert alert-danger">Request failed. Please try again.</div>');
        }
    });
});
$(document).on('click', '.make-seller-btn', function(e){
    e.preventDefault();
    var $btn = $(this);
    bootbox.confirm({
        title: 'Make seller',
        message: 'Grant seller status to <b>' + $btn.data('username') + '</b>?',
        buttons: {
            cancel:  { label: 'Cancel', className: 'btn-default' },
            confirm: { label: 'Make Seller', className: 'btn-primary' }
        },
        callback: function(result){
            if(!result) return;
            $.ajax({
                url: $btn.attr('href'), method: 'GET', dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(res){
                    adminFlash(res.ok, res.msg);
                    if (res.ok) swapSellerBtn($btn, true);
                },
                error: function(){ adminFlash(false, 'Request failed'); }
            });
        }
    });
});
$(document).on('click', '.remove-seller-btn', function(e){
    e.preventDefault();
    var $btn = $(this);
    bootbox.confirm({
        title: 'Remove seller status',
        message: 'Are you sure you want to remove seller status from <b>' + $btn.data('username') + '</b>?',
        buttons: {
            cancel:  { label: 'Cancel', className: 'btn-default' },
            confirm: { label: 'Remove', className: 'btn-danger'  }
        },
        callback: function(result){
            if(!result) return;
            $.ajax({
                url: $btn.attr('href'), method: 'GET', dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(res){
                    adminFlash(res.ok, res.msg);
                    if (res.ok) swapSellerBtn($btn, false);
                },
                error: function(){ adminFlash(false, 'Request failed'); }
            });
        }
    });
});
</script>


