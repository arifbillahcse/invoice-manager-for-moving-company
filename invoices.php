<?php
$pageTitle  = 'Invoices';
$activePage = 'invoices';
include 'includes/header.php';
?>

    <div class="content">
        <h2 style="margin-bottom:20px;">Invoices</h2>
        <div class="btn-row">
            <button class="btn btn-success" onclick="openInvoiceModal()">+ Create Invoice</button>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Invoice #</th><th>Company</th><th>Driver</th><th>Jobs</th><th>Subtotal</th><th>Total</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody id="invoicesTbody"></tbody>
            </table>
        </div>
    </div>

<!-- Create Invoice Modal -->
<div id="invoiceModal" class="modal">
    <div class="modal-box modal-xl">
        <div class="modal-hdr">
            <h2>Create Invoice</h2>
            <button class="close-btn" onclick="closeModal('invoiceModal')">&times;</button>
        </div>
        <form id="invoiceForm" onsubmit="saveInvoice(event)">
            <div class="form-grid">
                <div class="form-group">
                    <label>Company *</label>
                    <select id="invoiceCompany" required>
                        <option value="">-- Select Company --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Driver *</label>
                    <select id="invoiceDriver" required>
                        <option value="">-- Select Driver --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Invoice Date *</label>
                    <input type="date" id="invoiceDate" required>
                </div>
            </div>
            <div class="jobs-section">
                <h4>📦 Jobs</h4>
                <div class="line-items-scroll">
                    <div class="line-item-header-row">
                        <span>Job #</span><span>Customer</span><span>From</span><span>To</span>
                        <span>CF</span><span>Rate</span><span>Total</span>
                        <span>Bal. Due</span><span>New Bal.</span><span>Remarks</span><span></span>
                    </div>
                    <div id="lineItemsContainer"></div>
                </div>
                <button type="button" class="btn btn-primary" style="margin-top:10px;" onclick="addJobRow()">+ Add Job</button>
            </div>
            <div class="summary-box">
                <div class="summary-row"><span>Subtotal (CF × Rate):</span><span id="invSubtotal">$0.00</span></div>
                <div class="summary-row"><span>Carrier Fee (10%):</span><span id="invCarrierFee">$0.00</span></div>
                <div class="summary-row total"><span>TOTAL DUE:</span><span id="invTotal">$0.00</span></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('invoiceModal')">Cancel</button>
                <button type="submit" class="btn btn-success">✔ Save Invoice</button>
            </div>
        </form>
    </div>
</div>

<!-- View Invoice Modal -->
<div id="invoiceViewModal" class="modal">
    <div class="modal-box modal-xl">
        <div class="modal-hdr">
            <h2>Invoice Detail</h2>
            <button class="close-btn" onclick="closeModal('invoiceViewModal')">&times;</button>
        </div>
        <div id="invoiceViewContent"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('invoiceViewModal')">Close</button>
            <button class="btn btn-primary" onclick="triggerPrint(document.getElementById('invoiceViewContent').innerHTML)">🖨️ Print</button>
        </div>
    </div>
</div>

<!-- Dedicated print area (invisible on screen) -->
<div id="printArea"></div>

<?php include 'includes/footer.php'; ?>

<script>
let jobRows = [];
function emptyJob() { return { jobNumber:'', customerName:'', from:'', to:'', cubicFeet:'', rate:'', balanceDue:'', newBalance:'', remarks:'' }; }

// ── Table render ─────────────────────────────

function renderPage() {
    const tb = document.getElementById('invoicesTbody');
    if (!invoices.length) {
        tb.innerHTML = '<tr><td colspan="8" class="empty">No invoices yet. Click "+ Create Invoice" to start.</td></tr>';
        return;
    }
    tb.innerHTML = invoices.map(inv => {
        const co = companies.find(c => c.id === inv.companyId);
        const dr = drivers.find(d => d.id === inv.driverId);
        const n  = (inv.lineItems || []).length;
        return `
            <tr>
                <td><strong>#${inv.id}</strong></td>
                <td>${co?.name || '?'}</td>
                <td>${dr ? dr.firstName + ' ' + dr.lastName : '?'}</td>
                <td>${n} job${n !== 1 ? 's' : ''}</td>
                <td>$${(inv.subtotal || 0).toFixed(2)}</td>
                <td><strong>$${(inv.total || 0).toFixed(2)}</strong></td>
                <td>${inv.date}</td>
                <td><div class="action-btns">
                    <button class="btn-xs btn-xs-view"   onclick="viewInvoice(${inv.id})">👁️ View</button>
                    <button class="btn-xs btn-xs-print"  onclick="printInvoice(${inv.id})">🖨️ Print</button>
                    <button class="btn-xs btn-xs-delete" onclick="deleteInvoice(${inv.id})">🗑️ Delete</button>
                </div></td>
            </tr>`;
    }).join('');
}

// ── Create modal ──────────────────────────────

function openInvoiceModal() {
    document.getElementById('invoiceForm').reset();
    document.getElementById('invoiceDate').valueAsDate = new Date();
    document.getElementById('invoiceCompany').innerHTML =
        '<option value="">-- Select Company --</option>' +
        companies.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    document.getElementById('invoiceDriver').innerHTML =
        '<option value="">-- Select Driver --</option>' +
        drivers.map(d => `<option value="${d.id}">${d.firstName} ${d.lastName}</option>`).join('');
    jobRows = [emptyJob()];
    renderJobRows();
    document.getElementById('invoiceModal').classList.add('active');
}

function addJobRow()       { jobRows.push(emptyJob()); renderJobRows(); }
function removeJobRow(idx) { if (jobRows.length === 1) return; jobRows.splice(idx, 1); renderJobRows(); }

function setJobField(idx, field, val) {
    const num = ['cubicFeet','rate','balanceDue','newBalance'];
    jobRows[idx][field] = num.includes(field) ? (parseFloat(val) || 0) : val;
    if (field === 'cubicFeet' || field === 'rate') renderJobRows();
    else updateInvSummary();
}

function renderJobRows() {
    document.getElementById('lineItemsContainer').innerHTML = jobRows.map((r, i) => {
        const total = ((r.cubicFeet || 0) * (r.rate || 0)).toFixed(2);
        return `
        <div class="line-item-row">
            <input type="text"   placeholder="Job #"    value="${esc(r.jobNumber)}"    onchange="setJobField(${i},'jobNumber',this.value)">
            <input type="text"   placeholder="Customer" value="${esc(r.customerName)}" onchange="setJobField(${i},'customerName',this.value)">
            <input type="text"   placeholder="From"     value="${esc(r.from)}"         onchange="setJobField(${i},'from',this.value)">
            <input type="text"   placeholder="To"       value="${esc(r.to)}"           onchange="setJobField(${i},'to',this.value)">
            <input type="number" placeholder="CF"       value="${r.cubicFeet || ''}"   onchange="setJobField(${i},'cubicFeet',this.value)" min="0">
            <input type="number" placeholder="Rate"     value="${r.rate || ''}" step="0.01" onchange="setJobField(${i},'rate',this.value)" min="0">
            <div class="cell-total">$${total}</div>
            <input type="number" placeholder="Bal Due"  value="${r.balanceDue || ''}"  step="0.01" onchange="setJobField(${i},'balanceDue',this.value)" min="0">
            <input type="number" placeholder="New Bal"  value="${r.newBalance || ''}"  step="0.01" onchange="setJobField(${i},'newBalance',this.value)" min="0">
            <input type="text"   placeholder="Remarks"  value="${esc(r.remarks)}"      onchange="setJobField(${i},'remarks',this.value)">
            ${jobRows.length > 1
                ? `<button type="button" class="btn-remove" onclick="removeJobRow(${i})">&#x2715;</button>`
                : '<div></div>'}
        </div>`;
    }).join('');
    updateInvSummary();
}

function updateInvSummary() {
    const sub = jobRows.reduce((s, r) => s + (r.cubicFeet || 0) * (r.rate || 0), 0);
    const fee = sub * 0.1;
    document.getElementById('invSubtotal').textContent   = '$' + sub.toFixed(2);
    document.getElementById('invCarrierFee').textContent = '$' + fee.toFixed(2);
    document.getElementById('invTotal').textContent      = '$' + (sub + fee).toFixed(2);
}

function saveInvoice(e) {
    e.preventDefault();
    const cid = parseInt(document.getElementById('invoiceCompany').value);
    const did = parseInt(document.getElementById('invoiceDriver').value);
    if (!cid || !did) { toast('Please select company and driver.', 'error'); return; }
    if (!jobRows.some(r => r.customerName || r.jobNumber)) { toast('Add at least one job.', 'error'); return; }
    const sub = jobRows.reduce((s, r) => s + (r.cubicFeet || 0) * (r.rate || 0), 0);
    const fee = sub * 0.1;
    invoices.push({
        id: nextInvoiceId++, companyId: cid, driverId: did,
        date:      document.getElementById('invoiceDate').value,
        lineItems: JSON.parse(JSON.stringify(jobRows)),
        subtotal: sub, carrierFee: fee, total: sub + fee
    });
    save(); renderPage();
    closeModal('invoiceModal');
    toast('Invoice saved!', 'success');
}

function deleteInvoice(id) {
    if (!confirm('Delete this invoice?')) return;
    invoices = invoices.filter(i => i.id !== id);
    save(); renderPage();
    toast('Invoice deleted.', 'success');
}

// ── Invoice HTML builder ──────────────────────

function buildInvoiceHtml(id) {
    const inv = invoices.find(i => i.id === id);
    if (!inv) return '';
    const co   = companies.find(c => c.id === inv.companyId) || {};
    const dr   = drivers.find(d => d.id === inv.driverId)    || {};
    const jobs = inv.lineItems || [];
    let totalCF = jobs.reduce((s, j) => s + (j.cubicFeet || 0), 0);

    const rows = jobs.map(j => `
        <tr>
            <td>${j.jobNumber || ''}</td><td>${j.customerName || ''}</td>
            <td>${j.from || ''}</td><td>${j.to || ''}</td>
            <td>${j.cubicFeet || 0}</td>
            <td>$${parseFloat(j.rate || 0).toFixed(2)}</td>
            <td>$${((j.cubicFeet || 0) * (j.rate || 0)).toFixed(2)}</td>
            <td>$${parseFloat(j.balanceDue || 0).toFixed(2)}</td>
            <td>$${parseFloat(j.newBalance || 0).toFixed(2)}</td>
            <td>${j.remarks || ''}</td>
        </tr>`).join('');

    return `
        <div class="inv-view">
            <div class="inv-view-hdr">
                <h2>${co.name || 'Company'}</h2>
                <p>${co.address || ''}${co.city ? ', ' + co.city : ''}</p>
                <p>US DOT: ${co.dotNumber || '—'} &nbsp;&nbsp; MC/ICC: ${co.mcNumber || '—'} &nbsp;&nbsp; Tel: ${co.phone || '—'}</p>
            </div>
            <div class="inv-meta">
                <div><strong>Invoice #:</strong> ${inv.id}<br><strong>Date:</strong> ${inv.date}<br><strong>Driver:</strong> ${dr.firstName || ''} ${dr.lastName || ''}<br><strong>Phone:</strong> ${dr.phone || '—'}</div>
                <div><strong>Total Jobs:</strong> ${jobs.length}<br><strong>Total CF:</strong> ${totalCF}<br><strong>Subtotal:</strong> $${(inv.subtotal || 0).toFixed(2)}<br><strong>Carrier Fee (10%):</strong> $${(inv.carrierFee || 0).toFixed(2)}</div>
            </div>
            <div style="overflow-x:auto;">
            <table class="inv-table">
                <thead><tr><th>Job #</th><th>Customer</th><th>From</th><th>To</th><th>CF</th><th>Rate</th><th>Total</th><th>Bal. Due</th><th>New Bal.</th><th>Remarks</th></tr></thead>
                <tbody>
                    ${rows}
                    <tr class="inv-total-row"><td colspan="4"><strong>TOTALS</strong></td><td><strong>${totalCF}</strong></td><td></td><td><strong>$${(inv.subtotal || 0).toFixed(2)}</strong></td><td></td><td></td><td></td></tr>
                </tbody>
            </table>
            </div>
            <div class="inv-summary" style="margin-top:20px;">
                <div class="inv-summary-row"><span>Subtotal:</span><span>$${(inv.subtotal || 0).toFixed(2)}</span></div>
                <div class="inv-summary-row"><span>Carrier Fee (10%):</span><span>$${(inv.carrierFee || 0).toFixed(2)}</span></div>
                <div class="inv-summary-row"><span>TOTAL DUE:</span><span>$${(inv.total || 0).toFixed(2)}</span></div>
            </div>
            <div style="display:flex;gap:40px;margin-top:40px;">
                <div style="flex:1;border-top:2px solid #333;padding-top:6px;font-size:12px;color:#555;">Driver Signature</div>
                <div style="flex:0.4;border-top:2px solid #333;padding-top:6px;font-size:12px;color:#555;">Date</div>
            </div>
        </div>`;
}

function viewInvoice(id) {
    document.getElementById('invoiceViewContent').innerHTML = buildInvoiceHtml(id);
    document.getElementById('invoiceViewModal').classList.add('active');
}

function printInvoice(id) {
    triggerPrint(buildInvoiceHtml(id));
}

document.addEventListener('DOMContentLoaded', () => {
    if (!load()) loadDefaults();
    renderPage();
});
</script>
</body>
</html>
