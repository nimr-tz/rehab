# Rehab Summit Staff app

An Expo app for ushers and registration staff. It does one job: scan participant badges to record attendance at the conference entrance and at each session door. The portal uses session scans for CPD.

Only accounts with the `registration_officer` or `admin` role can sign in.

## Running

```bash
npm install
EXPO_PUBLIC_WEB_BASE_URL=http://<your-lan-ip>:8000 npx expo start
```

Use `npx expo start --tunnel` when the phone is not on the same network.

## Building an APK

Set `EXPO_PUBLIC_WEB_BASE_URL` for the build profile in `eas.json`, then:

```bash
eas build --platform android --profile preview
```

`app.json` has no EAS project ID yet. Run `eas init` once to link the app to the organisation's Expo account. The bundle ID `tz.or.rehabhealth.summitstaff` is a placeholder until it is confirmed.

## Tests

```bash
npx jest
```
