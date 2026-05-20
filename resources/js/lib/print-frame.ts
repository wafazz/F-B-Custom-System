/**
 * Print arbitrary HTML reliably on Android Chrome (incl. SUNMI tablets).
 *
 * Iframes don't work: `iframe.contentWindow.print()` is supposed to
 * print only the iframe's document, but Android Chrome — including the
 * Chromium build on SUNMI's stock browser — escalates the print intent
 * to the parent window. The POS queue page came out of the printer.
 *
 * Instead, we render the receipt body into the PARENT document, then
 * use the classic `visibility: hidden` print trick to hide everything
 * except our print zone. visibility:hidden is more reliable than
 * display:none in print engines because elements still occupy layout —
 * Chrome won't reflow the page mid-print and fall back to showing the
 * original DOM. We pair it with position:absolute on the print area
 * so the receipt occupies the page from top-left.
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

    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = `
        #${AREA_ID} {
            position: absolute;
            left: -10000px;
            top: 0;
        }
        @media print {
            html, body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            body * {
                visibility: hidden !important;
            }
            #${AREA_ID},
            #${AREA_ID} * {
                visibility: visible !important;
            }
            #${AREA_ID} {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
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

    // Two rAFs guarantee the injected DOM + style have been laid out
    // before the print engine snapshots the page.
    requestAnimationFrame(() => {
        requestAnimationFrame(() => window.print());
    });
}
