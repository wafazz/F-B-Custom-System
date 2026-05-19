# Star Coffee Print Runner

Runs at each branch on any always-on box on the same LAN as the WiFi printer
(Raspberry Pi, spare Android via Termux, mini PC). Subscribes to its branch's
Reverb channel and forwards receipts to the thermal printer over TCP:9100.

## Setup

```bash
cp .env.example .env
# edit .env with the branch id, printer IP, and Reverb app key
npm install
npm start
```

Keep it running with `pm2`, `systemd`, or `screen` — whatever your branch box uses.

## Server prerequisites

On the Laravel side, broadcasting must be wired to Reverb (not `log`):

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_APP_ID=...
REVERB_HOST=starcoffee.my
REVERB_PORT=443
REVERB_SCHEME=https
```

`php artisan reverb:start` (or a supervisor process) must be running.

## Tested printer

- Zywell ZY905-K4-W (80mm, generic ESC/POS over WiFi)
- Any other ESC/POS WiFi printer should work — adjust `PRINTER_WIDTH` (32 for 58mm paper, 48 for 80mm).
