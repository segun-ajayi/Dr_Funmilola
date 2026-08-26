# Demonstration Accounts

These accounts are created by the database seeder for local and staging review. They are active, claimed and email-verified, so each role can open its authorized workspace without completing the email-verification flow.

| Role | Email | Password | Destination after sign-in |
|---|---|---|---|
| Patient | `patient@example.test` | `ChangeMe!2026` | Patient portal |
| Moderator | `moderator@example.test` | `ChangeMe!2026` | Practice dashboard |
| Admin | `admin@example.test` | `ChangeMe!2026` | Practice dashboard |
| Power Admin | `power.admin@example.test` | `ChangeMe!2026` | Practice dashboard |

Run `php artisan db:seed` to create or refresh them. Seeding is repeatable and updates the same four records rather than creating duplicates.

## Security boundary

The `.test` addresses and shared password are demonstration credentials only. Do not seed these accounts in production. Production staff must receive unique accounts, verify their real email addresses, choose individual strong passwords and use the deployment's access-control process.
