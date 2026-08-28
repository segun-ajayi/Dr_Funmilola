# QA Findings

Audit date: 28 August 2026  
Environment: isolated local QA database, production-like debug disabled, Codex in-app Chromium browser.  
Important: suggested directions are remediation guidance only. No application fix was implemented during this audit.

---

FINDING ID:
QA-001

TITLE:
Protected CMS page loading is shadowed by the public slug route

CATEGORY:
CMS / Functional

SEVERITY:
High

REQUIREMENT:
REQ-074, REQ-075, REQ-076, REQ-080, REQ-082, REQ-138

LOCATION:
GET /api/cms/pages/{page}; routes/api.php; CmsEditorPage

ROLE:
Power Admin

PRECONDITIONS:
Verified Power Admin session and at least one CMS page.

STEPS TO REPRODUCE:
1. Sign in as Power Admin.
2. Request GET /api/cms/pages and note a numeric page ID.
3. Request GET /api/cms/pages/1.
4. Select the same page in the CMS UI.

EXPECTED RESULT:
The protected controller returns the editable page and sections so draft, preview, publish and reorder can continue.

ACTUAL RESULT:
The earlier public GET /api/cms/pages/{slug} route handles the numeric value and returns 404 because no page has slug 1. The editor cannot load a selected page.

STATUS:
Reproducible

EVIDENCE:
Power Admin runtime: list returned 200; item returned 404 with No query results for model App\Models\CmsPage. Route matcher resolved both /1 and /home to PublicCmsController@show. routes/api.php lines 45 and 121.

IMPACT:
The central CMS workflow is blocked despite the editor and backend endpoints existing.

SUGGESTED FIX DIRECTION:
Give public and protected page routes unambiguous paths or constraints, then add an authenticated route-integration test and an editor end-to-end test.

---

FINDING ID:
QA-002

TITLE:
Power Admin visual edit mode omits most required editing capabilities

CATEGORY:
CMS / Functional / UX

SEVERITY:
High

REQUIREMENT:
REQ-065 through REQ-073, REQ-077 through REQ-084

LOCATION:
Public Layout edit toggle; /staff/cms; CmsEditorPage

ROLE:
Power Admin

PRECONDITIONS:
Verified Power Admin session.

STEPS TO REPRODUCE:
1. Activate Edit site on a public page.
2. Attempt to select or double-click public text.
3. Open Website pages.
4. Look for rich text, image, button, menu, page lifecycle, section styling and version controls.

EXPECTED RESULT:
The required visual CMS supports inline selection/editing, typography, links, managed media, buttons, menus, page lifecycle, section presentation, drafts, preview, publish and rollback.

ACTUAL RESULT:
Edit site only reveals links to separate managers. The page editor exposes create page, add section, heading/text, move/delete, preview and publish. It has no inline editor, rich text, media library/upload/crop, full button/menu/page/section controls, or version UI.

STATUS:
Reproducible

EVIDENCE:
App.tsx Layout and CmsEditorPage source review; runtime edit toggle. The repository plan itself notes managed media is not implemented.

IMPACT:
A principal, explicitly high-priority product capability is substantially absent; routine site changes still require development work.

SUGGESTED FIX DIRECTION:
Define a safe component schema and implement the required editing controls, managed media, lifecycle and version UI as testable end-to-end workflows.

---

FINDING ID:
QA-003

TITLE:
Published navigation and theme settings do not affect the public site

CATEGORY:
CMS / Functional

SEVERITY:
Medium

REQUIREMENT:
REQ-079, REQ-085

LOCATION:
/api/cms/settings; /api/cms/public-settings; App.tsx Layout; app.css

ROLE:
Power Admin / Public

PRECONDITIONS:
A Power Admin publishes a navigation or theme setting.

STEPS TO REPRODUCE:
1. Inspect the settings/public-settings APIs.
2. Inspect the public Layout and CSS token source.
3. Load a public page after a published setting exists.
4. Compare navigation and theme to the stored value.

EXPECTED RESULT:
Published settings drive public navigation and visual theme.

ACTUAL RESULT:
The public Layout hard-codes links and CSS colors and never requests /api/cms/public-settings. No settings-management UI is present.

STATUS:
Reproducible

EVIDENCE:
routes/api.php lines 47 and 131-133; App.tsx lines 31-36; app.css root tokens.

IMPACT:
Publishing these settings creates a false sense of successful change and cannot satisfy menu/theme administration.

SUGGESTED FIX DIRECTION:
Connect a validated published settings contract to the renderer and provide guarded draft/preview/publish controls with regression tests.

---

FINDING ID:
QA-004

TITLE:
Booking API accepts unavailable times and unsupported consultation methods

CATEGORY:
Functional / Data

SEVERITY:
High

REQUIREMENT:
REQ-036, REQ-041, REQ-094

LOCATION:
POST /api/appointment-requests; AppointmentRequestController

ROLE:
Public / Patient

PRECONDITIONS:
Isolated QA database with normal weekday availability and Breast Surgery online_available=false.

STEPS TO REPRODUCE:
1. POST an otherwise valid request for Saturday 5 September 2026 at 03:00 Africa/Lagos, outside every availability rule.
2. Observe 201.
3. POST Breast Surgery as consultation_method=online at 04:00.
4. Observe 201.

EXPECTED RESULT:
Submission must match a currently generated slot and the service/rule method constraints.

ACTUAL RESULT:
Both requests were accepted. The controller checks only future time and conflicts, not slot membership, closures/rules or service online eligibility.

STATUS:
Reproducible

EVIDENCE:
201 references 9146817e-7939-4c79-8f00-08bbd708e3ce and 92266074-a6d1-41f7-a9fb-6c74d38a37e1; AppointmentRequestController lines 23-36.

IMPACT:
Forged requests can create appointments when the clinician is unavailable or for a method the service does not support.

SUGGESTED FIX DIRECTION:
Validate submission through one server-side availability decision using service, method, rule/exception, duration and current conflict data.

---

FINDING ID:
QA-005

TITLE:
Potential data-integrity finding: appointment overlap prevention is not concurrency-safe

CATEGORY:
Data / Functional

SEVERITY:
High

REQUIREMENT:
REQ-044

LOCATION:
AppointmentRequestController; AvailabilityService; appointments schema

ROLE:
Public / Patient

PRECONDITIONS:
Two simultaneous requests for the same free interval.

STEPS TO REPRODUCE:
1. Review the booking transaction.
2. Observe that hasConflict performs an exists query.
3. Observe that the insert follows without a row/advisory lock or database exclusion constraint.
4. Consider two transactions passing the check before either insert commits.

EXPECTED RESULT:
The database or serialized transaction guarantees that only one overlapping appointment is committed.

ACTUAL RESULT:
The implementation has a check-then-insert race window. Sequential overlap is rejected, but no database primitive closes the concurrent window. A simultaneous double-booking was not executed in this audit.

STATUS:
Unable to Reproduce

EVIDENCE:
Code evidence: AppointmentRequestController lines 31-36; AvailabilityService lines 47-49; appointments migration has no interval exclusion/locking strategy. No destructive load test was run.

IMPACT:
Real concurrent requests could double-book a clinician even though ordinary automated tests remain green.

SUGGESTED FIX DIRECTION:
Serialize the scheduling resource or enforce an appropriate database-level overlap invariant, then add a true concurrent integration test on PostgreSQL.

---

FINDING ID:
QA-006

TITLE:
Configured slot length is ignored during availability generation

CATEGORY:
Functional / Data

SEVERITY:
Medium

REQUIREMENT:
REQ-055, REQ-092

LOCATION:
AvailabilityService::slots

ROLE:
Moderator / Admin / Power Admin

PRECONDITIONS:
Availability rule whose slot_minutes differs from service duration plus buffer.

STEPS TO REPRODUCE:
1. Configure a rule with slot_minutes=45.
2. Select a service with a different duration.
3. Request availability.
4. Compare generated starts with the configured slot interval.

EXPECTED RESULT:
The documented rule fields have defined, observable scheduling behavior.

ACTUAL RESULT:
The step is service.duration_minutes + rule.buffer_minutes; slot_minutes is never used.

STATUS:
Reproducible

EVIDENCE:
AvailabilityService line 28; seeded rules set slot_minutes=45.

IMPACT:
Staff configuration can be misleading and yield a schedule different from what the rule records imply.

SUGGESTED FIX DIRECTION:
Clarify the domain meaning of slot_minutes and either enforce it in generation or remove/rename it from the managed contract.

---

FINDING ID:
QA-007

TITLE:
Declined cancellation requests cannot be resubmitted and display the wrong state

CATEGORY:
Functional / UX / Data

SEVERITY:
Medium

REQUIREMENT:
REQ-047, REQ-050

LOCATION:
POST /api/me/appointments/{id}/cancellation-request; PortalPage Appointments

ROLE:
Patient / Moderator / Admin

PRECONDITIONS:
Eligible patient appointment with a declined cancellation request.

STEPS TO REPRODUCE:
1. Patient submits a cancellation request.
2. Staff declines it.
3. Patient submits again with a new reason.
4. Refresh the appointment list.

EXPECTED RESULT:
The patient can create a new pending request or receives a clear rule; the declined state is displayed accurately.

ACTUAL RESULT:
firstOrCreate returns the original declined row with the original reason and 201. The UI treats any related record as Cancellation requested and hides the action permanently.

STATUS:
Reproducible

EVIDENCE:
Isolated runtime: request id 1 stayed status=declined after resubmit; CancellationRequestController line 19; PortalPage appointment condition.

IMPACT:
Patients cannot recover from a decline or accurately understand whether staff action is pending.

SUGGESTED FIX DIRECTION:
Model cancellation requests as repeatable events or explicitly reset a declined record, and render each decision state separately.

---

FINDING ID:
QA-008

TITLE:
Rescheduling never records or displays the required Rescheduled state

CATEGORY:
Functional / Data

SEVERITY:
Medium

REQUIREMENT:
REQ-050, REQ-089

LOCATION:
AppointmentWorkflowService::reschedule

ROLE:
Patient / Moderator / Admin

PRECONDITIONS:
Pending or confirmed appointment.

STEPS TO REPRODUCE:
1. Reschedule an eligible appointment through the staff API.
2. Retrieve it afterward.
3. Compare status with the required state model.
4. Inspect allowed transitions from Rescheduled.

EXPECTED RESULT:
The appointment visibly and consistently represents the rescheduled event while remaining operational.

ACTUAL RESULT:
The new time is saved with status Confirmed. The defined Rescheduled status is terminal and the reschedule method never uses it.

STATUS:
Reproducible

EVIDENCE:
AppointmentWorkflowService lines 17-24 and 49-68, especially line 64.

IMPACT:
Status reporting, filtering, audit interpretation and patient communication do not match the stated lifecycle.

SUGGESTED FIX DIRECTION:
Define whether Rescheduled is an event or durable state, then align transition rules, API representation, filters and UI copy.

---

FINDING ID:
QA-009

TITLE:
Operational calendar is a date-range list rather than the required full calendar

CATEGORY:
Functional / UI / Mobile

SEVERITY:
High

REQUIREMENT:
REQ-053, REQ-055, REQ-059, REQ-095 through REQ-099, REQ-137

LOCATION:
/staff/calendar; StaffCalendarPage

ROLE:
Moderator / Admin / Power Admin

PRECONDITIONS:
Authenticated staff session.

STEPS TO REPRODUCE:
1. Open Staff Calendar.
2. Switch day, week and month.
3. Look for agenda, grid, create/edit, drag/drop, reschedule and filters.
4. Inspect availability management.

EXPECTED RESULT:
Day/week/month/agenda views support creation, editing, drag/drop reschedule, patient/type/status filters and responsive use; recurring rules and exceptions are manageable.

ACTUAL RESULT:
Buttons only change the range of one chronological event list. There is no agenda, grid interaction, appointment manipulation or filtering. UI manages exceptions but not recurring rules.

STATUS:
Reproducible

EVIDENCE:
StaffCalendarPage View is day|week|month and renders calendar-event articles plus exception add/delete only.

IMPACT:
Staff cannot perform the central schedule-management workflow promised by the requirements.

SUGGESTED FIX DIRECTION:
Implement an accessible calendar model with complete operations and filters, retaining API-side authorization and conflict validation.

---

FINDING ID:
QA-010

TITLE:
Online consultation is a workflow shell with no live media provider

CATEGORY:
Functional / Privacy

SEVERITY:
High

REQUIREMENT:
REQ-013, REQ-057, REQ-102, REQ-103, REQ-143

LOCATION:
Consultation controllers/services; UnconfiguredVideoProvider; web/native consultation screens

ROLE:
Patient / Moderator / Admin

PRECONDITIONS:
Online appointment and related consultation.

STEPS TO REPRODUCE:
1. Inspect the mobile capability response.
2. Enter the web/native consultation workflow.
3. Request provider readiness/room access.
4. Attempt to identify an actual audio/video room provider.

EXPECTED RESULT:
Related users can complete an authenticated waiting-room-to-live-consultation journey.

ACTUAL RESULT:
Consent, waiting-room states and signed authorization exist, but the bound provider reports unconfigured and live_video=false. Native copy directs patients to separate instructions.

STATUS:
Reproducible

EVIDENCE:
AppServiceProvider binding; UnconfiguredVideoProvider; /api/v1/capabilities; MASTER_PLAN lines 176-190.

IMPACT:
The product cannot deliver its online consultation service despite presenting consultation workflows.

SUGGESTED FIX DIRECTION:
Select an approved provider after privacy/hosting review, implement the adapter, and run access, expiry, media, failure and device acceptance tests.

---

FINDING ID:
QA-011

TITLE:
Unknown and Contact routes silently render the homepage

CATEGORY:
Functional / UX

SEVERITY:
Medium

REQUIREMENT:
REQ-008, REQ-015, REQ-017

LOCATION:
React catch-all routing; /contact; arbitrary public paths

ROLE:
Public

PRECONDITIONS:
Public browser session.

STEPS TO REPRODUCE:
1. Navigate to /contact.
2. Observe the homepage content.
3. Navigate to /this-route-does-not-exist.
4. Observe the same homepage and 200 response.

EXPECTED RESULT:
/contact shows contact content, while unknown routes show a clear 404 with recovery links.

ACTUAL RESULT:
Both paths render the Home CMS/fallback route without an error state.

STATUS:
Reproducible

EVIDENCE:
IAB route sweep; App.tsx nested wildcard at line 61.

IMPACT:
Users cannot distinguish missing content from a valid page; broken links are masked and search engines can index soft 404s.

SUGGESTED FIX DIRECTION:
Add an explicit contact route/page and a dedicated 404 route that preserves helpful global navigation.

---

FINDING ID:
QA-012

TITLE:
Required public professional, service, education and publication journeys are incomplete

CATEGORY:
Content / Functional / UX

SEVERITY:
Medium

REQUIREMENT:
REQ-003 through REQ-011, REQ-059, REQ-107, REQ-108

LOCATION:
/about; /services; /research; /academic; /education; primary navigation

ROLE:
Public / Admin

PRECONDITIONS:
Seeded local application.

STEPS TO REPRODUCE:
1. Browse each public content route.
2. Look for career, achievements and contact.
3. Inspect services, education cards and publication controls.
4. Try to open a publication/article detail or DOI.

EXPECTED RESULT:
All required content areas are complete, navigable, sourced and support list-to-detail journeys, sorting/pagination and external links.

ACTUAL RESULT:
Career/achievements/contact are absent; CMS Services hides the six service cards; education cards are not links; publication UI lacks sort, pagination, detail, DOI and source links; academic/education are not in primary navigation.

STATUS:
Reproducible

EVIDENCE:
IAB route sweep and App.tsx, AcademicPortfolioPage, EducationPage source inspection.

IMPACT:
The public site looks polished but omits material information and research credibility workflows required for release.

SUGGESTED FIX DIRECTION:
Complete verified content models and public routes, then test every list/filter/detail/external-link journey with approved content.

---

FINDING ID:
QA-013

TITLE:
Research publishing state can diverge from public publication state

CATEGORY:
Content / Data / CMS

SEVERITY:
High

REQUIREMENT:
REQ-009, REQ-011, REQ-105, REQ-110

LOCATION:
VerificationQueueController; PublicContentController; AcademicContentController; publications schema

ROLE:
Power Admin / Public

PRECONDITIONS:
Verified research claim that has been published.

STEPS TO REPRODUCE:
1. Publish a verified claim.
2. Call the decision endpoint again with rejected.
3. Inspect the created Publication and public academic query.
4. Compare general public-feed and academic publication status filters.

EXPECTED RESULT:
Published/rejected state transitions are controlled, rejection retracts visibility where required, and all public feeds use one lifecycle.

ACTUAL RESULT:
decide accepts verified/rejected from any current status, including published. It does not retract the created record. Publishing writes verification_status=verified; /api/public filters published. updateOrCreate by nullable DOI can also target an unrelated null-DOI row.

STATUS:
Unable to Reproduce

EVIDENCE:
VerificationQueueController lines 6-7; PublicContentController line 15; AcademicContentController line 6; DOI is nullable and non-unique. A publish/reject mutation was not run because the audit charter prohibits publishing content.

IMPACT:
Rejected or stale information can remain public, feeds disagree, and publication records can be overwritten or duplicated.

SUGGESTED FIX DIRECTION:
Implement one constrained state machine, immutable source identity, unique identifiers where valid, explicit publish/unpublish/retract behavior and transactional tests.

---

FINDING ID:
QA-014

TITLE:
Seed publications bypass the verification queue and lack bibliographic metadata

CATEGORY:
Content / Data

SEVERITY:
Medium

REQUIREMENT:
REQ-011, REQ-104, REQ-111

LOCATION:
DatabaseSeeder publications and ResearchClaim seed data

ROLE:
Power Admin

PRECONDITIONS:
Fresh seeded database.

STEPS TO REPRODUCE:
1. Run the normal seeder in an isolated database.
2. Inspect Publication rows.
3. Inspect the Research & Verification Queue.
4. Compare title-only records to sourced claims.

EXPECTED RESULT:
Research seed material is retained as sourced claims and reaches Publication only after review/publish.

ACTUAL RESULT:
Six title-only Publication rows are created directly as pending_review without authors, journal, DOI, source or a queue relationship. Two matching sourced claims can later create separate verified records.

STATUS:
Reproducible

EVIDENCE:
DatabaseSeeder lines 52-71. External audit verified the sampled titles, so this is a governance/duplication defect, not a fabricated-content allegation.

IMPACT:
Power Admins cannot review those rows through the queue, and later publication can create duplicate/incomplete records.

SUGGESTED FIX DIRECTION:
Seed only ResearchClaim records with authoritative source metadata and create Publication records exclusively through the controlled publish transaction.

---

FINDING ID:
QA-015

TITLE:
Required patient, account and role administration is absent

CATEGORY:
Functional / Authorization / Data

SEVERITY:
High

REQUIREMENT:
REQ-059 through REQ-064, REQ-124

LOCATION:
Staff APIs and workspace UI

ROLE:
Admin / Power Admin

PRECONDITIONS:
Authenticated Admin or Power Admin.

STEPS TO REPRODUCE:
1. Open the staff dashboard.
2. Search for a patient.
3. Attempt to create/edit/deactivate a patient.
4. Attempt to invite staff or change a user's role.

EXPECTED RESULT:
Authorized administrators can manage patients and Power Admins can manage accounts/roles, with audit records.

ACTUAL RESULT:
Staff can search and view limited patient context only. There are no patient CRUD, staff account, activation or role-change routes/UI.

STATUS:
Reproducible

EVIDENCE:
104-route inventory; StaffDashboardPage and InboxController patient method; no user/role management controller.

IMPACT:
Essential operational administration and its required audit trail cannot be performed in the product.

SUGGESTED FIX DIRECTION:
Define minimum-necessary administrative operations and add policy-protected APIs/UI with re-authentication and complete before/after audit metadata.

---

FINDING ID:
QA-016

TITLE:
Native patient app is read-mostly and omits core required actions

CATEGORY:
Mobile / Functional

SEVERITY:
High

REQUIREMENT:
REQ-142 through REQ-145

LOCATION:
mobile/app screens; routes/mobile.php; /api/v1

ROLE:
Patient

PRECONDITIONS:
Authenticated native patient session.

STEPS TO REPRODUCE:
1. Open Profile, Documents, Messages, Notifications and Consultations.
2. Try to update profile, upload/download, compose/reply, mark read, consent/wait/join/leave.
3. Look for reminder preferences and device/session management.
4. Inspect the v1 routes.

EXPECTED RESULT:
Core mobile patient journeys are actionable using the versioned API.

ACTUAL RESULT:
These screens only display data. v1 exposes reads plus cancellation and a push endpoint that returns 409. Profile update, file transfer, messaging mutations, notification read, consultation actions, reminders and device/session management are absent.

STATUS:
Reproducible

EVIDENCE:
routes/mobile.php line 7; mobile/app profile/documents/messages/notifications/consultations screens; MobilePatientController.

IMPACT:
The Android/iOS product cannot fulfill the repository's claimed first-class patient-app scope.

SUGGESTED FIX DIRECTION:
Prioritize the required versioned mutations and native flows, with idempotency, offline/error handling, privacy review and device acceptance tests.

---

FINDING ID:
QA-017

TITLE:
Native app offers cancellation for server-ineligible appointment states

CATEGORY:
Mobile / Functional / UX

SEVERITY:
Medium

REQUIREMENT:
REQ-141

LOCATION:
mobile/app/appointments.tsx; AppointmentPolicy

ROLE:
Patient

PRECONDITIONS:
Appointment in checked_in, in_progress or rescheduled state.

STEPS TO REPRODUCE:
1. Load such an appointment in the native app.
2. Observe Request cancellation is shown.
3. Submit the request.
4. Observe server authorization/validation rejection.

EXPECTED RESULT:
The app presents cancellation only for states the server permits and explains restrictions.

ACTUAL RESULT:
The button is hidden only for cancelled, completed and no_show, so it is offered for multiple ineligible states.

STATUS:
Reproducible

EVIDENCE:
mobile/app/appointments.tsx status list compared with the web portal/policy eligibility states.

IMPACT:
Patients encounter a predictable failed action during an already sensitive care workflow.

SUGGESTED FIX DIRECTION:
Expose server-derived allowed actions per appointment and render the native controls from that contract.

---

FINDING ID:
QA-018

TITLE:
Mobile public navigation toggle is non-functional

CATEGORY:
Mobile / Functional / Accessibility

SEVERITY:
High

REQUIREMENT:
REQ-002, REQ-134, REQ-135, REQ-139

LOCATION:
Public header below Bootstrap lg breakpoint; App.tsx; main.tsx

ROLE:
Public / Patient

PRECONDITIONS:
Viewport below approximately 992 px.

STEPS TO REPRODUCE:
1. Load the homepage at 390 px width.
2. Confirm the desktop links are collapsed.
3. Activate Open navigation.
4. Inspect the navigation visibility and expanded state.

EXPECTED RESULT:
The menu opens, exposes navigation links, updates its accessible state and can close again.

ACTUAL RESULT:
#nav remains display:none; class is unchanged and aria-expanded is absent. Only Bootstrap CSS is imported; no JS behavior or React state controls the collapse.

STATUS:
Reproducible

EVIDENCE:
IAB at 390 px; App.tsx lines 33-35; main.tsx imports bootstrap.min.css only.

IMPACT:
Most public navigation is unreachable on phones and small tablets.

SUGGESTED FIX DIRECTION:
Implement a controlled React disclosure with correct aria-expanded/controls, keyboard behavior and close-on-navigation, then test all requested widths.

---

FINDING ID:
QA-019

TITLE:
Form labeling, skip navigation and focus treatment are incomplete

CATEGORY:
Accessibility / UX

SEVERITY:
Medium

REQUIREMENT:
REQ-128 through REQ-131, REQ-133

LOCATION:
Authentication, booking, portal, staff, CMS and academic forms; app.css

ROLE:
Public / Patient / Moderator / Admin / Power Admin

PRECONDITIONS:
Keyboard or assistive-technology user.

STEPS TO REPRODUCE:
1. Inspect sign-in email/password/checkbox accessibility names.
2. Tab from the top and look for a skip link.
3. Focus staff patient search.
4. Inspect placeholder-only inputs in portal/CMS/research forms.

EXPECTED RESULT:
Every input has a programmatic label, focus is visible, a skip path exists and errors/status are announced.

ACTUAL RESULT:
Sign-in controls have no id, associated label or aria-label; many other controls rely on placeholder text. No skip link exists. Patient search sets outline:0 without a replacement; no systematic live-region strategy is present.

STATUS:
Reproducible

EVIDENCE:
IAB sign-in DOM: three unnamed inputs and zero skip links; app.css patient-search input:focus; source review.

IMPACT:
Keyboard and screen-reader users may not know control purpose, current focus or mutation results.

SUGGESTED FIX DIRECTION:
Adopt associated labels/field errors, skip-to-content, consistent focus-visible styling and announced async status components; verify with keyboard and screen reader.

---

FINDING ID:
QA-020

TITLE:
Several small-text color combinations fail WCAG AA contrast

CATEGORY:
Accessibility / UI

SEVERITY:
Medium

REQUIREMENT:
REQ-132

LOCATION:
Global design tokens, booking progress, small rose labels

ROLE:
Public / Patient / Staff

PRECONDITIONS:
Small normal text using the affected tokens.

STEPS TO REPRODUCE:
1. Read --rose, ivory and inactive progress colors from app.css.
2. Calculate relative luminance contrast.
3. Compare to WCAG AA 4.5:1 for normal text.
4. Inspect affected small labels/progress text.

EXPECTED RESULT:
Normal-size text meets at least 4.5:1.

ACTUAL RESULT:
#b35d77 on #fbf8f3 is 4.17:1 and on #fff8f4 is 4.21:1; #a4979c on white is 2.81:1.

STATUS:
Reproducible

EVIDENCE:
Calculated from app.css tokens using the WCAG luminance formula; affected text includes .step-label and inactive .booking-progress.

IMPACT:
Low-vision users can struggle to read progress and contextual labels.

SUGGESTED FIX DIRECTION:
Adjust semantic text tokens to verified AA combinations and add automated contrast checks to visual regression criteria.

---

FINDING ID:
QA-021

TITLE:
Potential Security Finding: password reset does not revoke active browser sessions

CATEGORY:
Security / Privacy

SEVERITY:
Medium

REQUIREMENT:
REQ-028, REQ-123

LOCATION:
ResetPasswordController; database session storage

ROLE:
Patient / Staff

PRECONDITIONS:
Account with an active browser session and a completed password reset.

STEPS TO REPRODUCE:
1. Inspect the reset callback.
2. Observe the password and remember token change.
3. Observe Sanctum personal tokens are deleted.
4. Observe no stored browser sessions are invalidated.

EXPECTED RESULT:
Password reset terminates all other active sessions/tokens or explicitly offers secure session management.

ACTUAL RESULT:
Personal access tokens are deleted, but active database/browser session records are not revoked. Exploitation was not attempted.

STATUS:
Unable to Reproduce

EVIDENCE:
ResetPasswordController lines 22-25; SESSION_DRIVER=database in the normal environment. A two-browser-session exploit test was not performed.

IMPACT:
A previously compromised authenticated browser session may survive the credential reset.

SUGGESTED FIX DIRECTION:
Invalidate other sessions transactionally on reset and provide audited session/device revocation with regression tests.

---

FINDING ID:
QA-022

TITLE:
Upload pipeline is not protected by a production-grade malware scanner

CATEGORY:
Security / Privacy

SEVERITY:
High

REQUIREMENT:
REQ-121, REQ-122

LOCATION:
FileScannerInterface binding; BasicFileScanner; patient document upload

ROLE:
Patient / Staff

PRECONDITIONS:
Authenticated patient uploads an allowed MIME/extension.

STEPS TO REPRODUCE:
1. Inspect the FileScannerInterface binding.
2. Inspect BasicFileScanner.
3. Note only the first 8192 bytes and four string signatures are checked.
4. Review the production release note.

EXPECTED RESULT:
Sensitive uploads are scanned by an approved, monitored malware-analysis service before acceptance.

ACTUAL RESULT:
The default scanner is a small signature boundary, not malware analysis. The repository explicitly says production should bind ClamAV or an approved managed scanner.

STATUS:
Reproducible

EVIDENCE:
BasicFileScanner line 4; AppServiceProvider binding; MASTER_PLAN line 257.

IMPACT:
Allowed document containers can carry malicious content beyond the simplistic signatures, exposing staff endpoints/devices and retained patient storage.

SUGGESTED FIX DIRECTION:
Quarantine uploads, scan with an approved service, fail closed, monitor failures, and release files only after a clean result.

---

FINDING ID:
QA-023

TITLE:
Audit logging omits required actions and consistent before/after values

CATEGORY:
Security / Data

SEVERITY:
Medium

REQUIREMENT:
REQ-124, REQ-125

LOCATION:
AuditLog writers across auth, appointments, documents, messaging, consultation and CMS

ROLE:
Patient / Moderator / Admin / Power Admin

PRECONDITIONS:
Perform important actions in each domain.

STEPS TO REPRODUCE:
1. Inventory AuditLog::create calls.
2. Compare them with required auditable actions.
3. Inspect metadata for old/new values.
4. Compare with available account/role operations.

EXPECTED RESULT:
Important changes record actor, action, resource, time and before/after data where required.

ACTUAL RESULT:
Core identity/appointment/CMS/research events are logged, but successful document upload/download, message changes and consultation consent/join/leave are not. Role changes cannot be logged because management is absent. Several records omit before/after values.

STATUS:
Reproducible

EVIDENCE:
Static AuditLog::create inventory; PatientDocumentController only audits rejection; consultation attendance is separate from audit log.

IMPACT:
Incident response, privacy investigations and clinical/administrative accountability have material blind spots.

SUGGESTED FIX DIRECTION:
Define an auditable-event catalogue and consistent safe metadata schema, then assert logs in each mutation integration test.

---

FINDING ID:
QA-024

TITLE:
Multiple asynchronous UI failures have no user-visible recovery path

CATEGORY:
UX / Functional

SEVERITY:
Medium

REQUIREMENT:
REQ-133, REQ-150

LOCATION:
Portal mutations, staff mutations, calendar exceptions and CMS actions

ROLE:
Patient / Moderator / Admin / Power Admin

PRECONDITIONS:
API returns validation, authorization or network failure.

STEPS TO REPRODUCE:
1. Inspect async mutation handlers in PortalPage.
2. Inspect staff calendar/status/reply and CMS action handlers.
3. Force or reason about a rejected request.
4. Look for catch/onError, explanatory copy and retry/focus behavior.

EXPECTED RESULT:
Every failed action gives a meaningful explanation and recovery path without unhandled rejection.

ACTUAL RESULT:
Many handlers await requests without catch/onError and render no failure state. Examples include web cancellation, compose/reply, upload, profile save, staff replies/status changes and schedule exceptions.

STATUS:
Reproducible

EVIDENCE:
PortalPage and StaffCalendarPage source review; CMS editor load exposes the route failure rather than a recoverable workflow.

IMPACT:
Users can believe a sensitive action succeeded, lose form context or be stranded after routine API/network failures.

SUGGESTED FIX DIRECTION:
Use a shared mutation/error pattern with field errors, persistent form values, retry guidance, focus management and announced status.

---

FINDING ID:
QA-025

TITLE:
Public routes use one static metadata set and lack canonical/social discovery metadata

CATEGORY:
Content / Performance

SEVERITY:
Medium

REQUIREMENT:
REQ-016

LOCATION:
resources/views/app.blade.php; public route rendering

ROLE:
Public

PRECONDITIONS:
Load multiple public routes.

STEPS TO REPRODUCE:
1. Load Home, About, Services, Research, Academic and Education.
2. Inspect document title and meta description.
3. Inspect canonical/Open Graph metadata.
4. Request robots/sitemap assets.

EXPECTED RESULT:
Each public page has descriptive title/description, canonical and appropriate share metadata; sitemap supports discovery.

ACTUAL RESULT:
All routes retain one title and description. No canonical or Open Graph metadata is emitted and no sitemap is present.

STATUS:
Reproducible

EVIDENCE:
Multi-route IAB DOM inspection; app.blade.php; robots.txt allows crawling.

IMPACT:
Search results and social previews cannot accurately represent page content; soft 404 indexing risk is amplified.

SUGGESTED FIX DIRECTION:
Introduce route/CMS-controlled safe metadata, canonical/OG tags and a published-page sitemap with tests.

---

FINDING ID:
QA-026

TITLE:
Frontend loads global data and all route modules eagerly

CATEGORY:
Performance

SEVERITY:
Low

REQUIREMENT:
REQ-148, REQ-149

LOCATION:
App.tsx root and imports

ROLE:
Public / Patient / Staff

PRECONDITIONS:
Load any route, including authentication or staff.

STEPS TO REPRODUCE:
1. Inspect App imports and root queries.
2. Load a public route and inspect requests.
3. Load an auth/staff route and inspect the compiled entry.
4. Review production build sizes.

EXPECTED RESULT:
Routes load only necessary data/code, with splitting where it materially reduces initial cost.

ACTUAL RESULT:
Every page module is synchronously imported. App globally queries /api/public and public Layout also probes /api/me. Built JS is 405.45 KB (124.93 KB gzip).

STATUS:
Reproducible

EVIDENCE:
App.tsx lines 1-24 and 56-61; npm production build output.

IMPACT:
Users download and execute unrelated patient, staff and CMS code and make avoidable requests, especially costly on mobile networks.

SUGGESTED FIX DIRECTION:
Lazy-load route groups and scope public/identity queries to consumers; measure before/after with a defined performance budget.

---

FINDING ID:
QA-027

TITLE:
Privacy, Terms and Accessibility footer labels are not links

CATEGORY:
UI / Content

SEVERITY:
Low

REQUIREMENT:
REQ-014, REQ-015

LOCATION:
Global footer

ROLE:
Public / Patient

PRECONDITIONS:
Any page using the public Layout.

STEPS TO REPRODUCE:
1. Scroll to the footer.
2. Attempt to activate Privacy.
3. Attempt to activate Terms and Accessibility.
4. Inspect the DOM.

EXPECTED RESULT:
The labels link to complete policy/accessibility pages.

ACTUAL RESULT:
They are plain text inside footer-bottom.

STATUS:
Reproducible

EVIDENCE:
App.tsx Footer line 39; IAB footer inspection.

IMPACT:
Users cannot locate expected privacy, terms or accessibility information from a health-practice product.

SUGGESTED FIX DIRECTION:
Create approved policy pages and render real, keyboard-accessible links.

---

FINDING ID:
QA-028

TITLE:
Appointment booking omits the required document attachment step

CATEGORY:
Functional / Privacy

SEVERITY:
Medium

REQUIREMENT:
REQ-039

LOCATION:
/book; BookingPage; POST /api/appointment-requests

ROLE:
Public / Patient

PRECONDITIONS:
User completes the booking steps.

STEPS TO REPRODUCE:
1. Select service and method.
2. Select an available slot.
3. Proceed to patient details.
4. Look for a permitted-document upload and inspect request validation.

EXPECTED RESULT:
The user can attach an allowed document securely during the booking request.

ACTUAL RESULT:
The three-step form has no file control and the public appointment endpoint accepts no attachment.

STATUS:
Reproducible

EVIDENCE:
IAB /book; BookingPage steps; AppointmentRequestController validation.

IMPACT:
Patients cannot provide the required referral/report at the point of request, causing a separate manual exchange.

SUGGESTED FIX DIRECTION:
Design a privacy-minimizing pre-registration attachment workflow with quarantine/scanning, ownership transfer and retention rules.

---

FINDING ID:
QA-029

TITLE:
Patients have no reschedule request workflow

CATEGORY:
Functional / UX

SEVERITY:
Medium

REQUIREMENT:
REQ-048, REQ-141

LOCATION:
Patient portal; /api/me/appointments; mobile app

ROLE:
Patient

PRECONDITIONS:
Eligible upcoming appointment.

STEPS TO REPRODUCE:
1. Open an upcoming appointment in the web portal.
2. Look for Request reschedule.
3. Repeat in the native app.
4. Inspect self-service API routes.

EXPECTED RESULT:
The patient can request a new time subject to practice approval and availability restrictions.

ACTUAL RESULT:
Only cancellation is exposed. Staff has a direct reschedule endpoint; no patient request API or UI exists.

STATUS:
Reproducible

EVIDENCE:
routes/api.php self-service group; routes/mobile.php; PortalPage and mobile/app/appointments.tsx.

IMPACT:
Patients must leave the platform to request a time change, and the mobile appointment workflow remains incomplete.

SUGGESTED FIX DIRECTION:
Add a patient reschedule-request state/workflow with server-derived eligible slots, staff decision, audit and notifications.
