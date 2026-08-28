# Mobile and Responsive Findings

Audit date: 28 August 2026  
Coverage: responsive web at the requested representative widths plus native app/API source and automated-test review. No physical Android/iOS device or signed store build was available.

## Responsive web result

The public homepage was checked at 320, 375, 390, 414, 768, 1024, 1280, 1440 and 1920 px. No horizontal document overflow was observed at those widths. The organic layout, headings, cards and footer reflowed cleanly. The booking shell was also usable in the sampled mobile viewport without horizontal overflow.

The release-blocking responsive defect is the navigation: below the Bootstrap lg breakpoint, the links are hidden and the toggle does nothing (QA-018, High). The component depends on Bootstrap collapse data attributes, but only Bootstrap CSS is loaded.

| Width | Homepage overflow | Navigation | Notes |
| ---: | --- | --- | --- |
| 320 | None observed | FAIL | Toggle visible but cannot open links |
| 375 | None observed | FAIL | Same root cause |
| 390 | None observed | FAIL reproduced interactively | #nav remained display:none; aria-expanded absent |
| 414 | None observed | FAIL | Same breakpoint behavior |
| 768 | None observed | FAIL | Collapsed navigation still unavailable |
| 1024 | None observed | PASS | Desktop navigation visible |
| 1280 | None observed | PASS | Layout stable |
| 1440 | None observed | PASS | Layout stable |
| 1920 | None observed | PASS | Layout stable; content remains bounded |

## Responsive workflow notes

| Area | Result | Evidence/limitation |
| --- | --- | --- |
| Public Home/About/Services/Research | PARTIAL | Layout reflows, but mobile navigation blocks normal discovery |
| Booking | PASS for sampled layout | Steps and controls remained within viewport; availability API returned slots |
| Authentication | PARTIAL | Layout responsive by CSS; form-label accessibility defects remain |
| Patient portal | PARTIAL | Responsive CSS stacks cards/forms; complete authenticated nine-width run not performed |
| Staff dashboard | PARTIAL | Fixed bottom navigation/scrolling rules exist; full authenticated touch run not performed |
| Calendar | NOT TESTABLE as required | Feature is an incomplete list and no complete authenticated viewport sweep was run |
| Power Admin edit controls | BLOCKED | QA-001 prevents the meaningful page-edit workflow; QA-002 omits most controls |
| Modals | NOT APPLICABLE | No custom web modal in audited paths |

## Touch usability

Most primary public buttons and booking choices have generous padding. However, the dead menu prevents touch access to About, Services, Research and Patient Portal from the global header. Some staff icon buttons are 35×35 px, below the commonly used 44×44 CSS-pixel touch target guidance, and depend on title text. This is captured under QA-019 rather than duplicated as a separate root finding.

## Native Android/iOS application

### What works

- Shared Expo Router application for Android/iOS compiles and passes strict TypeScript.
- SecureStore-backed token session restoration exists.
- Versioned /api/v1 envelope, scoped expiring mobile token and current-token revoke exist.
- Dashboard, appointment list, document metadata, messages, notifications and consultation status support pull-to-refresh and explicit loading/empty/error states.
- Cancellation uses a stable UUID client request identifier and a server idempotency record.
- Mobile capabilities truthfully report push_notifications=false and live_video=false.

### Material gaps

QA-016 (High) consolidates the read-mostly native scope gap:

- Profile says editing is not enabled; v1 has no profile update.
- Documents are metadata only; no upload/download action or v1 transfer route.
- Messages have no compose/reply mutations.
- Notifications cannot be marked read.
- Consultations show status only; no consent, waiting-room, join authorization or leave action.
- Reminder preferences and device/session management are absent.
- Push registration returns 409 and no delivery provider is active.

QA-017 (Medium) covers an action mismatch: native cancellation is offered for checked_in, in_progress and rescheduled states even though the server permits only the eligible pre-visit states.

QA-029 (Medium) covers the missing reschedule request in both web and native patient apps.

## Native automated gates

| Gate | Result |
| --- | --- |
| Strict typecheck | PASS |
| Jest | PASS — 4 suites, 6 tests, 42.247 s |
| Covered behavior | API envelope, capability parsing, secure session support utilities and cancellation mutation ID behavior |
| Major gap | No rendered end-to-end native journey, physical device, accessibility tree, offline recovery, deep link, file transfer, notification or consultation test |

## Required retest devices/viewports

- Web: repeat all nine widths after QA-018; include 200% zoom, landscape phone and tablet orientations.
- iOS: current supported small and large iPhone, iPad, VoiceOver, Dynamic Type, reduced motion and poor network.
- Android: current small/large devices, tablet, TalkBack, font/display scaling, back navigation and interrupted network.
- Authenticated web: patient portal, staff dashboard/calendar and Power Admin editor at 320, 390, 768, 1024 and 1440 px minimum.

## Release conclusion

Responsive styling is generally strong, but public mobile navigation is a complete navigation failure and the native application does not yet satisfy its claimed core workflow scope. Web and native mobile release remain blocked pending QA-018 and QA-016, followed by physical-device accessibility/security acceptance.
