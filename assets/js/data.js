// ═══════════════════════════════════════════
// SHARED DATA LAYER  (MySQL via PHP API)
// ═══════════════════════════════════════════

let companies       = [];
let drivers         = [];
let companyInvoices = [];
let driverInvoices  = [];

// ── Low-level fetch helper ────────────────────
// Sends a request to the API and returns parsed JSON.
// Shows a toast and throws on network/server errors.
async function api(endpoint, method = 'GET', body = null, id = null) {
    const url = 'api/' + endpoint + '.php' + (id ? '?id=' + id : '');
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body !== null) opts.body = JSON.stringify(body);

    let res;
    try {
        res = await fetch(url, opts);
    } catch (e) {
        toast('Network error — is the server running?', 'error');
        throw e;
    }

    const json = await res.json().catch(() => ({}));
    if (!res.ok) {
        const msg = json.error || ('Server error ' + res.status);
        toast(msg, 'error');
        throw new Error(msg);
    }
    return json;
}

// ── Load all data from the database ──────────
// Call this once on DOMContentLoaded on every page.
async function loadFromDB() {
    const [c, d, ci, di] = await Promise.all([
        api('companies'),
        api('drivers'),
        api('inv-company'),
        api('inv-driver'),
    ]);
    companies       = c;
    drivers         = d;
    companyInvoices = ci;
    driverInvoices  = di;
}

// ── Header button: Load sample data ──────────
async function loadSampleData() {
    if ((companies.length || drivers.length || companyInvoices.length || driverInvoices.length) &&
        !confirm('This will replace all current data with sample data. Continue?')) return;

    try {
        await api('seed', 'POST');
        await loadFromDB();
        if (typeof renderPage === 'function') renderPage();
        toast('Sample data loaded!', 'success');
    } catch (_) { /* error already shown by api() */ }
}

// ── Header button: Clear all data ────────────
async function clearAllData() {
    if (!confirm('Delete ALL data? This cannot be undone.')) return;

    try {
        await api('clear', 'POST');
        companies = []; drivers = []; companyInvoices = []; driverInvoices = [];
        if (typeof renderPage === 'function') renderPage();
        toast('All data cleared.', 'success');
    } catch (_) { /* error already shown by api() */ }
}
