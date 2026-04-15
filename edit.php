<?php
include 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: index.php'); exit; }

$res = mysqli_query($conn, "SELECT * FROM books WHERE id = $id");
if (!$res || mysqli_num_rows($res) === 0) { header('Location: index.php'); exit; }

$book   = mysqli_fetch_assoc($res);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book['book_title']    = trim($_POST['book_title'] ?? '');
    $book['book_author']   = trim($_POST['book_author'] ?? '');
    $book['book_genre']    = trim($_POST['book_genre'] ?? '');
    $book['book_status']   = trim($_POST['book_status'] ?? '');
    $book['borrowed_date'] = trim($_POST['borrowed_date'] ?? '');
    $book['duedate']       = trim($_POST['duedate'] ?? '');

    if ($book['book_title']  === '') $errors[] = 'Book title is required.';
    if ($book['book_author'] === '') $errors[] = 'Author name is required.';
    if ($book['book_genre']  === '') $errors[] = 'Book genre is required.';
    if ($book['book_status'] === '') $errors[] = 'Book status is required.';

    if (empty($errors)) {
        $title    = mysqli_real_escape_string($conn, $book['book_title']);
        $author   = mysqli_real_escape_string($conn, $book['book_author']);
        $genre    = mysqli_real_escape_string($conn, $book['book_genre']);
        $status   = mysqli_real_escape_string($conn, $book['book_status']);
        $borrowed = $book['borrowed_date'] !== '' ? "'" . mysqli_real_escape_string($conn, $book['borrowed_date']) . "'" : 'NULL';
        $due      = $book['duedate']       !== '' ? "'" . mysqli_real_escape_string($conn, $book['duedate'])       . "'" : 'NULL';

        $sql = "UPDATE books SET
                    book_title   = '$title',
                    book_author  = '$author',
                    book_genre   = '$genre',
                    book_status  = '$status',
                    borrowed_date = $borrowed,
                    duedate      = $due
                WHERE id = $id";

        if (mysqli_query($conn, $sql)) {
            header('Location: index.php?msg=updated');
            exit;
        } else {
            $errors[] = 'Database error: ' . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Book — LRC</title>
<style>
  :root {
    --bg: #f4f6f9; --white: #ffffff; --primary: #2c5f8a; --primary-dark: #1e4468;
    --danger: #c0392b; --warning: #e67e22; --border: #e2e8f0; --text: #2d3748; --muted: #718096;
    --shadow: 0 2px 8px rgba(0,0,0,0.08);
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

  header { background: var(--primary); color: #fff; padding: 16px 32px; display: flex; align-items: center; gap: 14px; box-shadow: 0 2px 6px rgba(0,0,0,.15); }
  header .logo { font-size: 26px; }
  header h1 { font-size: 20px; font-weight: 600; }
  header p  { font-size: 12px; opacity: .75; margin-top: 2px; }

  .container { max-width: 680px; margin: 36px auto; padding: 0 20px; }

  .breadcrumb { font-size: 13px; color: var(--muted); margin-bottom: 18px; }
  .breadcrumb a { color: var(--primary); text-decoration: none; }
  .breadcrumb a:hover { text-decoration: underline; }

  .card { background: var(--white); border-radius: 10px; box-shadow: var(--shadow); padding: 32px; }
  .card h2 { font-size: 18px; font-weight: 600; margin-bottom: 24px; padding-bottom: 14px; border-bottom: 2px solid var(--warning); color: var(--warning); }

  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
  .form-group { margin-bottom: 18px; }
  .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text); }
  .form-group label .req { color: var(--danger); margin-left: 2px; }

  .form-group input,
  .form-group select {
    width: 100%; padding: 10px 13px; border: 1px solid var(--border); border-radius: 6px;
    font-size: 14px; font-family: inherit; outline: none; transition: border-color .2s, box-shadow .2s;
    background: #fff;
  }
  .form-group input:focus,
  .form-group select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(44,95,138,.12); }

  .form-actions { display: flex; gap: 12px; margin-top: 8px; }

  .btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 22px; border: none; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; text-decoration: none; transition: opacity .2s, transform .1s; }
  .btn:hover { opacity: .88; transform: translateY(-1px); }
  .btn-warning  { background: var(--warning); color: #fff; }
  .btn-secondary { background: #e2e8f0; color: var(--text); }

  .alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid var(--danger); padding: 12px 16px; border-radius: 7px; margin-bottom: 20px; font-size: 13.5px; }
  .alert-danger ul { margin: 6px 0 0 18px; }
  .alert-danger li { margin-bottom: 3px; }

  @media (max-width: 500px) { .form-row { grid-template-columns: 1fr; } }
</style>
</head>
<body>

<header>
  <div class="logo">📚</div>
  <div>
    <h1>LRC Management</h1>
    <p>Learning Resource Center — Book Records</p>
  </div>
</header>

<div class="container">
  <div class="breadcrumb">
    <a href="index.php">Home</a> / Edit Book
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert-danger">
      <strong>Please fix the following:</strong>
      <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <div class="card">
    <h2>✏️ Edit Book Record</h2>
    <form method="POST" action="edit.php?id=<?= $id ?>">

      <div class="form-row">
        <div class="form-group">
          <label>Book Title <span class="req">*</span></label>
          <input type="text" name="book_title" placeholder="e.g. The Great Gatsby" value="<?= htmlspecialchars($book['book_title']) ?>">
        </div>
        <div class="form-group">
          <label>Author's Name <span class="req">*</span></label>
          <input type="text" name="book_author" placeholder="e.g. F. Scott Fitzgerald" value="<?= htmlspecialchars($book['book_author']) ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Book Genre <span class="req">*</span></label>
          <select name="book_genre">
            <option value="">— Select Genre —</option>
            <?php
              $genres = ['Fiction','Non-Fiction','Science Fiction','Fantasy','Mystery','Thriller',
                         'Romance','Horror','Biography','History','Self-Help','Science','Technology',
                         'Philosophy','Poetry','Children','Young Adult','Reference','Textbook','Other'];
              foreach ($genres as $g): ?>
              <option value="<?= $g ?>" <?= $book['book_genre'] === $g ? 'selected' : '' ?>><?= $g ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Book Status <span class="req">*</span></label>
          <select name="book_status">
            <?php foreach (['Available','Borrowed','Reserved','Overdue'] as $s): ?>
            <option value="<?= $s ?>" <?= $book['book_status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Borrowed Date</label>
          <input type="date" name="borrowed_date" value="<?= htmlspecialchars($book['borrowed_date'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Due Date</label>
          <input type="date" name="duedate" value="<?= htmlspecialchars($book['duedate'] ?? '') ?>">
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-warning">Update Book</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
      </div>

    </form>
  </div>
</div>
</body>
</html>