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

// ── Table rows (same structure as buildCoInvoiceHtml in JS) ──────────────────
$rowsHtml = '';
foreach ($items as $r) {
    $amt  = (float)$r['cubic_feet'] * (float)$r['rate'];
    $col2 = ($type === 'CI')
        ? h(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) ?: '—')
        : h($r['company_name'] ?? '—');
    $rowsHtml .= '<tr>
        <td>' . h($r['job_number'])        . '</td>
        <td>' . $col2                      . '</td>
        <td>' . h($r['customer_name'])     . '</td>
        <td>' . h($r['from_location'])     . '</td>
        <td>' . h($r['to_location'])       . '</td>
        <td>' . (float)$r['cubic_feet']    . '</td>
        <td>$' . number_format((float)$r['rate'], 2) . '</td>
        <td><strong>' . money($amt)        . '</strong></td>
        <td>' . money((float)$r['balance_due'])  . '</td>
        <td>' . money((float)$r['new_balance'])  . '</td>
        <td>' . h($r['remarks'])           . '</td>
    </tr>';
}

// ── Build HTML — identical structure/CSS to the "View" modal ─────────────────
$html = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  /* Exact same rules as style.css for the view modal */
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: Arial, sans-serif; background: #fff; color: #111; }

  .inv-view { background: #fff; color: #111; padding: 28px; font-family: Arial, sans-serif; }
  .inv-view-hdr { text-align: center; border-bottom: 3px solid #111; padding-bottom: 14px; margin-bottom: 16px; }
  .inv-view-hdr h2 { font-size: 24px; text-transform: uppercase; letter-spacing: .04em; }
  .inv-view-hdr p  { font-size: 12px; color: #444; margin-top: 3px; }

  .inv-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; font-size: 13px; margin-bottom: 18px; }
  .inv-meta div { line-height: 1.8; }

  .inv-table { width: 100%; border-collapse: collapse; font-size: 11.5px; margin-bottom: 18px; }
  .inv-table th { background: #1e293b; color: #fff; padding: 8px 7px; text-align: left; border: 1px solid #555; white-space: nowrap; }
  .inv-table td { border: 1px solid #bbb; padding: 7px 7px; vertical-align: top; }
  .inv-table tbody tr:nth-child(even) { background: #f5f8ff; }
  .inv-total-row td { background: #e8edf5; font-weight: bold; border-top: 2px solid #333; }

  .inv-summary { margin-left: auto; width: 320px; border: 1px solid #ccc; font-size: 13px; margin-top: 20px; }
  .inv-summary-row { display: flex; justify-content: space-between; padding: 8px 12px; border-bottom: 1px solid #ddd; }
  .inv-summary-row:last-child { background: #1e293b; color: #fff; font-size: 15px; font-weight: bold; border-bottom: none; }

  .sig { display: flex; gap: 40px; margin-top: 40px; }
  .sig-line { flex: 1; border-top: 2px solid #333; padding-top: 6px; font-size: 12px; color: #555; }
  .sig-line.date { flex: 0.4; }
</style>
</head>
<body>
<div class="inv-view">

  <div class="inv-view-hdr">
    <h2>' . $title . '</h2>
    <p>' . $subTitle . '</p>
    <p>' . $detail . '</p>
  </div>

  <div class="inv-meta">
    <div>
      <strong>Invoice #:</strong> ' . h($invoiceNum) . '<br>
      <strong>Date:</strong> ' . h($inv['date']) . '<br>
      <strong>Type:</strong> ' . ($type === 'CI' ? 'Company Invoice' : 'Driver Invoice') . '
    </div>
    <div>
      <strong>Total Jobs:</strong> ' . $jobCount . '<br>
      <strong>Total CF:</strong> ' . $totalCF . '<br>
      <strong>Total Due:</strong> ' . money($total) . '
    </div>
  </div>

  <table class="inv-table">
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
      <tr class="inv-total-row">
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

  <div class="inv-summary">
    <div class="inv-summary-row"><span>Subtotal:</span><span>' . money($sub) . '</span></div>
    <div class="inv-summary-row"><span>Carrier Fee (10%):</span><span>' . money($fee) . '</span></div>
    <div class="inv-summary-row"><span>TOTAL DUE:</span><span>' . money($total) . '</span></div>
  </div>

  <div class="sig">
    <div class="sig-line">' . h($sigLabel) . '</div>
    <div class="sig-line date">Date</div>
  </div>

</div>
</body>
</html>';

// ── Call Playwright to render the PDF ─────────
// Auto-detect the node binary — try common install paths before falling back
$node = '';
$candidates = [
    trim((string)shell_exec('which node 2>/dev/null')),
    trim((string)shell_exec('which nodejs 2>/dev/null')),
    '/usr/local/bin/node',
    '/usr/bin/node',
    '/opt/node22/bin/node',
    '/opt/node/bin/node',
];
foreach ($candidates as $c) {
    if ($c !== '' && is_executable($c)) { $node = $c; break; }
}
if (!$node) {
    jsonOut(['error' => 'Node.js not found on this server. Contact your host to install Node.js.'], 500);
}
$script = __DIR__ . '/../scripts/generate-pdf.js';
$cmd    = escapeshellarg($node) . ' ' . escapeshellarg($script);

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
