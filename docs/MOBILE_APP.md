# Android and iOS patient application

## Scope

`mobile/` is the single Expo/React Native application for verified Patient accounts. It targets Android and iOS with the same reviewed patient journeys and consumes only the versioned `/api/v1` contract. Staff administration remains in the web portal.

## Local setup

Requirements: Node.js 22.13 or later, npm, and a running platform API.

1. Copy `mobile/.env.example` to `mobile/.env.local`.
2. Set `EXPO_PUBLIC_API_URL` to the externally reachable API base, including `/api/v1`. A physical device cannot reach the host through `127.0.0.1`; use a trusted LAN or TLS staging address.
3. From `mobile/`, run `npm ci` and then `npm start`.
4. Use an Android emulator/device or an iOS simulator/device. Local native iOS compilation requires macOS and Xcode.

Do not put passwords, API bearer tokens, EAS credentials, signing certificates or provider secrets in any `EXPO_PUBLIC_*` value. Expo public variables are embedded in the application bundle.

## Architecture and privacy boundaries

- `expo-router` owns typed native navigation and protected routes.
- The bearer token is the only persisted session value and uses `expo-secure-store` with device-only, when-unlocked access.
- Patient resources use an in-memory TanStack Query cache; sign-out and failed session restoration clear it.
- The client converts transport/API failures into safe messages and never logs tokens, document content, message content or medical details.
- Cancellation requests carry a new UUID `client_request_id`; the server owns deduplication and all business rules.
- Documents are listed but not downloaded or cached in this release.
- Push notifications and live video follow server capability discovery and remain disabled until approved providers and device lifecycles exist.
- The app requests no camera, microphone, contacts, location or photo permissions.

See `docs/ADR-001-MOBILE-ARCHITECTURE.md` and `MOBILE_API.md` for the architecture and wire contract.

## Quality gates

Run from `mobile/`:

```text
npm run typecheck
npm test
npx expo-doctor
npx expo export --platform android
npx expo export --platform ios
```

The automated suite covers safe API errors, bearer-header behavior, secure token storage, accessible core controls and cancellation request identifiers. Release candidates also require physical-device review for large text, screen readers, poor connectivity, sign-out, deep links and background/resume behavior.

## EAS builds

`mobile/eas.json` provides:

- `development`: internal development-client build.
- `preview`: internal review build; Android produces an installable APK.
- `production`: store-signed output with remote build-number auto-increment.

After an authorised release owner links an Expo project and configures protected environment values:

```text
eas build --platform android --profile preview
eas build --platform all --profile production
eas submit --platform android --profile production
eas submit --platform ios --profile production
```

EAS project linkage, Apple/Google signing credentials and store submission are controlled external actions and are not stored in this repository.
