// ==========================================
// INVOICE MANAGEMENT SYSTEM - JAVASCRIPT
// ==========================================

// DATA STORAGE
let companies = [
    { id: 1, name: 'Prime Relocations', address: '5695 Oakbrook Parkway, Suite D, Norcross GA 30093', phone: '(770) 954-7095', dotNumber: '806005', mcNumber: '358641' },
    { id: 2, name: 'BH Relocation', address: '123 Main St, Atlanta GA 30301', phone: '(404) 123-4567', dotNumber: '123456', mcNumber: '654321' }
];

let drivers = [
    { id: 1, companyId: 1, firstName: 'Joseph', lastName: 'Smith', phone: '(770) 555-0001', licenseNumber: 'DL123456' },
    { id: 2, companyId: 1, firstName: 'John', lastName: 'Doe', phone: '(770) 555-0002', licenseNumber: 'DL123457' },
    { id: 3, companyId: 2, firstName: 'Ahmed', lastName: 'Hassan', phone: '(404) 555-0003', licenseNumber: 'DL123458' }
];

let invoices = [
    { id: 1, invoiceNumber: '001', companyId: 1, driverId: 1, customerName: 'John Customer', deliveryLocation: 'Miami, FL', date: '2026-03-01', lineItems: [{description: 'Residential Move', cubicFeet: 1500, rate: 1}], total: 1500 },
    { id: 2, invoiceNumber: '002', companyId: 1, driverId: 2, customerName: 'Jane Client', deliveryLocation: 'Tampa, FL', date: '2026-03-02', lineItems: [{description: 'Commercial Move', cubicFeet: 2300, rate: 1}], total: 2300 }
];

let currentModalType = '';
let currentEditId = null;
let currentInvoiceForm = {
    companyId: '',
    driverId: '',
    customerName: '',
    deliveryLocation: '',
    date: new Date().toISOString().split('T')[0],
    lineItems: [{ description: '', cubicFeet: '', rate: '' }]
};

// ==========================================
// INITIALIZATION
// ==========================================

document.addEventListener('DOMContentLoaded', function() {
    setupNavigation();
    renderDashboard();
});

// ==========================================
// NAVIGATION & TAB SWITCHING
// ==========================================

function setupNavigation() {
    const navBtns = document.querySelectorAll('.nav-btn');
    navBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            switchTab(tabName);
        });
    });
}

function switchTab(tabName) {
    // Hide all tabs
    const allTabs = document.querySelectorAll('.tab-content');
    allTabs.forEach(tab => tab.classList.remove('active'));

    // Remove active from all buttons
    const allBtns = document.querySelectorAll('.nav-btn');
    allBtns.forEach(btn => btn.classList.remove('active'));

    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');

    // Render content
    if (tabName === 'dashboard') renderDashboard();
    else if (tabName === 'companies') renderCompanies();
    else if (tabName === 'drivers') renderDrivers();
    else if (tabName === 'invoices') renderInvoices();
}

// ==========================================
// DASHBOARD
// ==========================================

function renderDashboard() {
    const totalRevenue = invoices.reduce((sum, inv) => sum + (inv.total || 0), 0);
    
    document.getElementById('statCompanies').textContent = companies.length;
    document.getElementById('statDrivers').textContent = drivers.length;
    document.getElementById('statInvoicesCount').textContent = invoices.length;
    document.getElementById('statRevenue').textContent = `$${totalRevenue.toLocaleString()}`;
    document.getElementById('totalInvoices').textContent = invoices.length;

    // Recent invoices
    const recentHTML = invoices.slice(-5).reverse().map(inv => `
        <div class="invoice-item">
            <div class="invoice-item-left">
                <div class="invoice-item-number">Invoice #${inv.invoiceNumber}</div>
                <div class="invoice-item-details">${getCompanyName(inv.companyId)} - ${getDriverName(inv.driverId)}</div>
            </div>
            <div class="invoice-item-right">
                <div class="invoice-item-total">$${inv.total}</div>
                <div class="invoice-item-date">${inv.date}</div>
            </div>
        </div>
    `).join('');

    document.getElementById('recentInvoices').innerHTML = recentHTML;
}

// ==========================================
// COMPANIES
// ==========================================

function renderCompanies() {
    const html = companies.map(company => `
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">${company.name}</div>
                    <div class="card-subtitle">${company.address}</div>
                </div>
                <div class="card-actions">
                    <button class="card-btn" onclick="editCompany(${company.id})">✏️</button>
                    <button class="card-btn" onclick="deleteCompany(${company.id})">🗑️</button>
                </div>
            </div>
            <div class="card-content">
                <div class="card-field">
                    <span class="card-field-label">Phone:</span>
                    <span class="card-field-value">${company.phone}</span>
                </div>
                <div class="card-field">
                    <span class="card-field-label">DOT:</span>
                    <span class="card-field-value">${company.dotNumber}</span>
                </div>
                <div class="card-field">
                    <span class="card-field-label">MC:</span>
                    <span class="card-field-value">${company.mcNumber}</span>
                </div>
            </div>
        </div>
    `).join('');

    document.getElementById('companiesList').innerHTML = html;
}

function editCompany(id) {
    const company = companies.find(c => c.id === id);
    if (company) {
        currentEditId = id;
        openModal('company', company);
    }
}

function deleteCompany(id) {
    if (confirm('Are you sure you want to delete this company?')) {
        companies = companies.filter(c => c.id !== id);
        renderCompanies();
    }
}

// ==========================================
// DRIVERS
// ==========================================

function renderDrivers() {
    const html = drivers.map(driver => `
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">${driver.firstName} ${driver.lastName}</div>
                    <div class="card-subtitle">${getCompanyName(driver.companyId)}</div>
                </div>
                <div class="card-actions">
                    <button class="card-btn" onclick="editDriver(${driver.id})">✏️</button>
                    <button class="card-btn" onclick="deleteDriver(${driver.id})">🗑️</button>
                </div>
            </div>
            <div class="card-content">
                <div class="card-field">
                    <span class="card-field-label">Phone:</span>
                    <span class="card-field-value">${driver.phone}</span>
                </div>
                <div class="card-field">
                    <span class="card-field-label">License:</span>
                    <span class="card-field-value">${driver.licenseNumber}</span>
                </div>
            </div>
        </div>
    `).join('');

    document.getElementById('driversList').innerHTML = html;
}

function editDriver(id) {
    const driver = drivers.find(d => d.id === id);
    if (driver) {
        currentEditId = id;
        openModal('driver', driver);
    }
}

function deleteDriver(id) {
    if (confirm('Are you sure you want to delete this driver?')) {
        drivers = drivers.filter(d => d.id !== id);
        renderDrivers();
    }
}

// ==========================================
// INVOICES
// ==========================================

function renderInvoices() {
    const html = invoices.map(inv => `
        <tr>
            <td>#${inv.invoiceNumber}</td>
            <td>${getCompanyName(inv.companyId)}</td>
            <td>${getDriverName(inv.driverId)}</td>
            <td>${inv.customerName}</td>
            <td>$${inv.total}</td>
            <td>${inv.date}</td>
            <td>
                <button class="table-action-btn" onclick="previewPDF(${inv.id})" title="View">👁️</button>
            </td>
        </tr>
    `).join('');

    document.getElementById('invoicesTableBody').innerHTML = html;
}

// ==========================================
// MODAL FUNCTIONS
// ==========================================

function openModal(type, data = null) {
    currentModalType = type;
    const modal = document.getElementById('modal');
    const overlay = document.getElementById('modalOverlay');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const submitBtn = document.getElementById('submitBtn');

    if (type === 'company') {
        modalTitle.textContent = currentEditId ? 'Edit Company' : 'Add Company';
        submitBtn.textContent = currentEditId ? 'Update Company' : 'Add Company';
        modalBody.innerHTML = `
            <div class="form-group">
                <label>Company Name</label>
                <input type="text" class="form-input" id="companyName" value="${data?.name || ''}" placeholder="Enter company name">
            </div>
            <div class="form-group">
                <label>Address</label>
                <input type="text" class="form-input" id="companyAddress" value="${data?.address || ''}" placeholder="Enter full address">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" class="form-input" id="companyPhone" value="${data?.phone || ''}" placeholder="Enter phone number">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>DOT Number</label>
                    <input type="text" class="form-input" id="companyDOT" value="${data?.dotNumber || ''}" placeholder="DOT Number">
                </div>
                <div class="form-group">
                    <label>MC Number</label>
                    <input type="text" class="form-input" id="companyMC" value="${data?.mcNumber || ''}" placeholder="MC Number">
                </div>
            </div>
        `;
    } else if (type === 'driver') {
        modalTitle.textContent = currentEditId ? 'Edit Driver' : 'Add Driver';
        submitBtn.textContent = currentEditId ? 'Update Driver' : 'Add Driver';
        modalBody.innerHTML = `
            <div class="form-group">
                <label>Company</label>
                <select class="form-select" id="driverCompany">
                    <option value="">Select Company</option>
                    ${companies.map(c => `<option value="${c.id}" ${data?.companyId === c.id ? 'selected' : ''}>${c.name}</option>`).join('')}
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" class="form-input" id="driverFirstName" value="${data?.firstName || ''}" placeholder="First name">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" class="form-input" id="driverLastName" value="${data?.lastName || ''}" placeholder="Last name">
                </div>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" class="form-input" id="driverPhone" value="${data?.phone || ''}" placeholder="Phone number">
            </div>
            <div class="form-group">
                <label>License Number</label>
                <input type="text" class="form-input" id="driverLicense" value="${data?.licenseNumber || ''}" placeholder="License number">
            </div>
        `;
    } else if (type === 'invoice') {
        modalTitle.textContent = 'Create New Invoice';
        submitBtn.textContent = 'Generate Invoice';
        modalBody.innerHTML = getInvoiceFormHTML();
    }

    modal.classList.remove('hidden');
    overlay.classList.remove('hidden');
}

function getInvoiceFormHTML() {
    return `
        <div class="form-row">
            <div class="form-group">
                <label>Company</label>
                <select class="form-select" id="invoiceCompany" onchange="updateDriverDropdown()">
                    <option value="">Select Company</option>
                    ${companies.map(c => `<option value="${c.id}">${c.name}</option>`).join('')}
                </select>
            </div>
            <div class="form-group">
                <label>Driver</label>
                <select class="form-select" id="invoiceDriver">
                    <option value="">Select Driver</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Customer Name</label>
                <input type="text" class="form-input" id="invoiceCustomer" placeholder="Enter customer name">
            </div>
            <div class="form-group">
                <label>Date</label>
                <input type="date" class="form-input" id="invoiceDate" value="${currentInvoiceForm.date}">
            </div>
        </div>
        <div class="form-group">
            <label>Delivery Location</label>
            <input type="text" class="form-input" id="invoiceDelivery" placeholder="Enter delivery address">
        </div>
        
        <div class="line-items">
            <div class="line-items-header">
                <h3>Line Items</h3>
                <button class="add-item-btn" onclick="addLineItem()">+ Add Item</button>
            </div>
            <div id="lineItemsContainer">
                ${getLineItemsHTML()}
            </div>
        </div>

        <div class="summary-box">
            <h3>Summary</h3>
            <div class="summary-row">
                <span>Subtotal:</span>
                <span id="subtotal">$0.00</span>
            </div>
            <div class="summary-row">
                <span>Carrier Fee (10%):</span>
                <span id="carrierFee">$0.00</span>
            </div>
            <div class="summary-row total">
                <span>TOTAL DUE:</span>
                <span id="totalDue">$0.00</span>
            </div>
        </div>

        <div style="text-align: center; margin-top: 1rem;">
            <button class="btn btn-secondary" onclick="previewPDFBefore()" style="margin-right: 1rem;">Preview PDF</button>
        </div>
    `;
}

function getLineItemsHTML() {
    return currentInvoiceForm.lineItems.map((item, idx) => `
        <div class="line-item">
            <input type="text" class="form-input" placeholder="Description" value="${item.description}" onchange="updateLineItem(${idx}, 'description', this.value)">
            <input type="number" class="form-input" placeholder="Cubic Feet" value="${item.cubicFeet}" onchange="updateLineItem(${idx}, 'cubicFeet', this.value); updateCalculations()">
            <input type="number" class="form-input" placeholder="Rate" value="${item.rate}" onchange="updateLineItem(${idx}, 'rate', this.value); updateCalculations()">
            <div class="line-item-total">$${calculateLineTotal(item)}</div>
            ${currentInvoiceForm.lineItems.length > 1 ? `<button class="remove-item-btn" onclick="removeLineItem(${idx})">✕</button>` : '<div></div>'}
        </div>
    `).join('');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
    document.getElementById('modalOverlay').classList.add('hidden');
    currentEditId = null;
}

function handleSubmit() {
    if (currentModalType === 'company') {
        const name = document.getElementById('companyName').value;
        const address = document.getElementById('companyAddress').value;
        const phone = document.getElementById('companyPhone').value;
        const dotNumber = document.getElementById('companyDOT').value;
        const mcNumber = document.getElementById('companyMC').value;

        if (!name || !address || !phone) {
            alert('Please fill in all required fields');
            return;
        }

        if (currentEditId) {
            const company = companies.find(c => c.id === currentEditId);
            company.name = name;
            company.address = address;
            company.phone = phone;
            company.dotNumber = dotNumber;
            company.mcNumber = mcNumber;
        } else {
            companies.push({
                id: Date.now(),
                name, address, phone, dotNumber, mcNumber
            });
        }
        renderCompanies();
    } else if (currentModalType === 'driver') {
        const companyId = document.getElementById('driverCompany').value;
        const firstName = document.getElementById('driverFirstName').value;
        const lastName = document.getElementById('driverLastName').value;
        const phone = document.getElementById('driverPhone').value;
        const licenseNumber = document.getElementById('driverLicense').value;

        if (!companyId || !firstName || !lastName) {
            alert('Please fill in all required fields');
            return;
        }

        if (currentEditId) {
            const driver = drivers.find(d => d.id === currentEditId);
            driver.companyId = parseInt(companyId);
            driver.firstName = firstName;
            driver.lastName = lastName;
            driver.phone = phone;
            driver.licenseNumber = licenseNumber;
        } else {
            drivers.push({
                id: Date.now(),
                companyId: parseInt(companyId),
                firstName, lastName, phone, licenseNumber
            });
        }
        renderDrivers();
    } else if (currentModalType === 'invoice') {
        generateInvoice();
    }
    closeModal();
}

// ==========================================
// INVOICE FORM HANDLING
// ==========================================

function updateDriverDropdown() {
    const companyId = document.getElementById('invoiceCompany').value;
    const driverSelect = document.getElementById('invoiceDriver');
    
    if (companyId) {
        const driversForCompany = drivers.filter(d => d.companyId === parseInt(companyId));
        driverSelect.innerHTML = '<option value="">Select Driver</option>' +
            driversForCompany.map(d => `<option value="${d.id}">${d.firstName} ${d.lastName}</option>`).join('');
    } else {
        driverSelect.innerHTML = '<option value="">Select Driver</option>';
    }
}

function addLineItem() {
    currentInvoiceForm.lineItems.push({ description: '', cubicFeet: '', rate: '' });
    document.getElementById('lineItemsContainer').innerHTML = getLineItemsHTML();
}

function removeLineItem(idx) {
    currentInvoiceForm.lineItems.splice(idx, 1);
    document.getElementById('lineItemsContainer').innerHTML = getLineItemsHTML();
    updateCalculations();
}

function updateLineItem(idx, field, value) {
    currentInvoiceForm.lineItems[idx][field] = value;
    document.getElementById('lineItemsContainer').innerHTML = getLineItemsHTML();
}

function calculateLineTotal(item) {
    const cf = parseFloat(item.cubicFeet) || 0;
    const rate = parseFloat(item.rate) || 0;
    return (cf * rate).toFixed(2);
}

function updateCalculations() {
    const subtotal = currentInvoiceForm.lineItems.reduce((sum, item) => sum + parseFloat(calculateLineTotal(item)), 0);
    const carrierFee = subtotal * 0.1;
    const total = subtotal + carrierFee;

    document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('carrierFee').textContent = `$${carrierFee.toFixed(2)}`;
    document.getElementById('totalDue').textContent = `$${total.toFixed(2)}`;
}

function generateInvoice() {
    currentInvoiceForm.companyId = parseInt(document.getElementById('invoiceCompany').value);
    currentInvoiceForm.driverId = parseInt(document.getElementById('invoiceDriver').value);
    currentInvoiceForm.customerName = document.getElementById('invoiceCustomer').value;
    currentInvoiceForm.deliveryLocation = document.getElementById('invoiceDelivery').value;
    currentInvoiceForm.date = document.getElementById('invoiceDate').value;

    if (!currentInvoiceForm.companyId || !currentInvoiceForm.driverId || !currentInvoiceForm.customerName) {
        alert('Please fill in all required fields');
        return;
    }

    const subtotal = currentInvoiceForm.lineItems.reduce((sum, item) => sum + parseFloat(calculateLineTotal(item)), 0);
    const total = subtotal + (subtotal * 0.1);

    const newInvoice = {
        id: Date.now(),
        invoiceNumber: (invoices.length + 1).toString().padStart(3, '0'),
        ...currentInvoiceForm,
        total: parseFloat(total.toFixed(2))
    };

    invoices.push(newInvoice);
    
    // Reset form
    currentInvoiceForm = {
        companyId: '',
        driverId: '',
        customerName: '',
        deliveryLocation: '',
        date: new Date().toISOString().split('T')[0],
        lineItems: [{ description: '', cubicFeet: '', rate: '' }]
    };

    renderDashboard();
    renderInvoices();
    alert('Invoice created successfully!');
}

function previewPDFBefore() {
    currentInvoiceForm.companyId = parseInt(document.getElementById('invoiceCompany').value);
    currentInvoiceForm.driverId = parseInt(document.getElementById('invoiceDriver').value);
    currentInvoiceForm.customerName = document.getElementById('invoiceCustomer').value;
    currentInvoiceForm.deliveryLocation = document.getElementById('invoiceDelivery').value;
    
    if (!currentInvoiceForm.companyId || !currentInvoiceForm.driverId) {
        alert('Please select company and driver');
        return;
    }

    showPDFPreview(null);
}

// ==========================================
// PDF FUNCTIONS
// ==========================================

function previewPDF(invoiceId) {
    const invoice = invoices.find(i => i.id === invoiceId);
    if (invoice) {
        showPDFPreview(invoice);
    }
}

function showPDFPreview(invoice) {
    const pdfModal = document.getElementById('pdfModal');
    const pdfContent = document.getElementById('pdfContent');
    
    let company, driver, total, subtotal, carrierFee;

    if (invoice) {
        company = companies.find(c => c.id === invoice.companyId);
        driver = drivers.find(d => d.id === invoice.driverId);
        subtotal = invoice.lineItems.reduce((sum, item) => sum + parseFloat(calculateLineTotal(item)), 0);
        carrierFee = subtotal * 0.1;
        total = subtotal + carrierFee;
    } else {
        company = companies.find(c => c.id === currentInvoiceForm.companyId);
        driver = drivers.find(d => d.id === currentInvoiceForm.driverId);
        subtotal = currentInvoiceForm.lineItems.reduce((sum, item) => sum + parseFloat(calculateLineTotal(item)), 0);
        carrierFee = subtotal * 0.1;
        total = subtotal + carrierFee;
    }

    const lineItems = invoice ? invoice.lineItems : currentInvoiceForm.lineItems;

    let html = `
        <div class="invoice-document">
            <div class="invoice-header">
                <div class="invoice-company-name">${company.name}</div>
                <div class="invoice-subtitle">LOADING SHEET</div>
            </div>

            <div class="invoice-info-row">
                <div class="invoice-info-block">
                    <div>${company.address}</div>
                    <div>Tel: ${company.phone}</div>
                </div>
                <div class="invoice-info-block" style="text-align: center;">
                    <div><strong>US DOT ${company.dotNumber}</strong></div>
                </div>
                <div class="invoice-info-block" style="text-align: right;">
                    <div><strong>MC/ICC ${company.mcNumber}</strong></div>
                </div>
            </div>

            <div class="invoice-details">
                <div>
                    <div><strong>Customer:</strong> ${currentInvoiceForm.customerName}</div>
                    <div><strong>Delivery:</strong> ${currentInvoiceForm.deliveryLocation}</div>
                </div>
                <div>
                    <div><strong>Date:</strong> ${currentInvoiceForm.date}</div>
                    <div><strong>Driver:</strong> ${driver.firstName} ${driver.lastName}</div>
                </div>
            </div>

            <table class="invoice-items-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align: center;">Cubic Feet</th>
                        <th style="text-align: center;">Rate</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    ${lineItems.map(item => item.description ? `
                        <tr>
                            <td>${item.description}</td>
                            <td style="text-align: center;">${item.cubicFeet}</td>
                            <td style="text-align: center;">$${item.rate}</td>
                            <td style="text-align: right;"><strong>$${calculateLineTotal(item)}</strong></td>
                        </tr>
                    ` : '').join('')}
                </tbody>
            </table>

            <div class="invoice-totals">
                <div class="invoice-total-row">
                    <span><strong>SUBTOTAL</strong></span>
                    <span>$${subtotal.toFixed(2)}</span>
                </div>
                <div class="invoice-total-row">
                    <span><strong>CARRIER FEE</strong></span>
                    <span>$${carrierFee.toFixed(2)}</span>
                </div>
                <div class="invoice-total-row grand-total">
                    <span>TOTAL DUE</span>
                    <span>$${total.toFixed(2)}</span>
                </div>
            </div>

            <div style="text-align: center; margin-top: 2rem; font-size: 0.9rem; color: #666;">
                Professional Moving & Storage Services
            </div>
        </div>
    `;

    pdfContent.innerHTML = html;
    pdfModal.classList.remove('hidden');
    document.getElementById('modalOverlay').classList.remove('hidden');
}

function closePDFModal() {
    document.getElementById('pdfModal').classList.add('hidden');
    document.getElementById('modalOverlay').classList.add('hidden');
}

function printPDF() {
    window.print();
}

// ==========================================
// HELPER FUNCTIONS
// ==========================================

function getCompanyName(id) {
    const company = companies.find(c => c.id === id);
    return company ? company.name : 'N/A';
}

function getDriverName(id) {
    const driver = drivers.find(d => d.id === id);
    return driver ? `${driver.firstName} ${driver.lastName}` : 'N/A';
}
