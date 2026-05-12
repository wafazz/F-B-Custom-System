interface LabelOrder {
    id: number;
    number: string;
    order_type: 'pickup' | 'dine_in';
    dine_in_table: string | null;
    notes: string | null;
    created_at: string | null;
    items: {
        id: number;
        name: string;
        quantity: number;
        modifiers: { group_name: string; option_name: string }[];
        notes: string | null;
    }[];
}

interface PrintOptions {
    copies?: number;
    size?: '58mm' | '80mm';
    branchName?: string;
}

/**
 * Renders one label per item-unit (e.g. 2 lattes → 2 labels) and prints them
 * via a hidden iframe using the OS's default printer.
 */
export function printOrderLabels(order: LabelOrder, opts: PrintOptions = {}) {
    const copies = Math.max(1, opts.copies ?? 1);
    const size = opts.size ?? '58mm';
    const html = renderLabelsHtml(order, copies, size, opts.branchName ?? '');

    const iframe = document.createElement('iframe');
    iframe.setAttribute('aria-hidden', 'true');
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    document.body.appendChild(iframe);

    const doc = iframe.contentDocument;
    if (!doc) {
        document.body.removeChild(iframe);
        return;
    }
    doc.open();
    doc.write(html);
    doc.close();

    iframe.onload = () => {
        try {
            iframe.contentWindow?.focus();
            iframe.contentWindow?.print();
        } finally {
            window.setTimeout(() => {
                if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
            }, 1000);
        }
    };
}

function renderLabelsHtml(order: LabelOrder, copies: number, size: string, branchName: string): string {
    const widthMm = size === '80mm' ? 80 : 58;
    const labels: string[] = [];
    const time = order.created_at
        ? new Date(order.created_at).toLocaleTimeString('en-MY', {
              hour: '2-digit',
              minute: '2-digit',
          })
        : '';
    const where = order.order_type === 'dine_in' ? `Table ${order.dine_in_table ?? '—'}` : 'PICKUP';

    let seq = 0;
    const totalUnits = order.items.reduce((sum, i) => sum + i.quantity, 0) * copies;

    for (const item of order.items) {
        for (let q = 0; q < item.quantity; q++) {
            for (let c = 0; c < copies; c++) {
                seq++;
                const mods = item.modifiers.map((m) => escapeHtml(m.option_name)).join(' · ');
                labels.push(`
                    <article class="label">
                        <div class="num">${escapeHtml(order.number)}</div>
                        <div class="sub">${escapeHtml(where)} · ${escapeHtml(time)} · #${seq}/${totalUnits}</div>
                        <div class="item-name">${escapeHtml(item.name)}</div>
                        ${mods ? `<div class="mod">${mods}</div>` : ''}
                        ${item.notes ? `<div class="note">⚠ ${escapeHtml(item.notes)}</div>` : ''}
                        ${order.notes ? `<div class="order-note">${escapeHtml(order.notes)}</div>` : ''}
                        <div class="footer">${escapeHtml(branchName)}</div>
                    </article>
                `);
            }
        }
    }

    return `<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title>Labels — ${escapeHtml(order.number)}</title>
<style>
  @page { size: ${widthMm}mm auto; margin: 0; }
  html, body { margin: 0; padding: 0; background: #fff; color: #000; font-family: 'Helvetica Neue', Arial, sans-serif; }
  body { width: ${widthMm}mm; }
  .label { padding: 3mm 3mm 4mm 3mm; page-break-after: always; }
  .label:last-child { page-break-after: auto; }
  .num { font-size: 22pt; font-weight: 900; text-align: center; letter-spacing: 0.5px; line-height: 1; margin-bottom: 1mm; }
  .sub { font-size: 8pt; color: #444; text-align: center; margin-bottom: 2mm; border-bottom: 1px dashed #888; padding-bottom: 2mm; }
  .item-name { font-size: 13pt; font-weight: 800; line-height: 1.15; margin-top: 1mm; }
  .mod { font-size: 9pt; color: #333; line-height: 1.2; margin-top: 1mm; }
  .note { font-size: 9pt; font-weight: 700; margin-top: 2mm; padding: 1mm 1.5mm; border: 1.5px solid #000; }
  .order-note { font-size: 8pt; color: #333; margin-top: 2mm; font-style: italic; }
  .footer { font-size: 7pt; color: #777; text-align: center; margin-top: 3mm; }
</style>
</head>
<body>
${labels.join('')}
</body>
</html>`;
}

function escapeHtml(s: string): string {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
