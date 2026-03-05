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
