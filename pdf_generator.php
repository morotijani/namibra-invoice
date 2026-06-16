<?php
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function generateInvoicePDF($invoice_id, $pdo) {
    // Fetch invoice
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = :id");
    $stmt->execute([':id' => $invoice_id]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        return null;
    }

    // Fetch items
    $itemStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = :id");
    $itemStmt->execute([':id' => $invoice_id]);
    $items = $itemStmt->fetchAll();

    function formatCurrencyPDF($amount) {
        return 'GH₵ ' . number_format($amount, 2);
    }

    $status_class = 'status-' . str_replace(' ', '-', $invoice['status']);

    ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Invoice <?= htmlspecialchars($invoice['invoice_no']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Mono:wght@400;500&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    :root {
      --green:      #0f3d2e;
      --green-deep: #0a2c20;
      --green-soft: #1c5440;
      --gold:       #c9a24b;
      --gold-soft:  #e7d4a3;
      --ink:        #14201b;
      --ink-soft:   #4a5750;
      --ink-faint:  #8b988f;
      --rule:       #e4e9e5;
      --bg:         #ffffff;
      --white:      #ffffff;
      --cream:      #faf8f2;
    }

    body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--ink); padding: 0; margin: 0; }
    .page { width: 100%; margin: 0 auto; background: var(--white); }
    .header { background: var(--green); color: #fff; padding: 44px 52px 38px; }
    .brand-mark { width: 44px; height: 44px; border: 1.5px solid var(--gold); border-radius: 11px; text-align: center; font-family: 'DM Serif Display', serif; font-size: 24px; color: var(--gold-soft); line-height: 44px; }
    .brand-name { font-family: 'DM Serif Display', serif; font-size: 23px; line-height: 1.1; letter-spacing: .2px; }
    .brand-sub { font-size: 12px; color: var(--gold-soft); letter-spacing: 1.5px; text-transform: uppercase; margin-top: 5px; }
    .doc-title h1 { font-family: 'DM Serif Display', serif; font-size: 40px; letter-spacing: 3px; color: var(--gold-soft); line-height: 1; margin: 0; text-align: right; }
    .doc-no { font-family: 'DM Mono', monospace; font-size: 13px; color: #ccc; margin-top: 8px; letter-spacing: .5px; text-align: right; }
    .status-badge { display: inline-block; margin-top: 8px; padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; float: right; }
    .status-new { background: #e0f2fe; color: #0284c7; }
    .status-pending { background: #fef08a; color: #854d0e; }
    .status-partial-payment { background: #fed7aa; color: #c2410c; }
    .status-full-paid { background: #bbf7d0; color: #166534; }
    .status-completed { background: #dcfce3; color: #14532d; }
    .meta { width: 100%; margin-top: 30px; border-collapse: collapse; }
    .meta td { width: 33.33%; padding: 14px 18px; background: #114232; border: 1px solid #1c5440; }
    .meta .lbl { font-size: 10px; letter-spacing: 1.4px; text-transform: uppercase; color: var(--gold-soft); }
    .meta .val { font-family: 'DM Mono', monospace; font-size: 14px; color: #fff; margin-top: 4px; }
    .parties { width: 100%; padding: 40px 52px 8px; }
    .party-tag { font-size: 10px; letter-spacing: 1.6px; text-transform: uppercase; color: var(--gold); font-weight: 600; margin-bottom: 10px; }
    .party-name { font-family: 'DM Serif Display', serif; font-size: 18px; color: var(--ink); margin-bottom: 8px; }
    .party-info { font-size: 13px; line-height: 1.7; color: var(--ink-soft); }
    .table-wrap { padding: 32px 52px 0; }
    table.items { width: 100%; border-collapse: collapse; }
    table.items thead th { background: var(--cream); font-size: 10px; letter-spacing: 1.3px; text-transform: uppercase; color: var(--green-soft); font-weight: 700; text-align: left; padding: 13px 16px; border-bottom: 2px solid var(--gold); }
    table.items thead th.num { text-align: right; }
    table.items tbody td { padding: 17px 16px; font-size: 14px; color: var(--ink); border-bottom: 1px solid var(--rule); vertical-align: top; }
    table.items tbody td.num { text-align: right; font-family: 'DM Mono', monospace; }
    .item-desc { font-weight: 500; }
    .item-note { font-size: 12px; color: var(--ink-faint); margin-top: 3px; }
    .totals { padding: 24px 52px 0; }
    .tot-grand { background: var(--green); color: #fff; padding: 14px 20px; border-radius: 10px; }
    .bottom { width: 100%; padding: 40px 52px 44px; }
    .blk-title { font-size: 10px; letter-spacing: 1.6px; text-transform: uppercase; color: var(--gold); font-weight: 600; margin-bottom: 12px; }
    .momo-card { margin-top: 14px; background: #faf4e8; border: 1px solid #e7d4a3; border-radius: 9px; padding: 11px 14px; }
    .notes-text { font-size: 12.5px; line-height: 1.7; color: var(--ink-soft); }
    .footer { background: var(--green-deep); color: #ccc; padding: 18px 52px; font-size: 11.5px; }
    .notice { margin: 18px auto 0; text-align: center; font-size: 11.5px; color: var(--ink-faint); }
  </style>
</head>
<body>

  <div class="page">
    <div class="header">
      <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
          <td width="60%" valign="top">
            <table cellspacing="0" cellpadding="0">
              <tr>
                <td width="55" valign="top">
                  <div class="brand-mark">N</div>
                </td>
                <td valign="middle" style="padding-left: 12px;">
                  <div class="brand-name">Namibra Software<br/>Technologies</div>
                  <div class="brand-sub">Limited &nbsp;·&nbsp; namibra.io</div>
                </td>
              </tr>
            </table>
          </td>
          <td width="40%" align="right" valign="top">
            <div class="doc-title">
              <h1>INVOICE</h1>
              <div class="doc-no">No. <?= htmlspecialchars($invoice['invoice_no']) ?></div>
              <div class="status-badge <?= $status_class ?>"><?= htmlspecialchars($invoice['status']) ?></div>
            </div>
          </td>
        </tr>
      </table>

      <table class="meta">
        <tr>
          <td>
            <div class="lbl">Issue Date</div>
            <div class="val"><?= date('d M Y', strtotime($invoice['issue_date'])) ?></div>
          </td>
          <td>
            <div class="lbl">Due Date</div>
            <div class="val"><?= date('d M Y', strtotime($invoice['due_date'])) ?></div>
          </td>
          <td>
            <div class="lbl">Total Due</div>
            <div class="val"><?= formatCurrencyPDF($invoice['total_due']) ?></div>
          </td>
        </tr>
      </table>
    </div>

    <div class="parties">
      <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
          <td width="50%" valign="top" style="padding-right: 20px;">
            <div class="party-tag">Invoice From</div>
            <div class="party-name">Namibra Software Technologies LTD</div>
            <div class="party-info">
              Westlands, Greater Accra<br/>Ghana<br/>Tel: +233 551 963 210<br/>info@namibra.io &nbsp;·&nbsp; finance@namibra.io<br/>https://namibra.io
            </div>
          </td>
          <td width="50%" valign="top" style="padding-left: 20px;">
            <div class="party-tag">Bill To</div>
            <div class="party-name"><?= htmlspecialchars($invoice['bill_to_name']) ?></div>
            <div class="party-info">
              <?php if (!empty($invoice['bill_to_email'])) echo htmlspecialchars($invoice['bill_to_email']) . "<br/>"; ?>
              <?= htmlspecialchars($invoice['bill_to_town_city']) ?><br/>
              <?= htmlspecialchars($invoice['bill_to_region_country']) ?><br/>
              Tel: <?= htmlspecialchars($invoice['bill_to_phone']) ?>
            </div>
          </td>
        </tr>
      </table>
    </div>

    <div class="table-wrap">
      <?php if ($invoice['deposit_status'] === 'paid' && $invoice['deposit_amount'] > 0): ?>
      <div style="margin-bottom: 24px; background: #eff5f2; border: 1px solid #d1e2da; border-radius: 9px; padding: 14px 20px;">
        <table width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td width="36" valign="middle">
              <div style="width: 26px; height: 26px; background: #0f3d2e; border-radius: 13px; text-align: center; line-height: 26px; color: #fff; font-size: 14px; font-weight: bold;">&#10003;</div>
            </td>
            <td valign="middle" style="font-size: 13.5px; color: #14201b; line-height: 1.5;">
              <strong style="color: #0f3d2e;">Payment received.</strong> 
              A <?= $invoice['deposit_percentage'] ?>% deposit of <?= formatCurrencyPDF($invoice['deposit_amount']) ?> was paid on <?= date('d M Y', strtotime($invoice['deposit_paid_date'])) ?>. Thank you. 
              The remaining balance of <?= formatCurrencyPDF($invoice['balance_remaining']) ?> is due after completion &amp; delivery.
            </td>
          </tr>
        </table>
      </div>
      <?php endif; ?>

      <table class="items">
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
            <td class="num"><?= $item['rate'] > 0 ? formatCurrencyPDF($item['rate']) : '—' ?></td>
            <td class="num"><?= $item['amount'] > 0 ? formatCurrencyPDF($item['amount']) : '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="totals">
      <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
          <td width="55%" valign="top">
            <?php if ($invoice['deposit_status'] === 'paid'): ?>
              <div style="transform: rotate(-15deg); margin-top: 40px; margin-left: 20px; display: inline-block; padding: 10px 20px; border: 3px solid #6b9e78; color: #6b9e78; font-family: 'DM Serif Display', serif; font-size: 28px; letter-spacing: 4px; border-radius: 8px;">
                PAID<br/><span style="font-family: 'DM Sans', sans-serif; font-size: 10px; letter-spacing: 2px;"><?= $invoice['deposit_percentage'] ?>% DEPOSIT</span>
              </div>
            <?php endif; ?>
          </td>
          <td width="45%" valign="top">
            <table width="100%" cellspacing="0" cellpadding="0">
              <tr>
                <td style="padding: 11px 0; border-bottom: 1px solid var(--rule); font-size: 14px; color: var(--ink-soft);">Project Amount</td>
                <td align="right" style="padding: 11px 0; border-bottom: 1px solid var(--rule); font-size: 14px; font-family: 'DM Mono', monospace; color: var(--ink);"><?= formatCurrencyPDF($invoice['project_amount']) ?></td>
              </tr>
              <?php if ($invoice['discount'] > 0): ?>
              <tr>
                <td style="padding: 11px 0; border-bottom: 1px solid var(--rule); font-size: 14px; color: #a0521f;">Discount</td>
                <td align="right" style="padding: 11px 0; border-bottom: 1px solid var(--rule); font-size: 14px; font-family: 'DM Mono', monospace; color: #a0521f;">&minus; <?= formatCurrencyPDF($invoice['discount']) ?></td>
              </tr>
              <tr>
                <td style="padding: 11px 0; border-bottom: 1px solid var(--rule); font-size: 14px; color: var(--ink); font-weight: bold;">Net Project Total</td>
                <td align="right" style="padding: 11px 0; border-bottom: 1px solid var(--rule); font-size: 14px; font-family: 'DM Mono', monospace; color: var(--ink); font-weight: bold;"><?= formatCurrencyPDF($invoice['net_total']) ?></td>
              </tr>
              <?php endif; ?>
              
              <?php if ($invoice['deposit_amount'] > 0): ?>
              <tr>
                <td style="padding: 11px 0; border-bottom: 1px solid var(--rule); font-size: 14px; color: #a0521f;">Deposit Paid (<?= $invoice['deposit_percentage'] ?>%)</td>
                <td align="right" style="padding: 11px 0; border-bottom: 1px solid var(--rule); font-size: 14px; font-family: 'DM Mono', monospace; color: #a0521f;">&minus; <?= formatCurrencyPDF($invoice['deposit_amount']) ?></td>
              </tr>
              <tr>
                <td colspan="2" style="padding-top: 14px;">
                  <div class="tot-grand">
                    <table width="100%" cellspacing="0" cellpadding="0">
                      <tr>
                        <td style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; color: var(--gold-soft);">Balance Remaining</td>
                        <td align="right" style="font-family: 'DM Serif Display', serif; font-size: 24px; color: #fff;"><?= formatCurrencyPDF($invoice['balance_remaining']) ?></td>
                      </tr>
                    </table>
                  </div>
                </td>
              </tr>
              <?php else: ?>
              <tr>
                <td colspan="2" style="padding-top: 14px;">
                  <div class="tot-grand">
                    <table width="100%" cellspacing="0" cellpadding="0">
                      <tr>
                        <td style="font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; color: var(--gold-soft);">Total Due</td>
                        <td align="right" style="font-family: 'DM Serif Display', serif; font-size: 24px; color: #fff;"><?= formatCurrencyPDF($invoice['total_due']) ?></td>
                      </tr>
                    </table>
                  </div>
                </td>
              </tr>
              <?php endif; ?>
            </table>
          </td>
        </tr>
      </table>
    </div>

    <?php if ($invoice['deposit_percentage'] > 0 && $invoice['deposit_percentage'] < 100): ?>
    <div style="margin: 0 52px 40px; border: 1px solid #e4e9e5; border-radius: 10px; background: #faf8f2;">
      <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
          <td style="padding: 16px 20px; font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: #1c5440; font-weight: bold; border-bottom: 1px solid #e4e9e5;">Payment Schedule (<?= $invoice['deposit_percentage'] ?> / <?= 100 - $invoice['deposit_percentage'] ?>)</td>
          <td align="right" style="padding: 16px 20px; font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: #1c5440; font-weight: bold; border-bottom: 1px solid #e4e9e5;">Amount</td>
        </tr>
        <tr>
          <td style="padding: 16px 20px; font-size: 13.5px; color: #14201b; border-bottom: 1px solid #e4e9e5;">
            1 &middot; Deposit &mdash; before project commences
            <?php if ($invoice['deposit_status'] === 'paid'): ?>
              &nbsp;&nbsp;<span style="color: #0f3d2e; font-size: 10px; font-weight: bold;">[ PAID - <?= strtoupper(date('d M Y', strtotime($invoice['deposit_paid_date']))) ?> ]</span>
            <?php else: ?>
              &nbsp;&nbsp;<span style="color: #8b988f; font-size: 10px; font-weight: bold;">[ PENDING ]</span>
            <?php endif; ?>
          </td>
          <td align="right" style="padding: 16px 20px; font-size: 13.5px; font-family: 'DM Mono', monospace; border-bottom: 1px solid #e4e9e5;"><?= formatCurrencyPDF($invoice['deposit_amount']) ?></td>
        </tr>
        <tr>
          <td style="padding: 16px 20px; font-size: 13.5px; color: #14201b;">
            2 &middot; Final balance &mdash; after completion &amp; delivery
            <?php if ($invoice['status'] === 'completed' || $invoice['status'] === 'full paid'): ?>
              &nbsp;&nbsp;<span style="color: #0f3d2e; font-size: 10px; font-weight: bold;">[ PAID ]</span>
            <?php else: ?>
              &nbsp;&nbsp;<span style="color: #8b988f; font-size: 10px; font-weight: bold;">[ PENDING ]</span>
            <?php endif; ?>
          </td>
          <td align="right" style="padding: 16px 20px; font-size: 13.5px; font-family: 'DM Mono', monospace;"><?= formatCurrencyPDF($invoice['balance_remaining']) ?></td>
        </tr>
      </table>
    </div>
    <?php endif; ?>

    <div class="bottom">
      <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
          <td width="48%" valign="top" style="padding-right: 4%;">
            <div class="blk-title">Payment Details</div>
            <table width="100%" cellspacing="0" cellpadding="0">
              <tr>
                <td style="font-size: 13px; padding: 7px 0; border-bottom: 1px dotted var(--rule); color: var(--ink-faint);">Account Name</td>
                <td align="right" style="font-size: 13px; padding: 7px 0; border-bottom: 1px dotted var(--rule); font-family: 'DM Mono', monospace; color: var(--ink);">Namibra Software Technologies LTD</td>
              </tr>
              <tr>
                <td style="font-size: 13px; padding: 7px 0; border-bottom: 1px dotted var(--rule); color: var(--ink-faint);">Bank</td>
                <td align="right" style="font-size: 13px; padding: 7px 0; border-bottom: 1px dotted var(--rule); font-family: 'DM Mono', monospace; color: var(--ink);">Ecobank Ghana</td>
              </tr>
              <tr>
                <td style="font-size: 13px; padding: 7px 0; border-bottom: 1px dotted var(--rule); color: var(--ink-faint);">Account Number</td>
                <td align="right" style="font-size: 13px; padding: 7px 0; border-bottom: 1px dotted var(--rule); font-family: 'DM Mono', monospace; color: var(--ink);">1441005162603</td>
              </tr>
              <tr>
                <td style="font-size: 13px; padding: 7px 0; border-bottom: 1px dotted var(--rule); color: var(--ink-faint);">Swift Code</td>
                <td align="right" style="font-size: 13px; padding: 7px 0; border-bottom: 1px dotted var(--rule); font-family: 'DM Mono', monospace; color: var(--ink);">ECOCGHAC</td>
              </tr>
            </table>
            <div class="momo-card">
              <div style="font-size: 10px; letter-spacing: 1px; text-transform: uppercase; color: var(--green-soft); font-weight: 700;">MTN Mobile Money</div>
              <div style="font-family: 'DM Mono', monospace; font-size: 14px; color: var(--ink); margin-top: 2px;">0246 515 614</div>
              <div style="font-size: 11px; color: var(--ink-faint); margin-top: 1px;">Namibra Software Technologies Ltd</div>
            </div>
          </td>
          <td width="48%" valign="top">
            <div class="blk-title">Terms &amp; Notes</div>
            <div class="notes-text">
              Payment is due within <strong>14 days</strong> of the invoice date. Late payments attract a
              <strong>2% monthly</strong> charge on the outstanding balance. All intellectual property rights
              transfer to the client upon receipt of full payment.<br/><br/>
              Please reference <strong><?= htmlspecialchars($invoice['invoice_no']) ?></strong> when paying. Enquiries: <strong>finance@namibra.io</strong>.
            </div>
          </td>
        </tr>
      </table>
    </div>

    <div class="footer">
      <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
          <td width="60%" style="font-size: 11.5px; color: #ccc;"><strong>Namibra Software Technologies Ltd</strong> &nbsp;·&nbsp; Westlands, Greater Accra, Ghana</td>
          <td width="40%" align="right" style="font-size: 11.5px; color: #ccc;">info@namibra.io &nbsp;·&nbsp; +233 551 963 210</td>
        </tr>
      </table>
    </div>

  </div>

  <div class="notice">
    This is a computer-generated invoice and is valid without a physical signature.
  </div>
</body>
</html>
<?php
    $html = ob_get_clean();

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'Helvetica');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return $dompdf->output(); // Returns the PDF as a string
}
?>
