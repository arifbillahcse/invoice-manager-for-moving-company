// ==========================================
// INVOICE MANAGEMENT SYSTEM
// Moving Company - Loading Sheets Generator
// ==========================================

// ==========================================
// DATA STORAGE
// ==========================================

let companies = [
    {
        id: 1,
        name: 'Prime Relocations',
        address: '5695 Oakbrook Parkway, Suite D',
        city: 'Norcross GA 30093',
        phone: '(770) 954-7095',
        dotNumber: '806005',
        mcNumber: '358641'
    },
    {
        id: 2,
        name: 'BH Relocation INC',
        address: '1172 Amber Park DR Suite 160',
        city: 'Alpharetta, GA 30009',
        phone: '(404) 123-4567',
        dotNumber: '123456',
        mcNumber: '654321'
    }
];

// Drivers support many-to-many with companies via companyIds array
let drivers = [
    { id: 1, companyIds: [1, 2], firstName: 'Joseph', lastName: 'Smith', phone: '(770) 555-0001', licenseNumber: 'DL123456' },
    { id: 2, companyIds: [1],    firstName: 'John',   lastName: 'Doe',   phone: '(770) 555-0002', licenseNumber: 'DL123457' },
    { id: 3, companyIds: [2],    firstName: 'Ahmed',  lastName: 'Hassan',phone: '(404) 555-0003', licenseNumber: 'DL123458' }
];

// Individual job/delivery records
let jobs = [
    {
        id: 1, companyId: 1, driverId: 1,
        jobNumber: 'J001', date: '2026-03-01',
        customerName: 'Johnson', state: 'FL', fromTo: 'Atlanta GA / Miami FL',
        cubicFeet: 1500, rate: 0.50,
        balanceDue: 750, jobBalance: 200, blanket: 50, pads: 10, eta: '3/5', remarks: ''
    },
    {
        id: 2, companyId: 1, driverId: 1,
        jobNumber: 'J002', date: '2026-03-02',
        customerName: 'Williams', state: 'TX', fromTo: 'Atlanta GA / Dallas TX',
        cubicFeet: 800, rate: 0.55,
        balanceDue: 440, jobBalance: 150, blanket: 30, pads: 6, eta: '3/6', remarks: ''
    },
    {
        id: 3, companyId: 2, driverId: 1,
        jobNumber: 'J003', date: '2026-03-03',
        customerName: 'Davis', state: 'GA', fromTo: 'Alpharetta GA / Savannah GA',
        cubicFeet: 600, rate: 0.60,
        balanceDue: 360, jobBalance: 100, blanket: 20, pads: 4, eta: '3/7', remarks: ''
    }
];

// Generated loading sheets
let sheets = [];
let nextSheetNumber = 1;

// Modal state
let currentModalType = '';
let currentEditId = null;

// ==========================================
// INITIALIZATION
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    setupNavigation();
    renderDashboard();
});

// ==========================================
// NAVIGATION
// ==========================================

function setupNavigation() {
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            switchTab(this.dataset.tab, this);
        });
    });
}

function switchTab(tabName, btnEl) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    if (btnEl) btnEl.classList.add('active');

    if (tabName === 'dashboard') renderDashboard();
    else if (tabName === 'companies') renderCompanies();
    else if (tabName === 'drivers') renderDrivers();
    else if (tabName === 'jobs') { populateJobFilters(); renderJobs(); }
    else if (tabName === 'sheets') renderSheets();
}

// ==========================================
// DASHBOARD
// ==========================================

function renderDashboard() {
    document.getElementById('statCompanies').textContent = companies.length;
    document.getElementById('statDrivers').textContent = drivers.length;
    document.getElementById('statJobs').textContent = jobs.length;
    document.getElementById('statSheets').textContent = sheets.length;
    document.getElementById('totalSheets').textContent = sheets.length;

    const recentHTML = sheets.length
        ? sheets.slice(-5).reverse().map(s => `
            <div class="invoice-item">
                <div class="invoice-item-left">
                    <div class="invoice-item-number">Sheet #${s.sheetNumber}</div>
                    <div class="invoice-item-details">${getCompanyName(s.companyId)} &mdash; ${getDriverName(s.driverId)}
                        <span class="badge badge-${s.templateType}" style="margin-left:8px;">${s.templateType === 'bh' ? 'BH' : 'Prime'}</span>
                    </div>
                </div>
                <div class="invoice-item-right">
                    <div class="invoice-item-total">$${(s.total || 0).toFixed(2)}</div>
                    <div class="invoice-item-date">${s.date}</div>
                </div>
            </div>
        `).join('')
        : '<p style="color:var(--color-text-lighter);text-align:center;padding:2rem;">No sheets generated yet. Go to <strong>Jobs</strong> to add jobs, then <strong>Sheets</strong> to generate.</p>';

    document.getElementById('recentInvoices').innerHTML = recentHTML;
}

// ==========================================
// COMPANIES
// ==========================================

function renderCompanies() {
    document.getElementById('companiesList').innerHTML = companies.map(c => `
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">${c.name}</div>
                    <div class="card-subtitle">${c.address}${c.city ? ', ' + c.city : ''}</div>
                </div>
                <div class="card-actions">
                    <button class="card-btn" onclick="editCompany(${c.id})" title="Edit">&#9999;&#65039;</button>
                    <button class="card-btn" onclick="deleteCompany(${c.id})" title="Delete">&#128465;&#65039;</button>
                </div>
            </div>
            <div class="card-content">
                <div class="card-field"><span class="card-field-label">Phone:</span><span class="card-field-value">${c.phone}</span></div>
                <div class="card-field"><span class="card-field-label">US DOT:</span><span class="card-field-value">${c.dotNumber}</span></div>
                <div class="card-field"><span class="card-field-label">MC:</span><span class="card-field-value">${c.mcNumber}</span></div>
            </div>
        </div>
    `).join('');
}

function editCompany(id) {
    currentEditId = id;
    openModal('company', companies.find(c => c.id === id));
}

function deleteCompany(id) {
    if (confirm('Delete this company?')) {
        companies = companies.filter(c => c.id !== id);
        renderCompanies();
        renderDashboard();
    }
}

// ==========================================
// DRIVERS
// ==========================================

function renderDrivers() {
    document.getElementById('driversList').innerHTML = drivers.map(d => {
        const companyNames = (d.companyIds || []).map(cid => getCompanyName(cid)).join(', ') || 'No companies assigned';
        return `
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">${d.firstName} ${d.lastName}</div>
                        <div class="card-subtitle">${companyNames}</div>
                    </div>
                    <div class="card-actions">
                        <button class="card-btn" onclick="editDriver(${d.id})" title="Edit">&#9999;&#65039;</button>
                        <button class="card-btn" onclick="deleteDriver(${d.id})" title="Delete">&#128465;&#65039;</button>
                    </div>
                </div>
                <div class="card-content">
                    <div class="card-field"><span class="card-field-label">Phone:</span><span class="card-field-value">${d.phone}</span></div>
                    <div class="card-field"><span class="card-field-label">License:</span><span class="card-field-value">${d.licenseNumber}</span></div>
                </div>
            </div>
        `;
    }).join('');
}

function editDriver(id) {
    currentEditId = id;
    openModal('driver', drivers.find(d => d.id === id));
}

function deleteDriver(id) {
    if (confirm('Delete this driver?')) {
        drivers = drivers.filter(d => d.id !== id);
        renderDrivers();
        renderDashboard();
    }
}

// ==========================================
// JOBS
// ==========================================

function populateJobFilters() {
    const companyEl = document.getElementById('jobFilterCompany');
    const driverEl = document.getElementById('jobFilterDriver');
    if (!companyEl || !driverEl) return;

    const selCo = companyEl.value;
    companyEl.innerHTML = '<option value="">All Companies</option>' +
        companies.map(c => `<option value="${c.id}" ${selCo == c.id ? 'selected' : ''}>${c.name}</option>`).join('');

    const selDr = driverEl.value;
    const filteredDrivers = selCo
        ? drivers.filter(d => (d.companyIds || []).includes(parseInt(selCo)))
        : drivers;
    driverEl.innerHTML = '<option value="">All Drivers</option>' +
        filteredDrivers.map(d => `<option value="${d.id}" ${selDr == d.id ? 'selected' : ''}>${d.firstName} ${d.lastName}</option>`).join('');
}

function onJobFilterChange() {
    populateJobFilters();
    renderJobs();
}

function renderJobs() {
    const companyId = document.getElementById('jobFilterCompany')?.value;
    const driverId = document.getElementById('jobFilterDriver')?.value;
    let filtered = jobs;
    if (companyId) filtered = filtered.filter(j => j.companyId == companyId);
    if (driverId) filtered = filtered.filter(j => j.driverId == driverId);

    document.getElementById('jobsTableBody').innerHTML = filtered.length
        ? filtered.map(j => `
            <tr>
                <td>${j.jobNumber || '&mdash;'}</td>
                <td>${j.customerName}</td>
                <td>${getCompanyName(j.companyId)}</td>
                <td>${getDriverName(j.driverId)}</td>
                <td>${j.cubicFeet}</td>
                <td>$${parseFloat(j.rate).toFixed(2)}</td>
                <td>$${(j.cubicFeet * j.rate).toFixed(2)}</td>
                <td>$${parseFloat(j.balanceDue || 0).toFixed(2)}</td>
                <td>${j.date}</td>
                <td>
                    <button class="table-action-btn" onclick="editJob(${j.id})" title="Edit">&#9999;&#65039;</button>
                    <button class="table-action-btn" onclick="deleteJob(${j.id})" title="Delete">&#128465;&#65039;</button>
                </td>
            </tr>
        `).join('')
        : '<tr><td colspan="10" class="empty-row">No jobs found. Click "+ Add Job" to get started.</td></tr>';
}

function editJob(id) {
    currentEditId = id;
    openModal('job', jobs.find(j => j.id === id));
}

function deleteJob(id) {
    if (confirm('Delete this job?')) {
        jobs = jobs.filter(j => j.id !== id);
        renderJobs();
        renderDashboard();
    }
}

// ==========================================
// SHEETS
// ==========================================

function renderSheets() {
    document.getElementById('sheetsTableBody').innerHTML = sheets.length
        ? sheets.map(s => `
            <tr>
                <td>#${s.sheetNumber}</td>
                <td>${getCompanyName(s.companyId)}</td>
                <td>${getDriverName(s.driverId)}</td>
                <td><span class="badge badge-${s.templateType}">${s.templateType === 'bh' ? 'BH Relocation' : 'Prime Relocations'}</span></td>
                <td>${(s.jobSnapshots || []).length} jobs</td>
                <td>$${(s.total || 0).toFixed(2)}</td>
                <td>${s.date}</td>
                <td>
                    <button class="table-action-btn" onclick="previewSheet(${s.id})" title="View/Print">&#128065;&#65039;</button>
                    <button class="table-action-btn" onclick="deleteSheet(${s.id})" title="Delete">&#128465;&#65039;</button>
                </td>
            </tr>
        `).join('')
        : '<tr><td colspan="8" class="empty-row">No sheets generated yet. Click "+ Generate New Sheet".</td></tr>';
}

function deleteSheet(id) {
    if (confirm('Delete this sheet?')) {
        sheets = sheets.filter(s => s.id !== id);
        renderSheets();
        renderDashboard();
    }
}

// ==========================================
// MODAL SYSTEM
// ==========================================

function openModal(type, data = null) {
    currentModalType = type;
    const wrapper = document.getElementById('modalContentWrapper');
    const titleEl = document.getElementById('modalTitle');
    const bodyEl = document.getElementById('modalBody');
    const submitBtn = document.getElementById('submitBtn');

    // Reset width
    wrapper.style.maxWidth = '';
    wrapper.style.minWidth = '';

    if (type === 'company') {
        titleEl.textContent = currentEditId ? 'Edit Company' : 'Add Company';
        submitBtn.textContent = currentEditId ? 'Update' : 'Add Company';
        bodyEl.innerHTML = buildCompanyForm(data);
    } else if (type === 'driver') {
        titleEl.textContent = currentEditId ? 'Edit Driver' : 'Add Driver';
        submitBtn.textContent = currentEditId ? 'Update' : 'Add Driver';
        bodyEl.innerHTML = buildDriverForm(data);
    } else if (type === 'job') {
        titleEl.textContent = currentEditId ? 'Edit Job' : 'Add Job';
        submitBtn.textContent = currentEditId ? 'Update' : 'Add Job';
        bodyEl.innerHTML = buildJobForm(data);
        if (data) updateJobDriverDropdown(data.companyId, data.driverId);
    } else if (type === 'sheet') {
        titleEl.textContent = 'Generate Loading Sheet';
        submitBtn.textContent = 'Generate Sheet';
        wrapper.style.maxWidth = '780px';
        wrapper.style.minWidth = '680px';
        bodyEl.innerHTML = buildSheetForm();
    }

    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('modalOverlay').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
    document.getElementById('modalOverlay').classList.add('hidden');
    currentEditId = null;
    currentModalType = '';
}

function closePDFModal() {
    document.getElementById('pdfModal').classList.add('hidden');
    document.getElementById('modalOverlay').classList.add('hidden');
}

function handleOverlayClick() {
    // Only close the topmost modal
    if (!document.getElementById('pdfModal').classList.contains('hidden')) {
        closePDFModal();
    } else {
        closeModal();
    }
}

function handleSubmit() {
    if (currentModalType === 'company') saveCompany();
    else if (currentModalType === 'driver') saveDriver();
    else if (currentModalType === 'job') saveJob();
    else if (currentModalType === 'sheet') saveSheet();
}

// ==========================================
// FORM BUILDERS
// ==========================================

function buildCompanyForm(data) {
    return `
        <div class="form-group">
            <label>Company Name *</label>
            <input type="text" class="form-input" id="companyName" value="${esc(data?.name)}" placeholder="e.g. BH Relocation INC">
        </div>
        <div class="form-group">
            <label>Street Address</label>
            <input type="text" class="form-input" id="companyAddress" value="${esc(data?.address)}" placeholder="123 Main St, Suite 100">
        </div>
        <div class="form-group">
            <label>City, State ZIP</label>
            <input type="text" class="form-input" id="companyCity" value="${esc(data?.city)}" placeholder="Atlanta, GA 30301">
        </div>
        <div class="form-group">
            <label>Phone *</label>
            <input type="text" class="form-input" id="companyPhone" value="${esc(data?.phone)}" placeholder="(404) 000-0000">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>US DOT Number</label>
                <input type="text" class="form-input" id="companyDOT" value="${esc(data?.dotNumber)}" placeholder="DOT Number">
            </div>
            <div class="form-group">
                <label>MC / ICC Number</label>
                <input type="text" class="form-input" id="companyMC" value="${esc(data?.mcNumber)}" placeholder="MC Number">
            </div>
        </div>
    `;
}

function buildDriverForm(data) {
    const assignedIds = data?.companyIds || [];
    return `
        <div class="form-row">
            <div class="form-group">
                <label>First Name *</label>
                <input type="text" class="form-input" id="driverFirstName" value="${esc(data?.firstName)}" placeholder="First name">
            </div>
            <div class="form-group">
                <label>Last Name *</label>
                <input type="text" class="form-input" id="driverLastName" value="${esc(data?.lastName)}" placeholder="Last name">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Phone</label>
                <input type="text" class="form-input" id="driverPhone" value="${esc(data?.phone)}" placeholder="(000) 000-0000">
            </div>
            <div class="form-group">
                <label>License Number</label>
                <input type="text" class="form-input" id="driverLicense" value="${esc(data?.licenseNumber)}" placeholder="License #">
            </div>
        </div>
        <div class="form-group">
            <label>Assigned Companies <span style="color:var(--color-text-lighter);font-size:0.85rem;">(select all that apply)</span></label>
            <div class="checkbox-group">
                ${companies.length
                    ? companies.map(c => `
                        <label class="checkbox-label">
                            <input type="checkbox" name="driverCompanies" value="${c.id}" ${assignedIds.includes(c.id) ? 'checked' : ''}>
                            <span>${c.name}</span>
                        </label>
                    `).join('')
                    : '<p style="color:var(--color-text-lighter);">No companies yet. Add companies first.</p>'
                }
            </div>
        </div>
    `;
}

function buildJobForm(data) {
    const today = new Date().toISOString().split('T')[0];
    const cfPay = data ? (data.cubicFeet * data.rate).toFixed(2) : '0.00';
    return `
        <div class="form-row">
            <div class="form-group">
                <label>Company *</label>
                <select class="form-select" id="jobCompany" onchange="updateJobDriverDropdown()">
                    <option value="">Select Company</option>
                    ${companies.map(c => `<option value="${c.id}" ${data?.companyId == c.id ? 'selected' : ''}>${c.name}</option>`).join('')}
                </select>
            </div>
            <div class="form-group">
                <label>Driver *</label>
                <select class="form-select" id="jobDriver">
                    <option value="">Select Driver</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Job Number</label>
                <input type="text" class="form-input" id="jobNumber" value="${esc(data?.jobNumber)}" placeholder="e.g. J001">
            </div>
            <div class="form-group">
                <label>Date *</label>
                <input type="date" class="form-input" id="jobDate" value="${data?.date || today}">
            </div>
        </div>
        <div class="form-group">
            <label>Customer Name *</label>
            <input type="text" class="form-input" id="jobCustomer" value="${esc(data?.customerName)}" placeholder="Customer last name or full name">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>State <span style="color:var(--color-text-lighter);font-size:0.8rem;">(BH template)</span></label>
                <input type="text" class="form-input" id="jobState" value="${esc(data?.state)}" placeholder="e.g. FL">
            </div>
            <div class="form-group">
                <label>From / To <span style="color:var(--color-text-lighter);font-size:0.8rem;">(Prime template)</span></label>
                <input type="text" class="form-input" id="jobFromTo" value="${esc(data?.fromTo)}" placeholder="e.g. Atlanta GA / Miami FL">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Cubic Feet (CF)</label>
                <input type="number" class="form-input" id="jobCF" value="${data?.cubicFeet || ''}" placeholder="0" oninput="recalcJobCFPay()">
            </div>
            <div class="form-group">
                <label>Rate ($/CF)</label>
                <input type="number" step="0.01" class="form-input" id="jobRate" value="${data?.rate || ''}" placeholder="0.00" oninput="recalcJobCFPay()">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>CF Pay <span style="color:var(--color-text-lighter);font-size:0.8rem;">(auto)</span></label>
                <input type="text" class="form-input" id="jobCFPay" value="$${cfPay}" readonly style="opacity:0.7;">
            </div>
            <div class="form-group">
                <label>Balance Due</label>
                <input type="number" step="0.01" class="form-input" id="jobBalanceDue" value="${data?.balanceDue || ''}" placeholder="0.00">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Job Balance <span style="color:var(--color-text-lighter);font-size:0.8rem;">(Prime)</span></label>
                <input type="number" step="0.01" class="form-input" id="jobJobBalance" value="${data?.jobBalance || ''}" placeholder="0.00">
            </div>
            <div class="form-group">
                <label>Blanket $ <span style="color:var(--color-text-lighter);font-size:0.8rem;">(BH)</span></label>
                <input type="number" step="0.01" class="form-input" id="jobBlanket" value="${data?.blanket || ''}" placeholder="0.00">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Pads <span style="color:var(--color-text-lighter);font-size:0.8rem;">(Prime)</span></label>
                <input type="number" class="form-input" id="jobPads" value="${data?.pads || ''}" placeholder="0">
            </div>
            <div class="form-group">
                <label>ETA <span style="color:var(--color-text-lighter);font-size:0.8rem;">(Prime)</span></label>
                <input type="text" class="form-input" id="jobETA" value="${esc(data?.eta)}" placeholder="e.g. 3/5">
            </div>
        </div>
        <div class="form-group">
            <label>Remarks</label>
            <input type="text" class="form-input" id="jobRemarks" value="${esc(data?.remarks)}" placeholder="Any remarks or notes">
        </div>
    `;
}

function updateJobDriverDropdown(preselectCompanyId, preselectDriverId) {
    const companyId = preselectCompanyId !== undefined
        ? parseInt(preselectCompanyId)
        : parseInt(document.getElementById('jobCompany')?.value);
    const el = document.getElementById('jobDriver');
    if (!el) return;
    const filtered = drivers.filter(d => (d.companyIds || []).includes(companyId));
    el.innerHTML = '<option value="">Select Driver</option>' +
        filtered.map(d => `<option value="${d.id}" ${preselectDriverId == d.id ? 'selected' : ''}>${d.firstName} ${d.lastName}</option>`).join('');
}

function recalcJobCFPay() {
    const cf = parseFloat(document.getElementById('jobCF')?.value) || 0;
    const rate = parseFloat(document.getElementById('jobRate')?.value) || 0;
    const el = document.getElementById('jobCFPay');
    if (el) el.value = '$' + (cf * rate).toFixed(2);
}

function buildSheetForm() {
    const today = new Date().toISOString().split('T')[0];
    return `
        <div class="form-row">
            <div class="form-group">
                <label>Company *</label>
                <select class="form-select" id="sheetCompany" onchange="onSheetCompanyChange()">
                    <option value="">Select Company</option>
                    ${companies.map(c => `<option value="${c.id}">${c.name}</option>`).join('')}
                </select>
            </div>
            <div class="form-group">
                <label>Driver *</label>
                <select class="form-select" id="sheetDriver" onchange="loadJobsForSheet()">
                    <option value="">Select Driver</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Template Type *</label>
                <select class="form-select" id="sheetTemplate" onchange="onSheetTemplateChange()">
                    <option value="bh">BH Relocation Template</option>
                    <option value="prime">Prime Relocations Loading Sheet</option>
                </select>
            </div>
            <div class="form-group">
                <label>Sheet Date *</label>
                <input type="date" class="form-input" id="sheetDate" value="${today}">
            </div>
        </div>

        <!-- BH-specific header fields -->
        <div id="bhHeaderFields">
            <div class="form-row">
                <div class="form-group">
                    <label>Labor Rate ($/hr)</label>
                    <input type="number" step="0.01" class="form-input" id="sheetLaborRate" placeholder="e.g. 25.00">
                </div>
                <div class="form-group">
                    <label>Labor Amount ($)</label>
                    <input type="number" step="0.01" class="form-input" id="sheetLaborAmount" placeholder="0.00">
                </div>
            </div>
        </div>

        <!-- Prime-specific header fields -->
        <div id="primeHeaderFields" class="hidden">
            <div class="form-group">
                <label>Bill To (Carrier Name)</label>
                <input type="text" class="form-input" id="sheetBillTo" placeholder="Carrier company name">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Trip #</label>
                    <input type="text" class="form-input" id="sheetTripNumber" placeholder="Trip number">
                </div>
                <div class="form-group">
                    <label>Carrier Phone #</label>
                    <input type="text" class="form-input" id="sheetCarrierPhone" placeholder="Carrier phone">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Carrier's Dispatcher Name</label>
                    <input type="text" class="form-input" id="sheetDispatcherName" placeholder="Dispatcher name">
                </div>
                <div class="form-group">
                    <label>Dispatcher's Phone #</label>
                    <input type="text" class="form-input" id="sheetDispatcherPhone" placeholder="Dispatcher phone">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Received ($)</label>
                    <input type="number" step="0.01" class="form-input" id="sheetReceived" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Less Payment ($)</label>
                    <input type="number" step="0.01" class="form-input" id="sheetLessPayment" placeholder="0.00">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Labor ($)</label>
                    <input type="number" step="0.01" class="form-input" id="sheetPrimeLaborAmount" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Inventory Verified</label>
                    <select class="form-select" id="sheetInventory">
                        <option value="yes">Yes</option>
                        <option value="no">No</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Payment Due To</label>
                    <select class="form-select" id="sheetPaymentDueTo">
                        <option value="carrier">Carrier</option>
                        <option value="prime">Prime Moving</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Pads Owe To Prime</label>
                    <input type="number" class="form-input" id="sheetPadsOweToPrime" placeholder="0">
                </div>
            </div>
            <div class="form-group">
                <label>Remarks</label>
                <input type="text" class="form-input" id="sheetRemarks" placeholder="Remarks">
            </div>
        </div>

        <!-- Jobs selection -->
        <div class="sheet-jobs-section">
            <div class="line-items-header">
                <h3>Jobs to Include</h3>
                <span id="sheetJobsHint" style="color:var(--color-text-lighter);font-size:0.85rem;">Select company &amp; driver above</span>
            </div>
            <div id="sheetJobsList">
                <p class="jobs-empty-hint">Select a company and driver to see available jobs.</p>
            </div>
        </div>
    `;
}

function onSheetCompanyChange() {
    const companyId = parseInt(document.getElementById('sheetCompany').value);
    const driverEl = document.getElementById('sheetDriver');
    const filtered = drivers.filter(d => (d.companyIds || []).includes(companyId));
    driverEl.innerHTML = '<option value="">Select Driver</option>' +
        filtered.map(d => `<option value="${d.id}">${d.firstName} ${d.lastName}</option>`).join('');
    document.getElementById('sheetJobsList').innerHTML = '<p class="jobs-empty-hint">Now select a driver.</p>';
}

function onSheetTemplateChange() {
    const t = document.getElementById('sheetTemplate').value;
    document.getElementById('bhHeaderFields').classList.toggle('hidden', t !== 'bh');
    document.getElementById('primeHeaderFields').classList.toggle('hidden', t !== 'prime');
}

function loadJobsForSheet() {
    const companyId = parseInt(document.getElementById('sheetCompany').value);
    const driverId = parseInt(document.getElementById('sheetDriver').value);
    const container = document.getElementById('sheetJobsList');
    const hint = document.getElementById('sheetJobsHint');

    if (!companyId || !driverId) {
        container.innerHTML = '<p class="jobs-empty-hint">Select a company and driver first.</p>';
        return;
    }

    const available = jobs.filter(j => j.companyId === companyId && j.driverId === driverId);
    if (!available.length) {
        container.innerHTML = `<p class="jobs-empty-hint">No jobs found for this driver under ${getCompanyName(companyId)}. <br>Go to the <strong>Jobs</strong> tab to add jobs first.</p>`;
        hint.textContent = '0 jobs available';
        return;
    }

    hint.textContent = `${available.length} job(s) available`;
    container.innerHTML = `
        <label class="checkbox-label select-all-label">
            <input type="checkbox" id="selectAllSheetJobs" checked onchange="toggleAllSheetJobs(this)">
            <span>Select All Jobs</span>
        </label>
        <div class="jobs-checklist">
            ${available.map(j => `
                <label class="job-check-item">
                    <input type="checkbox" name="sheetJobs" value="${j.id}" checked>
                    <span class="job-check-info">
                        <strong>${j.jobNumber || 'No #'}</strong> &mdash; ${j.customerName}
                        &nbsp;|&nbsp; CF: ${j.cubicFeet} &times; $${parseFloat(j.rate).toFixed(2)} = <strong>$${(j.cubicFeet * j.rate).toFixed(2)}</strong>
                        &nbsp;|&nbsp; ${j.date}
                    </span>
                </label>
            `).join('')}
        </div>
    `;
}

function toggleAllSheetJobs(cb) {
    document.querySelectorAll('input[name="sheetJobs"]').forEach(el => el.checked = cb.checked);
}

// ==========================================
// SAVE HANDLERS
// ==========================================

function saveCompany() {
    const name = val('companyName');
    const phone = val('companyPhone');
    if (!name || !phone) { alert('Company name and phone are required.'); return; }

    const data = {
        name,
        address: val('companyAddress'),
        city: val('companyCity'),
        phone,
        dotNumber: val('companyDOT'),
        mcNumber: val('companyMC')
    };

    if (currentEditId) {
        Object.assign(companies.find(c => c.id === currentEditId), data);
    } else {
        companies.push({ id: Date.now(), ...data });
    }
    closeModal();
    renderCompanies();
    renderDashboard();
}

function saveDriver() {
    const firstName = val('driverFirstName');
    const lastName = val('driverLastName');
    if (!firstName || !lastName) { alert('First and last name are required.'); return; }

    const companyIds = Array.from(document.querySelectorAll('input[name="driverCompanies"]:checked'))
        .map(cb => parseInt(cb.value));

    const data = {
        firstName, lastName,
        phone: val('driverPhone'),
        licenseNumber: val('driverLicense'),
        companyIds
    };

    if (currentEditId) {
        Object.assign(drivers.find(d => d.id === currentEditId), data);
    } else {
        drivers.push({ id: Date.now(), ...data });
    }
    closeModal();
    renderDrivers();
    renderDashboard();
}

function saveJob() {
    const companyId = parseInt(document.getElementById('jobCompany').value);
    const driverId = parseInt(document.getElementById('jobDriver').value);
    const customerName = val('jobCustomer');
    const date = val('jobDate');

    if (!companyId || !driverId || !customerName || !date) {
        alert('Company, driver, customer name, and date are required.');
        return;
    }

    const data = {
        companyId, driverId,
        jobNumber: val('jobNumber'),
        date,
        customerName,
        state: val('jobState'),
        fromTo: val('jobFromTo'),
        cubicFeet: parseFloat(document.getElementById('jobCF').value) || 0,
        rate: parseFloat(document.getElementById('jobRate').value) || 0,
        balanceDue: parseFloat(document.getElementById('jobBalanceDue').value) || 0,
        jobBalance: parseFloat(document.getElementById('jobJobBalance').value) || 0,
        blanket: parseFloat(document.getElementById('jobBlanket').value) || 0,
        pads: parseInt(document.getElementById('jobPads').value) || 0,
        eta: val('jobETA'),
        remarks: val('jobRemarks')
    };

    if (currentEditId) {
        Object.assign(jobs.find(j => j.id === currentEditId), data);
    } else {
        jobs.push({ id: Date.now(), ...data });
    }
    closeModal();
    renderJobs();
    renderDashboard();
}

function saveSheet() {
    const companyId = parseInt(document.getElementById('sheetCompany').value);
    const driverId = parseInt(document.getElementById('sheetDriver').value);
    const templateType = document.getElementById('sheetTemplate').value;
    const date = val('sheetDate');

    if (!companyId || !driverId) { alert('Please select a company and driver.'); return; }

    const selectedJobIds = Array.from(document.querySelectorAll('input[name="sheetJobs"]:checked'))
        .map(cb => parseInt(cb.value));

    if (!selectedJobIds.length) { alert('Please select at least one job to include.'); return; }

    const selectedJobs = jobs.filter(j => selectedJobIds.includes(j.id)).map(j => ({ ...j }));

    const totalCFPay = selectedJobs.reduce((s, j) => s + j.cubicFeet * j.rate, 0);
    const totalBlanket = selectedJobs.reduce((s, j) => s + (j.blanket || 0), 0);
    const totalPads = selectedJobs.reduce((s, j) => s + (j.pads || 0), 0);

    const sheet = {
        id: Date.now(),
        sheetNumber: nextSheetNumber++,
        companyId, driverId, templateType, date,
        jobSnapshots: selectedJobs,
        total: totalCFPay,
        // BH fields
        laborRate: parseFloat(document.getElementById('sheetLaborRate')?.value) || 0,
        laborAmount: parseFloat(document.getElementById('sheetLaborAmount')?.value) || 0,
        // Prime fields
        billTo: val('sheetBillTo'),
        tripNumber: val('sheetTripNumber'),
        carrierPhone: val('sheetCarrierPhone'),
        dispatcherName: val('sheetDispatcherName'),
        dispatcherPhone: val('sheetDispatcherPhone'),
        received: parseFloat(document.getElementById('sheetReceived')?.value) || 0,
        lessPayment: parseFloat(document.getElementById('sheetLessPayment')?.value) || 0,
        primeLaborAmount: parseFloat(document.getElementById('sheetPrimeLaborAmount')?.value) || 0,
        inventoryVerified: document.getElementById('sheetInventory')?.value || 'yes',
        paymentDueTo: document.getElementById('sheetPaymentDueTo')?.value || 'carrier',
        padsOweToPrime: parseInt(document.getElementById('sheetPadsOweToPrime')?.value) || 0,
        sheetRemarks: val('sheetRemarks'),
        // Calc summaries
        totalBlanket, totalPads
    };

    sheets.push(sheet);
    closeModal();
    renderSheets();
    renderDashboard();
    // Auto-open preview
    setTimeout(() => previewSheet(sheet.id), 150);
}

// ==========================================
// SHEET PREVIEW / PRINT
// ==========================================

function previewSheet(sheetId) {
    const sheet = sheets.find(s => s.id === sheetId);
    if (!sheet) return;

    const company = companies.find(c => c.id === sheet.companyId);
    const driver = drivers.find(d => d.id === sheet.driverId);
    const sheetJobs = sheet.jobSnapshots || [];

    const html = sheet.templateType === 'bh'
        ? buildBHTemplate(company, driver, sheetJobs, sheet)
        : buildPrimeTemplate(company, driver, sheetJobs, sheet);

    document.getElementById('pdfContent').innerHTML = html;
    document.getElementById('pdfModal').classList.remove('hidden');
    document.getElementById('modalOverlay').classList.remove('hidden');
}

function buildBHTemplate(company, driver, sheetJobs, sheet) {
    const totalCF = sheetJobs.reduce((s, j) => s + (j.cubicFeet || 0), 0);
    const totalCFPay = sheetJobs.reduce((s, j) => s + j.cubicFeet * j.rate, 0);
    const totalBlanket = sheetJobs.reduce((s, j) => s + (j.blanket || 0), 0);
    const totalBalanceDue = sheetJobs.reduce((s, j) => s + (j.balanceDue || 0), 0);
    const laborAmount = sheet.laborAmount || 0;
    const totalDue = totalCFPay + laborAmount;
    const grandTotal = totalDue - totalBlanket;

    const rows = sheetJobs.map(j => `
        <tr>
            <td>${j.jobNumber || ''}</td>
            <td>${j.customerName || ''}</td>
            <td>${j.state || ''}</td>
            <td>${j.cubicFeet || ''}</td>
            <td>$${parseFloat(j.rate || 0).toFixed(2)}</td>
            <td>$${(j.cubicFeet * j.rate).toFixed(2)}</td>
            <td>$${parseFloat(j.balanceDue || 0).toFixed(2)}</td>
            <td>$${parseFloat(j.blanket || 0).toFixed(2)}</td>
            <td>${j.remarks || ''}</td>
        </tr>
    `).join('');

    return `
        <div class="bh-invoice">
            <div class="bh-header">
                <div class="bh-company-name">${company?.name || ''}</div>
                <div class="bh-company-sub">
                    ${company?.address || ''}, ${company?.city || ''}
                </div>
                <div class="bh-company-reg">
                    US DOT: <strong>${company?.dotNumber || ''}</strong>
                    &nbsp;&nbsp;&nbsp;
                    MC/ICC: <strong>${company?.mcNumber || ''}</strong>
                    &nbsp;&nbsp;&nbsp;
                    Phone: <strong>${company?.phone || ''}</strong>
                </div>
            </div>

            <div class="bh-driver-row">
                <div><strong>Driver Name:</strong> ${driver ? driver.firstName + ' ' + driver.lastName : ''}</div>
                <div><strong>Driver Phone:</strong> ${driver?.phone || ''}</div>
                <div><strong>Date:</strong> ${sheet.date || ''}</div>
            </div>

            <table class="bh-table">
                <thead>
                    <tr>
                        <th>Job #</th>
                        <th>Customer Name</th>
                        <th>State</th>
                        <th>CF</th>
                        <th>Rate</th>
                        <th>CF Pay</th>
                        <th>Balance Due</th>
                        <th>Blanket</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    ${rows}
                    <tr class="bh-total-row">
                        <td colspan="3"><strong>TOTALS</strong></td>
                        <td><strong>${totalCF}</strong></td>
                        <td></td>
                        <td><strong>$${totalCFPay.toFixed(2)}</strong></td>
                        <td><strong>$${totalBalanceDue.toFixed(2)}</strong></td>
                        <td><strong>$${totalBlanket.toFixed(2)}</strong></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            <div class="bh-summary">
                <div class="bh-summary-row">
                    <span>Labor @ ${sheet.laborRate ? '$' + parseFloat(sheet.laborRate).toFixed(2) + '/hr' : '_______'}</span>
                    <span>$${laborAmount.toFixed(2)}</span>
                </div>
                <div class="bh-summary-row">
                    <span>Total Due To ${company?.name || 'BH Relocation'}:</span>
                    <span><strong>$${totalDue.toFixed(2)}</strong></span>
                </div>
                <div class="bh-summary-row">
                    <span>Blanket Due To Carrier:</span>
                    <span>$${totalBlanket.toFixed(2)}</span>
                </div>
                <div class="bh-summary-row bh-grand-total">
                    <span><strong>TOTAL:</strong></span>
                    <span><strong>$${grandTotal.toFixed(2)}</strong></span>
                </div>
            </div>

            <div class="bh-signatures">
                <div class="bh-sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-label">Driver Signature</div>
                </div>
                <div class="bh-sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-label">Date</div>
                </div>
            </div>
        </div>
    `;
}

function buildPrimeTemplate(company, driver, sheetJobs, sheet) {
    const totalCF = sheetJobs.reduce((s, j) => s + (j.cubicFeet || 0), 0);
    const totalCFPay = sheetJobs.reduce((s, j) => s + j.cubicFeet * j.rate, 0);
    const totalPads = sheetJobs.reduce((s, j) => s + (j.pads || 0), 0);
    const totalJobBalance = sheetJobs.reduce((s, j) => s + (j.jobBalance || 0), 0);
    const carrierFee = totalCFPay * 0.10;
    const received = sheet.received || 0;
    const laborAmount = sheet.primeLaborAmount || 0;
    const lessPayment = sheet.lessPayment || 0;
    const balanceDue = totalCFPay + carrierFee + laborAmount - received - lessPayment;

    const rows = sheetJobs.map(j => `
        <tr>
            <td>${j.customerName || ''}</td>
            <td>${j.jobNumber || ''}</td>
            <td>${j.fromTo || ''}</td>
            <td>${j.cubicFeet || ''}</td>
            <td>$${parseFloat(j.rate || 0).toFixed(2)}</td>
            <td>$${(j.cubicFeet * j.rate).toFixed(2)}</td>
            <td>$${parseFloat(j.jobBalance || 0).toFixed(2)}</td>
            <td>${j.pads || ''}</td>
            <td>${j.eta || ''}</td>
        </tr>
    `).join('');

    return `
        <div class="prime-invoice">
            <div class="prime-header">
                <div class="prime-company-name">${company?.name || ''}</div>
                <div class="prime-company-sub">
                    ${company?.address || ''}, ${company?.city || ''}
                </div>
                <div class="prime-company-reg">
                    US DOT: <strong>${company?.dotNumber || ''}</strong>
                    &nbsp;&nbsp;&nbsp;
                    MC/ICC: <strong>${company?.mcNumber || ''}</strong>
                    &nbsp;&nbsp;&nbsp;
                    Phone: <strong>${company?.phone || ''}</strong>
                </div>
            </div>

            <div class="prime-info-grid">
                <div class="prime-info-left">
                    <div class="prime-info-row"><strong>Bill To:</strong> ${sheet.billTo || '_________________________'}</div>
                    <div class="prime-info-row"><strong>Description/Services:</strong> Delivery Out</div>
                    <div class="prime-info-row"><strong>Driver:</strong> ${driver ? driver.firstName + ' ' + driver.lastName : ''}</div>
                    <div class="prime-info-row"><strong>Phone #:</strong> ${driver?.phone || ''}</div>
                    <div class="prime-info-row"><strong>Carrier's Dispatcher Name:</strong> ${sheet.dispatcherName || '_________________________'}</div>
                </div>
                <div class="prime-info-right">
                    <div class="prime-info-row"><strong>Date:</strong> ${sheet.date || ''}</div>
                    <div class="prime-info-row"><strong>Trip #:</strong> ${sheet.tripNumber || '_________'}</div>
                    <div class="prime-info-row"><strong>Carrier Phone #:</strong> ${sheet.carrierPhone || '_________________________'}</div>
                    <div class="prime-info-row"><strong>Dispatcher's Phone #:</strong> ${sheet.dispatcherPhone || '_________________________'}</div>
                </div>
            </div>

            <table class="prime-table">
                <thead>
                    <tr>
                        <th>Customer Last Name</th>
                        <th>Job</th>
                        <th>FR / TO</th>
                        <th>CU.FT.</th>
                        <th>Rate</th>
                        <th>Total</th>
                        <th>Job Balance</th>
                        <th>Pads</th>
                        <th>ETA</th>
                    </tr>
                </thead>
                <tbody>
                    ${rows}
                    <tr class="prime-total-row">
                        <td colspan="3"><strong>TOTALS</strong></td>
                        <td><strong>${totalCF}</strong></td>
                        <td></td>
                        <td><strong>$${totalCFPay.toFixed(2)}</strong></td>
                        <td><strong>$${totalJobBalance.toFixed(2)}</strong></td>
                        <td><strong>${totalPads}</strong></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            <div class="prime-summary-section">
                <div class="prime-summary-col">
                    <div class="prime-sum-row"><span>Total Pads:</span><span>${totalPads}</span></div>
                    <div class="prime-sum-row"><span>Received:</span><span>$${received.toFixed(2)}</span></div>
                    <div class="prime-sum-row"><span>Carrier Fee (10%):</span><span>$${carrierFee.toFixed(2)}</span></div>
                    <div class="prime-sum-row"><span>Less Job Balance:</span><span>$${totalJobBalance.toFixed(2)}</span></div>
                    <div class="prime-sum-row"><span>Inventory Verified:</span><span>${sheet.inventoryVerified === 'yes' ? 'Yes' : 'No'}</span></div>
                </div>
                <div class="prime-summary-col">
                    <div class="prime-sum-row"><span>Labor:</span><span>$${laborAmount.toFixed(2)}</span></div>
                    <div class="prime-sum-row"><span>Less Payment:</span><span>$${lessPayment.toFixed(2)}</span></div>
                    <div class="prime-sum-row"><span>Remarks:</span><span>${sheet.sheetRemarks || ''}</span></div>
                    <div class="prime-sum-row"><span>Pads:</span><span>${totalPads}</span></div>
                    <div class="prime-sum-row prime-balance-due">
                        <span><strong>Balance Due:</strong></span>
                        <span><strong>$${Math.max(0, balanceDue).toFixed(2)}</strong></span>
                    </div>
                    <div class="prime-sum-row"><span>Payment Due To:</span><span>${sheet.paymentDueTo === 'prime' ? 'Prime Moving' : 'Carrier'}</span></div>
                    <div class="prime-sum-row"><span>Pads Owe To Prime:</span><span>${sheet.padsOweToPrime || 0}</span></div>
                </div>
            </div>

            <div class="prime-signature">
                <div class="prime-sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-label">Driver's Signature</div>
                </div>
                <div class="prime-sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-label">Date</div>
                </div>
            </div>
        </div>
    `;
}

function printPDF() {
    window.print();
}

// ==========================================
// HELPERS
// ==========================================

function getCompanyName(id) {
    return companies.find(c => c.id === id)?.name || 'N/A';
}

function getDriverName(id) {
    const d = drivers.find(d => d.id === id);
    return d ? `${d.firstName} ${d.lastName}` : 'N/A';
}

// Get trimmed value from input element by id
function val(id) {
    return (document.getElementById(id)?.value || '').trim();
}

// Escape HTML to prevent XSS in form field values
function esc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}
