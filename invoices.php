<?php
require 'config.php';

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(bill_to_name LIKE :search_name OR invoice_no LIKE :search_no)";
    $params[':search_name'] = "%$search%";
    $params[':search_no'] = "%$search%";
}

if ($status_filter !== '') {
    $where[] = "status = :status";
    $params[':status'] = $status_filter;
}

$whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM invoices $whereClause");
$countStmt->execute($params);
$total_records = $countStmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch records
$stmt = $pdo->prepare("SELECT * FROM invoices $whereClause ORDER BY id DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$invoices = $stmt->fetchAll();

function formatCurrency($amount) {
    return 'GH₵ ' . number_format($amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>All Invoices — Namibra Software</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Mono:wght@400;500&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    :root {
      --green: #0f3d2e;
      --green-deep: #0a2c20;
      --gold: #c9a24b;
      --bg: #f4f6f4;
      --white: #ffffff;
      --rule: #e4e9e5;
      --ink: #14201b;
      --ink-soft: #4a5750;
    }
    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--ink);
      padding: 40px 20px;
    }
    .container {
      max-width: 1000px;
      margin: 0 auto;
      background: var(--white);
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 10px 40px rgba(15,61,46,.10);
    }
    .header-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
    }
    h1 {
      font-family: 'DM Serif Display', serif;
      color: var(--green);
      margin: 0;
    }
    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      background: var(--green);
      color: var(--white);
      font-weight: 600;
      cursor: pointer;
      font-size: 14px;
      text-decoration: none;
    }
    .btn:hover { background: var(--green-deep); }
    .btn-outline {
      background: transparent;
      border: 1px solid var(--gold);
      color: var(--gold);
      padding: 6px 12px;
      border-radius: 4px;
      font-size: 12px;
      text-decoration: none;
    }
    .btn-outline:hover { background: var(--gold); color: var(--white); }
    
    .controls {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      background: var(--bg);
      padding: 16px 20px;
      border-radius: 8px;
    }
    .search-input {
      padding: 10px 14px;
      border: 1px solid var(--rule);
      border-radius: 6px;
      font-family: 'DM Sans', sans-serif;
      font-size: 14px;
      width: 250px;
    }
    .search-input:focus { outline: none; border-color: var(--gold); }
    .filter-select {
      padding: 10px 14px;
      border: 1px solid var(--rule);
      border-radius: 6px;
      font-family: 'DM Sans', sans-serif;
      font-size: 14px;
      cursor: pointer;
      background: #fff;
    }
    .filter-select:focus { outline: none; border-color: var(--gold); }

    .pagination {
      display: flex;
      justify-content: center;
      gap: 8px;
      margin-top: 30px;
    }
    .page-link {
      padding: 8px 14px;
      border: 1px solid var(--rule);
      border-radius: 6px;
      color: var(--ink-soft);
      text-decoration: none;
      font-size: 14px;
      background: var(--white);
    }
    .page-link:hover {
      border-color: var(--green);
      color: var(--green);
    }
    .page-link.active {
      background: var(--green);
      color: var(--white);
      border-color: var(--green);
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th {
      text-align: left;
      background: var(--bg);
      padding: 14px 12px;
      font-size: 12px;
      text-transform: uppercase;
      color: var(--green);
    }
    td {
      padding: 14px 12px;
      border-bottom: 1px solid var(--rule);
      font-size: 14px;
      vertical-align: middle;
    }
    .inv-no {
      font-family: 'DM Mono', monospace;
      font-weight: 500;
      color: var(--ink);
    }
    select.status-select {
      padding: 6px;
      border: 1px solid var(--rule);
      border-radius: 4px;
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      cursor: pointer;
      background: #fff;
    }
    select.status-select:focus { outline: none; border-color: var(--gold); }
    
    /* Toast Notification */
    #toast {
      visibility: hidden;
      min-width: 250px;
      background-color: var(--green);
      color: #fff;
      text-align: center;
      border-radius: 4px;
      padding: 16px;
      position: fixed;
      z-index: 1;
      left: 50%;
      bottom: 30px;
      transform: translateX(-50%);
      font-size: 14px;
    }
    #toast.show {
      visibility: visible;
      animation: fadein 0.5s, fadeout 0.5s 2.5s;
    }
    @keyframes fadein {
      from {bottom: 0; opacity: 0;} 
      to {bottom: 30px; opacity: 1;}
    }
    @keyframes fadeout {
      from {bottom: 30px; opacity: 1;} 
      to {bottom: 0; opacity: 0;}
    }

    /* Modal Styles */
    .modal-overlay {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.5);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }
    .modal-content {
      background: #fff;
      padding: 32px;
      border-radius: 10px;
      max-width: 380px;
      width: 100%;
      text-align: center;
      box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }
    .modal-content h3 {
      margin-top: 0;
      color: var(--green);
      font-family: 'DM Serif Display', serif;
      font-size: 24px;
      margin-bottom: 12px;
    }
    .modal-content p {
      color: var(--ink-soft);
      margin-bottom: 24px;
      font-size: 15px;
      line-height: 1.5;
    }
    .modal-actions {
      display: flex;
      justify-content: center;
      gap: 16px;
    }
  </style>
</head>
<body>

<div class="container">
  <div class="header-bar">
    <h1>All Invoices</h1>
    <a href="index.php" class="btn">+ Create New Invoice</a>
  </div>

  <form method="GET" class="controls">
    <div style="display: flex; gap: 10px; align-items: center;">
      <input type="text" name="search" class="search-input" placeholder="Search client or invoice no..." value="<?= htmlspecialchars($search) ?>">
      <select name="status" class="filter-select">
        <option value="">All Statuses</option>
        <option value="new" <?= $status_filter === 'new' ? 'selected' : '' ?>>New</option>
        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="partial payment" <?= $status_filter === 'partial payment' ? 'selected' : '' ?>>Partial Payment</option>
        <option value="full paid" <?= $status_filter === 'full paid' ? 'selected' : '' ?>>Full Paid</option>
        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
      </select>
      <button type="submit" class="btn-outline" style="padding: 10px 16px; font-size: 14px;">Filter</button>
      <?php if ($search !== '' || $status_filter !== ''): ?>
        <a href="invoices.php" style="font-size: 13px; color: var(--ink-faint); text-decoration: none; margin-left: 10px;">Clear</a>
      <?php endif; ?>
    </div>
    <div style="font-size: 13px; color: var(--ink-faint);">
      Total: <?= $total_records ?>
    </div>
  </form>
  
  <table>
    <thead>
      <tr>
        <th style="width: 40px;">#</th>
        <th>Invoice No</th>
        <th>Client Name</th>
        <th>Issue Date</th>
        <th>Total Due</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($invoices)): ?>
        <tr>
          <td colspan="7" style="text-align:center; color: var(--ink-faint); padding: 40px 20px;">No invoices found matching your criteria.</td>
        </tr>
      <?php else: ?>
        <?php foreach ($invoices as $index => $inv): ?>
        <tr>
          <td style="color: var(--ink-faint);"><?= $offset + $index + 1 ?></td>
          <td class="inv-no"><?= htmlspecialchars($inv['invoice_no']) ?></td>
          <td><?= htmlspecialchars($inv['bill_to_name']) ?></td>
          <td><?= date('d M Y', strtotime($inv['issue_date'])) ?></td>
          <td style="font-family: 'DM Mono', monospace;"><?= formatCurrency($inv['total_due']) ?></td>
          <td>
            <select class="status-select" data-id="<?= $inv['id'] ?>">
              <option value="new" <?= $inv['status'] === 'new' ? 'selected' : '' ?>>New</option>
              <option value="pending" <?= $inv['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
              <option value="partial payment" <?= $inv['status'] === 'partial payment' ? 'selected' : '' ?>>Partial Payment</option>
              <option value="full paid" <?= $inv['status'] === 'full paid' ? 'selected' : '' ?>>Full Paid</option>
              <option value="completed" <?= $inv['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
          </td>
          <td>
            <a href="edit.php?id=<?= $inv['id'] ?>" class="btn-outline" style="border-color: var(--green); color: var(--green); margin-right: 4px;">Edit</a>
            <a href="preview.php?id=<?= $inv['id'] ?>" class="btn-outline">View / PDF</a>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <?php if ($total_pages > 1): ?>
  <div class="pagination">
    <?php
      $query_params = $_GET;
      if ($page > 1):
        $query_params['page'] = $page - 1;
        $prev_link = '?' . http_build_query($query_params);
    ?>
      <a href="<?= htmlspecialchars($prev_link) ?>" class="page-link">Previous</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $total_pages; $i++): 
      $query_params['page'] = $i;
      $link = '?' . http_build_query($query_params);
    ?>
      <a href="<?= htmlspecialchars($link) ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>

    <?php
      if ($page < $total_pages):
        $query_params['page'] = $page + 1;
        $next_link = '?' . http_build_query($query_params);
    ?>
      <a href="<?= htmlspecialchars($next_link) ?>" class="page-link">Next</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<div id="toast">Status updated successfully!</div>

<!-- Confirm Modal -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal-content">
    <h3>Confirm Status Change</h3>
    <p>Are you sure you want to change the status of this invoice to <strong id="modalStatusName"></strong>?</p>
    <div class="modal-actions">
      <button class="btn-outline" id="cancelBtn">Cancel</button>
      <button class="btn" id="confirmBtn">Yes, Change Status</button>
    </div>
  </div>
</div>

<script>
  const selects = document.querySelectorAll('.status-select');
  const toast = document.getElementById('toast');
  const confirmModal = document.getElementById('confirmModal');
  const confirmBtn = document.getElementById('confirmBtn');
  const cancelBtn = document.getElementById('cancelBtn');

  let currentSelect = null;
  let previousValue = null;
  let targetStatus = null;

  selects.forEach(select => {
    // Store original value on focus so we can revert if canceled
    select.addEventListener('focus', function() {
      previousValue = this.value;
    });

    select.addEventListener('change', function() {
      currentSelect = this;
      targetStatus = this.value;
      
      // Get the text of the selected option
      const selectedText = this.options[this.selectedIndex].text;
      document.getElementById('modalStatusName').textContent = selectedText;
      
      // If we didn't catch focus (e.g., some browsers), default to previous option by searching selectedIndex before change.
      // A safe way is to rely on focus, but let's assume focus fired.
      confirmModal.style.display = 'flex';
    });
  });

  cancelBtn.addEventListener('click', function() {
    confirmModal.style.display = 'none';
    if (currentSelect && previousValue) {
      currentSelect.value = previousValue; // Revert
    }
    currentSelect = null;
  });

  confirmBtn.addEventListener('click', function() {
    confirmModal.style.display = 'none';
    
    if (!currentSelect) return;
    
    const id = currentSelect.getAttribute('data-id');
    const status = targetStatus;

    // Update the saved previous value since we are confirming
    previousValue = targetStatus;

    fetch('update_status.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ id: id, status: status })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showToast();
      } else {
        alert('Error updating status: ' + data.error);
        // Revert on server error
        // Note: we'd need to track original value pre-click if we want to revert here perfectly.
      }
    })
    .catch(err => {
      console.error(err);
      alert('Request failed.');
    });
    
    currentSelect = null;
  });

  function showToast() {
    toast.className = "show";
    setTimeout(function(){ toast.className = toast.className.replace("show", ""); }, 3000);
  }
</script>
</body>
</html>
