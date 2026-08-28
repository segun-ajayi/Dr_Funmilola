# UI/UX Review

Audit date: 28 August 2026

## Overall assessment

The public visual system is one of the stronger parts of the build. Wine, rose, blush and ivory create a coherent specialist breast-health identity; organic contours, rounded cards, restrained iconography, serif display typography and generous whitespace communicate compassion without feeling childish. The site is recognizably more considered than a default Bootstrap template.

The main UX risk is a gap between presentation and operational depth. Several polished surfaces—Edit site, Calendar, Consultation and native care screens—suggest a complete capability that is absent, blocked or read-only. That mismatch is more damaging than a visually plain unfinished interface because users form the wrong expectation.

## Visual identity

| Dimension | Assessment |
| --- | --- |
| Breast-health cues | Good: curved forms, soft rose palette, heart/health iconography and whole-person language |
| Professional trust | Good visual restraint and strong hierarchy; weakened by missing verified profile depth, real contact journey and approved photography |
| Academic credibility | Research section and verification-first language are promising; empty/incomplete publication journeys weaken proof |
| Feminine without childishness | Achieved: muted rose/wine rather than novelty pink, balanced by ivory/charcoal |
| Medical appropriateness | Generally achieved; medical disclaimer present and service language is calm/non-alarmist |
| Consistency | Strong across public cards/buttons; staff workspace is more conventional but retains shared tokens |
| Typography | Strong display/body pairing and scale; some small text contrast fails AA (QA-020) |
| Whitespace | Generous and clear on desktop; large hero/section spacing remains acceptable on tested mobile widths |
| Animations | Minimal hover transitions only; no distracting motion observed |

## Public website

Strengths:

- Clear homepage value proposition and prominent Book an appointment call to action.
- Trust language is patient-centered and avoids overclaiming unverified qualifications.
- Content widths, cards and calls to action remain readable from 320 to 1920 px.
- Empty research/education states explain the verification process rather than showing a blank screen.

Weaknesses:

- QA-018: the mobile header menu cannot open, making the strongest content undiscoverable on phones.
- QA-011: /contact and invalid routes show Home, causing disorientation and hiding broken destinations.
- QA-012: content depth does not support the brand promise—no complete career, achievements or contact; services CMS page loses the real service cards; education and publications have no detail journey.
- Current CMS Home contains no real portrait/media. The legacy fallback has an honest initials-based placeholder, but approved photography or clinically appropriate imagery is still needed for trust.
- QA-027: footer legal/accessibility labels look like expected destinations but are inert.
- QA-025: static metadata means browser/search/social context does not follow the visible page.

## Booking

The three-step structure, progress bar, service descriptions and privacy note are easy to understand. Service buttons use descriptive text rather than bare radio labels, and date/slot empty states are clear.

Usability/functional gaps:

- QA-004: client-side disabled states are not enforced server-side; users can receive a false successful booking for an unavailable or unsupported option.
- QA-028: the required attachment step is absent.
- The step count says three, while the required workflow includes appointment type and upload; the simplified information architecture should be explicitly reconciled with product requirements.
- Several final-step labels are visually adjacent but not programmatically associated (QA-019).
- Inactive progress text is very low contrast (QA-020).
- A failed submission has an error state in BookingPage, but other portal/staff mutations do not share that quality.

## Patient portal

The dashboard is clean, uses minimum-necessary data and groups appointments, messages, documents and profile appropriately. Empty states are understandable. Profile fields and document guidance use calm language.

Key UX defects:

- QA-007: a declined cancellation remains labeled Cancellation requested and cannot be tried again.
- QA-029: there is no Request reschedule action.
- QA-024: upload, compose/reply, profile and cancellation handlers can fail without visible recovery.
- Placeholder-only compose/upload fields reduce clarity and accessibility.
- The portal exposes no consolidated appointment detail view; most information/actions are embedded in summary cards.

## Staff workspace and calendar

The staff dashboard has clear metrics, needs-attention rows, patient lookup and today timeline. Status is expressed in text as well as color. Role-specific publishing links are hidden from non-Power Admins.

The Calendar label overpromises (QA-009). Day/week/month controls only change a list range; there is no agenda/grid, appointment creation/editing, drag/drop or filter model. Staff must switch to other pages/actions for work that should be contextual. Recurring availability rules have APIs but no UI.

Some 35×35 icon controls are compact for touch and use title rather than explicit aria-label. Patient search removes its focus outline. Failed staff mutations have little/no recovery feedback.

## Power Admin CMS

The edit toggle is visually understandable and the separate manager has a simple section list. Move/delete controls are recognizable and draft/preview/publish concepts exist in backend APIs.

The workflow does not meet the visual-edit requirement (QA-002), and QA-001 blocks even the limited page editor. A user clicking Edit site expects to modify what they see; being sent to a separate form with heading/text fields is a major interaction-model mismatch. Navigation/theme settings also create no public result (QA-003).

## Native application

Native screens use consistent cards, headings, notices, pull-to-refresh and error/empty states. Copy is careful about emergencies and honestly says video is not active.

The interface presents Care sections as destinations but offers almost no actions (QA-016). Documents cannot be opened, messages cannot be sent, notifications cannot be marked read, profile cannot be edited and consultation cannot be joined. The visual polish therefore exceeds functional usefulness. Native cancellation also advertises an action in server-ineligible states (QA-017).

## Error and recovery experience

Good examples include booking error copy, native pull-to-refresh errors and meaningful empty states. The broader mutation pattern is inconsistent (QA-024): many async handlers have no catch/onError. Unknown URLs are silently recovered to Home rather than explaining the error (QA-011). A health-practice platform should favor explicit status and safe recovery over silent failure.

## Prioritized UX recommendations

1. Restore mobile navigation and proper focus/accessible state (QA-018).
2. Align visible success with server truth in booking, cancellation and native action eligibility (QA-004, QA-007, QA-017).
3. Either complete or clearly re-scope the CMS, calendar, consultation and native app before release; do not label shells as complete.
4. Complete verified public profile/contact/service/publication journeys to support trust (QA-012).
5. Standardize labels, focus, errors, loading and status announcements (QA-019, QA-020, QA-024).
6. Add approved portrait/practice imagery only after content/privacy approval; do not replace the honest placeholder with unverified imagery.

## UI/UX release view

The design language is release-quality in concept, but navigation and workflow truthfulness are not. The product should not be released on visual polish alone while high-severity functional gaps remain.
