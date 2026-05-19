# Star Coffee SUNMI Print Bridge

A tiny native Android service that lets the Star Coffee POS PWA print to the
SUNMI device's built-in thermal printer.

## How it works

Runs as a foreground service on the SUNMI tablet:

1. Binds to SUNMI's `IWoyouService` (the built-in printer service).
2. Listens on `http://127.0.0.1:8765` (loopback only, never on the LAN).
3. Accepts `POST /print` with a JSON receipt payload from the PWA.
4. Forwards the receipt to the built-in printer via the SUNMI AIDL service.

The PWA at `starcoffee.my/pos` probes `http://127.0.0.1:8765/ping` on the
device. If it answers, the PWA prints through the bridge. Otherwise it falls
back to the Reverb → WiFi printer path used at branches without SUNMI hardware.

## Endpoints

- `GET  /ping`  → `{"ok":true,"device":"sunmi"}`
- `POST /print` → JSON body in the same shape as the `PrintReceiptRequested`
  broadcast payload (`{order: {...}, branch: {...}}`)

## Building

The APK is produced by the `.github/workflows/build-sunmi-bridge.yml` CI job
on every push to `main` that touches this folder. Grab the APK from the run's
Artifacts tab and sideload it onto the SUNMI device.

## Running

After installing, open the app once to start the foreground service. The
notification will say "Listening on 127.0.0.1:8765". Leave the app installed —
the service auto-restarts on boot.
