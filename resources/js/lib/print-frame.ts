/**
 * Print arbitrary HTML via Chrome's normal print dialog.
 *
 * Earlier attempts (hidden iframe; @media print on parent; full-viewport
 * cover) all foundered on Android Chrome / SUNMI Chromium: the engine
 * either escalates print() to the parent document or rasterizes the
 * visible viewport. None of those let us reliably get a print dialog
 * showing only the receipt.
 *
 * Cleanest fix: open the receipt in a NEW TAB and let the new tab call
 * window.print() on itself. That new document is pristine — nothing
 * else in it but the receipt — so Chrome prints exactly what's there
 * and offers "Save as PDF" / system printers in the normal dialog.
 *
 * Caveat: popup blockers may block window.open() from non-gesture
 * contexts. For user-clicked buttons it works; for auto-print from a
 * useEffect the cashier may need to allow popups for starcoffee.my.
 */
export function browserPrintHtml(html: string): void {
    // Inject a tiny script into the receipt doc that auto-prints on
    // load and then closes the tab when the dialog dismisses.
    const augmented = html.replace(
        '</body>',
        `<script>
            window.addEventListener('load', function () {
                setTimeout(function () {
                    window.print();
                    setTimeout(function () { window.close(); }, 1500);
                }, 100);
            });
        </script></body>`,
    );

    const win = window.open('', '_blank');
    if (!win) {
        console.warn('print: popup blocked; enable popups for this site in Chrome settings');
        return;
    }
    win.document.open();
    win.document.write(augmented);
    win.document.close();
}
