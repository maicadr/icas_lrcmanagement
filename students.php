<?php
include 'db.php';

$errors  = [];
$success = '';

// --- ADD student ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_student') {
    $student_number = trim($_POST['student_number'] ?? '');
    $student_name   = trim($_POST['student_name']   ?? '');
    $student_block  = trim($_POST['student_block']  ?? '');
    $notes          = trim($_POST['notes']          ?? '');

    if ($student_number === '') $errors[] = 'Student number is required.';
    if ($student_name   === '') $errors[] = 'Full name is required.';
    if ($student_block  === '') $errors[] = 'Block / Section is required.';

    if (empty($errors)) {
        $sno  = mysqli_real_escape_string($conn, $student_number);
        $sn   = mysqli_real_escape_string($conn, $student_name);
        $sb   = mysqli_real_escape_string($conn, $student_block);
        $note = mysqli_real_escape_string($conn, $notes);

        // check duplicate
        $dup = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM students WHERE student_number='$sno' LIMIT 1"));
        if ($dup) {
            $errors[] = "Student number <strong>$sno</strong> is already registered.";
        } else {
            $sql = "INSERT INTO students (student_number, student_name, student_block, notes, date_registered)
                    VALUES ('$sno','$sn','$sb','$note', CURDATE())";
            if (mysqli_query($conn, $sql)) {
                $success = "Student <strong>$student_name</strong> ($student_number) registered successfully!";
            } else {
                $errors[] = 'Database error: ' . mysqli_error($conn);
            }
        }
    }
}

// --- EDIT student ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_student') {
    $edit_id        = (int)($_POST['edit_id']       ?? 0);
    $student_number = trim($_POST['student_number'] ?? '');
    $student_name   = trim($_POST['student_name']   ?? '');
    $student_block  = trim($_POST['student_block']  ?? '');
    $notes          = trim($_POST['notes']          ?? '');

    if ($student_number === '') $errors[] = 'Student number is required.';
    if ($student_name   === '') $errors[] = 'Full name is required.';
    if ($student_block  === '') $errors[] = 'Block / Section is required.';

    if (empty($errors) && $edit_id > 0) {
        $sno  = mysqli_real_escape_string($conn, $student_number);
        $sn   = mysqli_real_escape_string($conn, $student_name);
        $sb   = mysqli_real_escape_string($conn, $student_block);
        $note = mysqli_real_escape_string($conn, $notes);

        // check duplicate (excluding self)
        $dup = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM students WHERE student_number='$sno' AND id != $edit_id LIMIT 1"));
        if ($dup) {
            $errors[] = "Student number <strong>$sno</strong> is already registered to another student.";
        } else {
            $sql = "UPDATE students SET student_number='$sno', student_name='$sn', student_block='$sb', notes='$note' WHERE id=$edit_id";
            if (mysqli_query($conn, $sql)) {
                header('Location: students.php?msg=updated');
                exit;
            } else {
                $errors[] = 'Database error: ' . mysqli_error($conn);
            }
        }
    }
}

// --- DELETE student ---
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM students WHERE id=$del_id");
    header('Location: students.php?msg=deleted');
    exit;
}

// --- DELETE ALL ---
if (isset($_GET['delete_all']) && $_GET['delete_all'] === '1') {
    mysqli_query($conn, "DELETE FROM students");
    header('Location: students.php?msg=deleted_all');
    exit;
}

// --- SEARCH & FETCH ---
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
if ($search !== '') {
    $query = "SELECT * FROM students WHERE
                student_number LIKE '%$search%' OR
                student_name   LIKE '%$search%' OR
                student_block  LIKE '%$search%'
              ORDER BY date_registered DESC, id DESC";
} else {
    $query = "SELECT * FROM students ORDER BY date_registered DESC, id DESC";
}

$result   = mysqli_query($conn, $query);
$total    = mysqli_num_rows($result);
$students = [];
while ($r = mysqli_fetch_assoc($result)) $students[] = $r;

// Block stats
$blocks = [];
foreach ($students as $s) {
    $b = $s['student_block'];
    $blocks[$b] = ($blocks[$b] ?? 0) + 1;
}
arsort($blocks);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LRC — Student Registry</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:           #f0f4fa;
    --surface:      #ffffff;
    --card:         #f8fafd;
    --card2:        #eef2f9;
    --border:       #dde3ef;
    --border-light: #eaeff8;
    --primary:      #3b7dd8;
    --primary-light: rgba(59,125,216,0.10);
    --primary-glow:  rgba(59,125,216,0.18);
    --green:        #0ea86a;
    --green-light:  rgba(14,168,106,0.10);
    --gold:         #d97706;
    --gold-light:   rgba(217,119,6,0.10);
    --red:          #e03c3c;
    --red-light:    rgba(224,60,60,0.10);
    --purple:       #7c3aed;
    --purple-light: rgba(124,58,237,0.10);
    --text:         #1a2340;
    --text2:        #3d4f6e;
    --muted:        #8696b4;
    --radius:       12px;
    --radius-lg:    16px;
    --shadow:       0 2px 16px rgba(59,100,180,0.08);
    --shadow-md:    0 4px 24px rgba(59,100,180,0.13);
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Nunito', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

  /* HEADER */
  header {
    background: var(--surface); border-bottom: 1.5px solid var(--border);
    padding: 14px 32px; display: flex; align-items: center; justify-content: space-between;
    gap: 12px; flex-wrap: wrap;
    position: sticky; top: 0; z-index: 100;
    box-shadow: 0 2px 12px rgba(59,100,180,0.07);
  }
  .header-brand { display: flex; align-items: center; gap: 12px; }
  .header-logo  { width: 42px; height: 42px; border-radius: 10px; overflow: hidden; flex-shrink: 0; box-shadow: 0 2px 8px rgba(59,100,180,0.15); }
  .header-title { font-family: 'Playfair Display', serif; font-size: 20px; font-weight: 700; color: var(--text); }
  .header-sub   { font-size: 11.5px; color: var(--muted); margin-top: 1px; }
  .header-nav   { display: flex; gap: 8px; flex-wrap: wrap; }

  .btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 18px; border: none; border-radius: 9px;
    font-size: 13px; font-weight: 700; font-family: inherit;
    cursor: pointer; text-decoration: none; transition: all .2s; white-space: nowrap;
  }
  .btn:hover { transform: translateY(-1px); box-shadow: var(--shadow-md); }
  .btn-primary { background: var(--primary); color: #fff; box-shadow: 0 2px 10px rgba(59,125,216,0.25); }
  .btn-green   { background: var(--green);   color: #fff; }
  .btn-gold    { background: var(--gold);    color: #fff; }
  .btn-red     { background: var(--red);     color: #fff; }
  .btn-purple  { background: var(--purple);  color: #fff; }
  .btn-ghost   { background: var(--card2); color: var(--text2); border: 1.5px solid var(--border); }
  .btn-ghost:hover { background: var(--border); }
  .btn-active  { background: var(--primary); color: #fff; box-shadow: 0 2px 10px rgba(59,125,216,0.25); }
  .btn-sm      { padding: 6px 14px; font-size: 12.5px; border-radius: 8px; }
  .btn-icon    { padding: 7px 10px; font-size: 14px; }

  .container { max-width: 1400px; margin: 0 auto; padding: 28px 20px; }

  /* ALERTS */
  .alert {
    padding: 13px 18px; border-radius: 10px; margin-bottom: 20px;
    font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px;
  }
  .alert-success { background: var(--green-light); color: var(--green); border-left: 3px solid var(--green); }
  .alert-danger  { background: var(--red-light);   color: var(--red);   border-left: 3px solid var(--red); }

  /* LAYOUT */
  .layout { display: grid; grid-template-columns: 340px 1fr; gap: 26px; align-items: start; }

  /* STAT CHIPS */
  .stat-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 22px; }
  .stat-chip {
    display: flex; align-items: center; gap: 14px;
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); padding: 14px 20px;
    box-shadow: var(--shadow); min-width: 130px;
  }
  .stat-chip .sc-icon { font-size: 26px; }
  .stat-chip .sc-num  { font-size: 22px; font-weight: 800; line-height: 1; font-family: 'Playfair Display', serif; }
  .stat-chip .sc-lbl  { font-size: 11px; color: var(--muted); margin-top: 2px; font-weight: 600; letter-spacing: .4px; text-transform: uppercase; }
  .sc-total  .sc-num  { color: var(--primary); }
  .sc-blocks .sc-num  { color: var(--purple); }
  .sc-today  .sc-num  { color: var(--green); }

  /* ADD FORM CARD */
  .form-card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); overflow: hidden;
    position: sticky; top: 76px; box-shadow: var(--shadow);
  }
  .form-card-header {
    padding: 18px 22px; background: var(--card2);
    border-bottom: 1.5px solid var(--border);
  }
  .form-card-header h2 {
    font-family: 'Playfair Display', serif; font-size: 17px; color: var(--purple);
    display: flex; align-items: center; gap: 8px;
  }
  .form-card-header p { font-size: 12px; color: var(--muted); margin-top: 4px; }
  .form-body { padding: 22px; }

  .fgroup { margin-bottom: 15px; }
  .fgroup label {
    display: block; font-size: 11px; font-weight: 800;
    letter-spacing: .5px; text-transform: uppercase; color: var(--muted); margin-bottom: 6px;
  }
  .fgroup input, .fgroup textarea {
    width: 100%; padding: 11px 13px;
    background: var(--card); border: 1.5px solid var(--border);
    border-radius: 9px; color: var(--text); font-size: 14px; font-family: inherit;
    outline: none; transition: border-color .2s, box-shadow .2s;
  }
  .fgroup input::placeholder, .fgroup textarea::placeholder { color: var(--muted); opacity: .7; }
  .fgroup input:focus, .fgroup textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
  .fgroup .hint { font-size: 11.5px; color: var(--muted); margin-top: 4px; }
  .frow { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

  /* ID PREVIEW */
  .id-preview {
    background: linear-gradient(135deg, #1a2340 0%, #2a3f6e 100%);
    border-radius: 12px; padding: 18px 20px; margin-bottom: 18px;
    box-shadow: 0 4px 18px rgba(26,35,64,0.22);
    border: 1.5px solid rgba(255,255,255,0.08);
    position: relative; overflow: hidden;
    min-height: 90px;
  }
  .id-preview::before {
    content: 'LRC';
    position: absolute; right: -8px; top: -14px;
    font-family: 'Playfair Display', serif;
    font-size: 62px; font-weight: 700; color: rgba(255,255,255,.04);
    pointer-events: none;
  }
  .id-preview .idp-label { font-size: 9px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; color: rgba(255,255,255,.45); margin-bottom: 6px; }
  .id-preview .idp-name  { font-size: 17px; font-weight: 700; color: #fff; font-family: 'Playfair Display', serif; line-height: 1.2; margin-bottom: 4px; min-height: 22px; }
  .id-preview .idp-block { font-size: 11.5px; color: rgba(255,255,255,.55); margin-bottom: 10px; }
  .id-preview .idp-num   {
    display: inline-block; font-size: 13px; font-weight: 800; font-family: 'Courier New', monospace;
    letter-spacing: 2px; color: #fff;
    background: rgba(59,125,216,.4); border: 1px solid rgba(59,125,216,.5);
    padding: 4px 12px; border-radius: 6px;
  }

  /* RIGHT PANEL */
  .right-panel { min-width: 0; }

  /* TOOLBAR */
  .toolbar {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px; margin-bottom: 16px;
  }
  .toolbar-left { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
  .search-wrap { position: relative; }
  .search-wrap input {
    padding: 9px 40px 9px 16px;
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 9px; color: var(--text); font-size: 13.5px;
    font-family: inherit; outline: none; width: 290px;
    transition: border-color .2s, box-shadow .2s;
  }
  .search-wrap input::placeholder { color: var(--muted); }
  .search-wrap input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
  .search-wrap .s-ico { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 14px; pointer-events: none; }

  /* SECTION HEADER */
  .section-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px; flex-wrap: wrap; gap: 10px;
  }
  .section-header h2 {
    font-family: 'Playfair Display', serif; font-size: 20px; color: var(--text);
    display: flex; align-items: center; gap: 10px;
  }
  .count-badge {
    background: var(--primary); color: #fff;
    font-size: 14px; font-weight: 800; padding: 4px 14px; border-radius: 20px;
    box-shadow: 0 2px 8px rgba(59,125,216,.3);
  }

  /* TABLE CARD */
  .table-card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: var(--radius-lg); overflow: hidden; overflow-x: auto;
    box-shadow: var(--shadow);
  }
  table { width: 100%; border-collapse: collapse; min-width: 680px; }
  thead tr { background: var(--card2); }
  thead th {
    padding: 13px 16px; text-align: left;
    font-size: 10.5px; font-weight: 800; letter-spacing: .8px; text-transform: uppercase;
    color: var(--muted); white-space: nowrap; border-bottom: 1.5px solid var(--border);
    user-select: none;
  }
  tbody tr { border-bottom: 1px solid var(--border-light); transition: background .15s; }
  tbody tr:last-child { border-bottom: none; }
  tbody tr:hover { background: #f0f5ff; }
  tbody td { padding: 13px 16px; font-size: 13.5px; vertical-align: middle; }

  .td-num   { color: var(--muted); font-size: 12px; font-weight: 700; }
  .td-name  { font-weight: 700; color: var(--text); }
  .td-muted { color: var(--text2); font-size: 13px; }

  .id-chip {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--primary-light); color: var(--primary);
    border: 1px solid rgba(59,125,216,.25); border-radius: 8px;
    padding: 4px 12px; font-size: 12.5px; font-weight: 800;
    font-family: 'Courier New', monospace; letter-spacing: 1px;
  }
  .id-chip::before { content: '🪪'; font-size: 12px; }

  .block-badge {
    display: inline-block; padding: 3px 10px; border-radius: 20px;
    font-size: 11.5px; font-weight: 700;
    background: var(--purple-light); color: var(--purple);
    border: 1px solid rgba(124,58,237,.2);
  }

  .date-cell { font-size: 12px; color: var(--muted); white-space: nowrap; }

  .actions { display: flex; gap: 6px; }

  .empty-state { text-align: center; padding: 72px 20px; color: var(--muted); }
  .empty-state .icon { font-size: 52px; margin-bottom: 14px; }
  .empty-state p { font-size: 15px; line-height: 1.7; color: var(--text2); }

  /* BLOCK STATS MINI */
  .block-stats-card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: var(--radius); padding: 16px 18px;
    box-shadow: var(--shadow); margin-bottom: 20px;
  }
  .block-stats-card h3 {
    font-size: 11px; font-weight: 800; text-transform: uppercase;
    letter-spacing: .6px; color: var(--muted); margin-bottom: 12px;
  }
  .block-bar-row { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
  .block-bar-row:last-child { margin-bottom: 0; }
  .block-bar-label { font-size: 12px; font-weight: 700; color: var(--text2); min-width: 90px; }
  .block-bar-track { flex: 1; height: 8px; background: var(--card2); border-radius: 20px; overflow: hidden; }
  .block-bar-fill  { height: 100%; border-radius: 20px; background: var(--purple); transition: width .4s ease; }
  .block-bar-count { font-size: 12px; font-weight: 800; color: var(--purple); min-width: 20px; text-align: right; }

  /* MODAL */
  .modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 1000;
    background: rgba(26,35,64,0.45); backdrop-filter: blur(6px);
    align-items: center; justify-content: center; padding: 16px;
  }
  .modal-overlay.open { display: flex; animation: fadeIn .2s; }
  @keyframes fadeIn  { from { opacity: 0; } to { opacity: 1; } }
  @keyframes slideUp { from { transform: translateY(24px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

  .modal {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 20px; width: 100%; max-width: 480px;
    box-shadow: 0 24px 60px rgba(26,35,64,0.18);
    animation: slideUp .25s ease; overflow: hidden;
    max-height: 93vh; display: flex; flex-direction: column;
  }
  .modal-header {
    background: var(--card2); padding: 20px 22px;
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
    border-bottom: 1.5px solid var(--border); flex-shrink: 0;
  }
  .modal-header h3 { font-family: 'Playfair Display', serif; font-size: 18px; color: var(--purple); }
  .modal-header p  { font-size: 12px; color: var(--muted); margin-top: 3px; }
  .modal-close {
    background: var(--border-light); border: 1.5px solid var(--border);
    color: var(--muted); width: 30px; height: 30px; border-radius: 8px;
    font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all .2s; flex-shrink: 0;
  }
  .modal-close:hover { background: var(--border); color: var(--text); }
  .modal-body { padding: 22px; overflow-y: auto; }
  .modal-footer {
    padding: 14px 22px; border-top: 1.5px solid var(--border);
    display: flex; gap: 8px; flex-shrink: 0; background: var(--card2);
  }

  /* ID PREVIEW in modal */
  .modal-id-preview {
    background: linear-gradient(135deg, #1a2340 0%, #2a3f6e 100%);
    border-radius: 10px; padding: 15px 18px; margin-bottom: 18px;
    box-shadow: 0 4px 14px rgba(26,35,64,0.20);
    border: 1.5px solid rgba(255,255,255,0.08);
    position: relative; overflow: hidden;
  }
  .modal-id-preview::before {
    content: 'LRC';
    position: absolute; right: -8px; top: -12px;
    font-family: 'Playfair Display', serif;
    font-size: 54px; font-weight: 700; color: rgba(255,255,255,.04);
    pointer-events: none;
  }
  .modal-id-preview .midp-label { font-size: 9px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; color: rgba(255,255,255,.4); margin-bottom: 5px; }
  .modal-id-preview .midp-name  { font-size: 15px; font-weight: 700; color: #fff; font-family: 'Playfair Display', serif; margin-bottom: 3px; min-height: 20px; }
  .modal-id-preview .midp-block { font-size: 11px; color: rgba(255,255,255,.5); margin-bottom: 8px; }
  .modal-id-preview .midp-num   {
    display: inline-block; font-size: 12px; font-weight: 800; font-family: 'Courier New', monospace;
    letter-spacing: 2px; color: #fff;
    background: rgba(59,125,216,.4); border: 1px solid rgba(59,125,216,.5);
    padding: 3px 10px; border-radius: 5px;
  }

  .mfgroup { margin-bottom: 15px; }
  .mfgroup label {
    display: block; font-size: 11px; font-weight: 800;
    letter-spacing: .5px; text-transform: uppercase; color: var(--muted); margin-bottom: 6px;
  }
  .mfgroup input, .mfgroup textarea {
    width: 100%; padding: 11px 13px;
    background: var(--card); border: 1.5px solid var(--border);
    border-radius: 9px; color: var(--text); font-size: 14px; font-family: inherit;
    outline: none; transition: border-color .2s, box-shadow .2s;
  }
  .mfgroup input::placeholder { color: var(--muted); opacity: .7; }
  .mfgroup input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
  .mfrow { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

  @media (max-width: 900px) {
    .layout { grid-template-columns: 1fr; }
    .form-card { position: static; }
  }
  @media (max-width: 600px) {
    .search-wrap input { width: 100%; }
    .toolbar { flex-direction: column; align-items: flex-start; }
    .frow, .mfrow { grid-template-columns: 1fr; }
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
      <div class="header-title">LRC Management</div>
      <div class="header-sub">Student Registry — Authorized ID Numbers</div>
    </div>
  </div>
  <div class="header-nav">
    <a href="index.php"    class="btn btn-ghost">Book Records</a>
    <a href="borrow.php"   class="btn btn-ghost">Student Kiosk</a>
    <a href="shelves.php"  class="btn btn-ghost">Shelf Manager</a>
    <a href="students.php" class="btn btn-active">Student Registry</a>
  </div>
</header>

<div class="container">

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?><div>⚠ <?= $e ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php endif; ?>

  <?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'updated'):     ?><div class="alert alert-success">Student record updated.</div><?php endif; ?>
    <?php if ($_GET['msg'] === 'deleted'):     ?><div class="alert alert-danger">Student record removed.</div><?php endif; ?>
    <?php if ($_GET['msg'] === 'deleted_all'): ?><div class="alert alert-danger">All student records cleared.</div><?php endif; ?>
  <?php endif; ?>

  <div class="layout">

    <!-- ===== ADD FORM ===== -->
    <div class="form-card">
      <div class="form-card-header">
        <h2>🪪 Register Student</h2>
        <p>Add an official student ID to the authorized registry</p>
      </div>
      <div class="form-body">

        <!-- Live ID card preview -->
        <div class="id-preview">
          <div class="idp-label">LRC — Learning Resource Center</div>
          <div class="idp-name"  id="prevName">Student Name</div>
          <div class="idp-block" id="prevBlock">Block / Section</div>
          <div class="idp-num"   id="prevNum">0000-00000</div>
        </div>

        <form method="POST" action="students.php">
          <input type="hidden" name="action" value="add_student">

          <div class="fgroup">
            <label>Student ID Number *</label>
            <input type="text" name="student_number" id="addNum"
                   placeholder="e.g. 2023-00123" required autocomplete="off"
                   oninput="updatePreview()">
            <div class="hint">Must match the student's official ID exactly.</div>
          </div>

          <div class="fgroup">
            <label>Full Name *</label>
            <input type="text" name="student_name" id="addName"
                   placeholder="e.g. Juan dela Cruz" required autocomplete="off"
                   oninput="updatePreview()">
          </div>

          <div class="fgroup">
            <label>Block / Section *</label>
            <input type="text" name="student_block" id="addBlock"
                   placeholder="e.g. BSIT 2-A" required autocomplete="off"
                   oninput="updatePreview()">
          </div>

          <div class="fgroup">
            <label>Notes <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:10px;">(optional)</span></label>
            <input type="text" name="notes" placeholder="e.g. Scholarship student, Transfer">
          </div>

          <button type="submit" class="btn btn-purple" style="width:100%;padding:13px;font-size:14px;justify-content:center;">
            Register Student
          </button>
        </form>
      </div>
    </div>

    <!-- ===== RIGHT PANEL ===== -->
    <div class="right-panel">

      <!-- Stats -->
      <div class="stat-row">
        <?php
          $today_count = 0;
          $today_str   = date('Y-m-d');
          foreach ($students as $s) {
            if (($s['date_registered'] ?? '') === $today_str) $today_count++;
          }
          $block_count = count($blocks);
        ?>
        <div class="stat-chip sc-total">
          <div class="sc-icon"></div>
          <div><div class="sc-num"><?= $total ?></div><div class="sc-lbl">Registered</div></div>
        </div>
        <div class="stat-chip sc-blocks">
          <div class="sc-icon"></div>
          <div><div class="sc-num"><?= $block_count ?></div><div class="sc-lbl">Blocks / Sections</div></div>
        </div>
        <div class="stat-chip sc-today">
          <div class="sc-icon"></div>
          <div><div class="sc-num"><?= $today_count ?></div><div class="sc-lbl">Added Today</div></div>
        </div>
      </div>

      <!-- Block distribution mini-chart -->
      <?php if (count($blocks) > 0): $maxCount = max($blocks); ?>
      <div class="block-stats-card">
        <h3>Students by Block / Section</h3>
        <?php $shown = 0; foreach ($blocks as $bname => $bcount): if ($shown++ >= 6) break; ?>
          <div class="block-bar-row">
            <div class="block-bar-label"><?= htmlspecialchars($bname) ?></div>
            <div class="block-bar-track">
              <div class="block-bar-fill" style="width:<?= round($bcount / $maxCount * 100) ?>%"></div>
            </div>
            <div class="block-bar-count"><?= $bcount ?></div>
          </div>
        <?php endforeach; ?>
        <?php if (count($blocks) > 6): ?>
          <div style="font-size:11.5px;color:var(--muted);margin-top:8px;text-align:right;">
            + <?= count($blocks) - 6 ?> more section<?= (count($blocks) - 6) !== 1 ? 's' : '' ?>
          </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- TOOLBAR -->
      <div class="toolbar">
        <div class="toolbar-left">
          <a href="students.php?delete_all=1" class="btn btn-red btn-sm"
             onclick="return confirm('Clear ALL student records? This cannot be undone.')">Clear All</a>
        </div>
        <form method="GET" action="students.php" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <div class="search-wrap">
            <input type="text" name="search" placeholder="Search name, ID, block…"
                   value="<?= htmlspecialchars($search) ?>">
            <span class="s-ico">🔍</span>
          </div>
          <button type="submit" class="btn btn-primary btn-sm">Search</button>
          <?php if ($search !== ''): ?>
            <a href="students.php" class="btn btn-ghost btn-sm">✕ Clear</a>
          <?php endif; ?>
        </form>
      </div>

      <?php if ($search !== ''): ?>
        <p style="font-size:13px;color:var(--muted);margin-bottom:14px;font-weight:600;">
          Found <strong style="color:var(--primary)"><?= $total ?></strong> result<?= $total !== 1 ? 's' : '' ?>
          for <strong style="color:var(--text)">"<?= htmlspecialchars($search) ?>"</strong>
        </p>
      <?php endif; ?>

      <!-- TABLE -->
      <div class="table-card">
        <?php if ($total > 0): ?>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Student ID</th>
              <th>Full Name</th>
              <th>Block / Section</th>
              <th>Registered</th>
              <th>Notes</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php $i = 1; foreach ($students as $s): ?>
            <tr>
              <td class="td-num"><?= $i++ ?></td>
              <td><span class="id-chip"><?= htmlspecialchars($s['student_number']) ?></span></td>
              <td><div class="td-name"><?= htmlspecialchars($s['student_name']) ?></div></td>
              <td><span class="block-badge"><?= htmlspecialchars($s['student_block']) ?></span></td>
              <td class="date-cell"><?= $s['date_registered'] ? date('M d, Y', strtotime($s['date_registered'])) : '—' ?></td>
              <td class="td-muted" style="font-size:12.5px;max-width:160px;">
                <?= $s['notes'] ? htmlspecialchars($s['notes']) : '<span style="color:var(--muted)">—</span>' ?>
              </td>
              <td onclick="event.stopPropagation()">
                <div class="actions">
                  <button class="btn btn-gold btn-sm btn-icon edit-btn"
                          title="Edit"
                          data-id="<?= $s['id'] ?>"
                          data-num="<?= htmlspecialchars($s['student_number'], ENT_QUOTES) ?>"
                          data-name="<?= htmlspecialchars($s['student_name'],   ENT_QUOTES) ?>"
                          data-block="<?= htmlspecialchars($s['student_block'],  ENT_QUOTES) ?>"
                          data-notes="<?= htmlspecialchars($s['notes'] ?? '',    ENT_QUOTES) ?>">✏️</button>
                  <a href="students.php?delete=<?= $s['id'] ?>"
                     class="btn btn-red btn-sm btn-icon" title="Delete"
                     onclick="return confirm('Remove this student from the registry?')">🗑️</a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
          <div class="icon"></div>
          <p><?= $search !== '' ? 'No students matched your search.<br>Try different keywords.' : 'No students registered yet.<br>Use the form on the left to add student IDs.' ?></p>
        </div>
        <?php endif; ?>
      </div>

    </div><!-- /right-panel -->
  </div><!-- /layout -->
</div><!-- /container -->

<!-- ===== EDIT MODAL ===== -->
<div class="modal-overlay" id="editModal">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-header">
      <div>
        <h3>Edit Student</h3>
        <p>Update this student's registered information</p>
      </div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">

      <!-- Live preview inside modal -->
      <div class="modal-id-preview">
        <div class="midp-label">LRC — Learning Resource Center</div>
        <div class="midp-name"  id="mPrevName">—</div>
        <div class="midp-block" id="mPrevBlock">—</div>
        <div class="midp-num"   id="mPrevNum">—</div>
      </div>

      <form method="POST" action="students.php" id="editForm">
        <input type="hidden" name="action"  value="edit_student">
        <input type="hidden" name="edit_id" id="eId">

        <div class="mfgroup">
          <label>Student ID Number *</label>
          <input type="text" name="student_number" id="eNum"
                 placeholder="e.g. 2023-00123" required
                 oninput="updateModalPreview()">
        </div>
        <div class="mfrow">
          <div class="mfgroup">
            <label>Full Name *</label>
            <input type="text" name="student_name" id="eName"
                   placeholder="e.g. Juan dela Cruz" required
                   oninput="updateModalPreview()">
          </div>
          <div class="mfgroup">
            <label>Block / Section *</label>
            <input type="text" name="student_block" id="eBlock"
                   placeholder="e.g. BSIT 2-A" required
                   oninput="updateModalPreview()">
          </div>
        </div>
        <div class="mfgroup">
          <label>Notes <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:10px;">(optional)</span></label>
          <input type="text" name="notes" id="eNotes" placeholder="e.g. Scholarship student">
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button type="submit" form="editForm" class="btn btn-purple" style="flex:1;justify-content:center;padding:12px;font-size:14px;">
        Save Changes
      </button>
      <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
    </div>
  </div>
</div>

<script>
// --- ADD form live ID card preview ---
function updatePreview() {
  const name  = document.getElementById('addName').value.trim()  || 'Student Name';
  const block = document.getElementById('addBlock').value.trim() || 'Block / Section';
  const num   = document.getElementById('addNum').value.trim()   || '0000-00000';
  document.getElementById('prevName').textContent  = name;
  document.getElementById('prevBlock').textContent = block;
  document.getElementById('prevNum').textContent   = num;
}

// --- EDIT modal ---
function openModal()  { document.getElementById('editModal').classList.add('open');    document.body.style.overflow = 'hidden'; }
function closeModal() { document.getElementById('editModal').classList.remove('open'); document.body.style.overflow = ''; }

document.getElementById('editModal').addEventListener('click', e => {
  if (e.target === document.getElementById('editModal')) closeModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

document.querySelectorAll('.edit-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('eId').value    = btn.dataset.id    || '';
    document.getElementById('eNum').value   = btn.dataset.num   || '';
    document.getElementById('eName').value  = btn.dataset.name  || '';
    document.getElementById('eBlock').value = btn.dataset.block || '';
    document.getElementById('eNotes').value = btn.dataset.notes || '';
    updateModalPreview();
    openModal();
    setTimeout(() => document.getElementById('eNum').focus(), 120);
  });
});

function updateModalPreview() {
  const name  = document.getElementById('eName').value.trim()  || '—';
  const block = document.getElementById('eBlock').value.trim() || '—';
  const num   = document.getElementById('eNum').value.trim()   || '—';
  document.getElementById('mPrevName').textContent  = name;
  document.getElementById('mPrevBlock').textContent = block;
  document.getElementById('mPrevNum').textContent   = num;
}

// --- Auto-dismiss alerts ---
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity 0.5s ease';
      el.style.opacity    = '0';
      setTimeout(() => el.remove(), 500);
    }, 3500);
  });
});
</script>
</body>
</html>