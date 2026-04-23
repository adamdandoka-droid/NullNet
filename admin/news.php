<?php
include "header.php";

function news_table($dbcon, $table, $type, $label) {
    $editId = (isset($_GET['edit']) && isset($_GET['type']) && $_GET['type'] === $type) ? (int)$_GET['edit'] : 0;
    $q = mysqli_query($dbcon, "SELECT * FROM `$table` ORDER BY id DESC");
    echo '<h4 style="margin-top:20px"><b>Existing '.$label.'</b></h4>';
    if (!$q || mysqli_num_rows($q) === 0) {
        echo '<div class="alert alert-info">No news yet.</div>';
        return;
    }
    echo '<table class="table table-bordered table-striped">
        <thead><tr><th>ID</th><th>Date</th><th>Title</th><th>Content</th><th style="width:160px">Options</th></tr></thead><tbody>';
    while ($r = mysqli_fetch_assoc($q)) {
        $id = (int)$r['id'];
        if ($editId === $id) {
            echo '<tr>
                <td>'.$id.'</td>
                <td>'.htmlspecialchars($r['date']).'</td>
                <td colspan="2"><form method="post" style="margin:0">
                    <input type="hidden" name="sub" value="edit">
                    <input type="hidden" name="type" value="'.$type.'">
                    <input type="hidden" name="id" value="'.$id.'">
                    <input type="text" name="title" class="form-control" placeholder="Title" required value="'.htmlspecialchars($r['title']).'" style="margin-bottom:5px">
                    <textarea name="new" class="form-control" rows="3" placeholder="Content" required>'.htmlspecialchars($r['content']).'</textarea>
                </td>
                <td>
                    <button type="submit" class="btn btn-success btn-sm">Save</button>
                    <a href="news.html" class="btn btn-default btn-sm">Cancel</a>
                </form></td></tr>';
        } else {
            echo '<tr>
                <td>'.$id.'</td>
                <td>'.htmlspecialchars($r['date']).'</td>
                <td><b>'.htmlspecialchars($r['title']).'</b></td>
                <td>'.nl2br(htmlspecialchars(substr($r['content'], 0, 200))).(strlen($r['content']) > 200 ? '...' : '').'</td>
                <td>
                    <a href="news.html?edit='.$id.'&amp;type='.$type.'" class="btn btn-primary btn-sm">Edit</a>
                    <a href="news.html?del='.$id.'&amp;type='.$type.'" class="btn btn-danger btn-sm" onclick="return confirm(\'Delete this news item?\');">Delete</a>
                </td></tr>';
        }
    }
    echo '</tbody></table>';
}

// Handle delete
if (isset($_GET['del']) && isset($_GET['type'])) {
    $delId = (int)$_GET['del'];
    $tbl = ($_GET['type'] === 'seller') ? 'newseller' : 'news';
    if ($delId > 0) {
        mysqli_query($dbcon, "DELETE FROM `$tbl` WHERE id='$delId'");
        echo '<div class="alert alert-success">News deleted.</div>';
    }
}

// Handle edit save
if (isset($_POST['sub']) && $_POST['sub'] === 'edit') {
    $eid = (int)$_POST['id'];
    $tbl = ($_POST['type'] === 'seller') ? 'newseller' : 'news';
    $title   = mysqli_real_escape_string($dbcon, trim($_POST['title'] ?? ''));
    $content = mysqli_real_escape_string($dbcon, $_POST['new']);
    if ($eid > 0) {
        mysqli_query($dbcon, "UPDATE `$tbl` SET title='$title', content='$content' WHERE id='$eid'");
        echo '<div class="alert alert-success">News updated.</div>';
    }
}

// Handle add buyer
if (isset($_POST['sub']) && $_POST['sub'] === 'news_buyer') {
    $title = mysqli_real_escape_string($dbcon, trim($_POST['title'] ?? ''));
    $news  = mysqli_real_escape_string($dbcon, $_POST['new']);
    $date  = date("Y-m-d H:i:s");
    if ($title === '' || $news === '') {
        echo '<div class="alert alert-warning">Title and content are required.</div>';
    } else {
        $q = mysqli_query($dbcon, "INSERT INTO news (title,content,date) VALUES ('$title','$news','$date')");
        echo $q
            ? '<div class="alert alert-success">Buyer news added.</div>'
            : '<div class="alert alert-danger">Buyer news not added.</div>';
    }
}

// Handle add seller
if (isset($_POST['sub']) && $_POST['sub'] === 'news_seller') {
    $title = mysqli_real_escape_string($dbcon, trim($_POST['title'] ?? ''));
    $news  = mysqli_real_escape_string($dbcon, $_POST['new']);
    $date  = date("Y-m-d H:i:s");
    if ($title === '' || $news === '') {
        echo '<div class="alert alert-warning">Title and content are required.</div>';
    } else {
        $q = mysqli_query($dbcon, "INSERT INTO newseller (title,content,date) VALUES ('$title','$news','$date')");
        echo $q
            ? '<div class="alert alert-success">Seller news added.</div>'
            : '<div class="alert alert-danger">Seller news not added.</div>';
    }
}
?>
<div class="alert alert-danger fade in radius-bordered alert-shadowed"><b>News Manager</b></div>

<div class="row">
    <div class="col-md-6">
        <b><font color="#000">Buyer News</font></b><br>
        <form method="post">
            Title :
            <input type="text" name="title" class="form-control" required maxlength="255" />
            <br>
            Content :
            <textarea name="new" class="form-control" required></textarea>
            <br>
            <input type="submit" class="btn btn-danger" value="Add >>" />
            <input type="hidden" name="sub" value="news_buyer" />
        </form>
        <?php news_table($dbcon, 'news', 'buyer', 'Buyer News'); ?>
    </div>

    <div class="col-md-6">
        <b><font color="#000">Seller News</font></b><br>
        <form method="post">
            Title :
            <input type="text" name="title" class="form-control" required maxlength="255" />
            <br>
            Content :
            <textarea name="new" class="form-control" required></textarea>
            <br>
            <input type="submit" class="btn btn-danger" value="Add >>" />
            <input type="hidden" name="sub" value="news_seller" />
        </form>
        <?php news_table($dbcon, 'newseller', 'seller', 'Seller News'); ?>
    </div>
</div>
