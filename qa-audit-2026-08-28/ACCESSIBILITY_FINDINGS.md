# Accessibility Findings

Audit date: 28 August 2026  
Method: browser DOM inspection, keyboard/focus-oriented source review, responsive checks and computed color contrast. This was not a formal WCAG conformance audit and did not include a physical screen reader or native-device accessibility session.

## Summary

The public site uses helpful semantic landmarks and generally coherent headings. Many buttons include visible text or aria-labels, form validation is present, and content remains readable without horizontal overflow at tested public widths. However, unresolved form-name, focus, navigation and contrast defects prevent an accessibility-ready release.

Relevant consolidated findings:

- QA-018 — High — Mobile navigation cannot open.
- QA-019 — Medium — Form labels, skip navigation, focus and announcements are incomplete.
- QA-020 — Medium — Small-text contrast failures.
- QA-024 — Medium — Failed async actions often have no perceivable error/recovery state.

## Detailed results

| Area | Result | Evidence |
| --- | --- | --- |
| Landmarks | PASS | Public DOM contains header/nav/main/footer |
| Heading structure | PASS in sampled public pages | One h1 per sampled route with meaningful headings |
| Skip navigation | FAIL | Zero skip links on sign-in/public shell |
| Sign-in labels | FAIL | Email, password and remember checkbox have no id/for or aria-label in DOM |
| Registration/reset labels | FAIL | Visually adjacent label elements are not programmatically associated |
| Booking labels | PARTIAL | Date uses for/id and method labels wrap inputs; final-step fields use unassociated labels |
| Portal labels | PARTIAL | Profile labels wrap controls; compose/upload inputs rely on placeholders |
| Staff/CMS labels | PARTIAL | Some aria-labels exist; several compact fields are placeholder-only |
| Focus visibility | FAIL in patient search | .patient-search input:focus sets outline:0 with no replacement |
| Mobile navigation | FAIL | Toggle cannot expose links and has no updated aria-expanded state |
| Button names | PARTIAL | Most visible-text buttons are named; several icon staff controls rely on title only |
| Images/alt | PARTIAL | Current CMS Home has no real image; fallback portrait has a descriptive placeholder role; CMS image alt is optional and not editable in UI |
| Errors | PARTIAL | Some alert blocks exist, but field association, focus transfer and live announcement are inconsistent |
| Loading/status | PARTIAL | Text loading/empty states exist; no shared aria-live/busy strategy was found |
| Modal/dialog semantics | NOT APPLICABLE to sampled web UI | No custom modal implementation was exercised; native uses platform Alert for cancellation |
| Keyboard order | PARTIAL | Source order is generally logical; complete authenticated keyboard traversal was not performed |

## Contrast measurements

WCAG AA requires 4.5:1 for normal text (with separate rules for large text/non-text controls). Measurements from the shipped design tokens:

| Foreground | Background | Ratio | Assessment |
| --- | --- | ---: | --- |
| #b35d77 | #fbf8f3 | 4.17:1 | Fails normal-text AA |
| #b35d77 | #fff8f4 | 4.21:1 | Fails normal-text AA |
| #b35d77 | #ffffff | 4.42:1 | Fails normal-text AA narrowly |
| #a4979c | #ffffff | 2.81:1 | Fails normal-text AA |
| #74686e | #fbf8f3 | 5.06:1 | Passes normal-text AA |
| #fdf7f1 | #285a4b | 7.44:1 | Passes AAA for normal text |

Affected small text includes rose .step-label text and inactive .booking-progress text. Decorative service numbers were not treated as required readable content, but their 1.79:1 ratio should not be reused for meaningful text.

## Page/workflow notes

### Authentication

The sign-in DOM exposed three unnamed form controls. A sighted user can infer purpose from adjacent text/icons, but a screen reader's form-control navigation lacks a reliable name. Error containers are not tied to fields and are not explicit live regions.

### Booking

Service choices are native buttons and method controls are wrapped labels, which is positive. The last-step labels are visually present but not associated with their inputs/textarea. Progress color is too low contrast in inactive states. The flow has no booking attachment control, so its accessibility could not be evaluated.

### Patient portal

Profile labels wrap controls correctly. Cancellation reason has aria-label. Compose subject/body and document label/file depend on placeholders. Async upload/message/profile/cancellation failures may not render any status (QA-024).

### Staff calendar/dashboard

Calendar date input has no label. Patient search has aria-label, but its outline is removed. Status is conveyed in text as well as color, which is positive. Day/week/month are text buttons, but there is no actual grid/agenda keyboard model to evaluate.

### CMS

Move/delete section buttons have aria-labels. The broader inline editor, media editor and styling controls are absent, so required accessibility behavior is missing rather than testable. Image alt is not a complete managed field.

## Remediation acceptance criteria

1. Every input/select/textarea has a visible programmatically associated label and field-level error reference.
2. A visible-on-focus skip link targets main content.
3. All interactive elements retain a 3:1 minimum visible focus indicator against adjacent colors; no outline is removed without replacement.
4. Mobile menu is a controlled disclosure with aria-expanded/aria-controls, keyboard support and focus behavior.
5. Normal text combinations meet 4.5:1 and status/control boundaries meet relevant 3:1 rules.
6. Async operations expose aria-busy and an announced success/error message; focus moves only when it helps recovery.
7. Icon-only controls have explicit accessible names independent of title tooltips.
8. Managed images require useful alt text or an explicit decorative choice.
9. Run automated axe checks plus manual keyboard, NVDA/JAWS, VoiceOver and TalkBack acceptance on representative workflows.

## Limitations

No claim of WCAG 2.2 AA conformance is made. Physical screen-reader testing, native accessibility tree inspection, zoom/reflow at 200–400%, reduced motion, high-contrast/forced-colors, voice control and switch control remain required.
