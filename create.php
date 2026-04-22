<?php
include 'db.php';

$errors = [];
$data   = ['book_title'=>'','book_author'=>'','book_genre'=>'','book_status'=>'Available','borrowed_date'=>'','duedate'=>'',
           'student_name'=>'','student_block'=>'','student_number'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['book_title']      = trim($_POST['book_title']      ?? '');
    $data['book_author']     = trim($_POST['book_author']     ?? '');
    $data['book_genre']      = trim($_POST['book_genre']      ?? '');
    $data['book_status']     = trim($_POST['book_status']     ?? 'Available');
    $data['borrowed_date']   = trim($_POST['borrowed_date']   ?? '');
    $data['duedate']         = trim($_POST['duedate']         ?? '');
    $data['student_name']    = trim($_POST['student_name']    ?? '');
    $data['student_block']   = trim($_POST['student_block']   ?? '');
    $data['student_number']  = trim($_POST['student_number']  ?? '');

    if ($data['book_title']  === '') $errors[] = 'Book title is required.';
    if ($data['book_author'] === '') $errors[] = 'Author name is required.';
    if ($data['book_genre']  === '') $errors[] = 'Book genre is required.';
    if ($data['book_status'] === '') $errors[] = 'Book status is required.';

    if (empty($errors)) {
        $title    = mysqli_real_escape_string($conn, $data['book_title']);
        $author   = mysqli_real_escape_string($conn, $data['book_author']);
        $genre    = mysqli_real_escape_string($conn, $data['book_genre']);
        $status   = mysqli_real_escape_string($conn, $data['book_status']);
        $borrowed = $data['borrowed_date'] !== '' ? "'" . mysqli_real_escape_string($conn, $data['borrowed_date']) . "'" : 'NULL';
        $due      = $data['duedate'] !== ''       ? "'" . mysqli_real_escape_string($conn, $data['duedate'])       . "'" : 'NULL';
        $sname    = mysqli_real_escape_string($conn, $data['student_name']);
        $sblock   = mysqli_real_escape_string($conn, $data['student_block']);
        $snumber  = mysqli_real_escape_string($conn, $data['student_number']);

        $sql = "INSERT INTO books (book_title, book_author, book_genre, book_status, borrowed_date, duedate, student_name, student_block, student_number)
                VALUES ('$title','$author','$genre','$status',$borrowed,$due,'$sname','$sblock','$snumber')";

        if (mysqli_query($conn, $sql)) {
            header('Location: index.php?msg=added');
            exit;
        } else {
            $errors[] = 'Database error: ' . mysqli_error($conn);
        }
    }
}

$needsBorrower = in_array($data['book_status'], ['Borrowed','Reserved','Overdue']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add New Book — LRC</title>
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

  header {
    background: var(--surface); border-bottom: 1px solid var(--border);
    padding: 16px 32px; display: flex; align-items: center; gap: 14px;
    position: sticky; top: 0; z-index: 50;
  }
  .header-logo {
    width: 42px; height: 42px; border-radius: 10px;
    overflow: hidden; flex-shrink: 0;
  }
  .header-title { font-family: 'Lora', serif; font-size: 20px; font-weight: 700; }
  .header-sub   { font-size: 11.5px; color: var(--muted); margin-top: 1px; }

  .btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 18px; border: none; border-radius: 9px;
    font-size: 13.5px; font-weight: 600; font-family: inherit;
    cursor: pointer; text-decoration: none; transition: all .2s; white-space: nowrap;
  }
  .btn:hover { transform: translateY(-1px); filter: brightness(1.1); }
  .btn-primary  { background: var(--primary); color: #fff; }
  .btn-green    { background: var(--green);   color: #000; }
  .btn-ghost    { background: rgba(255,255,255,.06); color: var(--text); border: 1px solid var(--border); }
  .btn-ghost:hover { background: rgba(255,255,255,.1); }

  .container { max-width: 760px; margin: 0 auto; padding: 32px 20px; }

  .breadcrumb { font-size: 13px; color: var(--muted); margin-bottom: 24px; display: flex; align-items: center; gap: 6px; }
  .breadcrumb a { color: var(--primary); text-decoration: none; }
  .breadcrumb a:hover { text-decoration: underline; }
  .breadcrumb span { color: var(--muted); }

  /* Error banner */
  .error-banner {
    background: rgba(248,113,113,.07); border: 1px solid rgba(248,113,113,.2);
    border-left: 3px solid var(--red);
    border-radius: 10px; padding: 14px 18px; margin-bottom: 22px;
    font-size: 13.5px; color: #fca5a5;
  }
  .error-banner strong { display: block; margin-bottom: 6px; font-size: 14px; }
  .error-banner ul { margin-left: 18px; }
  .error-banner li { margin-bottom: 3px; }

  /* FORM CARD */
  .form-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow);
  }

  /* Section headers inside card */
  .section-block {
    border-bottom: 1px solid var(--border);
  }
  .section-block:last-child { border-bottom: none; }
  .section-head {
    padding: 16px 24px;
    background: var(--card);
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px;
  }
  .section-head .sh-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0;
  }
  .sh-book  { background: rgba(79,142,247,.15); }
  .sh-info  { background: rgba(52,211,153,.12); }
  .sh-date  { background: rgba(251,191,36,.12); }
  .section-head .sh-label  { font-size: 14px; font-weight: 700; color: var(--text); }
  .section-head .sh-sublabel { font-size: 11.5px; color: var(--muted); margin-top: 1px; }
  .section-body { padding: 22px 24px; }

  .frow  { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .frow3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
  .fgroup { margin-bottom: 16px; }
  .fgroup:last-child { margin-bottom: 0; }

  .fgroup label {
    display: flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 700; letter-spacing: .4px;
    text-transform: uppercase; color: var(--muted); margin-bottom: 7px;
  }
  .fgroup label .req { color: var(--primary); font-size: 14px; line-height: 1; }
  .fgroup label .opt { font-weight: 400; text-transform: none; letter-spacing: 0; font-size: 10.5px; color: var(--muted); }

  .fgroup input, .fgroup select {
    width: 100%; padding: 11px 14px;
    background: var(--card); border: 1.5px solid var(--border);
    border-radius: 9px; color: var(--text); font-size: 14px; font-family: inherit;
    outline: none; transition: border-color .2s, box-shadow .2s, background .2s;
  }
  .fgroup input::placeholder { color: var(--muted); opacity: .65; }
  .fgroup input:focus, .fgroup select:focus {
    border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow);
    background: var(--card2);
  }
  .fgroup select option { background: #1a2236; }

  /* Status visual badges */
  .status-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
  .status-opt { display: none; }
  .status-lbl {
    display: flex; flex-direction: column; align-items: center; gap: 6px;
    padding: 12px 8px; border-radius: 10px;
    border: 2px solid var(--border); background: var(--card);
    cursor: pointer; transition: all .2s; text-align: center;
  }
  .status-lbl:hover { border-color: var(--primary); background: var(--primary-glow); }
  .status-opt:checked + .status-lbl { border-color: var(--primary); background: var(--primary-glow); }
  .status-lbl .sl-icon { font-size: 20px; }
  .status-lbl .sl-text { font-size: 11.5px; font-weight: 700; }
  .status-lbl .sl-desc { font-size: 10px; color: var(--muted); }

  /* Color per status */
  [data-status="Available"] .sl-text { color: var(--green); }
  [data-status="Borrowed"]  .sl-text { color: var(--gold); }
  [data-status="Overdue"]   .sl-text { color: var(--red); }
  [data-status="Reserved"]  .sl-text { color: var(--primary); }
  input[data-status="Available"]:checked + .status-lbl { border-color: var(--green); background: rgba(52,211,153,.08); }
  input[data-status="Borrowed"]:checked  + .status-lbl { border-color: var(--gold);  background: rgba(251,191,36,.08); }
  input[data-status="Overdue"]:checked   + .status-lbl { border-color: var(--red);   background: rgba(248,113,113,.08); }
  input[data-status="Reserved"]:checked  + .status-lbl { border-color: var(--primary); background: var(--primary-glow); }

  /* Borrower section toggle */
  .borrower-section { overflow: hidden; transition: max-height .35s ease, opacity .3s ease; }
  .borrower-section.hidden { max-height: 0; opacity: 0; pointer-events: none; }
  .borrower-section.visible { max-height: 600px; opacity: 1; }

  .borrower-note {
    background: rgba(79,142,247,.06); border: 1px solid rgba(79,142,247,.15);
    border-radius: 9px; padding: 10px 14px; margin-bottom: 18px;
    font-size: 12.5px; color: var(--primary);
    display: flex; align-items: center; gap: 8px;
  }

  /* Date section */
  .dates-preview {
    background: var(--card); border: 1px solid var(--border);
    border-radius: 10px; padding: 14px 18px; margin-bottom: 18px;
    display: flex; align-items: center; gap: 0;
  }
  .dp-item { flex: 1; text-align: center; }
  .dp-label { font-size: 10px; text-transform: uppercase; letter-spacing: .8px; color: var(--muted); font-weight: 600; margin-bottom: 4px; }
  .dp-val   { font-size: 15px; font-weight: 700; color: var(--text); }
  .dp-sub   { font-size: 11px; color: var(--muted); margin-top: 2px; }
  .dp-sep   { flex: none; padding: 0 10px; color: var(--muted); font-size: 18px; }

  /* Form footer */
  .form-footer {
    padding: 20px 24px; background: var(--card);
    border-top: 1px solid var(--border);
    display: flex; gap: 10px; align-items: center;
  }

  @media (max-width: 560px) {
    .frow, .frow3 { grid-template-columns: 1fr; }
    .status-grid  { grid-template-columns: repeat(2, 1fr); }
    .dates-preview { flex-direction: column; gap: 10px; }
    .dp-sep { display: none; }
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
  <div class="header-logo">
    <img src="https://scontent.fceb2-1.fna.fbcdn.net/v/t1.15752-9/655639216_902892782620982_679713768952904685_n.jpg?_nc_cat=108&ccb=1-7&_nc_sid=9f807c&_nc_eui2=AeGtmif84tBQGVhqMSMLc7-H8N-b6gQgzqjw35vqBCDOqA57yzk-L4KqAaEZF7QcCi-yqMYBAM_i9VCiZSMGHXkC&_nc_ohc=31kf0tEGRjkQ7kNvwF0PHLr&_nc_oc=AdrpM6rGMxUpupabTUwe-OkO-k8tbbEOvbpi6w8pc-O0gD06va8h3qa-JNvSpmPDZ8w&_nc_zt=23&_nc_ht=scontent.fceb2-1.fna&_nc_ss=7a3a8&oh=03_Q7cD5AGhronN_QuIoMGtujo28WGDJarngAlGnddgEQQXda_DrA&oe=6A0D97F4"
       alt="LRC Logo" style="width:100%; height:100%; object-fit:cover; border-radius:10px;">
  </div>
  <div>
    <div class="header-title">LRC Management</div>
    <div class="header-sub">Learning Resource Center — Book Records</div>
  </div>
  <div style="margin-left:auto; display:flex; gap:8px;">
    <a href="index.php" class="btn btn-ghost">Book Records</a>
    <a href="borrow.php" class="btn btn-ghost">Student Kiosk</a>
    <a href="shelves.php" class="btn btn-ghost">Shelf Manager</a>
  </div>
</header>

<div class="container">
  <div class="breadcrumb">
    <a href="index.php">Book Records</a>
    <span>/</span>
    <span style="color:var(--text)">Add New Book</span>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="error-banner">
      <strong>⚠ Please fix the following:</strong>
      <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST" action="create.php" id="createForm">
    <div class="form-card">

      <!-- SECTION 1: Book Info -->
      <div class="section-block">
        <div class="section-head">
          <div class="sh-icon sh-book"></div>
          <div>
            <div class="sh-label">Book Information</div>
            <div class="sh-sublabel">Title, author, and classification</div>
          </div>
        </div>
        <div class="section-body">
          <div class="frow">
            <div class="fgroup">
              <label>Book Title <span class="req">*</span></label>
              <input type="text" name="book_title" placeholder="e.g. The Great Gatsby"
                     value="<?= htmlspecialchars($data['book_title']) ?>" autocomplete="off">
            </div>
            <div class="fgroup">
              <label>Author's Name <span class="req">*</span></label>
              <input type="text" name="book_author" placeholder="e.g. F. Scott Fitzgerald"
                     value="<?= htmlspecialchars($data['book_author']) ?>" autocomplete="off">
            </div>
          </div>
          <div class="fgroup" style="margin-bottom:0">
            <label>Book Genre <span class="req">*</span></label>
            <select name="book_genre">
              <option value="">— Select a genre —</option>
              <?php
                $genres = ['Fiction','Non-Fiction','Science Fiction','Fantasy','Mystery','Thriller',
                           'Romance','Horror','Biography','History','Self-Help','Science','Technology',
                           'Philosophy','Poetry','Children','Young Adult','Reference','Textbook','Other'];
                foreach ($genres as $g):
              ?>
              <option value="<?= $g ?>" <?= $data['book_genre'] === $g ? 'selected' : '' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- SECTION 2: Status -->
      <div class="section-block">
        <div class="section-head">
          <div class="sh-icon sh-info"></div>
          <div>
            <div class="sh-label">Book Status</div>
            <div class="sh-sublabel">Current availability of this book</div>
          </div>
        </div>
        <div class="section-body">
          <div class="status-grid">
            <?php
              $statuses = [
                'Available' => ['🟢','In the shelf','Available to borrow'],
                'Borrowed'  => ['📤','Checked out','Currently with a student'],
                'Reserved'  => ['🔖','Reserved','Held for a student'],
                'Overdue'   => ['⚠️','Overdue','Past the due date'],
              ];
              foreach ($statuses as $sv => [$icon, $label, $desc]):
            ?>
            <input type="radio" name="book_status" id="st_<?= $sv ?>" value="<?= $sv ?>"
                   class="status-opt" data-status="<?= $sv ?>"
                   <?= $data['book_status'] === $sv ? 'checked' : '' ?>
                   onchange="onStatusChange()">
            <label for="st_<?= $sv ?>" class="status-lbl" data-status="<?= $sv ?>">
              <span class="sl-icon"><?= $icon ?></span>
              <span class="sl-text"><?= $label ?></span>
              <span class="sl-desc"><?= $desc ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- SECTION 3: Borrower (conditional) -->
      <div class="section-block borrower-section <?= $needsBorrower ? 'visible' : 'hidden' ?>" id="borrowerSection">
        <div class="section-head">
          <div class="sh-icon sh-info" style="background:rgba(79,142,247,.12);">👤</div>
          <div>
            <div class="sh-label">Borrower Information</div>
            <div class="sh-sublabel">Student who borrowed or reserved this book</div>
          </div>
        </div>
        <div class="section-body">
          <div class="borrower-note">
            Fill in the student details for this borrow record.
          </div>
          <div class="fgroup">
            <label>Full Name <span class="opt">(required for borrowed/reserved)</span></label>
            <input type="text" name="student_name" placeholder="e.g. Juan dela Cruz"
                   value="<?= htmlspecialchars($data['student_name']) ?>" autocomplete="name">
          </div>
          <div class="frow">
            <div class="fgroup" style="margin-bottom:0">
              <label>Block / Section</label>
              <input type="text" name="student_block" placeholder="e.g. BSIT 2-A"
                     value="<?= htmlspecialchars($data['student_block']) ?>">
            </div>
            <div class="fgroup" style="margin-bottom:0">
              <label>Student Number</label>
              <input type="text" name="student_number" placeholder="e.g. 2023-00123"
                     value="<?= htmlspecialchars($data['student_number']) ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- SECTION 4: Dates (conditional) -->
      <div class="section-block borrower-section <?= $needsBorrower ? 'visible' : 'hidden' ?>" id="datesSection">
        <div class="section-head">
          <div class="sh-icon sh-date"></div>
          <div>
            <div class="sh-label">Borrow Schedule</div>
            <div class="sh-sublabel">Record the borrow and due dates</div>
          </div>
        </div>
        <div class="section-body">
          <div class="dates-preview" id="datesPreview">
            <div class="dp-item">
              <div class="dp-label">Borrow Date</div>
              <div class="dp-val" id="dpBorrow"><?= $data['borrowed_date'] ? date('M d, Y', strtotime($data['borrowed_date'])) : '—' ?></div>
              <div class="dp-sub">Start</div>
            </div>
            <div class="dp-sep">→</div>
            <div class="dp-item">
              <div class="dp-label">Due Date</div>
              <div class="dp-val" id="dpDue"><?= $data['duedate'] ? date('M d, Y', strtotime($data['duedate'])) : '—' ?></div>
              <div class="dp-sub" id="dpDays">End</div>
            </div>
          </div>
          <div class="frow" style="margin-bottom:0">
            <div class="fgroup" style="margin-bottom:0">
              <label>Borrowed Date <span class="opt">optional</span></label>
              <input type="date" name="borrowed_date" id="inputBorrow"
                     value="<?= htmlspecialchars($data['borrowed_date']) ?>" onchange="updateDatesPreview()">
            </div>
            <div class="fgroup" style="margin-bottom:0">
              <label>Due Date <span class="opt">optional</span></label>
              <input type="date" name="duedate" id="inputDue"
                     value="<?= htmlspecialchars($data['duedate']) ?>" onchange="updateDatesPreview()">
            </div>
          </div>
        </div>
      </div>

      <!-- FOOTER -->
      <div class="form-footer">
        <button type="submit" class="btn btn-green" style="padding:12px 28px;font-size:15px;">
          Save Book Record
        </button>
        <a href="index.php" class="btn btn-ghost">✕ Cancel</a>
      </div>

    </div>
  </form>
</div>

<script>
function onStatusChange() {
  const status = document.querySelector('input[name="book_status"]:checked')?.value || 'Available';
  const needsBorrower = ['Borrowed','Reserved','Overdue'].includes(status);
  const bs = document.getElementById('borrowerSection');
  const ds = document.getElementById('datesSection');
  if (bs) { bs.classList.toggle('visible', needsBorrower); bs.classList.toggle('hidden', !needsBorrower); }
  if (ds) { ds.classList.toggle('visible', needsBorrower); ds.classList.toggle('hidden', !needsBorrower); }
}

function updateDatesPreview() {
  const b = document.getElementById('inputBorrow').value;
  const d = document.getElementById('inputDue').value;
  document.getElementById('dpBorrow').textContent = b ? formatDate(b) : '—';
  document.getElementById('dpDue').textContent    = d ? formatDate(d) : '—';
  if (b && d) {
    const days = Math.round((new Date(d) - new Date(b)) / 86400000);
    const el = document.getElementById('dpDays');
    if (days > 0) { el.textContent = `${days} day${days!==1?'s':''}`; el.style.color = ''; }
    else           { el.textContent = '⚠ Check dates'; el.style.color = 'var(--red)'; }
  }
}

function formatDate(s) {
  const d = new Date(s + 'T00:00:00');
  return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}

// Init
onStatusChange();
</script>
</body>
</html>