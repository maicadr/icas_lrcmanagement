<?php
include 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $sql = "DELETE FROM books WHERE id = $id";
    mysqli_query($conn, $sql);
}

header('Location: index.php?msg=deleted');
exit;
?>