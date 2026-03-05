// ═══════════════════════════════════════════
// SHARED DATA LAYER
// ═══════════════════════════════════════════

let companies        = [];
let drivers          = [];
let invoices         = [];
let companyInvoices  = [];
let driverInvoices   = [];

let nextCompanyId   = 1;
let nextDriverId    = 1;
let nextInvoiceId   = 1;
let nextCoInvId     = 1;
let nextDrInvId     = 1;

const SAMPLE = {
    companies: [
        { id:1, name:'BH Relocation INC',  address:'11723 Amber Park DR Suite 160', city:'Alpharetta, GA 30009', phone:'(770) 123-4567', dotNumber:'2521000', mcNumber:'875158' },
        { id:2, name:'Prime Relocations',   address:'5695 Oakbrook Parkway, Suite D', city:'Norcross, GA 30093',  phone:'(770) 954-7095', dotNumber:'806005',  mcNumber:'358641' }
    ],
    drivers: [
        { id:1, firstName:'BAKARY', lastName:'Diallo',  phone:'(770) 555-0001', license:'DL001' },
        { id:2, firstName:'JOHN',   lastName:'Doe',     phone:'(770) 555-0002', license:'DL002' },
        { id:3, firstName:'Joseph', lastName:'Smith',   phone:'(770) 555-0003', license:'DL003' },
        { id:4, firstName:'Ahmed',  lastName:'Hassan',  phone:'(404) 555-0004', license:'DL004' }
    ],
    invoices: [
        {
            id:1, companyId:1, driverId:1, date:'2026-03-01',
            lineItems:[
                { jobNumber:'J001', customerName:'TUSTIN',   from:'Atlanta, GA',  to:'Miami, FL',   cubicFeet:200,  rate:2.50, balanceDue:100, newBalance:0,   remarks:'' },
                { jobNumber:'J002', customerName:'Sara',     from:'Norcross, GA', to:'Tampa, FL',   cubicFeet:3200, rate:2.00, balanceDue:500, newBalance:200, remarks:'Paid partial' }
            ],
            subtotal:6900, carrierFee:690, total:7590
        },
        {
            id:2, companyId:2, driverId:3, date:'2026-03-03',
            lineItems:[
                { jobNumber:'P001', customerName:'Williams', from:'Norcross, GA', to:'Dallas, TX',  cubicFeet:1500, rate:0.55, balanceDue:300, newBalance:150, remarks:'Fragile items' },
                { jobNumber:'P002', customerName:'Johnson',  from:'Atlanta, GA',  to:'Houston, TX', cubicFeet:2100, rate:0.60, balanceDue:450, newBalance:0,   remarks:'' }
            ],
            subtotal:2085, carrierFee:208.5, total:2293.5
        }
    ],
    companyInvoices: [
        {
            id:1, companyId:1, date:'2026-03-01',
            lineItems:[
                { jobNumber:'CI001', driverId:1, customerName:'TUSTIN', from:'Atlanta, GA',  to:'Miami, FL',  cubicFeet:200,  rate:2.50, balanceDue:100, newBalance:0,   remarks:'' },
                { jobNumber:'CI002', driverId:2, customerName:'Sara',   from:'Norcross, GA', to:'Tampa, FL',  cubicFeet:3200, rate:2.00, balanceDue:500, newBalance:200, remarks:'Paid partial' }
            ],
            subtotal:6900, carrierFee:690, total:7590
        }
    ],
    driverInvoices: [
        {
            id:1, driverId:1, date:'2026-03-02',
            lineItems:[
                { jobNumber:'DI001', companyId:1, customerName:'Carter', from:'Marietta, GA', to:'Nashville, TN', cubicFeet:600, rate:1.50, balanceDue:200, newBalance:0, remarks:'' },
                { jobNumber:'DI002', companyId:2, customerName:'Rivera', from:'Atlanta, GA',  to:'Charlotte, NC', cubicFeet:950, rate:1.75, balanceDue:0,   newBalance:0, remarks:'Fully paid' }
            ],
            subtotal:2562.5, carrierFee:256.25, total:2818.75
        }
    ],
    nextCompanyId:3, nextDriverId:5, nextInvoiceId:3, nextCoInvId:2, nextDrInvId:2
};

// ── Storage ──────────────────────────────────

function save() {
    try {
        localStorage.setItem('ims_data', JSON.stringify({
            companies, drivers, invoices, companyInvoices, driverInvoices,
            nextCompanyId, nextDriverId, nextInvoiceId, nextCoInvId, nextDrInvId
        }));
    } catch(e) {}
}

function load() {
    try {
        const raw = localStorage.getItem('ims_data');
        if (!raw) return false;
        const d = JSON.parse(raw);
        companies       = d.companies       || [];
        drivers         = d.drivers         || [];
        invoices        = d.invoices        || [];
        companyInvoices = d.companyInvoices || [];
        driverInvoices  = d.driverInvoices  || [];
        nextCompanyId   = d.nextCompanyId   || 1;
        nextDriverId    = d.nextDriverId    || 1;
        nextInvoiceId   = d.nextInvoiceId   || 1;
        nextCoInvId     = d.nextCoInvId     || 1;
        nextDrInvId     = d.nextDrInvId     || 1;
        return true;
    } catch(e) { return false; }
}

// Silently load sample data (no confirm, used on first visit)
function loadDefaults() {
    companies       = JSON.parse(JSON.stringify(SAMPLE.companies));
    drivers         = JSON.parse(JSON.stringify(SAMPLE.drivers));
    invoices        = JSON.parse(JSON.stringify(SAMPLE.invoices));
    companyInvoices = JSON.parse(JSON.stringify(SAMPLE.companyInvoices));
    driverInvoices  = JSON.parse(JSON.stringify(SAMPLE.driverInvoices));
    nextCompanyId   = SAMPLE.nextCompanyId;
    nextDriverId    = SAMPLE.nextDriverId;
    nextInvoiceId   = SAMPLE.nextInvoiceId;
    nextCoInvId     = SAMPLE.nextCoInvId;
    nextDrInvId     = SAMPLE.nextDrInvId;
    save();
}

// Header button: replace with sample data (with confirm)
function loadSampleData() {
    if ((companies.length || drivers.length || invoices.length) &&
        !confirm('This will replace all current data with sample data. Continue?')) return;
    loadDefaults();
    if (typeof renderPage === 'function') renderPage();
    toast('Sample data loaded!', 'success');
}

// Header button: wipe everything
function clearAllData() {
    if (!confirm('Delete ALL data? This cannot be undone.')) return;
    companies = []; drivers = []; invoices = []; companyInvoices = []; driverInvoices = [];
    nextCompanyId = nextDriverId = nextInvoiceId = nextCoInvId = nextDrInvId = 1;
    save();
    if (typeof renderPage === 'function') renderPage();
    toast('All data cleared.', 'success');
}
