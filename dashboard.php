<?php
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
include 'includes/header.php';
?>

    <div class="content">
        <h2 style="margin-bottom:25px;">Dashboard Overview</h2>
        <div class="stats-grid">
            <div class="stat-card"        onclick="location.href='companies.php'"><div class="stat-value" id="statCompanies">0</div><div class="stat-label">Total Companies</div></div>
            <div class="stat-card green"  onclick="location.href='drivers.php'"  ><div class="stat-value" id="statDrivers">0</div><div class="stat-label">Total Drivers</div></div>
            <div class="stat-card purple" onclick="location.href='invoices.php'" ><div class="stat-value" id="statInvoices">0</div><div class="stat-label">Total Invoices</div></div>
            <div class="stat-card amber"                                          ><div class="stat-value" id="statRevenue">$0</div><div class="stat-label">Total Revenue</div></div>
        </div>
        <h3 style="margin-bottom:15px;">Recent Invoices</h3>
        <div id="recentActivity"></div>
    </div>

<?php include 'includes/footer.php'; ?>

<script>
function renderPage() {
    document.getElementById('statCompanies').textContent = companies.length;
    document.getElementById('statDrivers').textContent   = drivers.length;
    document.getElementById('statInvoices').textContent  = invoices.length;
    document.getElementById('statRevenue').textContent   = '$' + invoices.reduce((s, i) => s + (i.total || 0), 0).toLocaleString();

    const el = document.getElementById('recentActivity');
    if (!invoices.length) {
        el.innerHTML = '<div class="empty"><div class="empty-icon">📄</div><p>No invoices yet — go to <a href="invoices.php" style="color:#3b82f6;">Invoices</a> to create one</p></div>';
        return;
    }
    el.innerHTML = invoices.slice().reverse().slice(0, 6).map(inv => {
        const co = companies.find(c => c.id === inv.companyId);
        const dr = drivers.find(d => d.id === inv.driverId);
        const jobs = (inv.lineItems || []).length;
        const customers = [...new Set((inv.lineItems || []).map(j => j.customerName).filter(Boolean))].join(', ') || '—';
        return `
            <div class="activity-item">
                <div class="activity-title">Invoice #${inv.id} &mdash; ${co?.name || '?'} &nbsp;|&nbsp; Driver: ${dr ? dr.firstName + ' ' + dr.lastName : '?'}</div>
                <div class="activity-details">${jobs} job(s) &nbsp;|&nbsp; Customers: ${customers} &nbsp;|&nbsp; Total: $${(inv.total || 0).toFixed(2)} &nbsp;|&nbsp; ${inv.date}</div>
            </div>`;
    }).join('');
}

document.addEventListener('DOMContentLoaded', () => {
    if (!load()) loadDefaults();
    renderPage();
});
</script>
</body>
</html>
