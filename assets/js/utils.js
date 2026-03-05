// ═══════════════════════════════════════════
// SHARED UTILITIES
// ═══════════════════════════════════════════

function toast(msg, type) {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.className = `toast show ${type}`;
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('show'), 3000);
}

function esc(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Close any modal when clicking its backdrop
document.addEventListener('click', e => {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});

// Print a formatted invoice HTML string via the dedicated #printArea
function triggerPrint(html) {
    const area = document.getElementById('printArea');
    if (!area) { window.print(); return; }
    area.innerHTML = html;
    window.print();
    setTimeout(() => { area.innerHTML = ''; }, 800);
}

// Download invoice as PDF using html2pdf.js (client-side, no server needed)
function downloadPdf(html, filename) {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html;
    wrapper.style.cssText = 'background:#fff;color:#111;font-family:Arial,sans-serif;padding:20px;';

    const opt = {
        margin:      [10, 10, 10, 10],
        filename:    (filename || 'invoice') + '.pdf',
        image:       { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, logging: false },
        jsPDF:       { unit: 'mm', format: 'a4', orientation: 'landscape' }
    };

    if (typeof html2pdf === 'undefined') {
        toast('PDF library not loaded. Please check your connection.', 'error');
        return;
    }
    html2pdf().set(opt).from(wrapper).save();
}

// ── Pagination helper ─────────────────────────
// Renders pagination controls into containerId.
// Calls onChange(newPage) when a page button is clicked.
function renderPagination(containerId, total, current, pageSize, onChange) {
    const el = document.getElementById(containerId);
    if (!el) return;
    const totalPages = Math.ceil(total / pageSize);
    if (totalPages <= 1) { el.innerHTML = ''; return; }

    window._pgnCb = onChange;

    const start = (current - 1) * pageSize + 1;
    const end   = Math.min(current * pageSize, total);

    // Build page number list with ellipsis
    const pages = [];
    const delta = 2;
    const left  = Math.max(2, current - delta);
    const right = Math.min(totalPages - 1, current + delta);

    pages.push(1);
    if (left > 2) pages.push('…');
    for (let i = left; i <= right; i++) pages.push(i);
    if (right < totalPages - 1) pages.push('…');
    if (totalPages > 1) pages.push(totalPages);

    const pageBtn = p => p === '…'
        ? `<span class="pgn-dots">…</span>`
        : `<button class="pgn-btn${p === current ? ' pgn-active' : ''}" onclick="window._pgnCb(${p})">${p}</button>`;

    el.innerHTML = `
        <div class="pgn-info">Showing ${start}–${end} of ${total} entries</div>
        <div class="pgn-btns">
            <button class="pgn-btn" ${current === 1 ? 'disabled' : ''} onclick="window._pgnCb(${current - 1})">← Prev</button>
            ${pages.map(pageBtn).join('')}
            <button class="pgn-btn" ${current === totalPages ? 'disabled' : ''} onclick="window._pgnCb(${current + 1})">Next →</button>
        </div>`;
}
