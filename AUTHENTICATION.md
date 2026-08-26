# Authentication and Access

## Browser application

The React browser client uses Laravel Sanctum's same-origin cookie authentication. It first requests `/sanctum/csrf-cookie`, then sends credentials to the rate-limited authentication API. Sessions are regenerated after login and invalidated on logout. Production must use HTTPS-only, HTTP-only cookies.

## Android and iOS applications

Native apps will exchange credentials for a 30-day, device-named Sanctum bearer token. Patient tokens receive only `profile:read`, `profile:update`, and `appointments:self`. Staff tokens receive operational abilities. Tokens are individually revocable and all tokens are revoked after a password reset.

Tokens must be stored in Android Keystore or iOS Keychain, never ordinary application storage. Mobile apps must support remote session/device revocation before production launch.

## Account lifecycle

- Public registration always creates the `patient` role.
- A guest appointment creates an unclaimed invitation account; registering with that email claims it.
- Appointment details require a verified email address.
- Staff accounts and role changes are Power Admin workflows and are never exposed through public registration.
- Disabled accounts cannot sign in or continue using protected endpoints.
- Password-recovery responses are identical for known and unknown email addresses.

## Authorization

Policies enforce resource ownership. Patient list queries are scoped through the authenticated user's relationship. Staff routes additionally use explicit role middleware. The security regression suite must always retain a direct test that Patient A cannot access Patient B's appointment.
