export interface ReceiptOrder {
    number: string;
    order_type: 'pickup' | 'dine_in';
    dine_in_table: string | null;
    created_at: string | null;
    paid_at: string | null;
    payment_method: string | null;
    payment_reference: string | null;
    subtotal: number;
    sst_amount: number;
    discount_amount: number;
    total: number;
    customer_name: string | null;
    points_earned: number | null;
    items: {
        name: string;
        quantity: number;
        unit_price: number;
        line_total: number;
        modifiers: { option_name: string }[];
    }[];
}

interface ReceiptBranch {
    name: string;
    address?: string | null;
    receipt_header?: string | null;
    receipt_footer?: string | null;
    sst_rate?: number;
}

interface PrintOptions {
    size?: '58mm' | '80mm';
}

export function printOrderReceipt(
    order: ReceiptOrder,
    branch: ReceiptBranch,
    opts: PrintOptions = {},
) {
    const size = opts.size ?? '58mm';
    const html = renderReceiptHtml(order, branch, size);

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

function renderReceiptHtml(order: ReceiptOrder, branch: ReceiptBranch, size: string): string {
    const widthMm = size === '80mm' ? 80 : 58;
    const dt = order.paid_at ?? order.created_at;
    const when = dt
        ? new Date(dt).toLocaleString('en-MY', {
              day: '2-digit',
              month: 'short',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : '';
    const where = order.order_type === 'dine_in' ? `Dine-in · Table ${order.dine_in_table ?? '—'}` : 'Pickup';

    const itemRows = order.items
        .map((item) => {
            const mods = item.modifiers.map((m) => escapeHtml(m.option_name)).join(' · ');
            return `
                <tr>
                    <td class="qty">${item.quantity}×</td>
                    <td class="name">
                        ${escapeHtml(item.name)}
                        ${mods ? `<div class="mods">${mods}</div>` : ''}
                    </td>
                    <td class="line-total">${money(item.line_total)}</td>
                </tr>
            `;
        })
        .join('');

    const sstRow = order.sst_amount > 0
        ? `<tr><td>SST${branch.sst_rate ? ` ${branch.sst_rate.toFixed(0)}%` : ''}</td><td class="r">${money(order.sst_amount)}</td></tr>`
        : '';
    const discountRow = order.discount_amount > 0
        ? `<tr><td>Discount</td><td class="r">−${money(order.discount_amount)}</td></tr>`
        : '';

    return `<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title>Receipt — ${escapeHtml(order.number)}</title>
<style>
  @page { size: ${widthMm}mm auto; margin: 0; }
  html, body { margin: 0; padding: 0; background: #fff; color: #000; font-family: 'Helvetica Neue', Arial, sans-serif; }
  body { width: ${widthMm}mm; padding: 3mm 3mm 6mm 3mm; box-sizing: border-box; }
  .center { text-align: center; }
  .r { text-align: right; }
  .brand { font-size: 14pt; font-weight: 900; letter-spacing: 0.5px; }
  .addr { font-size: 8pt; color: #333; line-height: 1.25; margin-top: 1mm; }
  .header-extra { font-size: 8pt; color: #333; margin-top: 1.5mm; line-height: 1.25; }
  .sep { border-top: 1px dashed #777; margin: 2.5mm 0; }
  .sep-solid { border-top: 1px solid #000; margin: 2.5mm 0; }
  .meta { font-size: 8.5pt; line-height: 1.35; }
  .meta strong { font-weight: 700; }
  .order-num { font-size: 16pt; font-weight: 900; text-align: center; letter-spacing: 1px; margin: 2mm 0 1mm; }
  table.items { width: 100%; border-collapse: collapse; font-size: 9pt; }
  table.items td { vertical-align: top; padding: 0.8mm 0; }
  table.items .qty { width: 8mm; font-weight: 700; }
  table.items .line-total { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
  table.items .mods { font-size: 7.5pt; color: #555; margin-top: 0.3mm; line-height: 1.2; }
  table.totals { width: 100%; border-collapse: collapse; font-size: 9pt; margin-top: 1mm; }
  table.totals td { padding: 0.5mm 0; }
  table.totals td.r { text-align: right; font-variant-numeric: tabular-nums; }
  table.totals tr.grand td { font-size: 11pt; font-weight: 900; padding-top: 1.5mm; }
  .pay { font-size: 8.5pt; margin-top: 2mm; line-height: 1.3; }
  .loyalty { font-size: 8.5pt; text-align: center; margin-top: 2mm; padding: 1.5mm; border: 1px dashed #555; }
  .footer { font-size: 8pt; text-align: center; margin-top: 4mm; color: #333; line-height: 1.3; }
  .thanks { font-size: 9.5pt; font-weight: 700; text-align: center; margin-top: 3mm; }
</style>
</head>
<body>
  <div class="center brand">${escapeHtml(branch.name)}</div>
  ${branch.address ? `<div class="center addr">${escapeHtml(branch.address)}</div>` : ''}
  ${branch.receipt_header ? `<div class="center header-extra">${escapeHtml(branch.receipt_header)}</div>` : ''}

  <div class="sep"></div>

  <div class="order-num">${escapeHtml(order.number)}</div>
  <div class="meta center">${escapeHtml(where)}</div>
  <div class="meta center">${escapeHtml(when)}</div>
  ${order.customer_name ? `<div class="meta center"><strong>Member:</strong> ${escapeHtml(order.customer_name)}</div>` : ''}

  <div class="sep"></div>

  <table class="items">
    ${itemRows}
  </table>

  <div class="sep"></div>

  <table class="totals">
    <tr><td>Subtotal</td><td class="r">${money(order.subtotal)}</td></tr>
    ${discountRow}
    ${sstRow}
    <tr class="grand"><td>TOTAL</td><td class="r">${money(order.total)}</td></tr>
  </table>

  <div class="sep-solid"></div>

  <div class="pay">
    Paid: <strong>${escapeHtml((order.payment_method ?? '—').toUpperCase())}</strong><br />
    ${order.payment_reference ? `Ref: ${escapeHtml(order.payment_reference)}` : ''}
  </div>

  ${
      order.points_earned && order.points_earned > 0
          ? `<div class="loyalty">⭐ You earned <strong>${order.points_earned}</strong> points</div>`
          : ''
  }

  <div class="thanks">Thank you, see you again!</div>
  ${branch.receipt_footer ? `<div class="footer">${escapeHtml(branch.receipt_footer)}</div>` : ''}
</body>
</html>`;
}

function money(n: number): string {
    return `RM${Number(n).toFixed(2)}`;
}

function escapeHtml(s: string): string {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
