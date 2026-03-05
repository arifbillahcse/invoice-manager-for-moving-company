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
