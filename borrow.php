<?php
include 'db.php';

$errors              = [];
$success             = '';
$carousel_books      = [];
$most_borrowed_title = '';
$max_borrows         = -1;

$pre_title  = trim($_GET['title']  ?? '');
$pre_author = trim($_GET['author'] ?? '');
$pre_genre  = trim($_GET['genre']  ?? '');
$show_form  = isset($_GET['title']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'borrow') {
    $book_title     = trim($_POST['book_title']     ?? '');
    $book_author    = trim($_POST['book_author']    ?? '');
    $book_genre     = trim($_POST['book_genre']     ?? '');
    $borrowed_date  = trim($_POST['borrowed_date']  ?? '');
    $duedate        = trim($_POST['duedate']        ?? '');
    $student_name   = trim($_POST['student_name']   ?? '');
    $student_block  = trim($_POST['student_block']  ?? '');
    $student_number = trim($_POST['student_number'] ?? '');

    if ($book_title     === '') $errors[] = 'Book title is required.';
    if ($book_author    === '') $errors[] = 'Author is required.';
    if ($book_genre     === '') $errors[] = 'Genre is required.';
    if ($borrowed_date  === '') $errors[] = 'Borrowed date is required.';
    if ($duedate        === '') $errors[] = 'Due date is required.';
    if ($student_name   === '') $errors[] = 'Student name is required.';
    if ($student_block  === '') $errors[] = 'Block / Section is required.';
    if ($student_number === '') $errors[] = 'Student number is required.';

    if (empty($errors)) {
        $t   = mysqli_real_escape_string($conn, $book_title);
        $a   = mysqli_real_escape_string($conn, $book_author);
        $g   = mysqli_real_escape_string($conn, $book_genre);
        $bd  = mysqli_real_escape_string($conn, $borrowed_date);
        $dd  = mysqli_real_escape_string($conn, $duedate);
        $sn  = mysqli_real_escape_string($conn, $student_name);
        $sb  = mysqli_real_escape_string($conn, $student_block);
        $sno = mysqli_real_escape_string($conn, $student_number);

        $stock_result = mysqli_query($conn, "SELECT shelf_stock FROM shelf_books WHERE shelf_title = '$t' LIMIT 1");
        $stock_row    = mysqli_fetch_assoc($stock_result);
        if ($stock_row && (int)$stock_row['shelf_stock'] <= 0) {
            $errors[] = 'Sorry, this book is currently out of stock.';
        }
    }

    if (empty($errors)) {
        $sql = "INSERT INTO books (book_title, book_author, book_genre, book_status, borrowed_date, duedate, student_name, student_block, student_number)
                VALUES ('$t','$a','$g','Borrowed','$bd','$dd','$sn','$sb','$sno')";
        if (mysqli_query($conn, $sql)) {
            mysqli_query($conn, "UPDATE shelf_books SET shelf_stock = shelf_stock - 1 WHERE shelf_title = '$t' AND shelf_stock > 0");
            $success   = "\"$book_title\" has been borrowed successfully!";
            $show_form = false;
            $pre_title = $pre_author = $pre_genre = '';
        } else {
            $errors[] = 'Database error: ' . mysqli_error($conn);
        }
    } else {
        $show_form  = true;
        $pre_title  = $_POST['book_title']  ?? '';
        $pre_author = $_POST['book_author'] ?? '';
        $pre_genre  = $_POST['book_genre']  ?? '';
    }
}

// Runs on every page load
$genreEmoji = [
    'Fiction'         => '📖', 'Non-Fiction'     => '📰',
    'Science Fiction' => '🚀', 'Fantasy'         => '🧙',
    'Mystery'         => '🔍', 'Thriller'        => '😱',
    'Romance'         => '💕', 'Horror'          => '👻',
    'Biography'       => '👤', 'History'         => '🏛️',
    'Self-Help'       => '💪', 'Science'         => '🔬',
    'Technology'      => '💻', 'Philosophy'      => '🤔',
    'Poetry'          => '✍️', 'Children'        => '🌈',
    'Young Adult'     => '🌟', 'Reference'       => '📚',
    'Textbook'        => '🎓', 'Other'           => '📄',
];

$shelf_result = mysqli_query($conn, "SELECT * FROM shelf_books ORDER BY shelf_title ASC");
$shelf_count  = mysqli_num_rows($shelf_result);
$shelf_books  = [];
while ($row = mysqli_fetch_assoc($shelf_result)) $shelf_books[] = $row;

$borrow_counts = [];
$bc_result = mysqli_query($conn, "SELECT book_title, COUNT(*) as borrow_count FROM books GROUP BY book_title ORDER BY borrow_count DESC");
if ($bc_result) {
    while ($bc_row = mysqli_fetch_assoc($bc_result)) {
        $borrow_counts[$bc_row['book_title']] = (int)$bc_row['borrow_count'];
    }
}

foreach ($shelf_books as &$sb) {
    $sb['borrow_count'] = $borrow_counts[$sb['shelf_title']] ?? 0;
    if ($sb['borrow_count'] > $max_borrows) {
        $max_borrows         = $sb['borrow_count'];
        $most_borrowed_title = $sb['shelf_title'];
    }
}
unset($sb);

usort($shelf_books, function($a, $b) { return $b['borrow_count'] - $a['borrow_count']; });

if ($max_borrows > 0) {
    $borrowed_books = array_values(array_filter($shelf_books, function($s) { return $s['borrow_count'] > 0; }));
    usort($borrowed_books, function($a, $b) { return $b['borrow_count'] - $a['borrow_count']; });
    $center     = array_shift($borrowed_books);
    $left_side  = [];
    $right_side = [];
    foreach ($borrowed_books as $i => $book) {
        if ($i % 2 === 0) $right_side[] = $book;
        else              $left_side[]  = $book;
    }
    $left_side      = array_reverse($left_side);
    $carousel_books = array_merge($left_side, [$center], $right_side);
} else {
    $carousel_books = [];
}

$genres      = array_unique(array_column($shelf_books, 'shelf_genre'));
sort($genres);
$today       = date('Y-m-d');
$default_due = date('Y-m-d', strtotime('+7 days'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book Borrowing — LRC Kiosk</title>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:#0b0f1a; --surface:#111827; --card:#1a2236; --card2:#1e2a40;
    --border:rgba(255,255,255,0.08); --primary:#4f8ef7;
    --primary-glow:rgba(79,142,247,0.18); --green:#34d399;
    --gold:#fbbf24; --red:#f87171; --text:#f0f4ff;
    --muted:#6b7a99; --subtle:#2a3650; --radius:12px; --radius-lg:18px;
  }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
  header { background:var(--surface); border-bottom:1px solid var(--border); padding:16px 32px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; position:sticky; top:0; z-index:100; backdrop-filter:blur(12px); }
  .header-brand { display:flex; align-items:center; gap:12px; }
  .header-logo  { width:42px; height:42px; border-radius:10px; overflow:hidden; flex-shrink:0; }
  .header-title { font-family:'Lora',serif; font-size:20px; font-weight:700; color:var(--text); }
  .header-sub   { font-size:11.5px; color:var(--muted); margin-top:1px; }
  .header-nav   { display:flex; gap:8px; }
  .btn { display:inline-flex; align-items:center; gap:7px; padding:9px 18px; border:none; border-radius:9px; font-size:13.5px; font-weight:600; font-family:inherit; cursor:pointer; text-decoration:none; transition:all .2s; white-space:nowrap; }
  .btn:hover   { transform:translateY(-1px); filter:brightness(1.1); }
  .btn-primary { background:var(--primary); color:#fff; }
  .btn-ghost   { background:rgba(255,255,255,.06); color:var(--text); border:1px solid var(--border); }
  .btn-ghost:hover { background:rgba(255,255,255,.1); }
  .btn-active  { background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.25); box-shadow:inset 0 0 0 1px rgba(255,255,255,.1),0 0 12px rgba(255,255,255,.08); color:var(--text); }
  .container { max-width:1300px; margin:0 auto; padding:32px 20px; overflow:visible; }
  .success-banner { background:rgba(52,211,153,.08); border:1px solid rgba(52,211,153,.25); border-radius:var(--radius); padding:16px 20px; margin-bottom:28px; display:flex; align-items:center; gap:14px; }
  .success-icon  { width:40px; height:40px; border-radius:50%; background:rgba(52,211,153,.15); display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
  .success-title { font-size:15px; font-weight:600; color:var(--green); }
  .success-sub   { font-size:13px; color:var(--muted); margin-top:2px; }
  .kiosk-hero { text-align:center; margin-bottom:32px; }
  .kiosk-hero h2 { font-family:'Lora',serif; font-size:30px; font-weight:700; color:var(--text); margin-bottom:8px; }
  .kiosk-hero p  { font-size:15px; color:var(--muted); }
  .search-wrap { max-width:520px; margin:20px auto 0; position:relative; }
  .search-wrap .search-icon { position:absolute; left:16px; top:50%; transform:translateY(-50%); font-size:16px; pointer-events:none; }
  .search-wrap input { width:100%; padding:13px 56px 13px 46px; background:var(--surface); border:1.5px solid var(--border); border-radius:50px; color:var(--text); font-size:14.5px; font-family:inherit; outline:none; transition:border-color .2s,box-shadow .2s; }
  .search-wrap input::placeholder { color:var(--muted); }
  .search-wrap input:focus { border-color:var(--primary); box-shadow:0 0 0 4px var(--primary-glow); }
  .search-clear { position:absolute; right:14px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--muted); font-size:18px; cursor:pointer; display:none; padding:4px; line-height:1; }
  .genre-tabs { display:flex; gap:7px; flex-wrap:wrap; justify-content:center; margin-bottom:28px; }
  .genre-tab  { padding:6px 15px; border-radius:20px; font-size:12.5px; font-weight:600; border:1.5px solid var(--border); background:var(--surface); color:var(--muted); cursor:pointer; transition:all .2s; user-select:none; }
  .genre-tab:hover  { border-color:var(--primary); color:var(--primary); background:var(--primary-glow); }
  .genre-tab.active { background:var(--primary); color:#fff; border-color:var(--primary); }
  .books-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(175px,1fr)); gap:20px; }
  .book-card  { background:var(--card); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; cursor:pointer; transition:transform .25s,box-shadow .25s,border-color .25s; position:relative; }
  .book-card:hover { transform:translateY(-6px) scale(1.015); box-shadow:0 20px 50px rgba(0,0,0,.55),0 0 0 1px var(--primary); border-color:var(--primary); }
  .book-cover { height:195px; position:relative; overflow:hidden; }
  .book-cover img { width:100%; height:100%; object-fit:cover; transition:transform .3s; }
  .book-card:hover .book-cover img { transform:scale(1.07); }
  .cover-placeholder { width:100%; height:195px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; background:linear-gradient(160deg,var(--card2),var(--bg)); }
  .cp-emoji { font-size:50px; }
  .cp-genre { font-size:9.5px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; padding:3px 10px; border-radius:20px; background:var(--primary-glow); color:var(--primary); border:1px solid rgba(79,142,247,.3); }
  .borrow-hint { position:absolute; inset:0; background:linear-gradient(to top,rgba(79,142,247,.85) 0%,transparent 55%); display:flex; align-items:flex-end; justify-content:center; padding-bottom:12px; opacity:0; transition:opacity .25s; }
  .borrow-hint span { font-size:11.5px; font-weight:700; color:#fff; letter-spacing:.6px; text-transform:uppercase; }
  .book-card:hover .borrow-hint { opacity:1; }
  .book-meta { padding:13px 14px 15px; }
  .book-meta .bm-title  { font-size:13.5px; font-weight:700; line-height:1.35; margin-bottom:3px; color:var(--text); display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
  .book-meta .bm-author { font-size:12px; color:var(--muted); margin-bottom:8px; }
  .book-meta .bm-genre  { font-size:10.5px; font-weight:600; padding:2px 9px; border-radius:20px; background:rgba(52,211,153,.08); color:var(--green); border:1px solid rgba(52,211,153,.18); display:inline-block; }
  .empty-kiosk { text-align:center; padding:90px 20px; color:var(--muted); }
  .empty-kiosk .e-icon { font-size:64px; margin-bottom:16px; }
  .empty-kiosk h3 { font-size:19px; color:var(--text); margin-bottom:8px; font-family:'Lora',serif; }
  .empty-kiosk p  { font-size:14px; }
  .no-results { grid-column:1/-1; text-align:center; padding:50px 20px; color:var(--muted); font-size:14px; }
  .carousel-section { margin-bottom:36px; }
  .carousel-section h3 { font-family:'Lora',serif; font-size:18px; font-weight:700; color:var(--text); margin-bottom:16px; display:flex; align-items:center; gap:10px; }
  .carousel-wrap { position:relative; padding:24px 40px; margin:0 -40px; }
  .carousel-track-outer { overflow:visible; }
  .carousel-track { display:flex; gap:16px; transition:transform .4s cubic-bezier(.25,.8,.25,1); will-change:transform; }
  .carousel-item { flex:0 0 140px; cursor:pointer; border-radius:var(--radius-lg); overflow:visible; position:relative; height:210px; border:1px solid var(--border); transition:transform .25s,box-shadow .25s; }
  .carousel-item:hover { transform:translateY(-10px) scale(1.05); box-shadow:0 24px 56px rgba(0,0,0,.65); z-index:10; }
  .carousel-item.featured { flex:0 0 170px; height:240px; border:1px solid rgba(251,191,36,.4); box-shadow:0 0 20px rgba(251,191,36,.12); }
  .carousel-item.featured:hover { transform:translateY(-10px) scale(1.05); }
  .carousel-item img { width:100%; height:100%; object-fit:cover; border-radius:var(--radius-lg); display:block; }
  .carousel-placeholder { width:100%; height:100%; border-radius:var(--radius-lg); display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; background:linear-gradient(160deg,var(--card2),var(--bg)); }
  .carousel-overlay { position:absolute; inset:0; border-radius:var(--radius-lg); background:linear-gradient(to top,rgba(0,0,0,.88) 0%,transparent 55%); display:flex; flex-direction:column; justify-content:flex-end; padding:12px 10px; pointer-events:none; }
  .carousel-overlay .co-title  { font-size:12px; font-weight:700; color:#fff; line-height:1.3; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
  .carousel-overlay .co-author { font-size:10.5px; color:rgba(255,255,255,.6); margin-top:3px; }
  .carousel-btn { position:absolute; top:50%; transform:translateY(-50%); width:36px; height:36px; border-radius:50%; background:rgba(15,26,46,.9); border:1px solid var(--border); color:var(--text); font-size:18px; cursor:pointer; display:flex; align-items:center; justify-content:center; z-index:20; transition:all .2s; backdrop-filter:blur(6px); }
  .carousel-btn:hover { background:var(--primary); border-color:var(--primary); }
  .carousel-btn.prev { left:0; }
  .carousel-btn.next { right:0; }
  .modal-overlay { display:none; position:fixed; inset:0; z-index:1000; background:rgba(0,0,0,.75); backdrop-filter:blur(8px); align-items:center; justify-content:center; padding:16px; }
  .modal-overlay.open { display:flex; animation:fadeIn .2s; }
  @keyframes fadeIn  { from{opacity:0} to{opacity:1} }
  @keyframes slideUp { from{transform:translateY(28px) scale(.98);opacity:0} to{transform:translateY(0) scale(1);opacity:1} }
  .modal { background:var(--surface); border:1px solid var(--border); border-radius:20px; width:100%; max-width:540px; box-shadow:0 30px 90px rgba(0,0,0,.7); animation:slideUp .28s cubic-bezier(.34,1.1,.64,1); max-height:95vh; display:flex; flex-direction:column; overflow:hidden; }
  .modal-book-banner { position:relative; padding:22px 24px 18px; background:linear-gradient(135deg,#1a2744,#0f1a2e); border-bottom:1px solid var(--border); display:flex; gap:16px; align-items:flex-start; flex-shrink:0; }
  .mbb-thumb  { width:56px; height:72px; border-radius:8px; overflow:hidden; background:var(--card2); flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:26px; border:1px solid var(--border); }
  .mbb-thumb img { width:100%; height:100%; object-fit:cover; }
  .mbb-genre  { font-size:10px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--primary); margin-bottom:5px; }
  .mbb-title  { font-family:'Lora',serif; font-size:17px; font-weight:700; line-height:1.3; color:var(--text); margin-bottom:3px; }
  .mbb-author { font-size:12.5px; color:var(--muted); }
  .modal-close { position:absolute; top:16px; right:18px; background:rgba(255,255,255,.06); border:1px solid var(--border); color:var(--muted); width:30px; height:30px; border-radius:8px; font-size:16px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all .2s; line-height:1; }
  .modal-close:hover { background:rgba(255,255,255,.12); color:var(--text); }
  .modal-steps { display:flex; gap:0; border-bottom:1px solid var(--border); padding:0 24px; flex-shrink:0; background:var(--surface); }
  .step-tab { padding:13px 0; margin-right:24px; font-size:12px; font-weight:600; color:var(--muted); border-bottom:2px solid transparent; cursor:default; user-select:none; transition:all .2s; display:flex; align-items:center; gap:7px; white-space:nowrap; }
  .step-tab.active { color:var(--primary); border-bottom-color:var(--primary); }
  .step-tab.done   { color:var(--green); }
  .step-num { width:20px; height:20px; border-radius:50%; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; background:var(--subtle); color:var(--muted); flex-shrink:0; }
  .step-tab.active .step-num { background:var(--primary); color:#fff; }
  .step-tab.done   .step-num { background:var(--green); color:#000; }
  .modal-body { padding:24px; overflow-y:auto; flex:1; }
  .step-panel { display:none; }
  .step-panel.active { display:block; animation:fadeSlide .2s ease; }
  @keyframes fadeSlide { from{opacity:0;transform:translateX(10px)} to{opacity:1;transform:translateX(0)} }
  .step-heading { font-size:13.5px; font-weight:700; color:var(--text); margin-bottom:16px; display:flex; align-items:center; gap:8px; }
  .field-row   { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
  .field-group { margin-bottom:15px; }
  .field-group label { display:flex; align-items:center; justify-content:space-between; font-size:11.5px; font-weight:600; letter-spacing:.3px; text-transform:uppercase; color:var(--muted); margin-bottom:7px; }
  .field-group label .req { color:var(--primary); font-size:13px; font-weight:700; }
  .field-group input,.field-group select { width:100%; padding:11px 14px; background:var(--card); border:1.5px solid var(--border); border-radius:9px; color:var(--text); font-size:14px; font-family:inherit; outline:none; transition:border-color .2s,box-shadow .2s,background .2s; }
  .field-group input::placeholder { color:var(--muted); opacity:.7; }
  .field-group input:focus,.field-group select:focus { border-color:var(--primary); box-shadow:0 0 0 3px var(--primary-glow); background:var(--card2); }
  .field-group select option { background:#1a2236; }
  .date-hint { font-size:11.5px; color:var(--muted); margin-top:5px; }
  .date-range-viz { background:var(--card); border:1px solid var(--border); border-radius:10px; padding:14px 16px; margin-bottom:16px; display:flex; align-items:center; }
  .drv-item { flex:1; text-align:center; }
  .drv-item .drv-label { font-size:10px; text-transform:uppercase; letter-spacing:.8px; color:var(--muted); font-weight:600; margin-bottom:4px; }
  .drv-item .drv-date  { font-size:15px; font-weight:700; color:var(--text); }
  .drv-item .drv-sub   { font-size:11px; color:var(--muted); margin-top:2px; }
  .drv-sep  { flex:none; padding:0 12px; color:var(--muted); font-size:18px; font-weight:300; }
  .drv-days { background:var(--primary-glow); border-radius:20px; padding:2px 10px; font-size:11.5px; font-weight:700; color:var(--primary); text-align:center; margin-top:4px; white-space:nowrap; }
  .modal-errors { background:rgba(248,113,113,.08); border:1px solid rgba(248,113,113,.25); border-radius:9px; padding:12px 14px; margin-bottom:16px; font-size:13px; color:#fca5a5; }
  .modal-errors ul { margin:6px 0 0 16px; }
  .modal-nav { display:flex; gap:10px; padding:0 24px 22px; flex-shrink:0; }
  .modal-nav .btn { flex:1; justify-content:center; padding:13px; font-size:14px; }
  .btn-next   { background:var(--primary); color:#fff; }
  .btn-prev   { background:rgba(255,255,255,.06); color:var(--text); border:1px solid var(--border); flex:none; padding:13px 18px; }
  .btn-submit { background:linear-gradient(135deg,var(--green),#059669); color:#000; font-size:15px; font-weight:700; }
  .summary-card { background:var(--card); border:1px solid var(--border); border-radius:10px; overflow:hidden; margin-bottom:14px; }
  .sc-header { background:var(--card2); padding:10px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:var(--muted); border-bottom:1px solid var(--border); }
  .sc-body   { padding:14px 16px; }
  .sc-row    { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; padding:7px 0; border-bottom:1px solid var(--border); }
  .sc-row:last-child { border-bottom:none; }
  .sc-key    { font-size:12px; color:var(--muted); flex:none; width:110px; }
  .sc-val    { font-size:13px; font-weight:600; color:var(--text); text-align:right; }
  .sc-badge  { display:inline-block; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; background:rgba(251,191,36,.12); color:var(--gold); border:1px solid rgba(251,191,36,.25); }
  @media (max-width:560px) {
    .field-row { grid-template-columns:1fr; }
    .modal { border-radius:16px; }
    .modal-book-banner { flex-direction:column; }
    .date-range-viz { flex-direction:column; gap:10px; }
    .drv-sep { display:none; }
    .carousel-wrap { padding:24px 30px; margin:0 -30px; }
  }
</style>
</head>
<body>

<header>
  <div class="header-brand">
    <div class="header-logo">
      <img src="https://scontent.fceb2-1.fna.fbcdn.net/v/t1.15752-9/655639216_902892782620982_679713768952904685_n.jpg?_nc_cat=108&ccb=1-7&_nc_sid=9f807c&_nc_eui2=AeGtmif84tBQGVhqMSMLc7-H8N-b6gQgzqjw35vqBCDOqA57yzk-L4KqAaEZF7QcCi-yqMYBAM_i9VCiZSMGHXkC&_nc_ohc=31kf0tEGRjkQ7kNvwF0PHLr&_nc_oc=AdrpM6rGMxUpupabTUwe-OkO-k8tbbEOvbpi6w8pc-O0gD06va8h3qa-JNvSpmPDZ8w&_nc_zt=23&_nc_ht=scontent.fceb2-1.fna&_nc_ss=7a3a8&oh=03_Q7cD5AGhronN_QuIoMGtujo28WGDJarngAlGnddgEQQXda_DrA&oe=6A0D97F4"
           alt="LRC Logo" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">
    </div>
    <div>
      <div class="header-title">LRC Kiosk</div>
      <div class="header-sub">Learning Resource Center — Tap a book to borrow</div>
    </div>
  </div>
  <div class="header-nav">
    <a href="index.php"   class="btn btn-ghost">📋 Book Record</a>
    <a href="borrow.php"  class="btn btn-ghost btn-active">📚 Student Kiosk</a>
    <a href="shelves.php" class="btn btn-ghost">🗄️ Shelf Manager</a>
  </div>
</header>

<div class="container">

  <?php if ($success): ?>
    <div class="success-banner">
      <div class="success-icon">✅</div>
      <div>
        <div class="success-title"><?= htmlspecialchars($success) ?></div>
        <div class="success-sub">The borrow record has been saved. Return by your due date.</div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($shelf_count === 0): ?>
    <div class="empty-kiosk">
      <div class="e-icon">📭</div>
      <h3>No Books on Display Yet</h3>
      <p>Ask the librarian to add books to the shelf display.</p>
      <a href="shelves.php" class="btn btn-primary" style="margin-top:20px">Go to Shelf Manager →</a>
    </div>

  <?php else: ?>

    <div class="kiosk-hero">
      <h2>What would you like to borrow today?</h2>
      <p>Browse the collection below and tap any book to get started</p>
      <div class="search-wrap">
        <span class="search-icon">🔍</span>
        <input type="text" id="searchInput" placeholder="Search by title, author, or genre…"
               oninput="filterBooks()" autocomplete="off">
        <button class="search-clear" id="searchClear" onclick="clearSearch()">✕</button>
      </div>
    </div>

    <?php if (count($carousel_books) > 0): ?>
    <div class="carousel-section" id="carouselSection">
      <h3>🔥 Most Popular</h3>
      <div class="carousel-wrap">
        <?php if (count($carousel_books) > 1): ?>
          <button class="carousel-btn prev" onclick="carouselMove(-1)">‹</button>
        <?php endif; ?>
        <div class="carousel-track-outer">
          <div class="carousel-track" id="carouselTrack">
            <?php foreach ($carousel_books as $sb):
              $emoji      = $genreEmoji[$sb['shelf_genre']] ?? '📚';
              $isFeatured = trim(strtolower($sb['shelf_title'])) === trim(strtolower($most_borrowed_title));
              $safeTitle  = htmlspecialchars($sb['shelf_title'],  ENT_QUOTES);
              $safeAuthor = htmlspecialchars($sb['shelf_author'], ENT_QUOTES);
              $safeGenre  = htmlspecialchars($sb['shelf_genre'],  ENT_QUOTES);
              $safeCover  = htmlspecialchars($sb['shelf_cover']  ?? '', ENT_QUOTES);
              $safeDesc   = htmlspecialchars($sb['shelf_desc']   ?? '', ENT_QUOTES);
              $stock      = (int)($sb['shelf_stock'] ?? 1);
            ?>
            <div class="carousel-item <?= $isFeatured ? 'featured' : '' ?>"
                 data-title="<?= $safeTitle ?>"
                 data-author="<?= $safeAuthor ?>"
                 data-genre="<?= $safeGenre ?>"
                 data-cover="<?= $safeCover ?>"
                 data-emoji="<?= $emoji ?>"
                 data-desc="<?= $safeDesc ?>"
                 data-stock="<?= $stock ?>"
                 onclick="openBorrowModal(this)">
              <?php if (!empty($sb['shelf_cover'])): ?>
                <img src="<?= $safeCover ?>" alt="<?= $safeTitle ?>" onerror="this.style.display='none'">
              <?php else: ?>
                <div class="carousel-placeholder">
                  <div class="cp-emoji"><?= $emoji ?></div>
                </div>
              <?php endif; ?>
              <div class="carousel-overlay">
                <div class="co-title"><?= htmlspecialchars($sb['shelf_title']) ?></div>
                <div class="co-author">by <?= htmlspecialchars($sb['shelf_author']) ?></div>
                <div style="margin-top:6px;font-size:10px;font-weight:700;
                            background:<?= $isFeatured ? 'rgba(251,191,36,.25)' : 'rgba(255,255,255,.1)' ?>;
                            color:<?= $isFeatured ? '#fbbf24' : 'rgba(255,255,255,.8)' ?>;
                            border:1px solid <?= $isFeatured ? 'rgba(251,191,36,.4)' : 'rgba(255,255,255,.15)' ?>;
                            border-radius:20px;padding:2px 9px;display:inline-block;">
                  <?= $isFeatured ? '🔥' : '📖' ?> <?= $sb['borrow_count'] ?> <?= $sb['borrow_count'] === 1 ? 'borrow' : 'borrows' ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php if (count($carousel_books) > 1): ?>
          <button class="carousel-btn next" onclick="carouselMove(1)">›</button>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="genre-tabs" id="genreTabs">
      <div class="genre-tab active" onclick="filterByGenre('all', this)">All Books</div>
      <?php foreach ($genres as $g): ?>
        <div class="genre-tab" onclick="filterByGenre(<?= json_encode($g) ?>, this)">
          <?= ($genreEmoji[$g] ?? '📄') . ' ' . htmlspecialchars($g) ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="books-grid" id="booksGrid">
      <?php foreach ($shelf_books as $sb):
        $emoji = $genreEmoji[$sb['shelf_genre']] ?? '📚';
        $stock = (int)($sb['shelf_stock'] ?? 1);
      ?>
      <div class="book-card"
           data-title="<?= htmlspecialchars($sb['shelf_title'],  ENT_QUOTES) ?>"
           data-author="<?= htmlspecialchars($sb['shelf_author'], ENT_QUOTES) ?>"
           data-genre="<?= htmlspecialchars($sb['shelf_genre'],  ENT_QUOTES) ?>"
           data-cover="<?= htmlspecialchars($sb['shelf_cover']  ?? '', ENT_QUOTES) ?>"
           data-emoji="<?= $emoji ?>"
           data-desc="<?= htmlspecialchars($sb['shelf_desc']    ?? '', ENT_QUOTES) ?>"
           data-stock="<?= $stock ?>"
           onclick="openBorrowModal(this)">
        <div class="book-cover">
          <?php if (!empty($sb['shelf_cover'])): ?>
            <img src="<?= htmlspecialchars($sb['shelf_cover']) ?>"
                 alt="<?= htmlspecialchars($sb['shelf_title']) ?>"
                 onerror="this.parentNode.innerHTML='<div class=\'cover-placeholder\'><div class=\'cp-emoji\'><?= $emoji ?></div><div class=\'cp-genre\'><?= htmlspecialchars($sb['shelf_genre'], ENT_QUOTES) ?></div></div>'">
          <?php else: ?>
            <div class="cover-placeholder">
              <div class="cp-emoji"><?= $emoji ?></div>
              <div class="cp-genre"><?= htmlspecialchars($sb['shelf_genre']) ?></div>
            </div>
          <?php endif; ?>
          <div class="borrow-hint"><span>Tap to Borrow</span></div>
        </div>
        <div class="book-meta">
          <div class="bm-title"><?= htmlspecialchars($sb['shelf_title']) ?></div>
          <div class="bm-author">by <?= htmlspecialchars($sb['shelf_author']) ?></div>
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:6px;">
            <span class="bm-genre"><?= $emoji ?> <?= htmlspecialchars($sb['shelf_genre']) ?></span>
            <?php if ($stock > 0): ?>
              <span style="font-size:10.5px;font-weight:700;padding:2px 9px;border-radius:20px;background:rgba(52,211,153,.1);color:#34d399;border:1px solid rgba(52,211,153,.2);">
                ✓ Available · <?= $stock ?> <?= $stock === 1 ? 'copy' : 'copies' ?>
              </span>
            <?php else: ?>
              <span style="font-size:10.5px;font-weight:700;padding:2px 9px;border-radius:20px;background:rgba(248,113,113,.1);color:#f87171;border:1px solid rgba(248,113,113,.2);">
                ✕ Out of Stock
              </span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <div class="no-results" id="noResults" style="display:none">No books match your search.</div>
    </div>

  <?php endif; ?>
</div>

<div class="modal-overlay" id="borrowModal">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-book-banner">
      <div class="mbb-thumb" id="mbbThumb"></div>
      <div style="flex:1;min-width:0;">
        <div class="mbb-genre"  id="mbbGenre">—</div>
        <div class="mbb-title"  id="mbbTitle">—</div>
        <div class="mbb-author" id="mbbAuthor">—</div>
      </div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-steps">
      <div class="step-tab active" id="tab0"><div class="step-num" id="tabNum0">1</div> About</div>
      <div class="step-tab"        id="tab1"><div class="step-num" id="tabNum1">2</div> Your Info</div>
      <div class="step-tab"        id="tab2"><div class="step-num" id="tabNum2">3</div> Dates</div>
      <div class="step-tab"        id="tab3"><div class="step-num" id="tabNum3">4</div> Confirm</div>
    </div>
    <div class="modal-body">
      <div id="modalErrors"></div>
      <div class="step-panel active" id="step0">
        <div class="step-heading">📖 About this Book</div>
        <div style="background:var(--card);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:16px;font-size:14px;color:var(--muted);line-height:1.7;" id="descBox"><em>No description available.</em></div>
        <div style="display:flex;align-items:center;gap:10px;background:var(--card);border:1px solid var(--border);border-radius:10px;padding:14px 16px;">
          <span style="font-size:20px;">📦</span>
          <div>
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);font-weight:700;margin-bottom:3px;">Availability</div>
            <div id="stockDisplay" style="font-size:15px;font-weight:700;">—</div>
          </div>
        </div>
      </div>
      <form method="POST" action="borrow.php" id="borrowForm">
        <input type="hidden" name="action"         value="borrow">
        <input type="hidden" name="book_title"     id="fTitle">
        <input type="hidden" name="book_author"    id="fAuthor">
        <input type="hidden" name="book_genre"     id="fGenre">
        <input type="hidden" name="borrowed_date"  id="fBorrowed">
        <input type="hidden" name="duedate"        id="fDue">
        <input type="hidden" name="student_name"   id="fStudentName">
        <input type="hidden" name="student_block"  id="fStudentBlock">
        <input type="hidden" name="student_number" id="fStudentNumber">
        <div class="step-panel" id="step1">
          <div class="step-heading">👤 Who are you?</div>
          <div class="field-group">
            <label>Full Name <span class="req">*</span></label>
            <input type="text" id="sName" placeholder="e.g. Juan dela Cruz" autocomplete="name">
          </div>
          <div class="field-row">
            <div class="field-group">
              <label>Block / Section <span class="req">*</span></label>
              <input type="text" id="sBlock" placeholder="e.g. BSIT 2-A">
            </div>
            <div class="field-group">
              <label>Student Number <span class="req">*</span></label>
              <input type="text" id="sNumber" placeholder="e.g. 2023-00123">
            </div>
          </div>
        </div>
        <div class="step-panel" id="step2">
          <div class="step-heading">📅 Borrow Schedule</div>
          <div class="date-range-viz">
            <div class="drv-item"><div class="drv-label">Borrow Date</div><div class="drv-date" id="vizBorrowDate">—</div><div class="drv-sub">Today</div></div>
            <div class="drv-sep">→</div>
            <div class="drv-item"><div class="drv-label">Due Date</div><div class="drv-date" id="vizDueDate">—</div><div class="drv-days" id="vizDays">7 days</div></div>
          </div>
          <div class="field-row">
            <div class="field-group">
              <label>Borrow Date <span class="req">*</span></label>
              <input type="date" id="dBorrow" onchange="updateDateViz()">
              <div class="date-hint">Usually today's date</div>
            </div>
            <div class="field-group">
              <label>Due Date <span class="req">*</span></label>
              <input type="date" id="dDue" onchange="updateDateViz()">
              <div class="date-hint">Default: 7 days from borrow</div>
            </div>
          </div>
        </div>
        <div class="step-panel" id="step3">
          <div class="step-heading">✅ Review &amp; Confirm</div>
          <div class="summary-card">
            <div class="sc-header">📚 Book Details</div>
            <div class="sc-body">
              <div class="sc-row"><span class="sc-key">Title</span><span class="sc-val" id="sumTitle">—</span></div>
              <div class="sc-row"><span class="sc-key">Author</span><span class="sc-val" id="sumAuthor">—</span></div>
              <div class="sc-row"><span class="sc-key">Genre</span><span class="sc-val" id="sumGenre">—</span></div>
            </div>
          </div>
          <div class="summary-card">
            <div class="sc-header">👤 Borrower</div>
            <div class="sc-body">
              <div class="sc-row"><span class="sc-key">Name</span><span class="sc-val" id="sumName">—</span></div>
              <div class="sc-row"><span class="sc-key">Block</span><span class="sc-val" id="sumBlock">—</span></div>
              <div class="sc-row"><span class="sc-key">Student No.</span><span class="sc-val" id="sumSno">—</span></div>
            </div>
          </div>
          <div class="summary-card">
            <div class="sc-header">📅 Schedule</div>
            <div class="sc-body">
              <div class="sc-row"><span class="sc-key">Borrow Date</span><span class="sc-val" id="sumBorrow">—</span></div>
              <div class="sc-row">
                <span class="sc-key">Due Date</span>
                <div><div class="sc-val" id="sumDue">—</div><span class="sc-badge" id="sumDays">7 days</span></div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-nav">
      <button type="button" class="btn btn-prev" id="btnPrev" onclick="prevStep()" style="display:none">← Back</button>
      <button type="button" class="btn btn-next" id="btnNext" onclick="nextStep()">View Borrow Form →</button>
    </div>
  </div>
</div>

<div style="text-align:center;padding:28px 20px 20px;font-size:12px;color:var(--muted);border-top:1px solid var(--border);margin-top:40px;">
  Built with the help of <a href="https://claude.ai" target="_blank" style="color:var(--primary);text-decoration:none;font-weight:600;">Claude.ai</a> — AI assistant by Anthropic
</div>

<script>
const TODAY = '<?= $today ?>';
const DEFAULT_DUE = '<?= $default_due ?>';
let currentStep = 0, activeGenre = 'all', currentBookData = {};

function openBorrowModal(card) {
  currentBookData = { title:card.dataset.title, author:card.dataset.author, genre:card.dataset.genre, cover:card.dataset.cover, emoji:card.dataset.emoji, desc:card.dataset.desc||'', stock:parseInt(card.dataset.stock)||0 };
  document.getElementById('mbbGenre').textContent  = currentBookData.genre;
  document.getElementById('mbbTitle').textContent  = currentBookData.title;
  document.getElementById('mbbAuthor').textContent = 'by ' + currentBookData.author;
  const thumb = document.getElementById('mbbThumb');
  if (currentBookData.cover) { thumb.innerHTML = `<img src="${currentBookData.cover}" alt="" onerror="this.parentNode.textContent='${currentBookData.emoji}'">`; }
  else { thumb.textContent = currentBookData.emoji; }
  document.getElementById('fTitle').value  = currentBookData.title;
  document.getElementById('fAuthor').value = currentBookData.author;
  document.getElementById('fGenre').value  = currentBookData.genre;
  const descBox = document.getElementById('descBox');
  descBox.innerHTML = currentBookData.desc ? currentBookData.desc : '<em style="color:var(--muted)">No description available.</em>';
  const stockEl = document.getElementById('stockDisplay');
  if (currentBookData.stock > 0) { stockEl.textContent = `✓ ${currentBookData.stock} ${currentBookData.stock===1?'copy':'copies'} available`; stockEl.style.color='var(--green)'; }
  else { stockEl.textContent = '✕ Out of Stock'; stockEl.style.color='var(--red)'; }
  document.getElementById('dBorrow').value = TODAY;
  document.getElementById('dDue').value    = DEFAULT_DUE;
  updateDateViz();
  document.getElementById('sName').value = document.getElementById('sBlock').value = document.getElementById('sNumber').value = '';
  document.getElementById('modalErrors').innerHTML = '';
  goToStep(0);
  document.getElementById('borrowModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal() { document.getElementById('borrowModal').classList.remove('open'); document.body.style.overflow = ''; }
document.getElementById('borrowModal').addEventListener('click', e => { if (e.target===document.getElementById('borrowModal')) closeModal(); });
document.addEventListener('keydown', e => { if (e.key==='Escape') closeModal(); });

function goToStep(n) {
  currentStep = n;
  [0,1,2,3].forEach(i => {
    document.getElementById(`step${i}`).classList.toggle('active', i===n);
    const tab = document.getElementById(`tab${i}`);
    tab.classList.remove('active','done');
    if (i===n) tab.classList.add('active'); else if (i<n) tab.classList.add('done');
    document.getElementById(`tabNum${i}`).textContent = i<n ? '✓' : i+1;
  });
  document.getElementById('btnPrev').style.display = n>0 ? '' : 'none';
  const btn = document.getElementById('btnNext');
  if (n===3) {
    if (currentBookData.stock<=0) { btn.disabled=true; btn.textContent='✕ Out of Stock'; btn.style.background='var(--red)'; btn.style.opacity='0.6'; btn.style.cursor='not-allowed'; btn.className='btn'; }
    else { btn.disabled=false; btn.textContent='✅ Confirm Borrow'; btn.style.background=btn.style.opacity=btn.style.cursor=''; btn.className='btn btn-submit'; }
  } else if (n===0) { btn.disabled=false; btn.textContent='View Borrow Form →'; btn.style.background=btn.style.opacity=btn.style.cursor=''; btn.className='btn btn-next'; }
  else { btn.disabled=false; btn.textContent='Continue →'; btn.style.background=btn.style.opacity=btn.style.cursor=''; btn.className='btn btn-next'; }
}

function validateStep(n) {
  const errEl = document.getElementById('modalErrors'); errEl.innerHTML = '';
  const errs = [];
  if (n===1) { if (!document.getElementById('sName').value.trim()) errs.push('Full name is required.'); if (!document.getElementById('sBlock').value.trim()) errs.push('Block / Section is required.'); if (!document.getElementById('sNumber').value.trim()) errs.push('Student number is required.'); }
  else if (n===2) { const b=document.getElementById('dBorrow').value, d=document.getElementById('dDue').value; if (!b) errs.push('Borrow date is required.'); if (!d) errs.push('Due date is required.'); if (b&&d&&d<=b) errs.push('Due date must be after the borrow date.'); }
  if (errs.length) { errEl.innerHTML=`<div class="modal-errors"><ul>${errs.map(e=>`<li>${e}</li>`).join('')}</ul></div>`; return false; }
  return true;
}

function nextStep() {
  if (currentStep===3) { document.getElementById('fBorrowed').value=document.getElementById('dBorrow').value; document.getElementById('fDue').value=document.getElementById('dDue').value; document.getElementById('fStudentName').value=document.getElementById('sName').value.trim(); document.getElementById('fStudentBlock').value=document.getElementById('sBlock').value.trim(); document.getElementById('fStudentNumber').value=document.getElementById('sNumber').value.trim(); document.getElementById('borrowForm').submit(); return; }
  if (currentStep!==0 && !validateStep(currentStep)) return;
  if (currentStep===2) populateSummary();
  goToStep(currentStep+1);
}
function prevStep() { if (currentStep>0) goToStep(currentStep-1); }

function populateSummary() {
  document.getElementById('sumTitle').textContent  = currentBookData.title;
  document.getElementById('sumAuthor').textContent = currentBookData.author;
  document.getElementById('sumGenre').textContent  = currentBookData.genre;
  document.getElementById('sumName').textContent   = document.getElementById('sName').value.trim();
  document.getElementById('sumBlock').textContent  = document.getElementById('sBlock').value.trim();
  document.getElementById('sumSno').textContent    = document.getElementById('sNumber').value.trim();
  const bd=document.getElementById('dBorrow').value, dd=document.getElementById('dDue').value;
  document.getElementById('sumBorrow').textContent = formatDate(bd);
  document.getElementById('sumDue').textContent    = formatDate(dd);
  const days = Math.round((new Date(dd)-new Date(bd))/86400000);
  document.getElementById('sumDays').textContent = `${days} day${days!==1?'s':''}`;
}

function updateDateViz() {
  const bd=document.getElementById('dBorrow').value, dd=document.getElementById('dDue').value;
  document.getElementById('vizBorrowDate').textContent = bd ? formatDate(bd) : '—';
  document.getElementById('vizDueDate').textContent    = dd ? formatDate(dd) : '—';
  if (bd&&dd) { const days=Math.round((new Date(dd)-new Date(bd))/86400000); document.getElementById('vizDays').textContent=days>0?`${days} days`:'⚠ Invalid'; document.getElementById('vizDays').style.color=days>0?'':'var(--red)'; }
}

function formatDate(s) { if (!s) return '—'; const d=new Date(s+'T00:00:00'); return d.toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'}); }

function filterBooks() {
  const q=document.getElementById('searchInput').value.toLowerCase().trim();
  document.getElementById('searchClear').style.display = q?'block':'none';
  const c=document.getElementById('carouselSection'); if (c) c.style.display=q?'none':'';
  applyFilters(q,activeGenre);
}
function clearSearch() { document.getElementById('searchInput').value=''; document.getElementById('searchClear').style.display='none'; const c=document.getElementById('carouselSection'); if(c) c.style.display=''; applyFilters('',activeGenre); }
function filterByGenre(genre,tab) { activeGenre=genre; document.querySelectorAll('.genre-tab').forEach(t=>t.classList.remove('active')); tab.classList.add('active'); const q=document.getElementById('searchInput').value.toLowerCase().trim(); const c=document.getElementById('carouselSection'); if(c) c.style.display=q?'none':''; applyFilters(q,genre); }
function applyFilters(q,genre) { const cards=document.querySelectorAll('#booksGrid .book-card'); let visible=0; cards.forEach(c=>{ const gm=genre==='all'||c.dataset.genre===genre; const sm=(c.dataset.title+' '+c.dataset.author+' '+c.dataset.genre).toLowerCase().includes(q); const show=gm&&sm; c.style.display=show?'':'none'; if(show) visible++; }); document.getElementById('noResults').style.display=visible===0?'block':'none'; }

let carouselIndex=0; const itemWidth=156;
function carouselMove(dir) { const track=document.getElementById('carouselTrack'); if(!track) return; const items=track.querySelectorAll('.carousel-item'); const visible=Math.floor(track.parentElement.offsetWidth/itemWidth); const maxIndex=Math.max(0,items.length-visible); carouselIndex=Math.min(Math.max(carouselIndex+dir,0),maxIndex); track.style.transform=`translateX(-${carouselIndex*itemWidth}px)`; }
window.addEventListener('load',()=>{ const track=document.getElementById('carouselTrack'); if(!track) return; const items=track.querySelectorAll('.carousel-item'); const visible=Math.floor(track.parentElement.offsetWidth/itemWidth); let featuredIdx=0; items.forEach((item,i)=>{ if(item.classList.contains('featured')) featuredIdx=i; }); carouselIndex=Math.max(0,featuredIdx-Math.floor(visible/2)); track.style.transform=`translateX(-${carouselIndex*itemWidth}px)`; });
</script>
</body>
</html>