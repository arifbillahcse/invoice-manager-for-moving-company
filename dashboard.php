<?php
require 'includes/auth.php';
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
include 'includes/header.php';
?>

    <div class="content">
        <h2 style="margin-bottom:25px;">Dashboard Overview</h2>
        <div class="stats-grid">
            <div class="stat-card"        onclick="location.href='companies.php'"><div class="stat-value" id="statCompanies">0</div><div class="stat-label">Total Companies</div></div>
            <div class="stat-card green"  onclick="location.href='drivers.php'"  ><div class="stat-value" id="statDrivers">0</div><div class="stat-label">Total Drivers</div></div>
            <div class="stat-card purple"><div class="stat-value" id="statInvoices">0</div><div class="stat-label">Total Invoices</div></div>
            <div class="stat-card amber"                                          ><div class="stat-value" id="statRevenue">$0</div><div class="stat-label">Total Revenue</div></div>
        </div>
        <h3 style="margin-bottom:15px;">Recent Invoices</h3>
        <div id="recentActivity"></div>
    </div>

<?php include 'includes/footer.php'; ?>

<script>
function renderPage() {
    const allInvoices = [
        ...companyInvoices.map(i => ({ ...i, _type: 'CI' })),
        ...driverInvoices.map(i  => ({ ...i, _type: 'DI' })),
    ].sort((a, b) => b.id - a.id);

    const totalRevenue = allInvoices.reduce((s, i) => s + (i.total || 0), 0);

    document.getElementById('statCompanies').textContent = companies.length;
    document.getElementById('statDrivers').textContent   = drivers.length;
    document.getElementById('statInvoices').textContent  = allInvoices.length;
    document.getElementById('statRevenue').textContent   = '$' + totalRevenue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const el = document.getElementById('recentActivity');
    if (!allInvoices.length) {
        el.innerHTML = '<div class="empty"><div class="empty-icon">📄</div><p>No invoices yet — use Invoice / Company or Invoice / Driver to create one.</p></div>';
        return;
    }

    el.innerHTML = allInvoices.slice(0, 6).map(inv => {
        const jobs      = (inv.lineItems || []).length;
        const customers = [...new Set((inv.lineItems || []).map(j => j.customerName).filter(Boolean))].join(', ') || '—';
        let title;
        if (inv._type === 'CI') {
            const co = companies.find(c => c.id === inv.companyId);
            title = `CI-${inv.id} &mdash; Company: ${co?.name || '?'}`;
        } else {
            const dr = drivers.find(d => d.id === inv.driverId);
            title = `DI-${inv.id} &mdash; Driver: ${dr ? dr.firstName + ' ' + dr.lastName : '?'}`;
        }
        return `
            <div class="activity-item">
                <div class="activity-title">${title}</div>
                <div class="activity-details">${jobs} job(s) &nbsp;|&nbsp; Customers: ${customers} &nbsp;|&nbsp; Total: $${(inv.total || 0).toFixed(2)} &nbsp;|&nbsp; ${inv.date}</div>
            </div>`;
    }).join('');
}

document.addEventListener('DOMContentLoaded', async () => {
    await loadFromDB();
    renderPage();
});
</script>
</body>
</html>
