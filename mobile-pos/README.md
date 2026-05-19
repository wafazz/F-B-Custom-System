# Star Coffee POS — Android app

Native Android POS that talks to the existing Laravel API and prints to
a Bluetooth ESC/POS thermal printer.

Built with **React Native 0.74 (CLI)** and compiled by **GitHub
Actions** so you never need Android Studio on your Mac.

## How to get a fresh APK

Three paths — pick whichever fits.

### 1. Push to `main` (or change anything under `mobile-pos/`)

GitHub Actions runs `build-android-pos.yml`, builds the APK, attaches
it as an artifact named `star-coffee-pos-apk`. Find it under your
repo's **Actions** tab → latest run → Artifacts panel.

### 2. Cut a versioned release

```bash
git tag pos-v1.0.0
git push origin pos-v1.0.0
```

The workflow builds + automatically creates a **GitHub Release**
named `pos-v1.0.0` with the APK attached. Share that release URL with
staff — they tap the APK link on the tablet's browser to install.

### 3. Manual run

Repo's **Actions** tab → "Build Android POS APK" → **Run workflow**.
Useful when you've changed nothing under `mobile-pos/` but want a
fresh build (e.g. printer plugin version bump).

## What's in this folder

```
mobile-pos/
├── App.tsx                 # root navigator
├── tsconfig.json
├── src/
│   ├── config.ts          # API_BASE — edit this before each build
│   ├── api/pos.ts         # Sanctum-token API wrapper
│   ├── lib/printer.ts     # Bluetooth-classic ESC/POS bridge
│   ├── navigation/types.ts
│   └── screens/
│       ├── LoginScreen.tsx    # PIN sign-in
│       ├── QueueScreen.tsx    # live order queue
│       └── SettingsScreen.tsx # printer picker + test print
```

The CI workflow does the rest at build time:
- Generates a fresh RN 0.74 project skeleton
- Copies the files above on top
- Installs deps (`react-native-bluetooth-classic`, navigation, etc.)
- Patches `AndroidManifest.xml` with Bluetooth permissions
- Compiles `app-release.apk`

## Configure the API base URL

Edit `src/config.ts` before pushing:

```ts
export const API_BASE = 'https://order.starcoffee.my';
```

## Tablet-side setup (one-time per device)

1. Pair the Bluetooth thermal printer in Android Settings → Bluetooth.
2. Download the APK from your repo's Releases or Actions artifact.
3. Open it on the tablet → allow install from unknown sources.
4. Launch **Star Coffee POS** → enter branch code + staff PIN.
5. Top-right **Settings** → pick the paired printer → **Send test
   print**.
6. You're done. From now on the Queue screen lists live orders, you
   tap **Print ticket** for the kitchen and **Mark ready** as you go.

## Production signing (recommended)

The CI workflow ships APKs signed with the **debug keystore**, which
Android trusts for sideloading but isn't suitable for the Play Store.
When you're ready for a release keystore:

1. Generate a keystore on any machine:
   ```bash
   keytool -genkey -v -keystore star-coffee-pos.keystore \
     -alias star-coffee -keyalg RSA -keysize 2048 -validity 10000
   ```
2. In the GitHub repo → Settings → Secrets and variables → Actions,
   add:
   - `ANDROID_KEYSTORE_B64` — base64 of the keystore file
   - `ANDROID_KEYSTORE_PASSWORD`
   - `ANDROID_KEY_ALIAS`
   - `ANDROID_KEY_PASSWORD`
3. Ping me to wire those into the workflow's `assembleRelease` step.

## Backend contract

The app hits four endpoints under `/api/pos/*` on your Laravel API:

| Method | Path                                      | Purpose                     |
|--------|-------------------------------------------|-----------------------------|
| POST   | `/api/pos/login`                          | branch + PIN → Sanctum token |
| POST   | `/api/pos/logout`                         | revoke current token        |
| GET    | `/api/pos/branches/{branch}/queue`        | list of active orders       |
| POST   | `/api/pos/orders/{order}/transition`      | advance / cancel an order   |

Implemented in `app/Http/Controllers/Api/PosApiController.php`.
