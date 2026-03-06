#!/usr/bin/env node
// Reads a complete HTML string from stdin.
// Renders it with Playwright headless Chromium.
// Writes the PDF bytes to stdout.
// Usage: echo "<html>...</html>" | node generate-pdf.js

const { chromium } = require('/opt/node22/lib/node_modules/playwright');

async function main() {
    let html = '';
    for await (const chunk of process.stdin) html += chunk;

    const browser = await chromium.launch({ args: ['--no-sandbox', '--disable-setuid-sandbox'] });
    const page    = await browser.newPage();

    // Load the HTML directly — no network request needed
    await page.setContent(html, { waitUntil: 'networkidle' });

    const pdf = await page.pdf({
        format:              'A4',
        landscape:           true,
        printBackground:     true,
        margin: { top: '12mm', right: '10mm', bottom: '12mm', left: '10mm' },
        displayHeaderFooter: false,   // no URL, no page numbers in header/footer
    });

    await browser.close();

    process.stdout.write(pdf);
}

main().catch(err => {
    process.stderr.write('PDF generation error: ' + err.message + '\n');
    process.exit(1);
});
