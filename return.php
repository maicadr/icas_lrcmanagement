<?php
include 'db.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    // Get book title to restore stock
    $result = mysqli_query($conn, "SELECT book_title FROM books WHERE id = $id");
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        // Restore stock in shelf_books
        $title = mysqli_real_escape_string($conn, $row['book_title']);
        mysqli_query($conn, "UPDATE books SET returned = 1, book_status = 'Available' WHERE id = $id");
        mysqli_query($conn, "UPDATE shelf_books SET shelf_stock = shelf_stock + 1 WHERE shelf_title = '$title'");
    }
}

header('Location: index.php?msg=returned');
exit;
?>