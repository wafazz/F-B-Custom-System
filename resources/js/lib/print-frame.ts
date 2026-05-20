/**
 * Print arbitrary HTML reliably on Android Chrome (incl. SUNMI tablets).
 *
 * Two failure modes we have to defend against:
 *
 * 1. iframe.contentWindow.print() — Android Chrome escalates to the
 *    parent window's print intent regardless. The POS queue page comes
 *    out of the printer instead of the receipt.
 *
 * 2. @media print rules alone aren't enough — on several Android builds
 *    window.print() RASTERIZES the visible viewport instead of
 *    reflowing the document for print. The printer gets a literal
 *    screenshot of what's on screen, so display:none / visibility:hidden
 *    rules are ignored entirely.
 *
 * The fix: cover the visible viewport with the receipt while print() is
 * in flight. Whether Chrome reflows (uses @media print) or rasterizes
 * (uses the visible viewport), it only sees the receipt. Once
 * afterprint fires (or 30 s elapses) we remove the cover.
 */
export function browserPrintHtml(html: string): void {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const bodyHTML = doc.body.innerHTML;
    const rawStyles = Array.from(doc.head.querySelectorAll('style'))
        .map((s) => s.textContent ?? '')
        .join('\n');

    // Receipt CSS targets `body` (e.g. `body { width: 58mm }`) — re-scope
    // those rules to #sc-print-area so they apply to our injected node
    // instead of clobbering the host POS body during print.
    const scopedStyles = rawStyles
        .replace(/(^|[\s,}])html\s*,\s*body\s*\{/g, '$1#sc-print-area {')
        .replace(/(^|[\s,}])body\s*\{/g, '$1#sc-print-area {');

    const AREA_ID = 'sc-print-area';
    const STYLE_ID = 'sc-print-style';

    document.getElementById(AREA_ID)?.remove();
    document.getElementById(STYLE_ID)?.remove();

    const area = document.createElement('div');
    area.id = AREA_ID;
    area.innerHTML = bodyHTML;
    // Cover the visible viewport so Android Chrome's rasterize-print
    // path also sees only the receipt. Inside @media print we collapse
    // back to natural flow so reflow-print engines (desktop Chrome,
    // Safari) don't get a hard-pinned width.
    area.style.cssText = [
        'position: fixed',
        'inset: 0',
        'z-index: 2147483647',
        'background: #fff',
        'color: #000',
        'overflow: auto',
        'margin: 0',
        'padding: 0',
    ].join(';');

    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = `
        @media print {
            html, body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            body > *:not(#${AREA_ID}) {
                display: none !important;
            }
            #${AREA_ID} {
                position: static !important;
                width: 100% !important;
                height: auto !important;
                overflow: visible !important;
                z-index: auto !important;
            }
        }
        ${scopedStyles}
    `;

    document.head.appendChild(style);
    document.body.appendChild(area);

    const cleanup = () => {
        document.getElementById(AREA_ID)?.remove();
        document.getElementById(STYLE_ID)?.remove();
        window.removeEventListener('afterprint', cleanup);
    };
    window.addEventListener('afterprint', cleanup);
    // Safety net: some Android Chrome builds don't fire afterprint
    // (e.g. when the user cancels via the system back gesture).
    window.setTimeout(cleanup, 30_000);

    // Two rAFs guarantee the cover is painted before print() snapshots
    // the viewport on rasterize-print engines.
    requestAnimationFrame(() => {
        requestAnimationFrame(() => window.print());
    });
}
