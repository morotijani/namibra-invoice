<?php
session_start();
require 'config.php';

if (!isset($_GET['id'])) {
    die("Invoice ID is required.");
}

$invoice_id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = :id");
$stmt->execute([':id' => $invoice_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    die("Invoice not found.");
}

$itemStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = :id");
$itemStmt->execute([':id' => $invoice_id]);
$items = $itemStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Invoice <?= htmlspecialchars($invoice['invoice_no']) ?> — Namibra Software</title>
  <link
    href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Mono:wght@400;500&family=DM+Sans:wght@300;400;500;600;700&display=swap"
    rel="stylesheet" />
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
      --danger: #d9534f;
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--ink);
      padding: 40px 20px;
    }

    .container {
      max-width: 900px;
      margin: 0 auto;
      background: var(--white);
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 10px 40px rgba(15, 61, 46, .10);
    }

    h1 {
      font-family: 'DM Serif Display', serif;
      color: var(--green);
      margin-bottom: 24px;
    }

    .grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      margin-bottom: 16px;
    }

    label {
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 6px;
      color: var(--ink-soft);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    input,
    select,
    textarea {
      padding: 12px;
      border: 1px solid var(--rule);
      border-radius: 6px;
      font-family: 'DM Sans', sans-serif;
      font-size: 15px;
    }

    input:focus,
    select:focus,
    textarea:focus {
      outline: none;
      border-color: var(--gold);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      margin-bottom: 20px;
    }

    th {
      text-align: left;
      background: var(--bg);
      padding: 12px;
      font-size: 12px;
      text-transform: uppercase;
      color: var(--green);
    }

    td {
      padding: 10px;
      border-bottom: 1px solid var(--rule);
    }

    .btn {
      padding: 12px 24px;
      border: none;
      border-radius: 6px;
      background: var(--green);
      color: var(--white);
      font-weight: 600;
      cursor: pointer;
      font-size: 15px;
      text-decoration: none;
    }

    .btn:hover {
      background: var(--green-deep);
    }

    .btn-outline {
      background: transparent;
      border: 1px solid var(--gold);
      color: var(--gold);
    }

    .btn-outline:hover {
      background: var(--gold);
      color: var(--white);
    }

    .btn-danger {
      background: transparent;
      color: var(--danger);
      border: 1px solid var(--danger);
      padding: 8px 16px;
      font-size: 13px;
    }

    .btn-danger:hover {
      background: var(--danger);
      color: var(--white);
    }

    .totals {
      display: flex;
      justify-content: flex-end;
      font-size: 18px;
      font-weight: bold;
      margin-top: 20px;
      padding-top: 20px;
      border-top: 2px solid var(--rule);
    }
  </style>
</head>

<body>

  <div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
      <h1>Edit Invoice <?= htmlspecialchars($invoice['invoice_no']) ?></h1>
      <div style="display: flex; gap: 10px;">
        <a href="preview.php?id=<?= $invoice_id ?>" class="btn btn-outline" style="text-decoration: none;">Cancel</a>
        <a href="invoices.php" class="btn btn-outline" style="text-decoration: none;">View All Invoices</a>
      </div>
    </div>

    <form action="update_invoice.php" method="POST">
      <input type="hidden" name="invoice_id" value="<?= $invoice_id ?>">

      <h3>Invoice Details</h3>
      <div class="grid-2">
        <div class="form-group">
          <label>Issue Date</label>
          <input type="date" name="issue_date" required value="<?= htmlspecialchars($invoice['issue_date']) ?>">
        </div>
        <div class="form-group">
          <label>Due Date</label>
          <input type="date" name="due_date" required value="<?= htmlspecialchars($invoice['due_date']) ?>">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" required>
            <option value="new" <?= $invoice['status'] === 'new' ? 'selected' : '' ?>>New</option>
            <option value="pending" <?= $invoice['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="partial payment" <?= $invoice['status'] === 'partial payment' ? 'selected' : '' ?>>Partial Payment</option>
            <option value="full paid" <?= $invoice['status'] === 'full paid' ? 'selected' : '' ?>>Full Paid</option>
            <option value="completed" <?= $invoice['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
          </select>
        </div>
      </div>

      <h3>Bill To</h3>
      <div class="grid-2">
        <div class="form-group">
          <label>Client / Company Name</label>
          <input type="text" name="bill_to_name" required value="<?= htmlspecialchars($invoice['bill_to_name']) ?>" placeholder="e.g. Porche's Kiddy Mall">
        </div>
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="bill_to_email" value="<?= htmlspecialchars($invoice['bill_to_email']) ?>" placeholder="e.g. client@example.com">
        </div>
        <div class="form-group">
          <label>Town / City</label>
          <input type="text" name="bill_to_town_city" required value="<?= htmlspecialchars($invoice['bill_to_town_city']) ?>" placeholder="e.g. Adum, Kumasi">
        </div>
        <div class="form-group">
          <label>Region / State, Country</label>
          <input type="text" name="bill_to_region_country" required value="<?= htmlspecialchars($invoice['bill_to_region_country']) ?>" placeholder="e.g. Ashanti Region, Ghana">
        </div>
        <div class="form-group">
          <label>Telephone Number</label>
          <input type="text" name="bill_to_phone" required value="<?= htmlspecialchars($invoice['bill_to_phone']) ?>" placeholder="e.g. +233 54 303 3637">
        </div>
      </div>

      <h3>Items</h3>
      <table id="itemsTable">
        <thead>
          <tr>
            <th style="width: 50%">Description & Note</th>
            <th style="width: 10%">Qty</th>
            <th style="width: 15%">Rate (GH₵)</th>
            <th style="width: 15%">Amount (GH₵)</th>
            <th style="width: 10%">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): 
            $parts = explode("||", $item['description']);
            $desc = $parts[0] ?? '';
            $note = $parts[1] ?? '';
          ?>
          <tr>
            <td>
              <input type="text" name="item_desc[]" placeholder="Item Name" required value="<?= htmlspecialchars($desc) ?>" style="width: 100%; margin-bottom: 5px;">
              <input type="text" name="item_note[]" placeholder="Item note (optional)" value="<?= htmlspecialchars($note) ?>" style="width: 100%; font-size: 13px;">
            </td>
            <td><input type="number" name="item_qty[]" value="<?= htmlspecialchars($item['quantity']) ?>" min="1" class="qty" required style="width: 100%"></td>
            <td><input type="number" name="item_rate[]" value="<?= htmlspecialchars($item['rate']) ?>" step="0.01" min="0" class="rate" required style="width: 100%"></td>
            <td><input type="text" class="amount" value="<?= htmlspecialchars($item['amount']) ?>" readonly style="width: 100%; background: #eee;"></td>
            <td><button type="button" class="btn-danger removeRowBtn">X</button></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($items)): ?>
          <tr>
            <td>
              <input type="text" name="item_desc[]" placeholder="Item Name" required style="width: 100%; margin-bottom: 5px;">
              <input type="text" name="item_note[]" placeholder="Item note (optional)" style="width: 100%; font-size: 13px;">
            </td>
            <td><input type="number" name="item_qty[]" value="1" min="1" class="qty" required style="width: 100%"></td>
            <td><input type="number" name="item_rate[]" value="0" step="0.01" min="0" class="rate" required style="width: 100%"></td>
            <td><input type="text" class="amount" value="0.00" readonly style="width: 100%; background: #eee;"></td>
            <td><button type="button" class="btn-danger removeRowBtn">X</button></td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <button type="button" class="btn btn-outline" id="addRowBtn">+ Add Row</button>

      <h3 style="margin-top: 40px;">Payment & Discounts</h3>
      <div class="grid-2">
        <div class="form-group">
          <label>Discount (GH₵)</label>
          <input type="number" step="0.01" min="0" name="discount" id="discountInput" value="<?= htmlspecialchars($invoice['discount']) ?>">
        </div>
        <div class="form-group">
          <label>Deposit Percentage (%)</label>
          <input type="number" step="1" min="0" max="100" name="deposit_percentage" id="depositPercentageInput" value="<?= htmlspecialchars($invoice['deposit_percentage']) ?>">
        </div>
        <div class="form-group">
          <label>Deposit Status</label>
          <select name="deposit_status" id="depositStatusInput">
            <option value="pending" <?= $invoice['deposit_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="paid" <?= $invoice['deposit_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
          </select>
        </div>
        <div class="form-group">
          <label>Deposit Paid Date (If Paid)</label>
          <input type="date" name="deposit_paid_date" value="<?= htmlspecialchars($invoice['deposit_paid_date']) ?>">
        </div>
      </div>

      <div class="totals" style="flex-direction: column; align-items: flex-end; font-size: 15px; font-weight: normal; border-top: none;">
        <div style="margin-bottom: 8px;">Project Amount: &nbsp;<strong>GH₵ <span id="grandTotal"><?= htmlspecialchars($invoice['project_amount']) ?></span></strong></div>
        <div style="margin-bottom: 8px; color: var(--danger);">Discount: &nbsp;<strong>- GH₵ <span id="discountDisplay"><?= htmlspecialchars($invoice['discount']) ?></span></strong></div>
        <div style="margin-bottom: 8px; font-size: 17px;">Net Project Total: &nbsp;<strong>GH₵ <span id="netTotalDisplay"><?= htmlspecialchars($invoice['net_total']) ?></span></strong></div>
        <div style="margin-bottom: 8px; color: var(--gold);">Deposit (<span id="depPercentDisplay"><?= htmlspecialchars($invoice['deposit_percentage']) ?></span>%): &nbsp;<strong>- GH₵ <span id="depositDisplay"><?= htmlspecialchars($invoice['deposit_amount']) ?></span></strong></div>
        <div style="font-size: 22px; color: var(--green); font-weight: bold; margin-top: 10px; border-top: 2px solid var(--rule); padding-top: 10px;">Balance Remaining: &nbsp;GH₵ <span id="balanceDisplay"><?= htmlspecialchars($invoice['balance_remaining']) ?></span></div>
      </div>

      <input type="hidden" name="project_amount" id="project_amount_input" value="<?= htmlspecialchars($invoice['project_amount']) ?>">
      <input type="hidden" name="net_total" id="net_total_input" value="<?= htmlspecialchars($invoice['net_total']) ?>">
      <input type="hidden" name="deposit_amount" id="deposit_amount_input" value="<?= htmlspecialchars($invoice['deposit_amount']) ?>">
      <input type="hidden" name="balance_remaining" id="balance_remaining_input" value="<?= htmlspecialchars($invoice['balance_remaining']) ?>">
      <input type="hidden" name="total_due" id="total_due_input" value="<?= htmlspecialchars($invoice['total_due']) ?>">

      <div style="margin-top: 40px; text-align: right;">
        <button type="submit" class="btn">Update & Preview Invoice</button>
      </div>
    </form>

  </div>

  <script>
    const tableBody = document.querySelector('#itemsTable tbody');
    const addRowBtn = document.getElementById('addRowBtn');
    const grandTotalSpan = document.getElementById('grandTotal');
    const projectAmountInput = document.getElementById('project_amount_input');
    const totalDueInput = document.getElementById('total_due_input');

    const discountInput = document.getElementById('discountInput');
    const depositPercentageInput = document.getElementById('depositPercentageInput');

    const discountDisplay = document.getElementById('discountDisplay');
    const netTotalDisplay = document.getElementById('netTotalDisplay');
    const depPercentDisplay = document.getElementById('depPercentDisplay');
    const depositDisplay = document.getElementById('depositDisplay');
    const balanceDisplay = document.getElementById('balanceDisplay');

    const netTotalInput = document.getElementById('net_total_input');
    const depositAmountInput = document.getElementById('deposit_amount_input');
    const balanceRemainingInput = document.getElementById('balance_remaining_input');

    function calculateTotals() {
      let grandTotal = 0;
      const rows = tableBody.querySelectorAll('tr');

      rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.qty').value) || 0;
        const rate = parseFloat(row.querySelector('.rate').value) || 0;
        const amount = qty * rate;

        row.querySelector('.amount').value = amount.toFixed(2);
        grandTotal += amount;
      });

      const discount = parseFloat(discountInput.value) || 0;
      const netTotal = Math.max(0, grandTotal - discount);
      
      const depositPercent = parseFloat(depositPercentageInput.value) || 0;
      const depositAmount = (netTotal * depositPercent) / 100;
      
      const balance = netTotal - depositAmount;

      grandTotalSpan.textContent = grandTotal.toFixed(2);
      discountDisplay.textContent = discount.toFixed(2);
      netTotalDisplay.textContent = netTotal.toFixed(2);
      depPercentDisplay.textContent = depositPercent;
      depositDisplay.textContent = depositAmount.toFixed(2);
      balanceDisplay.textContent = balance.toFixed(2);

      projectAmountInput.value = grandTotal.toFixed(2);
      netTotalInput.value = netTotal.toFixed(2);
      depositAmountInput.value = depositAmount.toFixed(2);
      balanceRemainingInput.value = balance.toFixed(2);
      totalDueInput.value = netTotal.toFixed(2);
    }

    discountInput.addEventListener('input', calculateTotals);
    depositPercentageInput.addEventListener('input', calculateTotals);

    addRowBtn.addEventListener('click', () => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
      <td>
        <input type="text" name="item_desc[]" placeholder="Item Name" required style="width: 100%; margin-bottom: 5px;">
        <input type="text" name="item_note[]" placeholder="Item note (optional)" style="width: 100%; font-size: 13px;">
      </td>
      <td><input type="number" name="item_qty[]" value="1" min="1" class="qty" required style="width: 100%"></td>
      <td><input type="number" name="item_rate[]" value="0" step="0.01" min="0" class="rate" required style="width: 100%"></td>
      <td><input type="text" class="amount" value="0.00" readonly style="width: 100%; background: #eee;"></td>
      <td><button type="button" class="btn-danger removeRowBtn">X</button></td>
    `;
      tableBody.appendChild(tr);
    });

    tableBody.addEventListener('input', (e) => {
      if (e.target.classList.contains('qty') || e.target.classList.contains('rate')) {
        calculateTotals();
      }
    });

    tableBody.addEventListener('click', (e) => {
      if (e.target.classList.contains('removeRowBtn')) {
        e.target.closest('tr').remove();
        calculateTotals();
      }
    });

    // Run calculation once on load to ensure accuracy
    calculateTotals();
  </script>
</body>

</html>
