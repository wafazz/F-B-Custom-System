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
    // Inject a tiny script that auto-prints on load and closes the tab
    // only AFTER the print dialog is dismissed. window.print() on
    // Android Chrome doesn't block — it returns immediately — so we
    // can't close on a fixed timeout (the tab would die before the
    // dialog finishes painting). afterprint fires when the user
    // dismisses the dialog (Print, Save as PDF, or Cancel). A 60 s
    // safety net catches the case where afterprint never fires (some
    // Android builds).
    const augmented = html.replace(
        '</body>',
        `<script>
            (function () {
                var closed = false;
                function safeClose() {
                    if (closed) return;
                    closed = true;
                    window.close();
                }
                window.addEventListener('afterprint', safeClose);
                window.addEventListener('load', function () {
                    setTimeout(function () { window.print(); }, 150);
                });
                setTimeout(safeClose, 60000);
            })();
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
