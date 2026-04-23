<?php
function saveProof($dbcon, $table, $id) {
    static $sourcePath = null;
    static $sourceExt  = null;

    $tEsc  = preg_replace('/[^a-z0-9_]/i', '', $table);
    $idInt = (int)$id;
    if ($idInt <= 0 || $tEsc === '') return '';

    $dir = realpath(__DIR__ . '/..') . '/files/proofs';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    if ($sourcePath === null) {
        if (!isset($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
            return '';
        }
        $tmp  = $_FILES['proof']['tmp_name'];
        $name = $_FILES['proof']['name'];
        $size = $_FILES['proof']['size'];
        if ($size <= 0 || $size > 5 * 1024 * 1024) return '';
        $info = @getimagesize($tmp);
        if (!$info) return '';
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) return '';

        $fname = $tEsc . '_' . $idInt . '.' . $ext;
        $dst   = $dir . '/' . $fname;
        foreach (['jpg', 'jpeg', 'png', 'gif', 'webp'] as $oext) {
            $old = $dir . '/' . $tEsc . '_' . $idInt . '.' . $oext;
            if (file_exists($old) && $old !== $dst) @unlink($old);
        }
        if (!@move_uploaded_file($tmp, $dst)) return '';

        $sourcePath = $dst;
        $sourceExt  = $ext;
    } else {
        $ext   = $sourceExt;
        $fname = $tEsc . '_' . $idInt . '.' . $ext;
        $dst   = $dir . '/' . $fname;
        foreach (['jpg', 'jpeg', 'png', 'gif', 'webp'] as $oext) {
            $old = $dir . '/' . $tEsc . '_' . $idInt . '.' . $oext;
            if (file_exists($old) && $old !== $dst) @unlink($old);
        }
        if (!@copy($sourcePath, $dst)) return '';
    }

    $rel    = 'files/proofs/' . $fname;
    $relEsc = mysqli_real_escape_string($dbcon, $rel);
    @mysqli_query($dbcon, "UPDATE `$tEsc` SET proof='$relEsc' WHERE id='$idInt'");
    return $rel;
}
