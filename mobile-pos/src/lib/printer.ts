import AsyncStorage from '@react-native-async-storage/async-storage';
import RNBluetoothClassic, { BluetoothDevice } from 'react-native-bluetooth-classic';
import { Buffer } from 'buffer';

const PRINTER_KEY = 'pos.printer';

// ESC/POS commands
const ESC = 0x1b;
const GS = 0x1d;
const LF = 0x0a;
const INIT = Buffer.from([ESC, 0x40]);
const ALIGN_CENTRE = Buffer.from([ESC, 0x61, 0x01]);
const ALIGN_LEFT = Buffer.from([ESC, 0x61, 0x00]);
const TXT_DOUBLE = Buffer.from([GS, 0x21, 0x11]);
const TXT_NORMAL = Buffer.from([GS, 0x21, 0x00]);
const CUT = Buffer.from([GS, 0x56, 0x42, 0x00]);
const FEED_3 = Buffer.from([LF, LF, LF]);

export interface PairedDevice {
    name: string;
    address: string;
}

export async function listPairedDevices(): Promise<PairedDevice[]> {
    const enabled = await RNBluetoothClassic.isBluetoothEnabled();
    if (!enabled) {
        throw new Error('Bluetooth is off — turn it on in Android settings.');
    }
    const devices = await RNBluetoothClassic.getBondedDevices();
    return devices.map((d: BluetoothDevice) => ({
        name: d.name ?? '(unnamed)',
        address: d.address,
    }));
}

export async function selectPrinter(address: string) {
    await AsyncStorage.setItem(PRINTER_KEY, address);
}

export async function getSelectedPrinter(): Promise<string | null> {
    return AsyncStorage.getItem(PRINTER_KEY);
}

async function connect(): Promise<BluetoothDevice> {
    const address = await getSelectedPrinter();
    if (!address) {
        throw new Error('No printer selected. Pick one in Settings.');
    }
    let device = await RNBluetoothClassic.getConnectedDevice(address).catch(() => null);
    if (device) return device;

    device = await RNBluetoothClassic.connectToDevice(address, {
        delimiter: '\n',
    });
    return device;
}

/**
 * Build + send an ESC/POS kitchen ticket for an order.
 */
export async function printKitchenTicket(opts: {
    branchName: string;
    orderNumber: string;
    orderType: string;
    table?: string | null;
    items: { name: string; quantity: number; modifiers: string[]; notes: string | null }[];
}): Promise<void> {
    const lines: Buffer[] = [];
    lines.push(INIT);

    lines.push(ALIGN_CENTRE);
    lines.push(TXT_DOUBLE);
    lines.push(Buffer.from(`${opts.branchName}\n`, 'utf8'));
    lines.push(TXT_NORMAL);
    lines.push(Buffer.from(`Order ${opts.orderNumber}\n`, 'utf8'));
    lines.push(Buffer.from('--------------------------------\n', 'ascii'));

    lines.push(ALIGN_LEFT);
    const typeLine = opts.table
        ? `${opts.orderType.toUpperCase()} · Table ${opts.table}\n\n`
        : `${opts.orderType.toUpperCase()}\n\n`;
    lines.push(Buffer.from(typeLine, 'utf8'));

    for (const item of opts.items) {
        lines.push(TXT_DOUBLE);
        lines.push(Buffer.from(`${item.quantity}x ${item.name}\n`, 'utf8'));
        lines.push(TXT_NORMAL);
        for (const m of item.modifiers) {
            lines.push(Buffer.from(`  + ${m}\n`, 'utf8'));
        }
        if (item.notes) {
            lines.push(Buffer.from(`  ! ${item.notes}\n`, 'utf8'));
        }
        lines.push(Buffer.from('\n', 'ascii'));
    }

    lines.push(FEED_3);
    lines.push(CUT);

    const payload = Buffer.concat(lines);
    const device = await connect();
    await device.write(payload.toString('base64'), 'base64');
}

/** Plain text print used for tests / receipts. */
export async function printText(text: string, cut = true): Promise<void> {
    const lines: Buffer[] = [INIT, Buffer.from(text, 'utf8'), FEED_3];
    if (cut) lines.push(CUT);
    const device = await connect();
    await device.write(Buffer.concat(lines).toString('base64'), 'base64');
}
