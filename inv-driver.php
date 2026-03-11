<?php
require 'includes/auth.php';
$pageTitle  = 'Invoice / Driver';
$activePage = 'inv-driver';
include 'includes/header.php';
?>

    <div class="content">
        <h2 style="margin-bottom:20px;">Invoice for Driver</h2>
        <div class="btn-row">
            <button class="btn btn-success" onclick="openDrInvModal()">+ Create Invoice</button>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Invoice #</th><th>Driver</th><th>Jobs</th><th>Subtotal</th><th>Total</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody id="drInvTbody"></tbody>
            </table>
        </div>
        <div id="drInvPagination" class="pagination-wrap"></div>
    </div>

<!-- Create / Edit Driver Invoice Modal -->
<div id="drInvModal" class="modal">
    <div class="modal-box modal-xl">
        <div class="modal-hdr">
            <h2 id="drInvModalTitle">Create Driver Invoice</h2>
            <button class="close-btn" onclick="closeModal('drInvModal')">&times;</button>
        </div>
        <form id="drInvForm" onsubmit="saveDrInvoice(event)">
            <div class="form-grid">
                <div class="form-group">
                    <label>Driver *</label>
                    <select id="drInvDriver" required>
                        <option value="">-- Select Driver --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Invoice Date *</label>
                    <input type="date" id="drInvDate" required>
                </div>
                <div class="form-group">
                    <label>Paid Date</label>
                    <input type="date" id="drPaidDate">
                </div>
            </div>

            <div class="jobs-section">
                <h4>📦 Jobs</h4>
                <div class="line-items-scroll">
                    <div class="line-item-header-row-xl">
                        <span>Company</span>
                        <span>Job #</span>
                        <span>Customer</span>
                        <span>From</span>
                        <span>To</span>
                        <span>CF</span>
                        <span>Rate</span>
                        <span>Total</span>
                        <span>Bal. Due</span>
                        <span>Original Bal.</span>
                        <span>Remarks</span>
                        <span></span>
                    </div>
                    <div id="drJobsContainer"></div>
                </div>
                <button type="button" class="btn btn-primary" style="margin-top:10px;" onclick="addDrJobRow()">+ Add Job</button>
            </div>

            <div class="summary-box">
                <div class="summary-row"><span>Subtotal (CF × Rate):</span><span id="drSubtotal">$0.00</span></div>
                <div class="summary-row"><span>Carrier Fee (Bal. Due total):</span><span id="drCarrierFee">$0.00</span></div>
                <div class="summary-row">
                    <span>Labor Cost:</span>
                    <input type="number" id="drLaborCost" placeholder="0.00" step="0.01" style="width:110px;text-align:right;" oninput="updateDrSummary()">
                </div>
                <div class="summary-row">
                    <span>Pads:</span>
                    <input type="number" id="drPads" placeholder="0.00" step="0.01" style="width:110px;text-align:right;" oninput="updateDrSummary()">
                </div>
                <div class="summary-row">
                    <span>Paid:</span>
                    <input type="number" id="drPaid" placeholder="0.00" step="0.01" style="width:110px;text-align:right;" oninput="updateDrSummary()">
                </div>
                <div class="summary-row total"><span>TOTAL DUE:</span><span id="drTotal">$0.00</span></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('drInvModal')">Cancel</button>
                <button type="submit" class="btn btn-success" id="drInvSubmitBtn">✔ Save Invoice</button>
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
            <!-- <button class="btn btn-primary"   onclick="generateCoInvoices(currentViewId)">🏢 Generate Company Invoices</button> -->
            <button class="btn btn-success"   onclick="downloadDrInvoicePDF(currentViewId)">📥 Download PDF</button>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
let drJobRows      = [];
let editingDrInvId = null;
let currentViewId  = null;

function emptyDrJob() {
    return { jobNumber:'', companyId:'', customerName:'', from:'', to:'', cubicFeet:'', rate:'', balanceDue:'', newBalance:'', remarks:'' };
}

// ── Table render ─────────────────────────────

const PAGE_SIZE = 30;
let currentPage = 1;

function renderPage() {
    const tb = document.getElementById('drInvTbody');
    if (!driverInvoices.length) {
        tb.innerHTML = '<tr><td colspan="7" class="empty">No driver invoices yet. Click "+ Create Invoice" to start.</td></tr>';
        renderPagination('drInvPagination', 0, 1, PAGE_SIZE, () => {});
        return;
    }
    currentPage = Math.min(currentPage, Math.max(1, Math.ceil(driverInvoices.length / PAGE_SIZE)));
    const start    = (currentPage - 1) * PAGE_SIZE;
    const pageData = driverInvoices.slice(start, start + PAGE_SIZE);
    tb.innerHTML = pageData.map(inv => {
        const dr = drivers.find(d => d.id === inv.driverId);
        const n  = (inv.lineItems || []).length;
        return `
            <tr>
                <td><strong>DI-${inv.id}</strong></td>
                <td>${dr ? dr.firstName + ' ' + dr.lastName : '?'}</td>
                <td>${n} job${n !== 1 ? 's' : ''}</td>
                <td>$${(inv.subtotal || 0).toFixed(2)}</td>
                <td><strong>$${(inv.total || 0).toFixed(2)}</strong></td>
                <td>${inv.date}</td>
                <td><div class="action-btns">
                    <button class="btn-xs btn-xs-view"   onclick="viewDrInvoice(${inv.id})">👁️ View</button>
                    <button class="btn-xs btn-xs-pdf"    onclick="downloadDrInvoicePDF(${inv.id})">📥 PDF</button>
                    <button class="btn-xs btn-xs-edit"   onclick="editDrInvoice(${inv.id})">✏️ Edit</button>
                    <!-- <button class="btn-xs btn-xs-gen"    onclick="generateCoInvoices(${inv.id})">🏢 Generate CI</button> -->
                    <button class="btn-xs btn-xs-delete" onclick="deleteDrInvoice(${inv.id})">🗑️ Delete</button>
                </div></td>
            </tr>`;
    }).join('');
    renderPagination('drInvPagination', driverInvoices.length, currentPage, PAGE_SIZE, p => {
        currentPage = p;
        renderPage();
    });
}

// ── Open modal (create) ──────────────────────

function openDrInvModal() {
    editingDrInvId = null;
    document.getElementById('drInvModalTitle').textContent = 'Create Driver Invoice';
    document.getElementById('drInvSubmitBtn').textContent  = '✔ Save Invoice';
    document.getElementById('drInvForm').reset();
    document.getElementById('drInvDate').valueAsDate = new Date();
    populateDrDriverSelect(null);
    drJobRows = [emptyDrJob()];
    renderDrJobRows();
    document.getElementById('drInvModal').classList.add('active');
}

// ── Open modal (edit) ────────────────────────

function editDrInvoice(id) {
    const inv = driverInvoices.find(i => i.id === id);
    if (!inv) return;
    editingDrInvId = id;
    document.getElementById('drInvModalTitle').textContent = 'Edit Driver Invoice';
    document.getElementById('drInvSubmitBtn').textContent  = '✔ Update Invoice';
    document.getElementById('drInvDate').value = inv.date;
    document.getElementById('drLaborCost').value = inv.laborCost || '';
    document.getElementById('drPads').value      = inv.pads || '';
    document.getElementById('drPaid').value      = inv.paid || '';
    document.getElementById('drPaidDate').value  = inv.paidDate || '';
    populateDrDriverSelect(inv.driverId);
    drJobRows = JSON.parse(JSON.stringify(inv.lineItems || [emptyDrJob()]));
    renderDrJobRows();
    document.getElementById('drInvModal').classList.add('active');
}

function populateDrDriverSelect(selectedId) {
    document.getElementById('drInvDriver').innerHTML =
        '<option value="">-- Select Driver --</option>' +
        drivers.map(d => `<option value="${d.id}" ${d.id === selectedId ? 'selected' : ''}>${d.firstName} ${d.lastName}</option>`).join('');
}

// ── Job rows ─────────────────────────────────

function addDrJobRow()       { drJobRows.push(emptyDrJob()); renderDrJobRows(); }
function removeDrJobRow(idx) { if (drJobRows.length === 1) return; drJobRows.splice(idx, 1); renderDrJobRows(); }

function setDrJob(idx, field, val) {
    const num = ['cubicFeet','rate','balanceDue','newBalance'];
    drJobRows[idx][field] = num.includes(field) ? (parseFloat(val) || 0) : val;
    if (field === 'cubicFeet' || field === 'rate') renderDrJobRows();
    else updateDrSummary();
}

function renderDrJobRows() {
    document.getElementById('drJobsContainer').innerHTML = drJobRows.map((r, i) => {
        const total = ((r.cubicFeet || 0) * (r.rate || 0)).toFixed(2);
        const companySel = '<option value="">-- Company --</option>' +
            companies.map(c => `<option value="${c.id}" ${c.id == r.companyId ? 'selected' : ''}>${c.name}</option>`).join('');
        return `
        <div class="line-item-row-xl">
            <select onchange="setDrJob(${i},'companyId',this.value)">${companySel}</select>
            <input  type="text"   placeholder="Job #"    value="${esc(r.jobNumber)}"    onchange="setDrJob(${i},'jobNumber',this.value)">
            <input  type="text"   placeholder="Customer" value="${esc(r.customerName)}" onchange="setDrJob(${i},'customerName',this.value)">
            <input  type="text"   placeholder="From"     value="${esc(r.from)}"         onchange="setDrJob(${i},'from',this.value)">
            <input  type="text"   placeholder="To"       value="${esc(r.to)}"           onchange="setDrJob(${i},'to',this.value)">
            <input  type="number" placeholder="CF"       value="${r.cubicFeet || ''}"   onchange="setDrJob(${i},'cubicFeet',this.value)" min="0">
            <input  type="number" placeholder="Rate"     value="${r.rate || ''}"        onchange="setDrJob(${i},'rate',this.value)" step="0.01" min="0">
            <div class="cell-total">$${total}</div>
            <input  type="number" placeholder="Bal Due"  value="${r.balanceDue || ''}"  onchange="setDrJob(${i},'balanceDue',this.value)" step="0.01" min="0">
            <input  type="number" placeholder="Orig Bal" value="${r.newBalance || ''}"  onchange="setDrJob(${i},'newBalance',this.value)" step="0.01" min="0">
            <input  type="text"   placeholder="Remarks"  value="${esc(r.remarks)}"      onchange="setDrJob(${i},'remarks',this.value)">
            ${drJobRows.length > 1
                ? `<button type="button" class="btn-remove" onclick="removeDrJobRow(${i})">&#x2715;</button>`
                : '<div></div>'}
        </div>`;
    }).join('');
    updateDrSummary();
}

function updateDrSummary() {
    const sub   = drJobRows.reduce((s, r) => s + (r.cubicFeet || 0) * (r.rate || 0), 0);
    const fee   = drJobRows.reduce((s, r) => s + (r.balanceDue || 0), 0);
    const labor = parseFloat(document.getElementById('drLaborCost').value) || 0;
    const pads  = parseFloat(document.getElementById('drPads').value) || 0;
    const paid  = parseFloat(document.getElementById('drPaid').value) || 0;
    document.getElementById('drSubtotal').textContent   = '$' + sub.toFixed(2);
    document.getElementById('drCarrierFee').textContent = '$' + fee.toFixed(2);
    document.getElementById('drTotal').textContent      = '$' + (sub - fee + labor + pads + paid).toFixed(2);
}

// ── Save (create or update) ──────────────────

async function saveDrInvoice(e) {
    e.preventDefault();
    const did = parseInt(document.getElementById('drInvDriver').value);
    if (!did) { toast('Please select a driver.', 'error'); return; }
    if (!drJobRows.some(r => r.customerName || r.jobNumber)) { toast('Add at least one job.', 'error'); return; }

    const sub     = drJobRows.reduce((s, r) => s + (r.cubicFeet || 0) * (r.rate || 0), 0);
    const fee     = drJobRows.reduce((s, r) => s + (r.balanceDue || 0), 0);
    const labor   = parseFloat(document.getElementById('drLaborCost').value) || 0;
    const pads    = parseFloat(document.getElementById('drPads').value) || 0;
    const paid     = parseFloat(document.getElementById('drPaid').value) || 0;
    const date     = document.getElementById('drInvDate').value;
    const paidDate = document.getElementById('drPaidDate').value || '';
    const items    = JSON.parse(JSON.stringify(drJobRows));
    const payload  = { driverId: did, date, paidDate, lineItems: items, subtotal: sub, carrierFee: fee, laborCost: labor, pads, paid, total: sub - fee + labor + pads + paid };

    try {
        let drInvId;
        if (editingDrInvId) {
            drInvId = editingDrInvId;
            await api('inv-driver', 'PUT', payload, drInvId);
            Object.assign(driverInvoices.find(i => i.id === drInvId), payload);
            toast('Driver invoice updated!', 'success');
        } else {
            const res = await api('inv-driver', 'POST', payload);
            drInvId = res.id;
            driverInvoices.push({ id: drInvId, ...payload });
            toast('Driver invoice saved!', 'success');
        }
        editingDrInvId = null;
        renderPage();
        closeModal('drInvModal');
        // Option B: auto-sync company invoices silently
        await autoSyncCoInvoices(drInvId, { ...payload, id: drInvId });
    } catch (_) { /* error already shown by api() */ }
}

// ── Auto-sync company invoices (Option B) ────

async function autoSyncCoInvoices(drInvId, inv) {
    const groups = {};
    (inv.lineItems || []).forEach(job => {
        if (!job.companyId) return;
        if (!groups[job.companyId]) groups[job.companyId] = [];
        groups[job.companyId].push(job);
    });
    if (!Object.keys(groups).length) return;

    // Delete company invoices previously linked to this driver invoice
    const linked = companyInvoices.filter(ci => ci.driverInvoiceId === drInvId);
    for (const ci of linked) {
        try { await api('inv-company', 'DELETE', null, ci.id); } catch (_) {}
    }
    companyInvoices = companyInvoices.filter(ci => ci.driverInvoiceId !== drInvId);

    // Recreate one company invoice per company
    let created = 0;
    for (const [companyId, jobs] of Object.entries(groups)) {
        const sub  = jobs.reduce((s, j) => s + (j.cubicFeet || 0) * (j.rate || 0), 0);
        const fee  = sub * 0.1;
        const lineItems = jobs.map(j => ({
            jobNumber:    j.jobNumber,
            driverId:     inv.driverId,
            customerName: j.customerName,
            from:         j.from,
            to:           j.to,
            cubicFeet:    j.cubicFeet,
            rate:         j.rate,
            balanceDue:   j.balanceDue,
            newBalance:   j.newBalance,
            remarks:      j.remarks,
        }));
        const payload = {
            companyId:       parseInt(companyId),
            driverInvoiceId: drInvId,
            date:            inv.date,
            lineItems,
            subtotal:        sub,
            carrierFee:      fee,
            total:           sub - fee,
        };
        try {
            const res = await api('inv-company', 'POST', payload);
            companyInvoices.push({ id: res.id, ...payload });
            created++;
        } catch (_) {}
    }
    if (created > 0) {
        toast(`${created} company invoice${created > 1 ? 's' : ''} auto-synced.`, 'success');
    }
}

// ── Delete ───────────────────────────────────

async function deleteDrInvoice(id) {
    if (!confirm('Delete this invoice?')) return;
    try {
        await api('inv-driver', 'DELETE', null, id);
        driverInvoices = driverInvoices.filter(i => i.id !== id);
        renderPage();
        toast('Invoice deleted.', 'success');
    } catch (_) { /* error already shown by api() */ }
}

// ── Build invoice HTML (shared by view + print) ──

function buildDrInvoiceHtml(id) {
    const inv  = driverInvoices.find(i => i.id === id);
    if (!inv) return '';
    const dr   = drivers.find(d => d.id === inv.driverId) || {};
    const jobs = inv.lineItems || [];
    let totalCF = 0, totalAmt = 0, totalBal = 0, totalNewBal = 0;
    jobs.forEach(j => {
        totalCF     += (j.cubicFeet || 0);
        totalAmt    += (j.cubicFeet || 0) * (j.rate || 0);
        totalBal    += (j.balanceDue || 0);
        totalNewBal += (j.newBalance || 0);
    });

    const rows = jobs.map(j => {
        const co = companies.find(c => c.id == j.companyId) || {};
        return `<tr>
            <td>${co.name || '—'}</td>
            <td>${j.jobNumber || ''}</td>
            <td>${j.customerName || ''}</td>
            <td>${j.from || ''}</td>
            <td>${j.to || ''}</td>
            <td>${j.cubicFeet || 0}</td>
            <td>$${parseFloat(j.rate || 0).toFixed(2)}</td>
            <td><strong>$${((j.cubicFeet || 0) * (j.rate || 0)).toFixed(2)}</strong></td>
            <td>$${parseFloat(j.balanceDue || 0).toFixed(2)}</td>
            <td>$${parseFloat(j.newBalance || 0).toFixed(2)}</td>
            <td>${j.remarks || ''}</td>
        </tr>`;
    }).join('');

    const laborCost = inv.laborCost || 0;
    const pads      = inv.pads || 0;
    const paid      = inv.paid || 0;

    return `
        <div class="inv-view">

            <!-- ── Header: logo+company left, driver info right ── -->
            <div class="inv-header-row">
                <div class="inv-company-block">
                    <div class="inv-logo-name-row">
                        <img src="assets/bh-logo.png" alt="BH Logo" class="inv-brand-logo">
                    </div>
                    <div class="inv-company-addr">
                        11720 Amber Park Dr Ste 160<br>
                        Alpharetta, GA 30009<br>
                        DOT: 7521000<br>
                        Phone: +1 (347) 668-4584
                    </div>
                </div>
                <div class="inv-driver-block">
                    <h2>${dr.firstName || ''} ${dr.lastName || ''}</h2>
                    <p>Driver Statement</p>
                    <p>Phone: ${dr.phone || '—'} &nbsp;&nbsp; License: ${dr.license || '—'}</p>
                    ${inv.paidDate ? `<p class="inv-paid-date"><strong>Paid Date: ${inv.paidDate}</strong></p>` : ''}
                </div>
            </div>

            <!-- ── Invoice meta + quick summary side by side ── -->
            <div class="inv-meta">
                <div>
                    <strong>Invoice #:</strong> DI-${inv.id}<br>
                    <strong>Date:</strong> ${inv.date}<br>
                    <strong>Type:</strong> Driver Invoice
                </div>
                <div style="text-align:right;">
                    <strong>Total Jobs:</strong> ${jobs.length}<br>
                    <strong>Total CF:</strong> ${totalCF}<br>
                    <strong>Total Due:</strong> $${(inv.total || 0).toFixed(2)}
                </div>
            </div>

            <!-- ── Jobs table ── -->
            <div style="overflow-x:auto;">
            <table class="inv-table">
                <thead><tr>
                    <th>Company</th><th>Job #</th><th>Customer</th>
                    <th>From</th><th>To</th><th>CF</th><th>Rate</th>
                    <th>Total</th><th>Bal. Due</th><th>Original Bal.</th><th>Remarks</th>
                </tr></thead>
                <tbody>
                    ${rows}
                    <tr class="inv-total-row">
                        <td colspan="5"><strong>TOTALS</strong></td>
                        <td><strong>${totalCF}</strong></td>
                        <td></td>
                        <td><strong>$${totalAmt.toFixed(2)}</strong></td>
                        <td><strong>$${totalBal.toFixed(2)}</strong></td>
                        <td><strong>$${totalNewBal.toFixed(2)}</strong></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            </div>

            <!-- ── Summary (right-aligned) ── -->
            <div class="inv-summary" style="margin-top:20px;">
                <div class="inv-summary-row">
                    <span>Subtotal <em style="font-size:11px;font-weight:400;">(Total table value)</em></span>
                    <span>$${(inv.subtotal || 0).toFixed(2)}</span>
                </div>
                <div class="inv-summary-row">
                    <span>Carrier Fee <em style="font-size:11px;font-weight:400;">(Bal. Due total)</em></span>
                    <span>− $${(inv.carrierFee || 0).toFixed(2)}</span>
                </div>
                <div class="inv-summary-row">
                    <span>Labor Cost</span>
                    <span>$${laborCost.toFixed(2)}</span>
                </div>
                <div class="inv-summary-row">
                    <span>Pads</span>
                    <span>$${pads.toFixed(2)}</span>
                </div>
                ${paid !== 0 ? `<div class="inv-summary-row ${paid < 0 ? 'inv-paid-row' : 'inv-charge-row'}">
                    <span>${paid < 0 ? 'Paid' : 'Charge'}</span>
                    <span>${paid < 0 ? '− $' + Math.abs(paid).toFixed(2) : '+ $' + paid.toFixed(2)}</span>
                </div>` : ''}
                <div class="inv-summary-row">
                    <span>TOTAL DUE</span>
                    <span>$${(inv.total || 0).toFixed(2)}</span>
                </div>
            </div>

            <!-- ── Footer: Remarks left, Signature right ── -->
            <div class="inv-footer-row">
                <div class="inv-footer-cell"><strong>Remarks</strong></div>
                <div class="inv-footer-cell" style="text-align:right;"><strong>Signature</strong></div>
            </div>
        </div>`;
}

// ── View (opens modal) ────────────────────────

function viewDrInvoice(id) {
    currentViewId = id;
    document.getElementById('invoiceViewContent').innerHTML = buildDrInvoiceHtml(id);
    document.getElementById('invoiceViewModal').classList.add('active');
}

// ── Print directly (no modal needed) ─────────

function printDrInvoice(id) {
    triggerPrint(buildDrInvoiceHtml(id));
}

function invoiceInlineCSSText() {
    return `
        *{margin:0;padding:0;box-sizing:border-box;}
        body,div,td,th,p,span,strong{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif !important;color:#111;}
        h2{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif !important;font-weight:700;}
        strong,b{font-weight:700 !important;}
        .inv-view{background:#fff;color:#111;padding:28px;}

        /* ── Header row: company left, driver right ── */
        .inv-header-row{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px;padding-bottom:14px;border-bottom:2px solid #e2e8f0;}
        .inv-company-block{display:flex;flex-direction:row;align-items:flex-start;gap:12px;}
        .inv-logo-name-row{display:flex;align-items:center;gap:10px;}
        .inv-brand-logo{height:56px;width:auto;object-fit:contain;}
        .inv-company-addr{font-size:12px;color:#444;line-height:1.8;}
        .inv-driver-block{text-align:right;}
        .inv-driver-block h2{font-size:26px;font-weight:700;letter-spacing:.02em;}
        .inv-driver-block p{font-size:12px;color:#444;margin-top:4px;}

        /* ── Meta row ── */
        .inv-meta{display:grid;grid-template-columns:1fr 1fr;gap:20px;font-size:13px;margin-bottom:18px;padding:12px 14px;border:1px solid #ddd;background:#f9fafb;}
        .inv-meta div{line-height:1.9;}

        /* ── Table ── */
        .inv-table{width:100%;border-collapse:collapse;font-size:11.5px;margin-bottom:18px;}
        .inv-table th{background:#1e293b;color:#fff;padding:8px 7px;text-align:left;border:1px solid #555;white-space:nowrap;font-weight:700;}
        .inv-table td{border:1px solid #bbb;padding:7px;vertical-align:top;}
        .inv-table tbody tr:nth-child(even){background:#f5f8ff;}
        .inv-total-row td{background:#e8edf5;font-weight:700;border-top:2px solid #333;}

        /* ── Summary box ── */
        .inv-summary{margin-left:auto;width:360px;border:1px solid #ccc;font-size:13px;margin-top:20px;}
        .inv-summary-row{display:flex;justify-content:space-between;padding:8px 12px;border-bottom:1px solid #ddd;}
        .inv-summary-row:last-child{background:#1e293b;color:#fff;font-size:15px;font-weight:700;border-bottom:none;}
        .inv-summary-row:last-child span{color:#fff !important;}
        .inv-paid-row{background:#f0fdf4;color:#166534;font-weight:600;}
        .inv-paid-date{margin-top:8px;font-size:13px;color:#111;}
        .inv-charge-row{background:#fef2f2;color:#991b1b;font-weight:600;}

        /* ── Footer ── */
        .inv-footer-row{display:flex;justify-content:space-between;margin-top:48px;padding-top:14px;border-top:2px solid #333;}
        .inv-footer-cell{font-size:14px;font-weight:700;color:#111;}
    `;
}

async function downloadDrInvoicePDF(id) {
    const el = document.createElement('div');
    el.style.cssText = 'position:fixed;top:0;left:0;width:1100px;background:#fff;z-index:99999;';
    el.innerHTML = buildDrInvoiceHtml(id);
    document.body.appendChild(el);
    try {
        const canvas = await html2canvas(el, {
            scale: 2, useCORS: true, allowTaint: true, logging: false,
            scrollX: 0, scrollY: 0, windowWidth: 1100,
            onclone: (doc) => {
                const s = doc.createElement('style');
                s.textContent = invoiceInlineCSSText();
                doc.head.appendChild(s);
            }
        });
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({ unit: 'mm', format: 'a4', orientation: 'landscape' });
        const pageW = pdf.internal.pageSize.getWidth();
        const pageH = pdf.internal.pageSize.getHeight();
        const ratio = pageW / canvas.width;
        const totalH = canvas.height * ratio;
        const pages = Math.ceil(totalH / pageH);
        const imgData = canvas.toDataURL('image/jpeg', 0.98);
        for (let i = 0; i < pages; i++) {
            if (i > 0) pdf.addPage();
            pdf.addImage(imgData, 'JPEG', 0, -i * pageH, pageW, totalH);
        }
        pdf.save('DI-' + id + '.pdf');
    } finally {
        document.body.removeChild(el);
    }
}

// ── Manual re-sync button ────────────────────

async function generateCoInvoices(drInvId) {
    const inv = driverInvoices.find(i => i.id === drInvId);
    if (!inv) return;

    const hasCompany = (inv.lineItems || []).some(j => j.companyId);
    if (!hasCompany) {
        toast('No jobs with a company assigned.', 'error');
        return;
    }

    const linked = companyInvoices.filter(ci => ci.driverInvoiceId === drInvId);
    const msg = linked.length
        ? `This will delete ${linked.length} existing company invoice${linked.length > 1 ? 's' : ''} linked to DI-${drInvId} and recreate them.\n\nContinue?`
        : `Generate company invoices for DI-${drInvId}?\n\nContinue?`;
    if (!confirm(msg)) return;

    await autoSyncCoInvoices(drInvId, inv);
}

document.addEventListener('DOMContentLoaded', async () => {
    await loadFromDB();
    renderPage();
});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</body>
</html>
