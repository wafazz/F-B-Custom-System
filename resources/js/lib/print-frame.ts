/**
 * Print arbitrary HTML reliably on Android Chrome (incl. SUNMI tablets).
 *
 * Why not an iframe? `iframe.contentWindow.print()` is supposed to print
 * only the iframe's document, but Android Chrome — including the
 * Chromium build on SUNMI's stock browser — escalates the print intent
 * to the parent window regardless. Result: the POS queue page comes out
 * of the printer instead of the receipt / labels.
 *
 * Instead, we render the receipt body into the PARENT document and use
 * `@media print` to hide everything else. `window.print()` then targets
 * the only visible element — the receipt — and the queue page is
 * suppressed by CSS. Works in every browser, no iframe quirks.
 */
export function browserPrintHtml(html: string): void {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const bodyHTML = doc.body.innerHTML;
    const rawStyles = Array.from(doc.head.querySelectorAll('style'))
        .map((s) => s.textContent ?? '')
        .join('\n');

    // Re-scope "body" / "html,body" rules to our #sc-print-area so the
    // receipt's body-level dimensions (e.g. `body { width: 58mm }`) don't
    // clobber the parent POS body during print.
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
        #${AREA_ID} { display: none; }
        @media print {
            body > *:not(#${AREA_ID}) { display: none !important; }
            #${AREA_ID} { display: block !important; }
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

    // Defer one tick so the injected DOM is committed before the print
    // engine snapshots the page.
    window.setTimeout(() => window.print(), 50);
}
