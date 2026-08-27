# ADR-001: Shared React Native Application for Android and iOS

Status: Accepted — 27 August 2026

## Context

The practice needs first-class Android and iOS patient applications using the same Laravel authorization and workflow rules as the web portal. The existing team stack is React/TypeScript, most patient workflows are form/list/state driven, and camera/microphone, secure credential storage, document selection and push notifications require native capabilities.

## Decision

Build one React Native application with Expo's maintained native toolchain and TypeScript. Produce distinct Android and iOS binaries from the same source while retaining platform-specific configuration and testing. Consume `@dr-funmilola/mobile-contract` directly for API types.

Use:

- Expo Router for typed native navigation;
- TanStack Query for server state and controlled cache persistence;
- Expo SecureStore for bearer tokens—never AsyncStorage;
- Expo DocumentPicker and FileSystem for explicit patient document selection/upload;
- provider-specific native media SDK only after `VideoProviderInterface` gains an approved adapter;
- Expo Notifications only after server push registration is enabled and privacy review is complete.

## Security decisions

- `/api/v1` bearer tokens are Patient-only, named, scoped and expire after 30 days.
- Biometric unlock may protect local access but never replaces server authentication.
- Sensitive API responses are not persisted offline in Phase 1. TanStack Query cache remains memory-only until a field-level offline privacy design is approved.
- Screenshots are not programmatically blocked in the initial cross-platform scaffold; consultation/document screens must evaluate platform controls before store release.
- Deep links never contain bearer tokens, room locators or patient data.

## Consequences

One codebase lowers delivery and accessibility drift while native builds remain independently signed and reviewed. Expo Application Services may be used for CI builds, but store credentials and signing keys remain outside Git. A future feature requiring unsupported native code can use an Expo development build/config plugin without replacing the architecture.
