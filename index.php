<?php
include 'db.php';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

if ($search !== '') {
    $query = "SELECT * FROM books WHERE
                book_title     LIKE '%$search%' OR
                book_author    LIKE '%$search%' OR
                book_genre     LIKE '%$search%' OR
                book_status    LIKE '%$search%' OR
                student_name   LIKE '%$search%' OR
                student_block  LIKE '%$search%' OR
                student_number LIKE '%$search%'
              ORDER BY id DESC";
} else {
    $query = "SELECT * FROM books ORDER BY id DESC";
}

$result = mysqli_query($conn, $query);
$total  = mysqli_num_rows($result);
$rows   = [];
while ($r = mysqli_fetch_assoc($result)) $rows[] = $r;

// Status counts
$counts = ['Available'=>0,'Borrowed'=>0,'Overdue'=>0,'Reserved'=>0];
foreach ($rows as $r) {
    $s = ucfirst(strtolower($r['book_status']));
    if (isset($counts[$s])) $counts[$s]++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LRC — Book Records</title>
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
    --orange:   #fb923c;
    --text:     #f0f4ff;
    --muted:    #6b7a99;
    --radius:   12px;
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
    background: linear-gradient(135deg, #fbbf24, #f97316);
    display: flex; align-items: center; justify-content: center; font-size: 20px;
  }
  .header-title { font-family: 'Lora', serif; font-size: 20px; font-weight: 700; }
  .header-sub   { font-size: 11.5px; color: var(--muted); margin-top: 1px; }
  .header-nav   { display: flex; gap: 8px; flex-wrap: wrap; }

  .btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 18px; border: none; border-radius: 9px;
    font-size: 13.5px; font-weight: 600; font-family: inherit;
    cursor: pointer; text-decoration: none; transition: all .2s; white-space: nowrap;
  }
  .btn:hover { transform: translateY(-1px); filter: brightness(1.1); }
  .btn-primary  { background: var(--primary); color: #fff; }
  .btn-green    { background: var(--green);   color: #000; }
  .btn-gold     { background: var(--gold);    color: #000; }
  .btn-red      { background: var(--red);     color: #fff; }
  .btn-ghost    { background: rgba(255,255,255,.06); color: var(--text); border: 1px solid var(--border); }
  .btn-ghost:hover { background: rgba(255,255,255,.1); }
  .btn-sm       { padding: 6px 12px; font-size: 12.5px; border-radius: 7px; }
  .btn-icon     { padding: 7px 10px; font-size: 14px; }

  .container { max-width: 1400px; margin: 0 auto; padding: 28px 20px; }

  /* ALERTS */
  .alert {
    padding: 13px 18px; border-radius: 10px; margin-bottom: 20px;
    font-size: 13.5px; font-weight: 500; display: flex; align-items: center; gap: 10px;
  }
  .alert-success { background: rgba(52,211,153,.08); color: #6ee7b7; border-left: 3px solid var(--green); }
  .alert-danger  { background: rgba(248,113,113,.08); color: #fca5a5; border-left: 3px solid var(--red); }

  /* STAT CHIPS */
  .stat-row { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 22px; }
  .stat-chip {
    display: flex; align-items: center; gap: 9px;
    background: var(--card); border: 1px solid var(--border);
    border-radius: 10px; padding: 10px 16px;
    font-size: 13px; font-weight: 600;
  }
  .stat-chip .sc-num { font-size: 20px; font-weight: 700; line-height: 1; }
  .stat-chip .sc-lbl { font-size: 11px; color: var(--muted); margin-top: 1px; }
  .sc-total   .sc-num { color: var(--primary); }
  .sc-avail   .sc-num { color: var(--green); }
  .sc-borrow  .sc-num { color: var(--gold); }
  .sc-overdue .sc-num { color: var(--red); }

  /* TOOLBAR */
  .toolbar {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px; margin-bottom: 18px;
  }
  .toolbar-left { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }

  .search-wrap { position: relative; }
  .search-wrap input {
    padding: 9px 38px 9px 14px;
    background: var(--card); border: 1.5px solid var(--border);
    border-radius: 9px; color: var(--text); font-size: 13.5px;
    font-family: inherit; outline: none; width: 290px;
    transition: border-color .2s, box-shadow .2s;
  }
  .search-wrap input::placeholder { color: var(--muted); }
  .search-wrap input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
  .search-wrap .s-ico { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 14px; pointer-events: none; }

  /* TABLE CARD */
  .table-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden; overflow-x: auto;
    box-shadow: var(--shadow);
  }

  table { width: 100%; border-collapse: collapse; min-width: 980px; }

  thead tr { background: var(--card); }
  thead th {
    padding: 13px 16px; text-align: left;
    font-size: 10.5px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase;
    color: var(--muted); white-space: nowrap; border-bottom: 1px solid var(--border);
    user-select: none;
  }
  thead th.th-borrower { color: var(--primary); }

  tbody tr { border-bottom: 1px solid var(--border); transition: background .15s; cursor: pointer; }
  tbody tr:last-child { border-bottom: none; }
  tbody tr:hover { background: rgba(79,142,247,.04); }

  tbody td { padding: 12px 16px; font-size: 13.5px; vertical-align: middle; }
  td.td-borrower { background: rgba(79,142,247,.02); }
  tbody tr:hover td.td-borrower { background: rgba(79,142,247,.06); }

  .td-num { color: var(--muted); font-size: 12px; font-weight: 600; }
  .td-title { font-weight: 700; color: var(--text); }
  .td-muted { color: var(--muted); font-size: 13px; }

  .student-name { font-weight: 700; color: var(--primary); font-size: 13px; }
  .student-sub  { font-size: 11.5px; color: var(--muted); margin-top: 1px; }

  .badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 11px; border-radius: 20px;
    font-size: 11.5px; font-weight: 700; letter-spacing: .2px;
  }
  .badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
  .badge-available { background: rgba(52,211,153,.1);  color: var(--green); }
  .badge-borrowed  { background: rgba(251,191,36,.1);  color: var(--gold); }
  .badge-overdue   { background: rgba(248,113,113,.1); color: var(--red); }
  .badge-reserved  { background: rgba(79,142,247,.12); color: var(--primary); }

  .actions { display: flex; gap: 6px; }

  .empty-state { text-align: center; padding: 72px 20px; color: var(--muted); }
  .empty-state .icon { font-size: 52px; margin-bottom: 14px; }
  .empty-state p { font-size: 15px; line-height: 1.7; }

  /* ── VIEW RECORD MODAL ── */
  .modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 1000;
    background: rgba(0,0,0,.75); backdrop-filter: blur(8px);
    align-items: center; justify-content: center; padding: 16px;
  }
  .modal-overlay.open { display: flex; animation: fadeIn .2s; }
  @keyframes fadeIn  { from { opacity: 0; } to { opacity: 1; } }
  @keyframes slideUp { from { transform: translateY(24px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

  .modal {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 18px; width: 100%; max-width: 480px;
    box-shadow: 0 30px 80px rgba(0,0,0,.65);
    animation: slideUp .25s ease; overflow: hidden;
    max-height: 90vh; display: flex; flex-direction: column;
  }
  .modal-header {
    background: var(--card); padding: 20px 22px;
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
    border-bottom: 1px solid var(--border); flex-shrink: 0;
  }
  .modal-header h3 { font-family: 'Lora', serif; font-size: 18px; color: var(--text); }
  .modal-header p  { font-size: 12px; color: var(--muted); margin-top: 3px; }
  .modal-close {
    background: rgba(255,255,255,.06); border: 1px solid var(--border);
    color: var(--muted); width: 30px; height: 30px; border-radius: 8px;
    font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all .2s; flex-shrink: 0;
  }
  .modal-close:hover { background: rgba(255,255,255,.12); color: var(--text); }
  .modal-body { padding: 22px; overflow-y: auto; }

  /* Record detail rows */
  .rec-section { margin-bottom: 18px; }
  .rec-section-title {
    font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
    color: var(--muted); margin-bottom: 10px; padding-bottom: 6px;
    border-bottom: 1px solid var(--border);
  }
  .rec-row {
    display: flex; justify-content: space-between; align-items: flex-start;
    gap: 12px; padding: 7px 0; border-bottom: 1px solid rgba(255,255,255,.04);
  }
  .rec-row:last-child { border-bottom: none; }
  .rec-key { font-size: 12px; color: var(--muted); flex: none; min-width: 100px; }
  .rec-val { font-size: 13.5px; font-weight: 600; color: var(--text); text-align: right; }

  .modal-footer {
    padding: 14px 22px; border-top: 1px solid var(--border);
    display: flex; gap: 8px; flex-shrink: 0;
    background: var(--card);
  }

  /* Delete confirm */
  .del-confirm {
    display: none; background: rgba(248,113,113,.06); border: 1px solid rgba(248,113,113,.2);
    border-radius: 10px; padding: 14px 16px; margin-bottom: 16px;
  }
  .del-confirm.show { display: block; }
  .del-confirm p { font-size: 13.5px; color: #fca5a5; margin-bottom: 12px; font-weight: 500; }
  .del-confirm .del-btns { display: flex; gap: 8px; }

  @media (max-width: 700px) {
    .toolbar { flex-direction: column; align-items: flex-start; }
    .search-wrap input { width: 100%; }
    .stat-row { gap: 8px; }
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
    <div class="header-logo">📋</div>
    <div>
      <div class="header-title">LRC Management</div>
      <div class="header-sub">Learning Resource Center — Book Records</div>
    </div>
  </div>
  <div class="header-nav">
    <a href="index.php" class="btn btn-ghost btn-active">Book Records</a>
    <a href="borrow.php"  class="btn btn-ghost">Student Kiosk</a>
    <a href="shelves.php" class="btn btn-ghost">Shelf Manager</a>
  </div>
</header>

<div class="container">

  <?php if (isset($_GET['msg'])): $msg = $_GET['msg']; ?>
    <?php if ($msg === 'added'):    ?><div class="alert alert-success">Book successfully added.</div><?php endif; ?>
    <?php if ($msg === 'updated'):  ?><div class="alert alert-success">Book record updated.</div><?php endif; ?>
    <?php if ($msg === 'deleted'):  ?><div class="alert alert-danger">Book record deleted.</div><?php endif; ?>
    <?php if ($msg === 'deleted_all'): ?><div class="alert alert-danger">All book records deleted.</div><?php endif; ?>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stat-row">
    <div class="stat-chip sc-total">
      <div><div class="sc-num"><?= $total ?></div><div class="sc-lbl">Total Records</div></div>
    </div>
    <div class="stat-chip sc-avail">
      <div><div class="sc-num"><?= $counts['Available'] ?></div><div class="sc-lbl">Available</div></div>
    </div>
    <div class="stat-chip sc-borrow">
      <div><div class="sc-num"><?= $counts['Borrowed'] ?></div><div class="sc-lbl">Borrowed</div></div>
    </div>
    <div class="stat-chip sc-overdue">
      <div><div class="sc-num"><?= $counts['Overdue'] ?></div><div class="sc-lbl">Overdue</div></div>
    </div>
  </div>

  <div class="toolbar">
    <div class="toolbar-left">
      <a href="delete_all.php" class="btn btn-red"
         onclick="return confirm('Delete ALL book records? This cannot be undone.')">🗑 Delete All</a>
    </div>
    <form method="GET" action="index.php" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
      <div class="search-wrap">
        <input type="text" name="search" placeholder="Search title, author, student…"
               value="<?= htmlspecialchars($search) ?>">
        <span class="s-ico">🔍</span>
      </div>
      <button type="submit" class="btn btn-primary btn-sm">Search</button>
      <?php if ($search !== ''): ?>
        <a href="index.php" class="btn btn-ghost btn-sm">✕ Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <?php if ($search !== ''): ?>
    <p style="font-size:13px;color:var(--muted);margin-bottom:14px;">
      Found <strong style="color:var(--primary)"><?= $total ?></strong> result<?= $total !== 1 ? 's' : '' ?>
      for <strong style="color:var(--text)">"<?= htmlspecialchars($search) ?>"</strong>
    </p>
  <?php endif; ?>

  <div class="table-card">
    <?php if ($total > 0): ?>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Book Title</th>
          <th>Author</th>
          <th>Genre</th>
          <th>Status</th>
          <th class="th-borrower">Student Name</th>
          <th class="th-borrower">Block</th>
          <th class="th-borrower">Student No.</th>
          <th>Borrowed</th>
          <th>Due Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $i = 1; foreach ($rows as $row):
          $status = strtolower($row['book_status']);
          $badgeClass = match($status) {
            'available' => 'badge-available',
            'borrowed'  => 'badge-borrowed',
            'overdue'   => 'badge-overdue',
            'reserved'  => 'badge-reserved',
            default     => 'badge-borrowed',
          };
          $isOverdue = $status === 'borrowed' && !empty($row['duedate']) && strtotime($row['duedate']) < time();
        ?>
        <tr onclick="openRecord(<?= htmlspecialchars(json_encode($row)) ?>)" title="Click to view details">
          <td class="td-num"><?= $i++ ?></td>
          <td><div class="td-title"><?= htmlspecialchars($row['book_title']) ?></div></td>
          <td class="td-muted"><?= htmlspecialchars($row['book_author']) ?></td>
          <td class="td-muted" style="font-size:12.5px"><?= htmlspecialchars($row['book_genre']) ?></td>
          <td>
            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($row['book_status']) ?></span>
            <?php if ($isOverdue): ?>
              <span style="font-size:10.5px;color:var(--red);display:block;margin-top:3px">⚠ Overdue</span>
            <?php endif; ?>
          </td>
          <td class="td-borrower">
            <?php if (!empty($row['student_name'])): ?>
              <div class="student-name"><?= htmlspecialchars($row['student_name']) ?></div>
            <?php else: ?><span style="color:var(--muted)">—</span><?php endif; ?>
          </td>
          <td class="td-borrower">
            <?php if (!empty($row['student_block'])): ?>
              <div class="student-sub"><?= htmlspecialchars($row['student_block']) ?></div>
            <?php else: ?><span style="color:var(--muted)">—</span><?php endif; ?>
          </td>
          <td class="td-borrower">
            <?php if (!empty($row['student_number'])): ?>
              <div class="student-sub"><?= htmlspecialchars($row['student_number']) ?></div>
            <?php else: ?><span style="color:var(--muted)">—</span><?php endif; ?>
          </td>
          <td class="td-muted" style="font-size:12.5px;white-space:nowrap;">
            <?= $row['borrowed_date'] ? date('M d, Y', strtotime($row['borrowed_date'])) : '—' ?>
          </td>
          <td style="white-space:nowrap;">
            <?php if ($row['duedate']): ?>
              <?php $isPast = strtotime($row['duedate']) < time() && $status === 'borrowed'; ?>
              <span style="font-size:12.5px;color:<?= $isPast ? 'var(--red)' : 'var(--muted)' ?>;font-weight:<?= $isPast ? '700' : '400' ?>">
                <?= date('M d, Y', strtotime($row['duedate'])) ?>
              </span>
            <?php else: ?><span style="color:var(--muted)">—</span><?php endif; ?>
          </td>
          <td onclick="event.stopPropagation()">
            <div class="actions">
              <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-gold btn-sm btn-icon" title="Edit">Edit</a>
              <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-red btn-sm btn-icon" title="Delete"
                 onclick="return confirm('Delete this record?')">🗑️</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
      <div class="icon"></div>
      <p><?= $search !== '' ? 'No records matched your search.<br>Try different keywords.' : 'No book records yet.<br>Click <strong>Add New Book</strong> to get started.' ?></p>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── VIEW RECORD MODAL ── -->
<div class="modal-overlay" id="viewModal">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-header">
      <div>
        <h3 id="vTitle">Book Details</h3>
        <p id="vAuthorGenre">—</p>
      </div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">

      <div class="del-confirm" id="delConfirm">
        <p>Are you sure you want to delete this record? This cannot be undone.</p>
        <div class="del-btns">
          <a href="#" class="btn btn-red btn-sm" id="delConfirmLink">Yes, Delete</a>
          <button class="btn btn-ghost btn-sm" onclick="document.getElementById('delConfirm').classList.remove('show')">Cancel</button>
        </div>
      </div>

      <div class="rec-section">
        <div class="rec-section-title">Book Information</div>
        <div class="rec-row"><span class="rec-key">Title</span><span class="rec-val" id="vBookTitle">—</span></div>
        <div class="rec-row"><span class="rec-key">Author</span><span class="rec-val" id="vBookAuthor">—</span></div>
        <div class="rec-row"><span class="rec-key">Genre</span><span class="rec-val" id="vBookGenre">—</span></div>
        <div class="rec-row"><span class="rec-key">Status</span><span class="rec-val" id="vStatus">—</span></div>
      </div>

      <div class="rec-section" id="vBorrowerSection">
        <div class="rec-section-title">Borrower Information</div>
        <div class="rec-row"><span class="rec-key">Student Name</span><span class="rec-val" id="vSName">—</span></div>
        <div class="rec-row"><span class="rec-key">Block / Section</span><span class="rec-val" id="vSBlock">—</span></div>
        <div class="rec-row"><span class="rec-key">Student No.</span><span class="rec-val" id="vSSno">—</span></div>
      </div>

      <div class="rec-section">
        <div class="rec-section-title">Schedule</div>
        <div class="rec-row"><span class="rec-key">Borrowed Date</span><span class="rec-val" id="vBorrow">—</span></div>
        <div class="rec-row"><span class="rec-key">Due Date</span><span class="rec-val" id="vDue">—</span></div>
      </div>
    </div>
    <div class="modal-footer">
      <a href="#" class="btn btn-gold btn-sm" id="vEditLink">Edit</a>
      <button class="btn btn-red btn-sm" onclick="showDelConfirm()">Delete</button>
      <button class="btn btn-ghost btn-sm" style="margin-left:auto" onclick="closeModal()">Close</button>
    </div>
  </div>
</div>

<script>
let currentId = null;

function openRecord(row) {
  currentId = row.id;

  document.getElementById('vTitle').textContent       = row.book_title || '—';
  document.getElementById('vAuthorGenre').textContent = (row.book_author || '—') + ' · ' + (row.book_genre || '—');
  document.getElementById('vBookTitle').textContent   = row.book_title  || '—';
  document.getElementById('vBookAuthor').textContent  = row.book_author || '—';
  document.getElementById('vBookGenre').textContent   = row.book_genre  || '—';

  const status = row.book_status || '—';
  const badges = {available:'badge-available', borrowed:'badge-borrowed', overdue:'badge-overdue', reserved:'badge-reserved'};
  const cls    = badges[status.toLowerCase()] || 'badge-borrowed';
  document.getElementById('vStatus').innerHTML = `<span class="badge ${cls}">${status}</span>`;

  document.getElementById('vSName').textContent  = row.student_name   || '—';
  document.getElementById('vSBlock').textContent = row.student_block  || '—';
  document.getElementById('vSSno').textContent   = row.student_number || '—';

  document.getElementById('vBorrow').textContent = row.borrowed_date ? formatDate(row.borrowed_date) : '—';
  document.getElementById('vDue').textContent    = row.duedate        ? formatDate(row.duedate)       : '—';

  document.getElementById('vEditLink').href       = `edit.php?id=${row.id}`;
  document.getElementById('delConfirmLink').href  = `delete.php?id=${row.id}`;
  document.getElementById('delConfirm').classList.remove('show');

  document.getElementById('viewModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('viewModal').classList.remove('open');
  document.body.style.overflow = '';
}

function showDelConfirm() {
  document.getElementById('delConfirm').classList.add('show');
}

function formatDate(s) {
  if (!s) return '—';
  const d = new Date(s + 'T00:00:00');
  return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}

document.getElementById('viewModal').addEventListener('click', e => {
  if (e.target === document.getElementById('viewModal')) closeModal();
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeModal();
});
</script>
</body>
</html>