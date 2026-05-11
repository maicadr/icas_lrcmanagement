<?php
include 'db.php';

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_shelf') {
    $shelf_title  = trim($_POST['shelf_title']  ?? '');
    $shelf_author = trim($_POST['shelf_author'] ?? '');
    $shelf_genre  = trim($_POST['shelf_genre']  ?? '');
    $shelf_cover  = trim($_POST['shelf_cover']  ?? '');
    $shelf_desc   = trim($_POST['shelf_desc']   ?? '');

    if ($shelf_title  === '') $errors[] = 'Book title is required.';
    if ($shelf_author === '') $errors[] = 'Author is required.';
    if ($shelf_genre  === '') $errors[] = 'Genre is required.';

    if (empty($errors)) {
        $t = mysqli_real_escape_string($conn, $shelf_title);
        $a = mysqli_real_escape_string($conn, $shelf_author);
        $g = mysqli_real_escape_string($conn, $shelf_genre);
        $c = mysqli_real_escape_string($conn, $shelf_cover);
        $d = mysqli_real_escape_string($conn, $shelf_desc);
        $s = (int)($_POST['shelf_stock']?? 1 );
        $sql = "UPDATE shelf_books SET shelf_title='$t', shelf_author='$a', shelf_genre='$g', shelf_cover='$c', shelf_desc='$d', shelf_stock=$s WHERE id=$edit_id";
        $sql = "INSERT INTO shelf_books (shelf_title, shelf_author, shelf_genre, shelf_cover, shelf_desc, shelf_stock, date_added) VALUES ('$t','$a','$g','$c','$d', " . (int)($_POST['shelf_stock']?? 1) . ", CURDATE())";
        if (mysqli_query($conn, $sql)) {
            $success = 'Book added to shelf display!';
        } else {
            $errors[] = 'DB error: ' . mysqli_error($conn);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_shelf') {
    $edit_id      = (int)($_POST['edit_id']     ?? 0);
    $shelf_title  = trim($_POST['shelf_title']  ?? '');
    $shelf_author = trim($_POST['shelf_author'] ?? '');
    $shelf_genre  = trim($_POST['shelf_genre']  ?? '');
    $shelf_cover  = trim($_POST['shelf_cover']  ?? '');
    $shelf_desc   = trim($_POST['shelf_desc']   ?? '');
    $shelf_stock = (int)($_POST['shelf_stock'] ?? 1);

    if ($shelf_title  === '') $errors[] = 'Book title is required.';
    if ($shelf_author === '') $errors[] = 'Author is required.';
    if ($shelf_genre  === '') $errors[] = 'Genre is required.';

    if (empty($errors) && $edit_id > 0) {
        $t = mysqli_real_escape_string($conn, $shelf_title);
        $a = mysqli_real_escape_string($conn, $shelf_author);
        $g = mysqli_real_escape_string($conn, $shelf_genre);
        $c = mysqli_real_escape_string($conn, $shelf_cover);
        $d = mysqli_real_escape_string($conn, $shelf_desc);
        $s = $shelf_stock;
        $sql = "UPDATE shelf_books SET shelf_title='$t', shelf_author='$a', shelf_genre='$g', shelf_cover='$c', shelf_desc='$d', shelf_stock=$s WHERE id=$edit_id";
        if (mysqli_query($conn, $sql)) {
            header('Location: shelves.php?msg=updated');
            exit;
        } else {
            $errors[] = 'DB error: ' . mysqli_error($conn);
        }
    }
}

if (isset($_GET['remove'])) {
    $rid = (int)$_GET['remove'];
    mysqli_query($conn, "DELETE FROM shelf_books WHERE id=$rid");
    header('Location: shelves.php?msg=removed');
    exit;
}

$shelf_result = mysqli_query($conn, "SELECT * FROM shelf_books ORDER BY id DESC");
$shelf_count  = mysqli_num_rows($shelf_result);
$shelf_books  = [];
while ($row = mysqli_fetch_assoc($shelf_result)) $shelf_books[] = $row;

$genres = ['Fiction','Non-Fiction','Science Fiction','Fantasy','Mystery','Thriller',
           'Romance','Horror','Biography','History','Self-Help','Science','Technology',
           'Philosophy','Poetry','Children','Young Adult','Reference','Textbook','Other'];

$genreImage = [
  'Fiction'        => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=400&q=80',
  'Non-Fiction'    => 'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?w=400&q=80',
  'Science Fiction'=> 'https://images.unsplash.com/photo-1446776653964-20c1d3a81b06?w=400&q=80',
  'Fantasy'        => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=400&q=80',
  'Mystery'        => 'https://images.unsplash.com/photo-1509021436665-8f07dbf5bf1d?w=400&q=80',
  'Thriller'       => 'https://images.unsplash.com/photo-1590935217281-8f102120d683?w=400&q=80',
  'Romance'        => 'https://images.unsplash.com/photo-1474552226712-ac0f0961a954?w=400&q=80',
  'Horror'         => 'https://images.unsplash.com/photo-1509248961158-e54f6934749c?w=400&q=80',
  'Biography'      => 'https://images.unsplash.com/photo-1457369804613-52c61a468e7d?w=400&q=80',
  'History'        => 'https://images.unsplash.com/photo-1461360228754-6e81c478b882?w=400&q=80',
  'Self-Help'      => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=400&q=80',
  'Science'        => 'https://images.unsplash.com/photo-1507413245164-6160d8298b31?w=400&q=80',
  'Technology'     => 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=400&q=80',
  'Philosophy'     => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=400&q=80',
  'Poetry'         => 'https://images.unsplash.com/photo-1455390582262-044cdead277a?w=400&q=80',
  'Children'       => 'https://images.unsplash.com/photo-1512436991641-6745cdb1723f?w=400&q=80',
  'Young Adult'    => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=400&q=80',
  'Reference'      => 'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=400&q=80',
  'Textbook'       => 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=400&q=80',
  'Other'          => 'https://images.unsplash.com/photo-1535398089889-dd807df1dfaa?w=400&q=80',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shelf Manager — LRC</title>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:       #0b0f1a;
    --surface:  #111827;
    --card:     #1a2236;
    --card2:    #1e2a40;
    --border:   rgba(255,255,255,0.08);
    --primary:  #4f8ef7;
    --primary-glow: rgba(79,142,247,0.15);
    --green:    #34d399;
    --gold:     #fbbf24;
    --red:      #f87171;
    --text:     #f0f4ff;
    --muted:    #6b7a99;
    --radius:   12px;
    --radius-lg:18px;
    --shadow:   0 8px 30px rgba(0,0,0,.45);
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

  /* HEADER */
  header {
    background: var(--surface); border-bottom: 1px solid var(--border);
    padding: 16px 32px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
    position: sticky; top: 0; z-index: 100;
  }
  .header-brand { display: flex; align-items: center; gap: 12px; }
  .header-logo {
    width: 42px; height: 42px; border-radius: 10px;
    overflow: hidden; flex-shrink: 0;
  }
  .header-title { font-family: 'Lora', serif; font-size: 20px; font-weight: 700; }
  .header-sub   { font-size: 11.5px; color: var(--muted); margin-top: 1px; }
  .header-nav   { display: flex; gap: 8px; }

  .btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    padding: 9px 18px; border: none; border-radius: 9px;
    font-size: 13.5px; font-weight: 600; font-family: inherit;
    cursor: pointer; text-decoration: none; transition: all .2s; white-space: nowrap;
  }
  .btn:hover { transform: translateY(-1px); filter: brightness(1.1); }
  .btn-primary { background: var(--primary); color: #fff; }
  .btn-green   { background: var(--green);   color: #000; }
  .btn-gold    { background: var(--gold);    color: #000; }
  .btn-red     { background: var(--red);     color: #fff; }
  .btn-ghost   { background: rgba(255,255,255,.06); color: var(--text); border: 1px solid var(--border); }
  .btn-ghost:hover { background: rgba(255,255,255,.1); }

  .container { max-width: 1380px; margin: 0 auto; padding: 28px 20px; }
  .layout    { display: grid; grid-template-columns: 370px 1fr; gap: 28px; align-items: start; }

  /* ALERTS */
  .alert {
    padding: 13px 18px; border-radius: 10px; margin-bottom: 22px;
    font-size: 13.5px; font-weight: 500; display: flex; align-items: center; gap: 10px;
  }
  .alert-success { background: rgba(52,211,153,.08); color: #6ee7b7; border-left: 3px solid var(--green); }
  .alert-danger  { background: rgba(248,113,113,.08); color: #fca5a5; border-left: 3px solid var(--red); }
  .alert-danger ul { margin: 6px 0 0 18px; }

  /* ADD FORM CARD */
  .form-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius-lg); overflow: hidden;
    position: sticky; top: 76px; box-shadow: var(--shadow);
  }
  .form-card-header {
    padding: 18px 22px; background: var(--card);
    border-bottom: 1px solid var(--border);
  }
  .form-card-header h2 {
    font-family: 'Lora', serif; font-size: 17px; color: var(--gold);
    display: flex; align-items: center; gap: 8px;
  }
  .form-card-header p  { font-size: 12px; color: var(--muted); margin-top: 4px; }
  .form-body { padding: 22px; }

  .fgroup { margin-bottom: 15px; }
  .fgroup label {
    display: block; font-size: 11px; font-weight: 700;
    letter-spacing: .5px; text-transform: uppercase; color: var(--muted); margin-bottom: 6px;
  }
  .fgroup input, .fgroup select, .fgroup textarea {
    width: 100%; padding: 11px 13px;
    background: var(--card); border: 1.5px solid var(--border);
    border-radius: 9px; color: var(--text); font-size: 14px; font-family: inherit;
    outline: none; transition: border-color .2s, box-shadow .2s;
  }
  .fgroup input::placeholder, .fgroup textarea::placeholder { color: var(--muted); opacity: .7; }
  .fgroup input:focus, .fgroup select:focus, .fgroup textarea:focus {
    border-color: var(--gold); box-shadow: 0 0 0 3px rgba(251,191,36,.12);
  }
  .fgroup select option { background: #1a2236; }
  .fgroup textarea { resize: vertical; min-height: 68px; }
  .hint { font-size: 11.5px; color: var(--muted); margin-top: 4px; }

  .cover-preview-box {
    width: 100%; height: 86px; border-radius: 9px;
    background: var(--card); border: 2px dashed var(--border);
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; color: var(--muted); margin-top: 8px; overflow: hidden;
    transition: border-color .2s;
  }
  .cover-preview-box img { width: 100%; height: 100%; object-fit: cover; }
  .cover-preview-box.has-img { border-color: rgba(251,191,36,.3); }

  /* SHELF GRID */
  .shelf-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px; flex-wrap: wrap; gap: 10px;
  }
  .shelf-header h2 {
    font-family: 'Lora', serif; font-size: 20px; color: var(--text);
    display: flex; align-items: center; gap: 10px;
  }
  .shelf-header p  { font-size: 13px; color: var(--muted); margin-top: 3px; }
  .count-badge {
    background: var(--primary); color: #fff;
    font-size: 11px; font-weight: 700; padding: 2px 10px; border-radius: 20px;
  }

  .books-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(172px, 1fr)); gap: 18px; }

  .book-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius-lg); overflow: hidden;
    transition: transform .22s, box-shadow .22s, border-color .22s;
  }
  .book-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 16px 44px rgba(0,0,0,.55);
    border-color: rgba(251,191,36,.35);
  }

  .book-cover-area { width: 100%; height: 180px; overflow: hidden; position: relative; }
  .book-cover-area img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s; }
  .book-card:hover .book-cover-area img { transform: scale(1.05); }

  .genre-cover-wrap { position: relative; width: 100%; height: 180px; overflow: hidden; }
  .genre-cover-img {
    width: 100%; height: 100%; object-fit: cover;
    filter: brightness(.5) saturate(.65); transition: transform .3s, filter .3s;
  }
  .book-card:hover .genre-cover-img { transform: scale(1.05); filter: brightness(.65) saturate(1); }
  .genre-cover-label {
    position: absolute; bottom: 10px; left: 0; right: 0; text-align: center;
    font-size: 9.5px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase;
    color: rgba(255,255,255,.9); text-shadow: 0 1px 4px rgba(0,0,0,.8);
  }

  .book-info { padding: 12px 13px 10px; }
  .book-info .title {
    font-size: 13px; font-weight: 700; line-height: 1.35; margin-bottom: 3px; color: var(--text);
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
  }
  .book-info .author { font-size: 11.5px; color: var(--muted); margin-bottom: 7px; }
  .book-info .genre-pill {
    display: inline-block; font-size: 10px; font-weight: 600;
    padding: 2px 9px; border-radius: 20px;
    background: rgba(52,211,153,.08); color: var(--green); border: 1px solid rgba(52,211,153,.18);
  }

  .card-actions { padding: 8px 12px 13px; display: flex; gap: 7px; }
  .btn-edit-card {
    flex: 1; padding: 8px; font-size: 12.5px; border-radius: 8px;
    background: rgba(251,191,36,.12); color: var(--gold);
    border: 1px solid rgba(251,191,36,.25); font-weight: 700;
    cursor: pointer; transition: all .2s;
    display: flex; align-items: center; justify-content: center; gap: 5px;
  }
  .btn-edit-card:hover { background: var(--gold); color: #000; }
  .btn-trash-card {
    width: 34px; height: 34px; flex-shrink: 0;
    background: rgba(248,113,113,.1); color: var(--red);
    border: 1px solid rgba(248,113,113,.2); border-radius: 8px;
    font-size: 14px; cursor: pointer; transition: all .2s;
    display: flex; align-items: center; justify-content: center;
    text-decoration: none;
  }
  .btn-trash-card:hover { background: var(--red); color: #fff; }

  .empty-shelf { grid-column: 1/-1; text-align: center; padding: 70px 20px; color: var(--muted); }
  .empty-shelf .icon { font-size: 52px; margin-bottom: 14px; }
  .empty-shelf p { font-size: 15px; line-height: 1.7; }

  /* ── EDIT MODAL ── */
  .modal-overlay {
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(0,0,0,.78); backdrop-filter: blur(8px);
    display: none; align-items: center; justify-content: center; padding: 16px;
  }
  .modal-overlay.open { display: flex; animation: fadeIn .2s; }
  @keyframes fadeIn  { from { opacity: 0; } to { opacity: 1; } }
  @keyframes slideUp { from { transform: translateY(24px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

  .modal {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius-lg); width: 100%; max-width: 500px;
    box-shadow: 0 30px 80px rgba(0,0,0,.7);
    overflow: hidden; max-height: 93vh;
    display: flex; flex-direction: column;
    animation: slideUp .25s cubic-bezier(.34,1.1,.64,1);
  }

  .modal-header {
    background: var(--card); padding: 20px 24px;
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
    border-bottom: 1px solid var(--border); flex-shrink: 0;
  }
  .modal-header h3 { font-family: 'Lora', serif; font-size: 18px; color: var(--gold); }
  .modal-header p  { font-size: 12px; color: var(--muted); margin-top: 3px; }
  .modal-close {
    background: rgba(255,255,255,.06); border: 1px solid var(--border);
    color: var(--muted); width: 30px; height: 30px; border-radius: 8px;
    font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all .2s; flex-shrink: 0;
  }
  .modal-close:hover { background: rgba(255,255,255,.12); color: var(--text); }

  /* Preview strip inside modal */
  .modal-preview-strip {
    background: var(--card2); border-bottom: 1px solid var(--border);
    padding: 14px 24px; display: flex; gap: 14px; align-items: center; flex-shrink: 0;
  }
  .mps-thumb {
    width: 46px; height: 58px; border-radius: 7px; overflow: hidden;
    background: var(--card); flex-shrink: 0; display: flex; align-items: center; justify-content: center;
    font-size: 22px; border: 1px solid var(--border);
  }
  .mps-thumb img { width: 100%; height: 100%; object-fit: cover; }
  .mps-title  { font-size: 14px; font-weight: 700; color: var(--text); line-height: 1.3; }
  .mps-author { font-size: 12px; color: var(--muted); margin-top: 2px; }
  .mps-genre  {
    font-size: 10px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase;
    color: var(--gold); margin-top: 4px;
  }

  .modal-body { padding: 22px; overflow-y: auto; }

  .mfgroup { margin-bottom: 15px; }
  .mfgroup label {
    display: block; font-size: 11px; font-weight: 700;
    letter-spacing: .5px; text-transform: uppercase; color: var(--muted); margin-bottom: 6px;
  }
  .mfgroup input, .mfgroup select, .mfgroup textarea {
    width: 100%; padding: 11px 13px;
    background: var(--card); border: 1.5px solid var(--border);
    border-radius: 9px; color: var(--text); font-size: 14px; font-family: inherit;
    outline: none; transition: border-color .2s, box-shadow .2s;
  }
  .mfgroup input::placeholder, .mfgroup textarea::placeholder { color: var(--muted); opacity: .7; }
  .mfgroup input:focus, .mfgroup select:focus, .mfgroup textarea:focus {
    border-color: var(--gold); box-shadow: 0 0 0 3px rgba(251,191,36,.12);
  }
  .mfgroup select option { background: #1a2236; }
  .mfgroup textarea { resize: vertical; min-height: 68px; }

  .mfrow { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

  .modal-cover-preview {
    width: 100%; height: 88px; border-radius: 8px;
    background: var(--card); border: 2px dashed var(--border);
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; color: var(--muted); margin-top: 8px; overflow: hidden;
  }
  .modal-cover-preview img { width: 100%; height: 100%; object-fit: cover; }

  .modal-footer {
    padding: 14px 24px; border-top: 1px solid var(--border);
    display: flex; gap: 10px; flex-shrink: 0;
    background: var(--card);
  }

  @media (max-width: 900px) {
    .layout { grid-template-columns: 1fr; }
    .form-card { position: static; }
  }
  @media (max-width: 500px) {
    .mfrow { grid-template-columns: 1fr; }
  }

  .btn-active {
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.25);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.1), 0 0 12px rgba(255,255,255,.08);
    color: var(--text);
  }
</style>
</head>
<body>

<header>
  <div class="header-brand">
    <div class="header-logo">
      <img src="https://scontent.fceb2-1.fna.fbcdn.net/v/t1.15752-9/655639216_902892782620982_679713768952904685_n.jpg?_nc_cat=108&ccb=1-7&_nc_sid=9f807c&_nc_eui2=AeGtmif84tBQGVhqMSMLc7-H8N-b6gQgzqjw35vqBCDOqA57yzk-L4KqAaEZF7QcCi-yqMYBAM_i9VCiZSMGHXkC&_nc_ohc=31kf0tEGRjkQ7kNvwF0PHLr&_nc_oc=AdrpM6rGMxUpupabTUwe-OkO-k8tbbEOvbpi6w8pc-O0gD06va8h3qa-JNvSpmPDZ8w&_nc_zt=23&_nc_ht=scontent.fceb2-1.fna&_nc_ss=7a3a8&oh=03_Q7cD5AGhronN_QuIoMGtujo28WGDJarngAlGnddgEQQXda_DrA&oe=6A0D97F4"
       alt="LRC Logo" style="width:100%; height:100%; object-fit:cover; border-radius:10px;">
    </div>
    <div>
      <div class="header-title">Shelf Manager</div>
      <div class="header-sub">Librarian Panel — Manage the student kiosk display</div>
    </div>
  </div>
  <div class="header-nav">
    <a href="index.php" class="btn btn-ghost">Book Records</a>
    <a href="borrow.php"  class="btn btn-ghost">Student Kiosk</a>
    <a href="shelves.php" class="btn btn-ghost btn-active">Shelf Manager</a>
  </div>
</header>

<div class="container">

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <div><strong>Please fix the following:</strong>
        <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
      </div>
    </div>
  <?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'removed'): ?><div class="alert alert-danger">Book removed from shelf display.</div><?php endif; ?>
    <?php if ($_GET['msg'] === 'updated'): ?><div class="alert alert-success">Book updated successfully.</div><?php endif; ?>
  <?php endif; ?>

  <div class="layout">

    <!-- ADD FORM -->
    <div class="form-card">
      <div class="form-card-header">
        <h2>Add Book to Shelf</h2>
        <p>Books added here appear on the student kiosk</p>
      </div>
      <div class="form-body">
        <form method="POST" action="shelves.php">
          <input type="hidden" name="action" value="add_shelf">

          <div class="fgroup">
              <label>Stock / Copies</label>
              <input type="number" name="shelf_stock" min="1" value="1" placeholder="e.g. 3">
          </div>

          <div class="fgroup">
            <label>Book Title *</label>
            <input type="text" name="shelf_title" placeholder="e.g. To Kill a Mockingbird" required autocomplete="off">
          </div>
          <div class="fgroup">
            <label>Author *</label>
            <input type="text" name="shelf_author" placeholder="e.g. Harper Lee" required autocomplete="off">
          </div>
          <div class="fgroup">
            <label>Genre *</label>
            <select name="shelf_genre" id="addGenreSelect" required onchange="updateAddGenrePreview()">
              <option value="">— Select Genre —</option>
              <?php foreach ($genres as $g) echo "<option value=\"$g\">$g</option>"; ?>
            </select>
          </div>
          <div class="fgroup">
            <label>Cover Image URL <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:10px;color:var(--muted)">(optional)</span></label>
            <input type="url" name="shelf_cover" id="addCoverInput" placeholder="https://…/cover.jpg" oninput="previewCover(this.value,'addCoverPreview')">
            <div class="hint">Paste a direct link to a book cover image</div>
            <div class="cover-preview-box" id="addCoverPreview"><span>No image yet</span></div>
          </div>
          <div class="fgroup">
            <label>Short Description <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:10px;color:var(--muted)">(optional)</span></label>
            <textarea name="shelf_desc" placeholder="Brief synopsis or librarian notes…"></textarea>
          </div>

          <button type="submit" class="btn btn-gold" style="width:100%;padding:13px;font-size:14px;">
            Add to Shelf Display
          </button>
        </form>
      </div>
    </div>

    <!-- SHELF DISPLAY -->
    <div>
      <div class="shelf-header">
        <div>
          <h2>Current Shelf <span class="count-badge"><?= $shelf_count ?></span></h2>
          <p>Books visible to students on the kiosk</p>
        </div>
        <?php if ($shelf_count > 0): ?>
          <a href="borrow.php" class="btn btn-primary">Preview Kiosk →</a>
        <?php endif; ?>
      </div>

      <div class="books-grid">
        <?php if ($shelf_count === 0): ?>
          <div class="empty-shelf">
            <div class="icon"></div>
            <p>No books on the shelf yet.<br>Use the form on the left to add books.</p>
          </div>
        <?php else: ?>
          <?php foreach ($shelf_books as $shelf):
            $fallbackImg = $genreImage[$shelf['shelf_genre']] ?? $genreImage['Other'];
          ?>
          <div class="book-card">
            <div class="book-cover-area">
              <?php if (!empty($shelf['shelf_cover'])): ?>
                <img src="<?= htmlspecialchars($shelf['shelf_cover']) ?>"
                     alt="<?= htmlspecialchars($shelf['shelf_title']) ?>"
                     onerror="this.parentNode.innerHTML='<div class=\'genre-cover-wrap\'><img class=\'genre-cover-img\' src=\'<?= htmlspecialchars($fallbackImg, ENT_QUOTES) ?>\' alt=\'cover\'><div class=\'genre-cover-label\'><?= htmlspecialchars($shelf['shelf_genre'], ENT_QUOTES) ?></div></div>'">
              <?php else: ?>
                <div class="genre-cover-wrap">
                  <img class="genre-cover-img" src="<?= htmlspecialchars($fallbackImg) ?>" alt="">
                  <div class="genre-cover-label"><?= htmlspecialchars($shelf['shelf_genre']) ?></div>
                </div>
              <?php endif; ?>
            </div>

            <div class="book-info">
              <div class="title"><?= htmlspecialchars($shelf['shelf_title']) ?></div>
              <div class="author">by <?= htmlspecialchars($shelf['shelf_author']) ?></div>
              <span class="genre-pill"><?= htmlspecialchars($shelf['shelf_genre']) ?></span>
            </div>

            <div class="card-actions">
              <button class="btn-edit-card edit-btn"
                      data-id="<?= $shelf['id'] ?>"
                      data-title="<?= htmlspecialchars($shelf['shelf_title'],  ENT_QUOTES) ?>"
                      data-author="<?= htmlspecialchars($shelf['shelf_author'], ENT_QUOTES) ?>"
                      data-genre="<?= htmlspecialchars($shelf['shelf_genre'],  ENT_QUOTES) ?>"
                      data-cover="<?= htmlspecialchars($shelf['shelf_cover']  ?? '', ENT_QUOTES) ?>"
                      data-desc="<?= htmlspecialchars($shelf['shelf_desc']    ?? '', ENT_QUOTES) ?>"
                      data-stock="<?= (int)($shelf['shelf_stock'] ?? 1) ?>">
                Edit
              </button>
              <a href="shelves.php?remove=<?= $shelf['id'] ?>"
                 class="btn-trash-card"
                 title="Remove from shelf"
                 onclick="return confirm('Remove \"<?= htmlspecialchars($shelf['shelf_title'], ENT_QUOTES) ?>\" from the shelf?')">
                🗑️
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<!-- ── EDIT MODAL ── -->
<div id="editModal" class="modal-overlay">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-header">
      <div>
        <h3>Edit Book</h3>
        <p>Update book details for the shelf display</p>
      </div>
      <button class="modal-close" id="btnModalClose">✕</button>
    </div>

    <!-- Live preview strip -->
    <div class="modal-preview-strip">
      <div class="mps-thumb" id="mpsThumb">📖</div>
      <div>
        <div class="mps-genre"  id="mpsGenre">—</div>
        <div class="mps-title"  id="mpsTitle">—</div>
        <div class="mps-author" id="mpsAuthor">—</div>
      </div>
    </div>

    <div class="modal-body">
      <form method="POST" action="shelves.php" id="editForm">
        <input type="hidden" name="action"  value="edit_shelf">
        <input type="hidden" name="edit_id" id="eId">

        <div class="mfrow">
          <div class="mfgroup">
            <label>Book Title *</label>
            <input type="text" name="shelf_title" id="eTitle" placeholder="Book title" required oninput="livePreview()">
          </div>
          <div class="mfgroup">
            <label>Author *</label>
            <input type="text" name="shelf_author" id="eAuthor" placeholder="Author name" required oninput="livePreview()">
          </div>
        </div>

        <div class="mfgroup">
          <label>Genre *</label>
          <select name="shelf_genre" id="eGenre" required onchange="livePreview()">
            <option value="">— Select Genre —</option>
            <?php foreach ($genres as $g): ?>
              <option value="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mfgroup">
          <label>Stock / Copies</label>
          <input type="number" name="shelf_stock" id="eStock" min="1" placeholder="e.g. 3">
        </div>

        <div class="mfgroup">
          <label>Cover Image URL <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:10px;color:var(--muted)">(optional)</span></label>
          <input type="url" name="shelf_cover" id="eCover" placeholder="https://…/cover.jpg"
                 oninput="previewCover(this.value,'editCoverPreview'); livePreview()">
          <div class="modal-cover-preview" id="editCoverPreview"><span>No cover image</span></div>
        </div>

        <div class="mfgroup">
          <label>Short Description <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:10px;color:var(--muted)">(optional)</span></label>
          <textarea name="shelf_desc" id="eDesc" placeholder="Brief synopsis or librarian notes…"></textarea>
        </div>
      </form>
    </div>

    <div class="modal-footer">
      <button type="submit" form="editForm" class="btn btn-gold" style="flex:1;padding:12px;font-size:14px;">
        Save Changes
      </button>
      <button type="button" class="btn btn-ghost" id="btnModalCancel">Cancel</button>
    </div>
  </div>
</div>

<script>
const genreImages = <?= json_encode($genreImage) ?>;

/* ── Cover preview helper ── */
function previewCover(url, previewId) {
  const el = document.getElementById(previewId);
  if (!el) return;
  if (url && url.trim()) {
    const img = new Image();
    img.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block;';
    img.onload  = () => { el.innerHTML = ''; el.appendChild(img); el.classList.add('has-img'); };
    img.onerror = () => { el.innerHTML = '<span>⚠ Invalid image URL</span>'; el.classList.remove('has-img'); };
    img.src = url;
  } else {
    el.innerHTML = '<span>No cover image</span>';
    el.classList.remove('has-img');
  }
}

/* ── Add form: genre default cover ── */
function updateAddGenrePreview() {
  const g = document.getElementById('addGenreSelect').value;
  const coverInput = document.getElementById('addCoverInput');
  if (!coverInput.value && g) {
    const fallback = genreImages[g] || '';
    previewCover(fallback, 'addCoverPreview');
  }
}

/* ── Edit modal live preview ── */
function livePreview() {
  const title  = document.getElementById('eTitle').value  || '—';
  const author = document.getElementById('eAuthor').value || '—';
  const genre  = document.getElementById('eGenre').value  || '—';
  const cover  = document.getElementById('eCover').value;

  document.getElementById('mpsTitle').textContent  = title;
  document.getElementById('mpsAuthor').textContent = 'by ' + author;
  document.getElementById('mpsGenre').textContent  = genre;

  const thumb = document.getElementById('mpsThumb');
  if (cover && cover.trim()) {
    const img = new Image();
    img.style.cssText = 'width:100%;height:100%;object-fit:cover;';
    img.onload  = () => { thumb.innerHTML = ''; thumb.appendChild(img); };
    img.onerror = () => { thumb.textContent = '📖'; };
    img.src = cover;
  } else {
    thumb.textContent = '📖';
  }
}

/* ── Modal open/close ── */
const modal = document.getElementById('editModal');

function openModal() {
  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal() {
  modal.classList.remove('open');
  document.body.style.overflow = '';
}


document.getElementById('btnModalClose').addEventListener('click',  closeModal);
document.getElementById('btnModalCancel').addEventListener('click', closeModal);
modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.classList.contains('open')) closeModal(); });

/* ── Edit buttons ── */
document.querySelectorAll('.edit-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const d = btn.dataset;

    document.getElementById('eId').value     = d.id     || '';
    document.getElementById('eTitle').value  = d.title  || '';
    document.getElementById('eAuthor').value = d.author || '';
    document.getElementById('eDesc').value   = d.desc   || '';
    document.getElementById('eCover').value  = d.cover  || '';
    document.getElementById('eStock').value  = d.stock  || '1';

    const sel = document.getElementById('eGenre');
    for (let i = 0; i < sel.options.length; i++) sel.options[i].selected = sel.options[i].value === d.genre;

    previewCover(d.cover || '', 'editCoverPreview');
    livePreview();
    openModal();

    setTimeout(() => document.getElementById('eTitle').focus(), 120);
  });
});
</script>
</body>
</html>