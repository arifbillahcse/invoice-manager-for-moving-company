<?php
// Generate and stream a PDF for a company or driver invoice.
// GET api/pdf.php?type=CI&id=N  or  ?type=DI&id=N
require_once '../config/db.php';
require_once '../includes/auth-api.php';

$type = strtoupper(trim($_GET['type'] ?? ''));
$id   = (int)($_GET['id']   ?? 0);

if (!in_array($type, ['CI', 'DI']) || $id <= 0) {
    jsonOut(['error' => 'Invalid parameters.'], 400);
}

$db = getDB();

// ── Fetch invoice + related data ─────────────
if ($type === 'CI') {
    $stmt = $db->prepare("SELECT * FROM company_invoices WHERE id = ?");
    $stmt->execute([$id]);
    $inv = $stmt->fetch();
    if (!$inv) jsonOut(['error' => 'Invoice not found.'], 404);

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
    if (!$inv) jsonOut(['error' => 'Invoice not found.'], 404);

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

// ── Helpers ───────────────────────────────────
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

$sub        = (float)$inv['subtotal'];
$fee        = (float)$inv['carrier_fee'];
$total      = (float)$inv['total'];
$invoiceNum = $type . '-' . $id;
$jobCount   = count($items);

// ── Header block ──────────────────────────────
if ($type === 'CI') {
    $title    = h($entity['name']       ?? 'Company');
    $subTitle = h(trim(($entity['address'] ?? '') . ($entity['city'] ? ', ' . $entity['city'] : '')));
    $detail   = 'US DOT: ' . h($entity['dot_number'] ?? '—') .
                ' &nbsp; MC/ICC: ' . h($entity['mc_number'] ?? '—') .
                ' &nbsp; Tel: ' . h($entity['phone'] ?? '—');
    $col2Hdr  = 'Driver';
    $sigLabel = 'Authorized Signature';
} else {
    $title    = h(trim(($entity['first_name'] ?? '') . ' ' . ($entity['last_name'] ?? '')));
    $subTitle = 'Driver Statement';
    $detail   = 'Phone: ' . h($entity['phone'] ?? '—') . ' &nbsp; License: ' . h($entity['license'] ?? '—');
    $col2Hdr  = 'Company';
    $sigLabel = 'Driver Signature';
}

// ── Table rows ────────────────────────────────
$rowsHtml = '';
foreach ($items as $i => $r) {
    $amt  = (float)$r['cubic_feet'] * (float)$r['rate'];
    $col2 = ($type === 'CI')
        ? h(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) ?: '—')
        : h($r['company_name'] ?? '—');
    $bg   = ($i % 2 === 1) ? ' style="background:#f5f8ff;"' : '';
    $rowsHtml .= "
        <tr>
            <td{$bg}>" . h($r['job_number'])             . "</td>
            <td{$bg}>{$col2}</td>
            <td{$bg}>" . h($r['customer_name'])          . "</td>
            <td{$bg}>" . h($r['from_location'])          . "</td>
            <td{$bg}>" . h($r['to_location'])            . "</td>
            <td{$bg}>" . (float)$r['cubic_feet']                      . "</td>
            <td{$bg}>" . money((float)$r['rate'])                     . "</td>
            <td{$bg}><strong>" . money($amt)                          . "</strong></td>
            <td{$bg}>" . money((float)$r['balance_due'])              . "</td>
            <td{$bg}>" . money((float)$r['new_balance'])              . "</td>
            <td{$bg}>" . h($r['remarks'])                . "</td>
        </tr>";
}

// ── Build the full HTML for Playwright ───────
// All styles are self-contained; no external resources fetched.
$html = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    color: #111;
    background: #fff;
    padding: 16px 20px;
  }

  /* Header */
  .hdr {
    text-align: center;
    border-bottom: 3px solid #111;
    padding-bottom: 12px;
    margin-bottom: 14px;
  }
  .hdr-title {
    font-size: 21px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 4px;
  }
  .hdr p { font-size: 11px; color: #444; margin-top: 3px; }

  /* Meta row */
  .meta {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 14px;
  }
  .meta td {
    width: 50%;
    border: 1px solid #ccc;
    padding: 8px 12px;
    font-size: 12px;
    line-height: 1.85;
    vertical-align: top;
  }
  .meta td:first-child { border-right: none; }
  .meta strong { color: #222; }

  /* Invoice table */
  .inv {
    width: 100%;
    border-collapse: collapse;
    font-size: 10.5px;
    margin-bottom: 14px;
  }
  .inv th {
    background: #1e293b;
    color: #fff;
    padding: 7px 5px;
    text-align: left;
    border: 1px solid #3a4a5a;
    white-space: nowrap;
  }
  .inv td {
    border: 1px solid #bbb;
    padding: 6px 5px;
    vertical-align: top;
  }
  .inv tr.tot td {
    background: #e8edf5;
    font-weight: bold;
    border-top: 2px solid #333;
  }

  /* Summary */
  .summary {
    float: right;
    width: 280px;
    border-collapse: collapse;
    font-size: 12px;
    margin-bottom: 10px;
  }
  .summary td { padding: 7px 12px; border-bottom: 1px solid #ddd; }
  .summary tr.total td {
    background: #1e293b;
    color: #fff;
    font-size: 14px;
    font-weight: bold;
    border: none;
  }
  .clear { clear: both; }

  /* Signature */
  .sig {
    width: 100%;
    border-collapse: collapse;
    margin-top: 38px;
  }
  .sig td {
    border-top: 2px solid #333;
    padding-top: 6px;
    font-size: 11px;
    color: #555;
  }
  .sig .date { width: 28%; padding-left: 24px; }
</style>
</head>
<body>

<div class="hdr">
  <div class="hdr-title">' . $title . '</div>
  <p>' . $subTitle . '</p>
  <p>' . $detail . '</p>
</div>

<table class="meta">
  <tr>
    <td>
      <strong>Invoice #:</strong> ' . h($invoiceNum) . '<br>
      <strong>Date:</strong> ' . h($inv['date']) . '<br>
      <strong>Type:</strong> ' . ($type === 'CI' ? 'Company Invoice' : 'Driver Invoice') . '
    </td>
    <td>
      <strong>Total Jobs:</strong> ' . $jobCount . '<br>
      <strong>Total CF:</strong> ' . $totalCF . '<br>
      <strong>Total Due:</strong> ' . money($total) . '
    </td>
  </tr>
</table>

<table class="inv">
  <thead>
    <tr>
      <th>Job #</th>
      <th>' . h($col2Hdr) . '</th>
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
    ' . $rowsHtml . '
    <tr class="tot">
      <td colspan="5"><strong>TOTALS</strong></td>
      <td><strong>' . $totalCF . '</strong></td>
      <td></td>
      <td><strong>' . money($totalAmt) . '</strong></td>
      <td><strong>' . money($totalBal) . '</strong></td>
      <td><strong>' . money($totalNewBal) . '</strong></td>
      <td></td>
    </tr>
  </tbody>
</table>

<table class="summary">
  <tr><td>Subtotal:</td><td>' . money($sub) . '</td></tr>
  <tr><td>Carrier Fee (10%):</td><td>' . money($fee) . '</td></tr>
  <tr class="total"><td>TOTAL DUE:</td><td>' . money($total) . '</td></tr>
</table>
<div class="clear"></div>

<table class="sig">
  <tr>
    <td>' . h($sigLabel) . '</td>
    <td class="date">Date</td>
  </tr>
</table>

</body>
</html>';

// ── Call Playwright to render the PDF ─────────
$node   = '/opt/node22/bin/node';
$script = __DIR__ . '/../scripts/generate-pdf.js';
$cmd    = escapeshellcmd($node) . ' ' . escapeshellarg($script);

$descriptors = [
    0 => ['pipe', 'r'],  // stdin  — we write HTML here
    1 => ['pipe', 'w'],  // stdout — PDF bytes come out here
    2 => ['pipe', 'w'],  // stderr — error messages
];

$proc = proc_open($cmd, $descriptors, $pipes);
if (!is_resource($proc)) {
    jsonOut(['error' => 'PDF generation process failed to start.'], 500);
}

// Send HTML in, read PDF out
fwrite($pipes[0], $html);
fclose($pipes[0]);

$pdf    = stream_get_contents($pipes[1]);
$errors = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);

$exitCode = proc_close($proc);

if ($exitCode !== 0 || empty($pdf)) {
    jsonOut(['error' => 'PDF generation failed: ' . $errors], 500);
}

// ── Stream PDF to browser as download ────────
$filename = $invoiceNum . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: no-store');
echo $pdf;
exit;
