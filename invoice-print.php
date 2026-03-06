<?php
// Standalone invoice print/PDF page.
// Opens in a new tab and immediately shows the browser print dialog.
// User chooses "Save as PDF" to download — zero dependencies, pixel-perfect.
require_once 'includes/auth.php';
require_once 'config/db.php';

$type = strtoupper(trim($_GET['type'] ?? ''));
$id   = (int)($_GET['id']   ?? 0);

if (!in_array($type, ['CI', 'DI']) || $id <= 0) {
    http_response_code(400);
    die('Invalid parameters.');
}

$db = getDB();

if ($type === 'CI') {
    $stmt = $db->prepare("SELECT * FROM company_invoices WHERE id = ?");
    $stmt->execute([$id]);
    $inv = $stmt->fetch();
    if (!$inv) { http_response_code(404); die('Invoice not found.'); }

    $stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$inv['company_id']]);
    $entity = $stmt->fetch() ?: [];

    $stmt = $db->prepare(
        "SELECT i.*, d.first_name, d.last_name
           FROM company_invoice_items i
           LEFT JOIN drivers d ON d.id = i.driver_id
          WHERE i.invoice_id = ?
          ORDER BY i.sort_order, i.id"
    );
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();

} else {
    $stmt = $db->prepare("SELECT * FROM driver_invoices WHERE id = ?");
    $stmt->execute([$id]);
    $inv = $stmt->fetch();
    if (!$inv) { http_response_code(404); die('Invoice not found.'); }

    $stmt = $db->prepare("SELECT * FROM drivers WHERE id = ?");
    $stmt->execute([$inv['driver_id']]);
    $entity = $stmt->fetch() ?: [];

    $stmt = $db->prepare(
        "SELECT i.*, c.name AS company_name
           FROM driver_invoice_items i
           LEFT JOIN companies c ON c.id = i.company_id
          WHERE i.invoice_id = ?
          ORDER BY i.sort_order, i.id"
    );
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();
}

// ── Helpers ────────────────────────────────────
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
function money(float $v): string {
    return '$' . number_format($v, 2);
}

// ── Compute totals ────────────────────────────
$totalCF = $totalAmt = $totalBal = $totalNewBal = 0;
foreach ($items as $r) {
    $totalCF     += (float)$r['cubic_feet'];
    $totalAmt    += (float)$r['cubic_feet'] * (float)$r['rate'];
    $totalBal    += (float)$r['balance_due'];
    $totalNewBal += (float)$r['new_balance'];
}

$sub   = (float)$inv['subtotal'];
$fee   = (float)$inv['carrier_fee'];
$total = (float)$inv['total'];
$invoiceNum = $type . '-' . $id;
$date       = h($inv['date']);
$jobCount   = count($items);

// ── Build header block ────────────────────────
if ($type === 'CI') {
    $name     = h($entity['name']       ?? 'Company');
    $addrLine = h($entity['address']    ?? '');
    $cityLine = h($entity['city']       ?? '');
    $dot      = h($entity['dot_number'] ?? '—');
    $mc       = h($entity['mc_number']  ?? '—');
    $phone    = h($entity['phone']      ?? '—');
    $headerHtml = "
        <div class='inv-hdr-name'>{$name}</div>
        <p>" . ($addrLine ? $addrLine . ($cityLine ? ", $cityLine" : '') : '') . "</p>
        <p>US DOT: {$dot} &nbsp;&nbsp; MC/ICC: {$mc} &nbsp;&nbsp; Tel: {$phone}</p>";
    $col2Label  = 'Driver';
    $sigLabel   = 'Authorized Signature';
} else {
    $firstName = h($entity['first_name'] ?? '');
    $lastName  = h($entity['last_name']  ?? '');
    $drPhone   = h($entity['phone']      ?? '—');
    $drLic     = h($entity['license']    ?? '—');
    $headerHtml = "
        <div class='inv-hdr-name'>{$firstName} {$lastName}</div>
        <p>Driver Statement</p>
        <p>Phone: {$drPhone} &nbsp;&nbsp; License: {$drLic}</p>";
    $col2Label  = 'Company';
    $sigLabel   = 'Driver Signature';
}

// ── Build table rows ──────────────────────────
$rowsHtml = '';
foreach ($items as $i => $r) {
    $amt  = (float)$r['cubic_feet'] * (float)$r['rate'];
    $col2 = ($type === 'CI')
        ? h(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) ?: '—')
        : h($r['company_name'] ?? '—');
    $cls  = ($i % 2 === 1) ? ' class="even"' : '';
    $rowsHtml .= "
        <tr{$cls}>
            <td>" . h($r['job_number'])    . "</td>
            <td>{$col2}</td>
            <td>" . h($r['customer_name']) . "</td>
            <td>" . h($r['from_location']) . "</td>
            <td>" . h($r['to_location'])   . "</td>
            <td>" . (float)$r['cubic_feet']          . "</td>
            <td>" . money((float)$r['rate'])          . "</td>
            <td><strong>" . money($amt)              . "</strong></td>
            <td>" . money((float)$r['balance_due'])  . "</td>
            <td>" . money((float)$r['new_balance'])  . "</td>
            <td>" . h($r['remarks'])       . "</td>
        </tr>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= h($invoiceNum) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #111;
            background: #fff;
            padding: 20px;
        }

        /* ── Header ── */
        .inv-header {
            text-align: center;
            border-bottom: 3px solid #111;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .inv-hdr-name {
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .inv-header p { font-size: 11px; color: #444; margin-top: 3px; }

        /* ── Meta ── */
        .inv-meta {
            display: table;
            width: 100%;
            margin-bottom: 16px;
            border-collapse: separate;
            border-spacing: 10px 0;
        }
        .inv-meta-cell {
            display: table-cell;
            width: 50%;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 12px;
            line-height: 1.9;
            vertical-align: top;
        }
        .inv-meta-cell strong { color: #333; }

        /* ── Invoice table ── */
        .inv-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-bottom: 16px;
        }
        .inv-table th {
            background: #1e293b;
            color: #fff;
            padding: 7px 6px;
            text-align: left;
            border: 1px solid #444;
            white-space: nowrap;
        }
        .inv-table td {
            border: 1px solid #bbb;
            padding: 6px;
            vertical-align: top;
        }
        .inv-table tr.even td { background: #f5f8ff; }
        .inv-table tr.totals td {
            background: #e8edf5;
            font-weight: bold;
            border-top: 2px solid #333;
        }

        /* ── Summary ── */
        .inv-summary {
            float: right;
            width: 300px;
            border: 1px solid #ccc;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .inv-summary tr td {
            padding: 8px 12px;
            border-bottom: 1px solid #ddd;
        }
        .inv-summary tr.total-row td {
            background: #1e293b;
            color: #fff;
            font-size: 14px;
            font-weight: bold;
            border: none;
        }
        .clearfix { clear: both; }

        /* ── Signature ── */
        .sig-line {
            display: table;
            width: 100%;
            margin-top: 40px;
        }
        .sig-cell {
            display: table-cell;
            border-top: 2px solid #333;
            padding-top: 6px;
            font-size: 11px;
            color: #555;
        }
        .sig-cell.date { width: 30%; padding-left: 30px; }

        /* ── Print: hide browser chrome, show only page content ── */
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            @page { margin: 1.2cm; size: A4 landscape; }
        }
    </style>
</head>
<body>

<!-- Print button — hidden when printing -->
<div class="no-print" style="text-align:right; margin-bottom:16px;">
    <button onclick="window.print()"
            style="padding:10px 24px;background:#16a34a;color:#fff;border:none;border-radius:6px;font-size:14px;font-weight:bold;cursor:pointer;">
        ⬇ Save as PDF / Print
    </button>
    <button onclick="window.close()"
            style="padding:10px 20px;background:#475569;color:#fff;border:none;border-radius:6px;font-size:14px;font-weight:bold;cursor:pointer;margin-left:8px;">
        Close
    </button>
</div>

<!-- Invoice -->
<div class="inv-header">
    <?= $headerHtml ?>
</div>

<div class="inv-meta">
    <div class="inv-meta-cell">
        <strong>Invoice #:</strong> <?= h($invoiceNum) ?><br>
        <strong>Date:</strong> <?= $date ?><br>
        <strong>Type:</strong> <?= $type === 'CI' ? 'Company Invoice' : 'Driver Invoice' ?>
    </div>
    <div class="inv-meta-cell">
        <strong>Total Jobs:</strong> <?= $jobCount ?><br>
        <strong>Total CF:</strong> <?= $totalCF ?><br>
        <strong>Total Due:</strong> <?= money($total) ?>
    </div>
</div>

<table class="inv-table">
    <thead>
        <tr>
            <th>Job #</th>
            <th><?= h($col2Label) ?></th>
            <th>Customer</th>
            <th>From</th>
            <th>To</th>
            <th>CF</th>
            <th>Rate</th>
            <th>Total</th>
            <th>Bal. Due</th>
            <th>New Bal.</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>
        <?= $rowsHtml ?>
        <tr class="totals">
            <td colspan="5"><strong>TOTALS</strong></td>
            <td><strong><?= $totalCF ?></strong></td>
            <td></td>
            <td><strong><?= money($totalAmt) ?></strong></td>
            <td><strong><?= money($totalBal) ?></strong></td>
            <td><strong><?= money($totalNewBal) ?></strong></td>
            <td></td>
        </tr>
    </tbody>
</table>

<table class="inv-summary">
    <tr><td>Subtotal:</td><td><?= money($sub) ?></td></tr>
    <tr><td>Carrier Fee (10%):</td><td><?= money($fee) ?></td></tr>
    <tr class="total-row"><td>TOTAL DUE:</td><td><?= money($total) ?></td></tr>
</table>
<div class="clearfix"></div>

<div class="sig-line">
    <div class="sig-cell"><?= h($sigLabel) ?></div>
    <div class="sig-cell date">Date</div>
</div>

<script>
    // Auto-open print dialog when page loads
    window.addEventListener('load', () => window.print());
</script>
</body>
</html>
