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
 * The fix is layered:
 *
 * - An outer cover div (#sc-print-cover) goes full-viewport white over
 *   the POS UI so neither code path can see the queue page underneath.
 * - An inner area div (#sc-print-area) holds the receipt content at
 *   its native 58/80mm width. Receipt CSS that targets `body` is
 *   re-scoped here so it doesn't fight the cover's sizing.
 * - @media print collapses the cover to natural flow and lays the
 *   receipt at the top of the print page.
 *
 * Once afterprint fires (or 30 s elapses) the injected nodes are removed.
 */
export function browserPrintHtml(html: string): void {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const bodyHTML = doc.body.innerHTML;
    const rawStyles = Array.from(doc.head.querySelectorAll('style'))
        .map((s) => s.textContent ?? '')
        .join('\n');

    // Receipt CSS targets `body` (e.g. `body { width: 58mm }`) — re-scope
    // those rules to the INNER #sc-print-area so they apply to the
    // 58mm content, not the full-viewport cover.
    const scopedStyles = rawStyles
        .replace(/(^|[\s,}])html\s*,\s*body\s*\{/g, '$1#sc-print-area {')
        .replace(/(^|[\s,}])body\s*\{/g, '$1#sc-print-area {');

    const COVER_ID = 'sc-print-cover';
    const AREA_ID = 'sc-print-area';
    const STYLE_ID = 'sc-print-style';

    document.getElementById(COVER_ID)?.remove();
    document.getElementById(STYLE_ID)?.remove();

    const cover = document.createElement('div');
    cover.id = COVER_ID;

    const area = document.createElement('div');
    area.id = AREA_ID;
    area.innerHTML = bodyHTML;
    cover.appendChild(area);

    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = `
        /* Full-viewport white sheet that hides the POS UI while the
           print dialog is open — defends against rasterize-print
           engines that snapshot the visible viewport. */
        #${COVER_ID} {
            position: fixed !important;
            inset: 0 !important;
            z-index: 2147483647 !important;
            background: #fff !important;
            color: #000 !important;
            overflow: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            display: flex !important;
            justify-content: center !important;
            align-items: flex-start !important;
        }
        @media print {
            html, body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            body > *:not(#${COVER_ID}) {
                display: none !important;
            }
            #${COVER_ID} {
                position: static !important;
                display: block !important;
                background: transparent !important;
                z-index: auto !important;
                overflow: visible !important;
            }
            #${AREA_ID} {
                margin: 0 !important;
            }
        }
        ${scopedStyles}
    `;

    document.head.appendChild(style);
    document.body.appendChild(cover);

    const cleanup = () => {
        document.getElementById(COVER_ID)?.remove();
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
