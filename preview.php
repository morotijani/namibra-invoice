<?php
session_start();
require 'config.php';

if (!isset($_GET['id'])) {
  die("Invoice ID is required.");
}

$invoice_id = (int) $_GET['id'];

// Fetch invoice
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = :id");
$stmt->execute([':id' => $invoice_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
  die("Invoice not found.");
}

// Fetch items
$itemStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = :id");
$itemStmt->execute([':id' => $invoice_id]);
$items = $itemStmt->fetchAll();

function formatCurrency($amount)
{
  return 'GH₵ ' . number_format($amount, 2);
}

// Format status label
$status_class = 'status-' . str_replace(' ', '-', $invoice['status']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Invoice <?= htmlspecialchars($invoice['invoice_no']) ?> — Namibra Software Technologies LTD</title>
  <link
    href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Mono:wght@400;500&family=DM+Sans:wght@300;400;500;600;700&display=swap"
    rel="stylesheet" />
  <style>
    :root {
      --green: #0f3d2e;
      --green-deep: #0a2c20;
      --green-soft: #1c5440;
      --gold: #c9a24b;
      --gold-soft: #e7d4a3;
      --ink: #14201b;
      --ink-soft: #4a5750;
      --ink-faint: #8b988f;
      --rule: #e4e9e5;
      --bg: #f4f6f4;
      --white: #ffffff;
      --cream: #faf8f2;
      --shadow: 0 10px 40px rgba(15, 61, 46, .10), 0 2px 6px rgba(15, 61, 46, .05);
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--ink);
      min-height: 100vh;
      padding: 48px 24px;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    /* ACTION BAR (Hidden in print/pdf) */
    .action-bar {
      max-width: 860px;
      margin: 0 auto 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #fff;
      padding: 15px 25px;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, .05);
    }

    .action-bar .left-acts {
      display: flex;
      align-items: center;
      gap: 24px;
    }

    .action-bar .left-acts a {
      text-decoration: none;
      color: var(--ink-soft);
      font-size: 14px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .action-bar .left-acts a:hover {
      color: var(--green);
    }

    .btn-pdf {
      background: var(--green);
      color: #fff;
      text-decoration: none;
      padding: 10px 20px;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 600;
    }

    .btn-pdf:hover {
      background: var(--green-deep);
    }

    .page {
      max-width: 860px;
      margin: 0 auto;
      background: var(--white);
      border-radius: 14px;
      box-shadow: var(--shadow);
      overflow: hidden;
      animation: rise .55s cubic-bezier(.22, 1, .36, 1) both;
    }

    @keyframes rise {
      from {
        opacity: 0;
        transform: translateY(18px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .header {
      background: var(--green);
      background-image:
        radial-gradient(120% 140% at 100% 0%, rgba(201, 162, 75, .18) 0%, transparent 45%),
        linear-gradient(180deg, var(--green) 0%, var(--green-deep) 100%);
      color: #fff;
      padding: 44px 52px 38px;
      position: relative;
    }

    .header::after {
      content: "";
      position: absolute;
      left: 0;
      right: 0;
      bottom: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--gold) 0%, var(--gold-soft) 50%, var(--gold) 100%);
    }

    .header-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 28px;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .brand-mark {
      width: 52px;
      height: 52px;
      border: 1.5px solid var(--gold);
      border-radius: 11px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'DM Serif Display', serif;
      font-size: 26px;
      color: var(--gold-soft);
      flex-shrink: 0;
    }

    .brand-name {
      font-family: 'DM Serif Display', serif;
      font-size: 23px;
      line-height: 1.1;
      letter-spacing: .2px;
    }

    .brand-sub {
      font-size: 12px;
      color: var(--gold-soft);
      letter-spacing: 1.5px;
      text-transform: uppercase;
      margin-top: 5px;
    }

    .doc-title {
      text-align: right;
    }

    .doc-title h1 {
      font-family: 'DM Serif Display', serif;
      font-size: 40px;
      letter-spacing: 3px;
      color: var(--gold-soft);
      line-height: 1;
    }

    .doc-title .doc-no {
      font-family: 'DM Mono', monospace;
      font-size: 13px;
      color: rgba(255, 255, 255, .75);
      margin-top: 8px;
      letter-spacing: .5px;
    }

    .status-badge {
      display: inline-block;
      margin-top: 8px;
      padding: 4px 10px;
      border-radius: 4px;
      font-size: 10px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .status-new {
      background: #e0f2fe;
      color: #0284c7;
    }

    .status-pending {
      background: #fef08a;
      color: #854d0e;
    }

    .status-partial-payment {
      background: #fed7aa;
      color: #c2410c;
    }

    .status-full-paid {
      background: #bbf7d0;
      color: #166534;
    }

    .status-completed {
      background: #dcfce3;
      color: #14532d;
    }

    .meta {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1px;
      background: rgba(255, 255, 255, .12);
      border: 1px solid rgba(255, 255, 255, .12);
      border-radius: 9px;
      overflow: hidden;
      margin-top: 30px;
    }

    .meta-cell {
      background: rgba(255, 255, 255, .04);
      padding: 14px 18px;
    }

    .meta-cell .lbl {
      font-size: 10px;
      letter-spacing: 1.4px;
      text-transform: uppercase;
      color: var(--gold-soft);
    }

    .meta-cell .val {
      font-family: 'DM Mono', monospace;
      font-size: 14px;
      color: #fff;
      margin-top: 4px;
    }

    .parties {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 40px;
      padding: 40px 52px 8px;
    }

    .party-tag {
      font-size: 10px;
      letter-spacing: 1.6px;
      text-transform: uppercase;
      color: var(--gold);
      font-weight: 600;
      margin-bottom: 10px;
    }

    .party-name {
      font-family: 'DM Serif Display', serif;
      font-size: 18px;
      color: var(--ink);
      margin-bottom: 8px;
    }

    .party-info {
      font-size: 13px;
      line-height: 1.7;
      color: var(--ink-soft);
    }

    .table-wrap {
      padding: 32px 52px 0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    thead th {
      background: var(--cream);
      font-size: 10px;
      letter-spacing: 1.3px;
      text-transform: uppercase;
      color: var(--green-soft);
      font-weight: 700;
      text-align: left;
      padding: 13px 16px;
      border-bottom: 2px solid var(--gold);
    }

    thead th.num {
      text-align: right;
    }

    tbody td {
      padding: 17px 16px;
      font-size: 14px;
      color: var(--ink);
      border-bottom: 1px solid var(--rule);
      vertical-align: top;
    }

    tbody td.num {
      text-align: right;
      font-family: 'DM Mono', monospace;
    }

    .item-desc {
      font-weight: 500;
    }

    .item-note {
      font-size: 12px;
      color: var(--ink-faint);
      margin-top: 3px;
    }

    .totals {
      display: flex;
      justify-content: flex-end;
      padding: 24px 52px 0;
    }

    .totals-box {
      width: 340px;
    }

    .tot-row {
      display: flex;
      justify-content: space-between;
      padding: 11px 0;
      font-size: 14px;
      color: var(--ink-soft);
      border-bottom: 1px solid var(--rule);
    }

    .tot-row .v {
      font-family: 'DM Mono', monospace;
      color: var(--ink);
    }

    .tot-row.credit .v {
      color: #a0521f;
    }

    .tot-grand {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 14px;
      background: var(--green);
      color: #fff;
      padding: 16px 20px;
      border-radius: 10px;
    }

    .tot-grand .lbl {
      font-size: 11px;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: var(--gold-soft);
    }

    .tot-grand .v {
      font-family: 'DM Serif Display', serif;
      font-size: 24px;
      color: #fff;
    }

    .bottom {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 36px;
      padding: 40px 52px 44px;
    }

    .blk-title {
      font-size: 10px;
      letter-spacing: 1.6px;
      text-transform: uppercase;
      color: var(--gold);
      font-weight: 600;
      margin-bottom: 12px;
    }

    .bank-row {
      display: flex;
      justify-content: space-between;
      font-size: 13px;
      padding: 7px 0;
      border-bottom: 1px dotted var(--rule);
    }

    .bank-row .bk-l {
      color: var(--ink-faint);
    }

    .bank-row .bk-v {
      font-family: 'DM Mono', monospace;
      color: var(--ink);
    }

    .momo-card {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-top: 14px;
      background: rgba(201, 162, 75, .10);
      border: 1px solid rgba(201, 162, 75, .30);
      border-radius: 9px;
      padding: 11px 14px;
    }

    .momo-card .mc-icon {
      width: 30px;
      height: 30px;
      flex-shrink: 0;
      background: var(--gold);
      border-radius: 7px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .momo-card .mc-icon svg {
      width: 16px;
      height: 16px;
      stroke: #fff;
    }

    .momo-card .mc-title {
      font-size: 10px;
      letter-spacing: 1px;
      text-transform: uppercase;
      color: var(--green-soft);
      font-weight: 700;
    }

    .momo-card .mc-num {
      font-family: 'DM Mono', monospace;
      font-size: 14px;
      color: var(--ink);
      margin-top: 2px;
    }

    .momo-card .mc-name {
      font-size: 11px;
      color: var(--ink-faint);
      margin-top: 1px;
    }

    .notes-text {
      font-size: 12.5px;
      line-height: 1.7;
      color: var(--ink-soft);
    }

    .footer {
      background: var(--green-deep);
      color: rgba(255, 255, 255, .78);
      padding: 18px 52px;
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 8px;
      font-size: 11.5px;
    }

    .footer strong {
      color: var(--gold-soft);
      font-weight: 600;
    }

    .notice {
      margin: 18px auto 0;
      text-align: center;
      font-size: 11.5px;
      color: var(--ink-faint);
    }

    /* Preloader Styles */
    .preloader-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.9);
      z-index: 9999;
      display: none;
      align-items: center;
      justify-content: center;
      flex-direction: column;
    }

    .spinner {
      border: 4px solid var(--rule);
      border-top: 4px solid var(--green);
      border-radius: 50%;
      width: 40px;
      height: 40px;
      animation: spin 1s linear infinite;
      margin-bottom: 16px;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    .preloader-text {
      font-family: 'DM Sans', sans-serif;
      font-weight: 600;
      color: var(--green);
      font-size: 16px;
    }

    .notice svg {
      width: 15px;
      height: 15px;
      flex-shrink: 0;
    }

    @media print {
      body {
        padding: 0;
        background: #fff;
        min-height: 0;
      }

      .page {
        box-shadow: none;
        border-radius: 0;
        animation: none;
      }

      .notice {
        margin-top: 10px;
        font-size: 10px;
      }

      .action-bar {
        display: none !important;
      }
    }

    @media (max-width: 768px) {
      body { padding: 15px 10px; }
      .action-bar { flex-direction: column; gap: 16px; padding: 15px; }
      .action-bar .left-acts { flex-wrap: wrap; justify-content: center; width: 100%; gap: 15px; }
      .action-bar > div:last-child { width: 100%; justify-content: center; flex-wrap: wrap; }
      .page { border-radius: 8px; }
      
      .header-top { flex-direction: column; gap: 20px; }
      .doc-title { text-align: left; }
      .doc-title h1 { font-size: 32px; }
      
      .meta, .parties, .bottom { grid-template-columns: 1fr; }
      .parties { gap: 25px; }
      
      .header, .parties, .table-wrap, .totals, .bottom, .footer { padding-left: 20px; padding-right: 20px; }
      
      .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; padding-top: 20px; }
      table { min-width: 600px; }
      
      .totals { justify-content: flex-start; padding-top: 15px; }
      .totals-box { width: 100%; }
      
      .footer { flex-direction: column; text-align: center; justify-content: center; }
      .footer > td { width: 100%; text-align: center; }
      
      .payment-schedule-box { margin-left: 20px !important; margin-right: 20px !important; overflow-x: auto; }
    }
  </style>
</head>

<body>

  <!-- Preloader -->
  <div class="preloader-overlay" id="preloader">
    <div class="spinner"></div>
    <div class="preloader-text">Sending notifications... Please wait.</div>
  </div>

  <!-- ACTION BAR -->
  <div class="action-bar">
    <div class="left-acts">
      <a href="index.php">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Create New
      </a>
      <a href="edit.php?id=<?= $invoice_id ?>">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
        Edit Invoice
      </a>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
      <?php if (isset($_SESSION['notify_msg'])): ?>
        <span
          style="color: var(--green); font-size: 13px; font-weight: 600;"><?= htmlspecialchars($_SESSION['notify_msg']) ?></span>
        <?php unset($_SESSION['notify_msg']); ?>
      <?php endif; ?>
      <a href="send_initial.php?id=<?= $invoice_id ?>" class="btn-pdf" id="sendBtn"
        style="background: var(--gold); color: #fff;">Send to Customer</a>
      <a href="download_pdf.php?id=<?= $invoice_id ?>" class="btn-pdf">Download PDF</a>
    </div>
  </div>

  <div class="page">
    <!-- HEADER -->
    <div class="header">
      <div class="header-top">
        <div class="brand">
          <div class="brand-mark">N</div>
          <div class="brand-text">
            <div class="brand-name">Namibra Software<br />Technologies</div>
            <div class="brand-sub">Limited &nbsp;·&nbsp; namibra.io</div>
          </div>
        </div>
        <div class="doc-title">
          <h1>INVOICE</h1>
          <div class="doc-no">No. <?= htmlspecialchars($invoice['invoice_no']) ?></div>
          <div class="status-badge <?= $status_class ?>"><?= htmlspecialchars($invoice['status']) ?></div>
        </div>
      </div>

      <div class="meta">
        <div class="meta-cell">
          <div class="lbl">Issue Date</div>
          <div class="val"><?= date('d M Y', strtotime($invoice['issue_date'])) ?></div>
        </div>
        <div class="meta-cell">
          <div class="lbl">Due Date</div>
          <div class="val"><?= date('d M Y', strtotime($invoice['due_date'])) ?></div>
        </div>
        <div class="meta-cell">
          <div class="lbl">Total Due</div>
          <div class="val"><?= formatCurrency($invoice['total_due']) ?></div>
        </div>
      </div>
    </div>

    <!-- PARTIES -->
    <div class="parties">
      <div>
        <div class="party-tag">Invoice From</div>
        <div class="party-name">Namibra Software Technologies LTD</div>
        <div class="party-info">
          Westlands, Greater Accra<br />
          Ghana<br />
          Tel: +233 551 963 210<br />
          info@namibra.io &nbsp;·&nbsp; finance@namibra.io<br />
          https://namibra.io
        </div>
      </div>
      <div>
        <div class="party-tag">Bill To</div>
        <div class="party-name"><?= htmlspecialchars($invoice['bill_to_name']) ?></div>
        <div class="party-info">
          <?= htmlspecialchars($invoice['bill_to_town_city']) ?><br />
          <?= htmlspecialchars($invoice['bill_to_region_country']) ?><br />
          Tel: <?= htmlspecialchars($invoice['bill_to_phone']) ?>
        </div>
      </div>
    </div>

    <?php if ($invoice['deposit_status'] === 'paid' && $invoice['deposit_amount'] > 0): ?>
      <div style="margin: 0 52px 24px;">
        <div style="background: #eff5f2; border: 1px solid #d1e2da; border-radius: 9px; padding: 14px 20px; display: flex; align-items: center; gap: 14px;">
          <div style="width: 28px; height: 28px; background: var(--green); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <svg fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
              <path d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <div style="font-size: 13.5px; color: var(--ink);">
            <strong style="color: var(--green);">Payment received.</strong>
            <?php if ($invoice['balance_status'] === 'paid'): ?>
              The <?= $invoice['deposit_percentage'] ?>% deposit of <?= formatCurrency($invoice['deposit_amount']) ?> was paid on <?= date('d M Y', strtotime($invoice['deposit_paid_date'])) ?>.
              The final balance of <?= formatCurrency($invoice['balance_remaining']) ?> was paid on <?= date('d M Y', strtotime($invoice['balance_paid_date'])) ?>. <strong style="color: var(--green);">This invoice is fully paid. Thank you!</strong>
            <?php else: ?>
              A <?= $invoice['deposit_percentage'] ?>% deposit of <?= formatCurrency($invoice['deposit_amount']) ?> was paid on <?= date('d M Y', strtotime($invoice['deposit_paid_date'])) ?>. Thank you.
              The remaining balance of <?= formatCurrency($invoice['balance_remaining']) ?> is due after completion &amp; delivery.
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- TABLE -->
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:58%;">Description</th>
            <th class="num">Qty</th>
            <th class="num">Rate</th>
            <th class="num">Amount</th>
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
                <div class="item-desc"><?= htmlspecialchars($desc) ?></div>
                <?php if ($note): ?>
                  <div class="item-note"><?= htmlspecialchars($note) ?></div>
                <?php endif; ?>
              </td>
              <td class="num"><?= htmlspecialchars($item['quantity']) ?></td>
              <td class="num"><?= $item['rate'] > 0 ? formatCurrency($item['rate']) : '—' ?></td>
              <td class="num"><?= $item['amount'] > 0 ? formatCurrency($item['amount']) : '—' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- TOTALS -->
    <div class="totals" style="position: relative;">
      <?php if ($invoice['deposit_status'] === 'paid'): ?>
        <div style="position: absolute; left: 140px; top: 40px; transform: rotate(-15deg); display: inline-block; padding: 10px 20px; border: 3px solid #6b9e78; color: #6b9e78; font-family: 'DM Serif Display', serif; font-size: 28px; letter-spacing: 4px; border-radius: 8px; opacity: 0.8; pointer-events: none;">
          <?php if ($invoice['balance_status'] === 'paid'): ?>
            FULLY PAID<br /><span style="font-family: 'DM Sans', sans-serif; font-size: 10px; letter-spacing: 2px; text-align: center; display: block;">THANK YOU</span>
          <?php else: ?>
            PAID<br /><span style="font-family: 'DM Sans', sans-serif; font-size: 10px; letter-spacing: 2px; text-align: center; display: block;"><?= $invoice['deposit_percentage'] ?>% DEPOSIT</span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <div class="totals-box">
        <div class="tot-row"><span>Project Amount</span><span
            class="v"><?= formatCurrency($invoice['project_amount']) ?></span></div>
        <?php if ($invoice['discount'] > 0): ?>
          <div class="tot-row" style="color: #a0521f;"><span>Discount</span><span class="v"
              style="color: #a0521f;">&minus; <?= formatCurrency($invoice['discount']) ?></span></div>
          <div class="tot-row" style="font-weight: 700; color: var(--ink);"><span>Net Project Total</span><span
              class="v"><?= formatCurrency($invoice['net_total']) ?></span></div>
        <?php endif; ?>

        <?php if ($invoice['deposit_amount'] > 0): ?>
          <div class="tot-row" style="color: #a0521f;"><span>Deposit Paid
              (<?= $invoice['deposit_percentage'] ?>%)</span><span class="v" style="color: #a0521f;">&minus;
              <?= formatCurrency($invoice['deposit_amount']) ?></span></div>
          <?php if ($invoice['balance_status'] === 'paid'): ?>
            <div class="tot-row" style="color: #a0521f;"><span>Balance Paid</span><span class="v" style="color: #a0521f;">&minus; <?= formatCurrency($invoice['balance_remaining']) ?></span></div>
            <div class="tot-grand">
              <span class="lbl">Balance Due</span>
              <span class="v"><?= formatCurrency(0) ?></span>
            </div>
          <?php else: ?>
            <div class="tot-grand" style="background: var(--green); margin-top: 14px; border-radius: 8px;">
              <span class="lbl" style="color: var(--gold-soft);">Balance Remaining</span>
              <span class="v"><?= formatCurrency($invoice['balance_remaining']) ?></span>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="tot-grand">
            <span class="lbl">Total Due</span>
            <span class="v"><?= formatCurrency($invoice['total_due']) ?></span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($invoice['deposit_percentage'] > 0 && $invoice['deposit_percentage'] < 100): ?>
      <div class="payment-schedule-box" style="margin: 40px 52px 40px; background: #faf8f2; border: 1px solid var(--rule); border-radius: 10px; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; min-width: 500px;">
          <thead>
            <tr>
              <th
                style="padding: 16px 20px; font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: var(--green-soft); text-align: left; border-bottom: 1px solid var(--rule);">
                Payment Schedule (<?= $invoice['deposit_percentage'] ?> / <?= 100 - $invoice['deposit_percentage'] ?>)
              </th>
              <th
                style="padding: 16px 20px; font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: var(--green-soft); text-align: right; border-bottom: 1px solid var(--rule);">
                Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td style="padding: 16px 20px; font-size: 13.5px; color: var(--ink); border-bottom: 1px solid var(--rule);">
                1 &middot; Deposit &mdash; before project commences
                <?php if ($invoice['deposit_status'] === 'paid'): ?>
                  <span
                    style="background: var(--green); color: #fff; padding: 3px 8px; border-radius: 12px; font-size: 9.5px; font-weight: bold; letter-spacing: 0.5px; margin-left: 10px; position: relative; top: -1px;">PAID
                    - <?= strtoupper(date('d M Y', strtotime($invoice['deposit_paid_date']))) ?></span>
                <?php else: ?>
                  <span
                    style="background: #e2e8f0; color: #64748b; padding: 3px 8px; border-radius: 12px; font-size: 9.5px; font-weight: bold; letter-spacing: 0.5px; margin-left: 10px; position: relative; top: -1px;">PENDING</span>
                <?php endif; ?>
              </td>
              <td
                style="padding: 16px 20px; font-size: 13.5px; font-family: 'DM Mono', monospace; text-align: right; border-bottom: 1px solid var(--rule);">
                <?= formatCurrency($invoice['deposit_amount']) ?></td>
            </tr>
            <tr>
              <td style="padding: 16px 20px; font-size: 13.5px; color: var(--ink);">
                2 &middot; Final balance &mdash; after completion &amp; delivery
                <?php if ($invoice['status'] === 'completed' || $invoice['status'] === 'full paid'): ?>
                  <span
                    style="background: var(--green); color: #fff; padding: 3px 8px; border-radius: 12px; font-size: 9.5px; font-weight: bold; letter-spacing: 0.5px; margin-left: 10px; position: relative; top: -1px;">PAID</span>
                <?php else: ?>
                  <span
                    style="background: #e2e8f0; color: #64748b; padding: 3px 8px; border-radius: 12px; font-size: 9.5px; font-weight: bold; letter-spacing: 0.5px; margin-left: 10px; position: relative; top: -1px;">PENDING</span>
                <?php endif; ?>
              </td>
              <td style="padding: 16px 20px; font-size: 13.5px; font-family: 'DM Mono', monospace; text-align: right;">
                <?= formatCurrency($invoice['balance_remaining']) ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <!-- BOTTOM GRID -->
    <div class="bottom">
      <div>
        <div class="blk-title">Payment Details</div>
        <div class="bank-row"><span class="bk-l">Account Name</span><span class="bk-v">Namibra Software Technologies
            LTD</span></div>
        <div class="bank-row"><span class="bk-l">Bank</span><span class="bk-v">Ecobank Ghana</span></div>
        <div class="bank-row"><span class="bk-l">Account Number</span><span class="bk-v">1441005162603</span></div>
        <div class="bank-row"><span class="bk-l">Swift Code</span><span class="bk-v">ECOCGHAC</span></div>
        <div class="momo-card">
          <div class="mc-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
              <rect x="5" y="2" width="14" height="20" rx="2" />
              <path d="M12 18h.01" />
            </svg>
          </div>
          <div>
            <div class="mc-title">MTN Mobile Money</div>
            <div class="mc-num">0246 515 614</div>
            <div class="mc-name">Namibra Software Technologies Ltd</div>
          </div>
        </div>
      </div>
      <div>
        <div class="blk-title">Terms &amp; Notes</div>
        <div class="notes-text">
          Payment is due within <strong>14 days</strong> of the invoice date. Late payments attract a
          <strong>2% monthly</strong> charge on the outstanding balance. All intellectual property rights
          transfer to the client upon receipt of full payment.<br /><br />
          Please reference <strong><?= htmlspecialchars($invoice['invoice_no']) ?></strong> when paying. Enquiries:
          <strong>finance@namibra.io</strong>.
        </div>
      </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
      <div><strong>Namibra Software Technologies Ltd</strong> &nbsp;·&nbsp; Westlands, Greater Accra, Ghana</div>
      <div>info@namibra.io &nbsp;·&nbsp; +233 551 963 210</div>
    </div>

  </div>

  <div class="notice">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="10" />
      <path d="M12 8v4m0 4h.01" />
    </svg>
    This is a computer-generated invoice and is valid without a physical signature.
  </div>

  <script>
    document.getElementById('sendBtn').addEventListener('click', function (e) {
      document.getElementById('preloader').style.display = 'flex';
    });
  </script>
</body>

</html>