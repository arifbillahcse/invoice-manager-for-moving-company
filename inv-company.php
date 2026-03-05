<?php
$pageTitle  = 'Invoice / Company';
$activePage = 'inv-company';
include 'includes/header.php';
?>

    <div class="content">
        <h2 style="margin-bottom:20px;">Invoice for Company</h2>
        <div class="btn-row">
            <button class="btn btn-success" onclick="openCoInvModal()">+ Create Invoice</button>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Invoice #</th><th>Company</th><th>Jobs</th><th>Subtotal</th><th>Total</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody id="coInvTbody"></tbody>
            </table>
        </div>
    </div>

<!-- Create / Edit Company Invoice Modal -->
<div id="coInvModal" class="modal">
    <div class="modal-box modal-xl">
        <div class="modal-hdr">
            <h2 id="coInvModalTitle">Create Company Invoice</h2>
            <button class="close-btn" onclick="closeModal('coInvModal')">&times;</button>
        </div>
        <form id="coInvForm" onsubmit="saveCoInvoice(event)">
            <div class="form-grid">
                <div class="form-group">
                    <label>Company *</label>
                    <select id="coInvCompany" required>
                        <option value="">-- Select Company --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Invoice Date *</label>
                    <input type="date" id="coInvDate" required>
                </div>
            </div>

            <div class="jobs-section">
                <h4>📦 Jobs</h4>
                <div class="line-items-scroll">
                    <div class="line-item-header-row-xl">
                        <span>Job #</span>
                        <span>Driver</span>
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
                    <div id="coJobsContainer"></div>
                </div>
                <button type="button" class="btn btn-primary" style="margin-top:10px;" onclick="addCoJobRow()">+ Add Job</button>
            </div>

            <div class="summary-box">
                <div class="summary-row"><span>Subtotal (CF × Rate):</span><span id="coSubtotal">$0.00</span></div>
                <div class="summary-row"><span>Carrier Fee (10%):</span><span id="coCarrierFee">$0.00</span></div>
                <div class="summary-row total"><span>TOTAL DUE:</span><span id="coTotal">$0.00</span></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('coInvModal')">Cancel</button>
                <button type="submit" class="btn btn-success" id="coInvSubmitBtn">✔ Save Invoice</button>
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
let coJobRows      = [];
let editingCoInvId = null;

function emptyCoJob() {
    return { jobNumber:'', driverId:'', customerName:'', from:'', to:'', cubicFeet:'', rate:'', balanceDue:'', newBalance:'', remarks:'' };
}

// ── Table render ─────────────────────────────

function renderPage() {
    const tb = document.getElementById('coInvTbody');
    if (!companyInvoices.length) {
        tb.innerHTML = '<tr><td colspan="7" class="empty">No company invoices yet. Click "+ Create Invoice" to start.</td></tr>';
        return;
    }
    tb.innerHTML = companyInvoices.map(inv => {
        const co = companies.find(c => c.id === inv.companyId);
        const n  = (inv.lineItems || []).length;
        return `
            <tr>
                <td><strong>CI-${inv.id}</strong></td>
                <td>${co?.name || '?'}</td>
                <td>${n} job${n !== 1 ? 's' : ''}</td>
                <td>$${(inv.subtotal || 0).toFixed(2)}</td>
                <td><strong>$${(inv.total || 0).toFixed(2)}</strong></td>
                <td>${inv.date}</td>
                <td><div class="action-btns">
                    <button class="btn-xs btn-xs-view"   onclick="viewCoInvoice(${inv.id})">👁️ View</button>
                    <button class="btn-xs btn-xs-print"  onclick="printCoInvoice(${inv.id})">🖨️ Print</button>
                    <button class="btn-xs btn-xs-edit"   onclick="editCoInvoice(${inv.id})">✏️ Edit</button>
                    <button class="btn-xs btn-xs-delete" onclick="deleteCoInvoice(${inv.id})">🗑️ Delete</button>
                </div></td>
            </tr>`;
    }).join('');
}

// ── Open modal (create) ──────────────────────

function openCoInvModal() {
    editingCoInvId = null;
    document.getElementById('coInvModalTitle').textContent  = 'Create Company Invoice';
    document.getElementById('coInvSubmitBtn').textContent   = '✔ Save Invoice';
    document.getElementById('coInvForm').reset();
    document.getElementById('coInvDate').valueAsDate = new Date();
    populateCoCompanySelect(null);
    coJobRows = [emptyCoJob()];
    renderCoJobRows();
    document.getElementById('coInvModal').classList.add('active');
}

// ── Open modal (edit) ────────────────────────

function editCoInvoice(id) {
    const inv = companyInvoices.find(i => i.id === id);
    if (!inv) return;
    editingCoInvId = id;
    document.getElementById('coInvModalTitle').textContent = 'Edit Company Invoice';
    document.getElementById('coInvSubmitBtn').textContent  = '✔ Update Invoice';
    document.getElementById('coInvDate').value = inv.date;
    populateCoCompanySelect(inv.companyId);
    coJobRows = JSON.parse(JSON.stringify(inv.lineItems || [emptyCoJob()]));
    renderCoJobRows();
    document.getElementById('coInvModal').classList.add('active');
}

function populateCoCompanySelect(selectedId) {
    document.getElementById('coInvCompany').innerHTML =
        '<option value="">-- Select Company --</option>' +
        companies.map(c => `<option value="${c.id}" ${c.id === selectedId ? 'selected' : ''}>${c.name}</option>`).join('');
}

// ── Job rows ─────────────────────────────────

function addCoJobRow()       { coJobRows.push(emptyCoJob()); renderCoJobRows(); }
function removeCoJobRow(idx) { if (coJobRows.length === 1) return; coJobRows.splice(idx, 1); renderCoJobRows(); }

function setCoJob(idx, field, val) {
    const num = ['cubicFeet','rate','balanceDue','newBalance'];
    coJobRows[idx][field] = num.includes(field) ? (parseFloat(val) || 0) : val;
    if (field === 'cubicFeet' || field === 'rate') renderCoJobRows();
    else updateCoSummary();
}

function renderCoJobRows() {
    document.getElementById('coJobsContainer').innerHTML = coJobRows.map((r, i) => {
        const total = ((r.cubicFeet || 0) * (r.rate || 0)).toFixed(2);
        const driverSel = '<option value="">-- Driver --</option>' +
            drivers.map(d => `<option value="${d.id}" ${d.id == r.driverId ? 'selected' : ''}>${d.firstName} ${d.lastName}</option>`).join('');
        return `
        <div class="line-item-row-xl">
            <input  type="text"   placeholder="Job #"    value="${esc(r.jobNumber)}"    onchange="setCoJob(${i},'jobNumber',this.value)">
            <select onchange="setCoJob(${i},'driverId',this.value)">${driverSel}</select>
            <input  type="text"   placeholder="Customer" value="${esc(r.customerName)}" onchange="setCoJob(${i},'customerName',this.value)">
            <input  type="text"   placeholder="From"     value="${esc(r.from)}"         onchange="setCoJob(${i},'from',this.value)">
            <input  type="text"   placeholder="To"       value="${esc(r.to)}"           onchange="setCoJob(${i},'to',this.value)">
            <input  type="number" placeholder="CF"       value="${r.cubicFeet || ''}"   onchange="setCoJob(${i},'cubicFeet',this.value)" min="0">
            <input  type="number" placeholder="Rate"     value="${r.rate || ''}"        onchange="setCoJob(${i},'rate',this.value)" step="0.01" min="0">
            <div class="cell-total">$${total}</div>
            <input  type="number" placeholder="Bal Due"  value="${r.balanceDue || ''}"  onchange="setCoJob(${i},'balanceDue',this.value)" step="0.01" min="0">
            <input  type="number" placeholder="New Bal"  value="${r.newBalance || ''}"  onchange="setCoJob(${i},'newBalance',this.value)" step="0.01" min="0">
            <input  type="text"   placeholder="Remarks"  value="${esc(r.remarks)}"      onchange="setCoJob(${i},'remarks',this.value)">
            ${coJobRows.length > 1
                ? `<button type="button" class="btn-remove" onclick="removeCoJobRow(${i})">&#x2715;</button>`
                : '<div></div>'}
        </div>`;
    }).join('');
    updateCoSummary();
}

function updateCoSummary() {
    const sub = coJobRows.reduce((s, r) => s + (r.cubicFeet || 0) * (r.rate || 0), 0);
    const fee = sub * 0.1;
    document.getElementById('coSubtotal').textContent   = '$' + sub.toFixed(2);
    document.getElementById('coCarrierFee').textContent = '$' + fee.toFixed(2);
    document.getElementById('coTotal').textContent      = '$' + (sub + fee).toFixed(2);
}

// ── Save (create or update) ──────────────────

function saveCoInvoice(e) {
    e.preventDefault();
    const cid = parseInt(document.getElementById('coInvCompany').value);
    if (!cid) { toast('Please select a company.', 'error'); return; }
    if (!coJobRows.some(r => r.customerName || r.jobNumber)) { toast('Add at least one job.', 'error'); return; }

    const sub   = coJobRows.reduce((s, r) => s + (r.cubicFeet || 0) * (r.rate || 0), 0);
    const fee   = sub * 0.1;
    const date  = document.getElementById('coInvDate').value;
    const items = JSON.parse(JSON.stringify(coJobRows));

    if (editingCoInvId) {
        const inv = companyInvoices.find(i => i.id === editingCoInvId);
        inv.companyId = cid; inv.date = date; inv.lineItems = items;
        inv.subtotal = sub; inv.carrierFee = fee; inv.total = sub + fee;
        toast('Company invoice updated!', 'success');
    } else {
        companyInvoices.push({ id: nextCoInvId++, companyId: cid, date, lineItems: items, subtotal: sub, carrierFee: fee, total: sub + fee });
        toast('Company invoice saved!', 'success');
    }

    editingCoInvId = null;
    save(); renderPage();
    closeModal('coInvModal');
}

// ── Delete ───────────────────────────────────

function deleteCoInvoice(id) {
    if (!confirm('Delete this invoice?')) return;
    companyInvoices = companyInvoices.filter(i => i.id !== id);
    save(); renderPage();
    toast('Invoice deleted.', 'success');
}

// ── Build invoice HTML (shared by view + print) ──

function buildCoInvoiceHtml(id) {
    const inv  = companyInvoices.find(i => i.id === id);
    if (!inv) return '';
    const co   = companies.find(c => c.id === inv.companyId) || {};
    const jobs = inv.lineItems || [];
    let totalCF = 0, totalAmt = 0, totalBal = 0, totalNewBal = 0;
    jobs.forEach(j => {
        totalCF     += (j.cubicFeet || 0);
        totalAmt    += (j.cubicFeet || 0) * (j.rate || 0);
        totalBal    += (j.balanceDue || 0);
        totalNewBal += (j.newBalance || 0);
    });

    const rows = jobs.map(j => {
        const dr = drivers.find(d => d.id == j.driverId) || {};
        return `<tr>
            <td>${j.jobNumber || ''}</td>
            <td>${dr.firstName ? dr.firstName + ' ' + dr.lastName : '—'}</td>
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
                <h2>${co.name || 'Company'}</h2>
                <p>${co.address || ''}${co.city ? ', ' + co.city : ''}</p>
                <p>US DOT: ${co.dotNumber || '—'} &nbsp;&nbsp; MC/ICC: ${co.mcNumber || '—'} &nbsp;&nbsp; Tel: ${co.phone || '—'}</p>
            </div>
            <div class="inv-meta">
                <div><strong>Invoice #:</strong> CI-${inv.id}<br><strong>Date:</strong> ${inv.date}<br><strong>Type:</strong> Company Invoice</div>
                <div><strong>Total Jobs:</strong> ${jobs.length}<br><strong>Total CF:</strong> ${totalCF}<br><strong>Total Due:</strong> $${(inv.total || 0).toFixed(2)}</div>
            </div>
            <div style="overflow-x:auto;">
            <table class="inv-table">
                <thead><tr><th>Job #</th><th>Driver</th><th>Customer</th><th>From</th><th>To</th><th>CF</th><th>Rate</th><th>Total</th><th>Bal. Due</th><th>New Bal.</th><th>Remarks</th></tr></thead>
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
                <div style="flex:1;border-top:2px solid #333;padding-top:6px;font-size:12px;color:#555;">Authorized Signature</div>
                <div style="flex:0.4;border-top:2px solid #333;padding-top:6px;font-size:12px;color:#555;">Date</div>
            </div>
        </div>`;
}

// ── View (opens modal) ────────────────────────

function viewCoInvoice(id) {
    document.getElementById('invoiceViewContent').innerHTML = buildCoInvoiceHtml(id);
    document.getElementById('invoiceViewModal').classList.add('active');
}

// ── Print directly (no modal needed) ─────────

function printCoInvoice(id) {
    triggerPrint(buildCoInvoiceHtml(id));
}

document.addEventListener('DOMContentLoaded', () => {
    if (!load()) loadDefaults();
    renderPage();
});
</script>
</body>
</html>
