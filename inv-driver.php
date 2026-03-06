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
            </div>

            <div class="jobs-section">
                <h4>📦 Jobs</h4>
                <div class="line-items-scroll">
                    <div class="line-item-header-row-xl">
                        <span>Job #</span>
                        <span>Company</span>
                        <span>Customer</span>
                        <span>From</span>
                        <span>To</span>
                        <span>CF</span>
                        <span>Rate</span>
                        <span>Total</span>
                        <span>Bal. Due</span>
                        <span>New Bal.</span>
                        <span>Remarks</span>
                        <span></span>
                    </div>
                    <div id="drJobsContainer"></div>
                </div>
                <button type="button" class="btn btn-primary" style="margin-top:10px;" onclick="addDrJobRow()">+ Add Job</button>
            </div>

            <div class="summary-box">
                <div class="summary-row"><span>Subtotal (CF × Rate):</span><span id="drSubtotal">$0.00</span></div>
                <div class="summary-row"><span>Carrier Fee (10%):</span><span id="drCarrierFee">$0.00</span></div>
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
            <button class="btn btn-success" onclick="downloadDrInvoicePDF(currentViewId)">📥 Download PDF</button>
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
            <input  type="text"   placeholder="Job #"    value="${esc(r.jobNumber)}"    onchange="setDrJob(${i},'jobNumber',this.value)">
            <select onchange="setDrJob(${i},'companyId',this.value)">${companySel}</select>
            <input  type="text"   placeholder="Customer" value="${esc(r.customerName)}" onchange="setDrJob(${i},'customerName',this.value)">
            <input  type="text"   placeholder="From"     value="${esc(r.from)}"         onchange="setDrJob(${i},'from',this.value)">
            <input  type="text"   placeholder="To"       value="${esc(r.to)}"           onchange="setDrJob(${i},'to',this.value)">
            <input  type="number" placeholder="CF"       value="${r.cubicFeet || ''}"   onchange="setDrJob(${i},'cubicFeet',this.value)" min="0">
            <input  type="number" placeholder="Rate"     value="${r.rate || ''}"        onchange="setDrJob(${i},'rate',this.value)" step="0.01" min="0">
            <div class="cell-total">$${total}</div>
            <input  type="number" placeholder="Bal Due"  value="${r.balanceDue || ''}"  onchange="setDrJob(${i},'balanceDue',this.value)" step="0.01" min="0">
            <input  type="number" placeholder="New Bal"  value="${r.newBalance || ''}"  onchange="setDrJob(${i},'newBalance',this.value)" step="0.01" min="0">
            <input  type="text"   placeholder="Remarks"  value="${esc(r.remarks)}"      onchange="setDrJob(${i},'remarks',this.value)">
            ${drJobRows.length > 1
                ? `<button type="button" class="btn-remove" onclick="removeDrJobRow(${i})">&#x2715;</button>`
                : '<div></div>'}
        </div>`;
    }).join('');
    updateDrSummary();
}

function updateDrSummary() {
    const sub = drJobRows.reduce((s, r) => s + (r.cubicFeet || 0) * (r.rate || 0), 0);
    const fee = sub * 0.1;
    document.getElementById('drSubtotal').textContent   = '$' + sub.toFixed(2);
    document.getElementById('drCarrierFee').textContent = '$' + fee.toFixed(2);
    document.getElementById('drTotal').textContent      = '$' + (sub + fee).toFixed(2);
}

// ── Save (create or update) ──────────────────

async function saveDrInvoice(e) {
    e.preventDefault();
    const did = parseInt(document.getElementById('drInvDriver').value);
    if (!did) { toast('Please select a driver.', 'error'); return; }
    if (!drJobRows.some(r => r.customerName || r.jobNumber)) { toast('Add at least one job.', 'error'); return; }

    const sub     = drJobRows.reduce((s, r) => s + (r.cubicFeet || 0) * (r.rate || 0), 0);
    const fee     = sub * 0.1;
    const date    = document.getElementById('drInvDate').value;
    const items   = JSON.parse(JSON.stringify(drJobRows));
    const payload = { driverId: did, date, lineItems: items, subtotal: sub, carrierFee: fee, total: sub + fee };

    try {
        if (editingDrInvId) {
            await api('inv-driver', 'PUT', payload, editingDrInvId);
            Object.assign(driverInvoices.find(i => i.id === editingDrInvId), payload);
            toast('Driver invoice updated!', 'success');
        } else {
            const res = await api('inv-driver', 'POST', payload);
            driverInvoices.push({ id: res.id, ...payload });
            toast('Driver invoice saved!', 'success');
        }
        editingDrInvId = null;
        renderPage();
        closeModal('drInvModal');
    } catch (_) { /* error already shown by api() */ }
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
            <td>${j.jobNumber || ''}</td>
            <td>${co.name || '—'}</td>
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

    return `
        <div class="inv-view">
            <div class="inv-view-hdr">
                <h2>${dr.firstName || ''} ${dr.lastName || ''}</h2>
                <p>Driver Statement</p>
                <p>Phone: ${dr.phone || '—'} &nbsp;&nbsp; License: ${dr.license || '—'}</p>
            </div>
            <div class="inv-meta">
                <div><strong>Invoice #:</strong> DI-${inv.id}<br><strong>Date:</strong> ${inv.date}<br><strong>Type:</strong> Driver Invoice</div>
                <div><strong>Total Jobs:</strong> ${jobs.length}<br><strong>Total CF:</strong> ${totalCF}<br><strong>Total Due:</strong> $${(inv.total || 0).toFixed(2)}</div>
            </div>
            <div style="overflow-x:auto;">
            <table class="inv-table">
                <thead><tr><th>Job #</th><th>Company</th><th>Customer</th><th>From</th><th>To</th><th>CF</th><th>Rate</th><th>Total</th><th>Bal. Due</th><th>New Bal.</th><th>Remarks</th></tr></thead>
                <tbody>
                    ${rows}
                    <tr class="inv-total-row">
                        <td colspan="5"><strong>TOTALS</strong></td>
                        <td><strong>${totalCF}</strong></td><td></td>
                        <td><strong>$${totalAmt.toFixed(2)}</strong></td>
                        <td><strong>$${totalBal.toFixed(2)}</strong></td>
                        <td><strong>$${totalNewBal.toFixed(2)}</strong></td><td></td>
                    </tr>
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

function downloadDrInvoicePDF(id) {
    const el = document.createElement('div');
    el.style.cssText = 'position:fixed;left:-9999px;top:0;width:1100px;background:#fff;';
    el.innerHTML = buildDrInvoiceHtml(id);
    document.body.appendChild(el);
    html2pdf().set({
        margin: 10,
        filename: 'DI-' + id + '.pdf',
        image:     { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, logging: false },
        jsPDF:     { unit: 'mm', format: 'a4', orientation: 'landscape' }
    }).from(el).save().then(() => document.body.removeChild(el));
}

document.addEventListener('DOMContentLoaded', async () => {
    await loadFromDB();
    renderPage();
});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</body>
</html>
