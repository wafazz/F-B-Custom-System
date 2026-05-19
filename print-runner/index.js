require('dotenv').config();
const net = require('net');
const Pusher = require('pusher-js');

const BRANCH_ID = parseInt(process.env.BRANCH_ID || '0', 10);
const PRINTER_HOST = process.env.PRINTER_HOST;
const PRINTER_PORT = parseInt(process.env.PRINTER_PORT || '9100', 10);
const PRINTER_WIDTH = parseInt(process.env.PRINTER_WIDTH || '48', 10);

if (!BRANCH_ID || !PRINTER_HOST) {
    console.error('Missing BRANCH_ID or PRINTER_HOST. Copy .env.example to .env and fill it in.');
    process.exit(1);
}

// Minimal ESC/POS byte builder. Works on any standard thermal printer
// including Zywell ZY905-K4-W.
const ESC = 0x1b;
const GS = 0x1d;
const LF = 0x0a;

function buildReceipt(payload) {
    const { order, branch } = payload;
    const out = [];
    const push = (...bytes) => out.push(...bytes);
    const text = (s) => { for (const c of Buffer.from(String(s), 'utf8')) out.push(c); };
    const line = (s = '') => { text(s); push(LF); };
    const align = (mode) => push(ESC, 0x61, mode); // 0=left, 1=centre, 2=right
    const bold = (on) => push(ESC, 0x45, on ? 1 : 0);
    const sizeBig = (on) => push(GS, 0x21, on ? 0x11 : 0x00);
    const cut = () => push(GS, 0x56, 0x42, 0x00);
    const divider = () => line('-'.repeat(PRINTER_WIDTH));

    const fmtMoney = (n) => `RM ${Number(n).toFixed(2)}`;
    const pad = (left, right) => {
        const space = Math.max(1, PRINTER_WIDTH - left.length - right.length);
        return left + ' '.repeat(space) + right;
    };

    push(ESC, 0x40); // init

    align(1);
    bold(1); sizeBig(1);
    line(branch.name);
    sizeBig(0); bold(0);
    line(`Branch: ${branch.code}`);
    line('');

    align(0);
    line(pad(`Order #${order.number}`, new Date(order.created_at).toLocaleString('en-MY')));
    line(`Type: ${order.order_type}${order.dine_in_table ? ` · Table ${order.dine_in_table}` : ''}`);
    if (order.customer_snapshot?.name) line(`Customer: ${order.customer_snapshot.name}`);
    divider();

    for (const item of order.items) {
        const left = `${item.quantity} x ${item.name}`;
        line(pad(left, fmtMoney(item.line_total)));
        for (const mod of item.modifiers || []) {
            line(`   + ${mod.name}`);
        }
        if (item.notes) line(`   * ${item.notes}`);
    }
    divider();

    align(2);
    line(pad('Subtotal', fmtMoney(order.subtotal)));
    bold(1); sizeBig(1);
    line(pad('TOTAL', fmtMoney(order.total)));
    sizeBig(0); bold(0);
    line('');

    align(1);
    line('Thank you!');
    line('');
    line('');
    line('');
    cut();

    return Buffer.from(out);
}

function sendToPrinter(bytes) {
    return new Promise((resolve, reject) => {
        const socket = net.createConnection({ host: PRINTER_HOST, port: PRINTER_PORT }, () => {
            socket.write(bytes, () => socket.end());
        });
        socket.on('close', () => resolve());
        socket.on('error', reject);
        socket.setTimeout(10000, () => {
            socket.destroy(new Error('Printer connection timed out'));
        });
    });
}

const pusher = new Pusher(process.env.REVERB_APP_KEY, {
    wsHost: process.env.REVERB_HOST,
    wsPort: parseInt(process.env.REVERB_PORT || '443', 10),
    wssPort: parseInt(process.env.REVERB_PORT || '443', 10),
    forceTLS: (process.env.REVERB_SCHEME || 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    cluster: 'mt1',
    disableStats: true,
});

pusher.connection.bind('state_change', ({ current }) => {
    console.log(`[reverb] ${current}`);
});

const channel = pusher.subscribe(`branch.${BRANCH_ID}.print`);
channel.bind('receipt.print', async (payload) => {
    console.log(`[print] order #${payload.order?.number}`);
    try {
        await sendToPrinter(buildReceipt(payload));
        console.log(`[print] order #${payload.order?.number} done`);
    } catch (err) {
        console.error(`[print] failed: ${err.message}`);
    }
});

console.log(`Star Coffee print runner — branch ${BRANCH_ID} -> ${PRINTER_HOST}:${PRINTER_PORT}`);
