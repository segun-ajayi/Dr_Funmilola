# Release Quality Checklist

Run every gate from the repository root. A release candidate fails if any command fails.

## Automated gates

1. `php artisan test --compact` — backend behavior, authorization, isolation, workflows and security regression suite.
2. `npm test` — React component and accessible-state tests in deterministic jsdom with mocked APIs.
3. `npm run build` — TypeScript/Vite production compilation and asset generation.
4. `npm run typecheck` — strict web and shared mobile-contract TypeScript validation.
5. `git diff --check` — whitespace/conflict-marker check.
6. `php artisan route:list` — inspect middleware and route naming for unexpected public mutations.
7. `php artisan schedule:list` — confirm reminder schedule registration.
8. `php artisan practice:prune-expired` — dry-run retention report only.

## Browser journeys

- Desktop and 390×844 mobile: homepage navigation, services and academic/education empty states.
- Mobile booking: choose a service, reach date/time step, confirm no horizontal overflow; do not submit a medical appointment as an automated visual check.
- Unauthenticated guards: Patient, Staff, CMS, consultation and research-review routes show friendly sign-in/access states.
- Authenticated role workflows require a controlled staging account and must cover Patient, Moderator, Admin and Power Admin before launch.

## Accessibility review

- Keyboard order and visible focus on navigation, forms, editor controls and waiting-room actions.
- Accessible names for icon-only controls.
- Heading hierarchy and landmarks on public and portal pages.
- Text contrast at WCAG AA, 200% zoom, reduced-motion preference and screen-reader smoke review.

## Known non-production integrations

- Video provider remains `unconfigured`; no live media claim is permitted.
- Email requires production provider credentials, queue workers and delivery monitoring.
- Mobile push remains disabled until native device registration exists.
- Basic upload scanning must be replaced by approved production malware scanning.
- Authoritative profile seeds remain Pending Review until Power Admin approval.

## Release evidence

Record commit SHA, environment, operator, test counts, build result, browser/device matrix, database migration status, restore-test date, readiness result and unresolved risks in the deployment record.
