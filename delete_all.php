<?php
include 'db.php';

mysqli_query($conn, "DELETE FROM books");
mysqli_query($conn, "ALTER TABLE books AUTO_INCREMENT = 1");

header('Location: index.php?msg=deleted_all');
exit;
?>