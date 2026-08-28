# Requirements Traceability Matrix

Audit date: 28 August 2026  
Baseline: docs/MASTER_PLAN.md (the repository's declared source of truth) plus the supplied Independent QA, Testing & Requirements Audit charter.  
Status vocabulary: PASS, PARTIAL, FAIL, MISSING, NOT TESTABLE, NOT APPLICABLE, BLOCKED.

PASS is used only where a test, safe runtime observation, or direct implementation evidence supports the result. BLOCKED means the CMS defect prevented the dependent workflow from being exercised. NOT TESTABLE identifies external infrastructure, unavailable browsers/devices, or an authenticated viewport that was not safely available during this audit.

| Requirement ID | Requirement | Expected Behavior | Actual Implementation | Status | Evidence | Finding ID |
| --- | --- | --- | --- | --- | --- | --- |
| REQ-001 | Public homepage | A complete specialist-practice homepage renders | Published CMS homepage renders with coherent sections and calls to action | PASS | IAB runtime at /; 200 response; no ordinary-use console error | — |
| REQ-002 | Public navigation | All primary navigation works at every supported width | Desktop links work; collapsed navigation cannot be opened below 992 px | FAIL | 390 px runtime: toggle click left #nav hidden | QA-018 |
| REQ-003 | About page | Verified professional biography is available | Sparse CMS page; biography, education, appointments and training remain pending | PARTIAL | Runtime /about; CoreCmsPageSeeder | QA-012 |
| REQ-004 | Professional profile | Complete, sourced profile is public | Name, specialty, location and limited association copy only | PARTIAL | /about and /api/academic/profile | QA-012 |
| REQ-005 | Career timeline | Sourced career entries are publicly browsable | API/model exists; no published seeded career entry or complete public journey | MISSING | /api/academic/profile returned no career items | QA-012 |
| REQ-006 | Achievements | Sourced achievements/fellowships are public | Data model and queue target exist; no complete public achievements display | MISSING | Empty academic profile payload | QA-012 |
| REQ-007 | Services | All active services and details are shown | CMS services page replaces dynamic six-card listing with one summary section | PARTIAL | Runtime /services; six active services in API | QA-012 |
| REQ-008 | Contact | Public contact page and contact details are available | /contact resolves to the homepage; no contact route/page | MISSING | IAB runtime /contact | QA-011, QA-012 |
| REQ-009 | Research | Verified research portfolio is usable | Research introduction exists; key publication journey remains incomplete | PARTIAL | Runtime /research and /academic | QA-012, QA-013 |
| REQ-010 | Patient education | Published reviewed education can be read in full | Listing/empty state exists; cards have no detail navigation | PARTIAL | EducationPage; runtime /education | QA-012 |
| REQ-011 | Publications | Verified publications list with complete metadata and links | Search/filter list exists, but publishing state and UI controls are inconsistent | PARTIAL | AcademicContentController; runtime /academic | QA-012, QA-013, QA-014 |
| REQ-012 | Appointment entry points | Clear booking calls to action work | Homepage, navigation and services links reach /book | PASS | IAB link navigation and route inspection | — |
| REQ-013 | Online-consultation entry | Eligible users can enter a real consultation workflow | Online option and workflow shell exist; live media is unavailable | PARTIAL | Capabilities live_video=false; provider readiness false | QA-010 |
| REQ-014 | Footer | Functional practice, legal and accessibility destinations | Practice links work; Privacy, Terms and Accessibility are plain text | PARTIAL | App.tsx Footer | QA-027 |
| REQ-015 | Public links | No dead or misleading public destinations | /contact and arbitrary paths silently show Home; legal labels are not links | PARTIAL | Runtime route sweep | QA-011, QA-027 |
| REQ-016 | SEO basics | Per-route title, description, canonical and social metadata | One static title/description; no canonical/Open Graph or sitemap | FAIL | app.blade.php and multi-route DOM inspection | QA-025 |
| REQ-017 | Error pages | Invalid URLs show a meaningful 404 and recovery path | Catch-all renders the homepage at HTTP 200 | FAIL | Runtime /this-route-does-not-exist | QA-011 |
| REQ-018 | No placeholder functionality | Public-facing capabilities represent real behavior | Consultation is explicitly unconfigured; portrait is a generated placeholder in fallback content | PARTIAL | Video provider and App.tsx fallback | QA-010 |
| REQ-019 | Ordinary-use console stability | Normal public use produces no console errors | No console errors observed on tested public routes | PASS | IAB console inspection | — |
| REQ-020 | Valid registration | Patient can register with valid data | Implemented and feature-tested | PASS | Identity feature tests | — |
| REQ-021 | Invalid registration email | Invalid email is rejected | Validation present and tested | PASS | RegisterController tests | — |
| REQ-022 | Duplicate registration email | Duplicate account is handled safely | Duplicate is rejected, though response reveals account existence | PASS | Identity tests and controller behavior | — |
| REQ-023 | Password strength | Weak passwords are rejected | 10-character mixed-case, number, symbol and uncompromised rule | PASS | Reset/Register validation tests | — |
| REQ-024 | Registration required fields | Missing fields are rejected meaningfully | Server validation and frontend required controls exist | PASS | Feature tests and AuthPage | — |
| REQ-025 | Email verification | Verification and resend workflow work | Verified middleware and signed verification route are tested | PASS | Identity feature tests | — |
| REQ-026 | Login | Valid login succeeds; invalid login fails | Cookie session login and role sessions worked | PASS | Runtime role matrix and tests | — |
| REQ-027 | Logout | Logout invalidates current session | Implemented and tested | PASS | LogoutController tests | — |
| REQ-028 | Password reset | Reset works and revokes all active access | Reset revokes API tokens but leaves active browser sessions intact | PARTIAL | ResetPasswordController lines 22-25 | QA-021 |
| REQ-029 | Patient dashboard identity | Correct patient's identity is shown | Self-scoped /me and profile data render | PASS | Runtime patient session; tests | — |
| REQ-030 | Upcoming and past appointments | Patient sees own appointment history | Paginated self-owned list is implemented | PASS | /api/me/appointments and isolation tests | — |
| REQ-031 | Patient notifications | Patient sees and can read notifications | Web list/read mutation exists | PASS | NotificationController and portal UI | — |
| REQ-032 | Patient messages | Patient can create threads and reply | Web create/reply/list workflow exists and is scoped | PASS | MessageThreadController tests | — |
| REQ-033 | Patient documents | Patient can upload/list/download private files | Web workflow, private authorization and validation exist | PASS | PatientDocumentController and tests | — |
| REQ-034 | Patient profile settings | Patient can edit minimum necessary profile | Web profile update exists and is scoped | PASS | PatientProfileController tests | — |
| REQ-035 | Booking service selection | User selects an active service | Step-one service options render from API | PASS | IAB /book; BookingPage | — |
| REQ-036 | Booking consultation method | Only service-supported methods are accepted | UI disables unsupported online option, but API accepts a forged value | FAIL | 201 for online Breast Surgery request | QA-004 |
| REQ-037 | Booking date and slots | User sees server-generated available slots | Availability endpoint produced four Monday slots and UI displayed selection step | PASS | GET /api/availability/1 runtime | — |
| REQ-038 | Booking reason | Required reason is validated | 10–2000 character validation implemented | PASS | AppointmentRequestController line 23 | — |
| REQ-039 | Booking document attachment | Permitted document may accompany booking | Booking form and public request API accept no file | MISSING | BookingPage; AppointmentRequestController | QA-028 |
| REQ-040 | Booking confirmation | Successful request returns a reference and message | 201 response contains message and UUID reference | PASS | Isolated runtime requests | — |
| REQ-041 | Unavailable slot rejection | API rejects times outside current availability | Saturday early-morning request outside all rules was accepted | FAIL | 201, reference 9146817e-7939-4c79-8f00-08bbd708e3ce | QA-004 |
| REQ-042 | Past date prevention | Past bookings are rejected | UI minimum and server future check exist | PASS | Controller and tests | — |
| REQ-043 | Sequential conflict prevention | Overlapping submitted appointment is rejected | 03:15 overlap was rejected with 422 | PASS | Isolated runtime request and tests | — |
| REQ-044 | Concurrent conflict prevention | Simultaneous overlap cannot double-book | Check-then-insert has no lock or database exclusion/unique protection | FAIL | AppointmentRequestController lines 31-36; schema | QA-005 |
| REQ-045 | Appointment isolation | Patient A cannot access Patient B appointment | Foreign appointment request returned 403 | PASS | Runtime manipulated ID; PatientIsolationTest | — |
| REQ-046 | Other patient-data isolation | Profile, documents, messages and consultations are owner-scoped | Policies/queries and authorization matrix cover each domain | PASS | Backend authorization/isolation tests | — |
| REQ-047 | Patient cancellation | Eligible patient can request and retry after decline | First request works; declined record is permanently reused | FAIL | Isolated decline/resubmit workflow | QA-007 |
| REQ-048 | Patient reschedule | Patient can request a reschedule | Only staff can directly reschedule; no patient reschedule request workflow | MISSING | Routes and portal UI | QA-029 |
| REQ-049 | Reminder preferences | Patient can manage reminder channels/timing | Web preferences endpoint and page exist | PASS | NotificationPreferenceController tests | — |
| REQ-050 | Accurate status display | Appointment and cancellation states are accurately shown | Declined cancellation still reads requested; reschedule becomes confirmed | FAIL | PortalPage and workflow runtime/code | QA-007, QA-008 |
| REQ-051 | Moderator dashboard | Moderator can access operational dashboard | Runtime /api/staff/dashboard returned 200 | PASS | Isolated role session | — |
| REQ-052 | Moderator appointment operations | Confirm, status and reschedule are role-authorized | Staff endpoints and tests allow moderator | PASS | AuthorizationMatrixTest and runtime | — |
| REQ-053 | Moderator calendar | Moderator has a complete operational calendar | Bounded list exists; required calendar interactions do not | PARTIAL | StaffCalendarPage | QA-009 |
| REQ-054 | Moderator patient lookup | Minimum-necessary patient search works | Search and scoped patient context exist | PASS | Staff endpoints and UI | — |
| REQ-055 | Moderator availability management | Recurring rules and exceptions are manageable | APIs cover both; UI exposes exceptions only | PARTIAL | Routes and StaffCalendarPage | QA-006, QA-009 |
| REQ-056 | Moderator communication | Moderator can reply to patients | Staff inbox reply is implemented | PASS | InboxController tests | — |
| REQ-057 | Moderator consultation workflow | Moderator can safely operate online consultations | State transitions exist; no live provider | PARTIAL | Staff consultation tests and provider state | QA-010 |
| REQ-058 | Moderator power restriction | Moderator cannot reach Power Admin CMS | CMS request returned 403 | PASS | Runtime role matrix | — |
| REQ-059 | Admin operational functions | Admin can manage operations end to end | Dashboard/appointments/inbox work; calendar, consultation and patient management incomplete | PARTIAL | Runtime and UI inspection | QA-009, QA-010, QA-015 |
| REQ-060 | Admin patient management | Admin can create/edit/manage patients | Search and read-only context only; no patient management API/UI | MISSING | Route inventory and StaffDashboardPage | QA-015 |
| REQ-061 | Admin content management where required | Admin can manage permitted non-structural content | Content APIs are Power Admin-only; no admin-scoped content workflow | MISSING | routes/api.php CMS group | QA-015 |
| REQ-062 | Admin structural restriction | Admin cannot access Power Admin CMS | Admin CMS request returned 403 | PASS | Runtime role matrix | — |
| REQ-063 | Power Admin access | Power Admin alone can access CMS APIs | Power Admin list returned 200; other roles returned 403 | PASS | Runtime role matrix | — |
| REQ-064 | Power Admin account and role management | Power Admin can manage users/roles with audit | No account/role management endpoints or UI | MISSING | Route inventory | QA-015 |
| REQ-065 | Activate Edit Mode | Power Admin can enable a real editing mode | Toggle only opens a link panel | PARTIAL | App.tsx Layout | QA-002 |
| REQ-066 | Navigate while editing | Public site remains navigable with contextual editing | Public navigation works on desktop; editing redirects to separate manager | PARTIAL | App.tsx Layout | QA-002 |
| REQ-067 | Select editable elements | Editable public elements can be selected | No editable overlays/selection mechanism | MISSING | Frontend inspection | QA-002 |
| REQ-068 | Double-click paragraph editing | Paragraph opens inline editor on double-click | No double-click or contenteditable behavior | MISSING | Frontend inspection | QA-002 |
| REQ-069 | Inline text editing | Public text can be edited in place | Separate heading/text fields only | MISSING | CmsEditorPage | QA-002 |
| REQ-070 | Font family, size and weight | Power Admin can change typography | No controls or persisted presentation fields in UI | MISSING | CmsEditorPage | QA-002 |
| REQ-071 | Bold, italic and underline | Rich-text emphasis is editable | No rich-text editor | MISSING | CmsEditorPage | QA-002 |
| REQ-072 | Alignment, color, line height and spacing | Required text presentation can be configured | No controls | MISSING | CmsEditorPage | QA-002 |
| REQ-073 | Inline link editing | Links can be added and edited | No generic link editor | MISSING | CmsEditorPage | QA-002 |
| REQ-074 | CMS save draft | Edits persist without publishing | Backend update exists, but editor cannot load selected page by ID | BLOCKED | /api/cms/pages/1 returned 404 | QA-001 |
| REQ-075 | CMS preview | Draft can be previewed safely | Backend preview exists, but normal editor journey is blocked | BLOCKED | Route collision on page load | QA-001 |
| REQ-076 | CMS publish | Draft can be published from editor | Publish backend exists; UI cannot load selected page | BLOCKED | Route collision on page load | QA-001 |
| REQ-077 | Image editing | Replace/upload/library/alt/crop/dimensions/alignment/radius/link | Managed media and image editing UI are absent | MISSING | CmsEditorPage; MASTER_PLAN implementation note | QA-002 |
| REQ-078 | Button editing | Text, URL, internal route, style, icon and visibility | No button editor | MISSING | CmsEditorPage | QA-002 |
| REQ-079 | Menu editing | Rename/add/delete/reorder/submenu/destination/hide | Flat settings API only; no management UI and public menu is hard-coded | MISSING | App.tsx and SettingController | QA-002, QA-003 |
| REQ-080 | Page creation | Power Admin can create a page | Create form exists; subsequent loading/editing is broken | PARTIAL | CmsEditorPage and QA runtime | QA-001, QA-002 |
| REQ-081 | Page rename, slug, SEO, unpublish and duplicate | Full page lifecycle is manageable | No UI for these operations and no unpublish/duplicate endpoint | MISSING | CMS routes and UI | QA-002 |
| REQ-082 | Section add/remove/reorder | Sections can be structurally managed | Backend/UI operations exist, but selected page load is blocked | BLOCKED | CMS route collision | QA-001 |
| REQ-083 | Section duplicate/hide/background/spacing | Complete section presentation management | Fields/backend support are partial; controls are absent | MISSING | CmsEditorPage | QA-002 |
| REQ-084 | Version history and rollback | Power Admin can inspect and restore versions | Backend endpoints exist; no usable UI and page load is broken | PARTIAL | routes/api.php; CmsEditorPage | QA-001, QA-002 |
| REQ-085 | Published navigation/theme effect | Published settings change the public website | Public Layout uses hard-coded links/colors and never reads settings | FAIL | App.tsx; /api/cms/public-settings unused | QA-003 |
| REQ-086 | CMS role restriction | CMS APIs enforce verified Power Admin | Middleware works in runtime matrix | PASS | 403 moderator/admin; 200 Power Admin list | — |
| REQ-087 | Appointment state model | Required states are represented | Enum/transition map contains all listed states | PASS | AppointmentStatus and workflow service | — |
| REQ-088 | State transition enforcement | Valid transitions work; invalid ones fail | Transition service and feature tests enforce map | PASS | AppointmentWorkflowTest | — |
| REQ-089 | Rescheduled state semantics | A moved appointment displays Rescheduled accurately | Reschedule writes Confirmed; Rescheduled is terminal and unused | FAIL | AppointmentWorkflowService line 64 | QA-008 |
| REQ-090 | Recurring availability | Weekly availability drives slot generation | Active weekday rules are queried | PASS | AvailabilityService and tests | — |
| REQ-091 | Closures, leave and blocked periods | Closed exceptions remove overlapping slots | Implemented and tested | PASS | AvailabilityService and SchedulingFeatureTest | — |
| REQ-092 | Slot interval and buffers | Configured slot_minutes and buffer are honored | buffer_minutes is used; slot_minutes is ignored | PARTIAL | AvailabilityService line 28 | QA-006 |
| REQ-093 | Special clinic days | Additional exceptions create slots | Additional-clinic generation exists and is tested | PASS | AvailabilityService lines 40-44 | — |
| REQ-094 | Online/in-person availability | Service and rule constraints are enforced on submission | Availability GET filters rules; POST bypasses rule/service constraints | FAIL | Isolated 201 unsupported online request | QA-004 |
| REQ-095 | Day/week/month calendar views | Usable calendar views exist | Three range buttons alter a chronological list; no grid calendar | PARTIAL | StaffCalendarPage | QA-009 |
| REQ-096 | Agenda calendar view | Agenda view exists | No agenda option | MISSING | StaffCalendarPage View union | QA-009 |
| REQ-097 | Calendar create/edit/drag/reschedule | Staff can manipulate appointments in calendar | No such controls; only exception add/delete | MISSING | StaffCalendarPage | QA-009 |
| REQ-098 | Calendar filtering/search/status | Filters by patient/type/status and clear status visibility | Status badges exist; filters and patient search are absent | PARTIAL | StaffCalendarPage | QA-009 |
| REQ-099 | Authenticated calendar responsiveness | Calendar is usable desktop/tablet/mobile | Full authenticated responsive calendar sweep was not safely completed | NOT TESTABLE | Audit scope limitation | QA-009 |
| REQ-100 | Consultation authentication/ownership | Only related authenticated users enter consultation | Policies and tests cover unrelated user denial | PASS | OnlineConsultationTest | — |
| REQ-101 | Waiting room, consent and signed expiry | Consent/state/signed short-lived access work | Backend workflow and expiry/authorization tests pass | PASS | OnlineConsultationTest | — |
| REQ-102 | Patient/staff consultation workflow | Both roles can progress a real consultation | State shell works; provider is not ready | PARTIAL | Consultation controllers and UI | QA-010 |
| REQ-103 | Live online consultation | Audio/video room is fully functional | Default provider intentionally supplies no live media | FAIL | UnconfiguredVideoProvider; mobile capability false | QA-010 |
| REQ-104 | Sourced professional claims | Claims are verified before public release | Queue exists; current public profile copy is sparse and not fully institutionally confirmed | PARTIAL | ORCID/UHN/PubMed review | QA-014 |
| REQ-105 | Publication listing | Verified records are public and complete | Academic list exists; general public feed expects an unused status | PARTIAL | AcademicContentController vs PublicContentController | QA-013 |
| REQ-106 | Publication search/filter | Search and category filtering work | API/UI provide both | PASS | AcademicPortfolioPage and controller | — |
| REQ-107 | Publication sort/pagination/detail | Sort, pagination and detail journey are exposed | API supports sort/pagination/detail; UI lacks controls/detail route | FAIL | AcademicPortfolioPage and App routes | QA-012 |
| REQ-108 | DOI and external links | DOI and source links are usable | UI renders neither DOI nor external link | FAIL | AcademicPortfolioPage | QA-012 |
| REQ-109 | Verification queue retention | Claim, source title/URL and status are retained | ResearchClaim workflow stores and displays them | PASS | Seeder, model, queue tests | — |
| REQ-110 | Rejected information remains non-public | Rejection cannot leave a published record public | Published claim can later be marked rejected; public record is not retracted | FAIL | VerificationQueueController lines 6-7 | QA-013 |
| REQ-111 | Seed research enters review queue | Seed records are reviewable before publication | Six Publication rows are inserted directly as pending without source metadata | FAIL | DatabaseSeeder lines 52-60 | QA-014 |
| REQ-112 | Education review metadata | Published article has reviewer/date/disclaimer | Backend validation and public display support this | PASS | Education feature tests | — |
| REQ-113 | Authentication and role access control | Protected APIs enforce identity and role | Runtime patient/moderator/admin/Power Admin matrix matched policy | PASS | Isolated role sessions | — |
| REQ-114 | IDOR/privacy protection | Sensitive records cannot be accessed by foreign IDs | No cross-patient access reproduced | PASS | Runtime 403 and isolation tests | — |
| REQ-115 | CSRF protection | Cookie-auth mutations require CSRF protection | Sanctum cookie flow and middleware are configured | PASS | Runtime session setup and web middleware | — |
| REQ-116 | Security headers | Browser responses set defensive headers | CSP, frame denial, nosniff, referrer, permissions and opener/resource policies present | PASS | Runtime response headers | — |
| REQ-117 | Rate limiting | Sensitive/high-volume endpoints are throttled | Auth, booking, upload, preview, join and audit limits exist | PASS | Route inventory | — |
| REQ-118 | Secrets and production debug | Secrets are untracked; production debug is off | .env ignored; production example uses APP_DEBUG=false | PASS | Git/config inspection | — |
| REQ-119 | Upload MIME/extension/size validation | PDF/JPEG/PNG up to 10 MB only | Laravel file/mimes/max validation present | PASS | PatientDocumentController line 20 and tests | — |
| REQ-120 | Private document access | Stored files are private and owner/staff authorized | Storage path hidden; download ownership check exists | PASS | Controller and authorization tests | — |
| REQ-121 | Renamed/oversize/direct URL upload tests | Safe adversarial upload cases are exercised | Some automated validation/privacy coverage; full runtime matrix not executed | PARTIAL | UploadSecurityTest review | QA-022 |
| REQ-122 | Production malware scanning | Approved malware analysis gates uploads | Only an 8 KB in-process signature check is bound | FAIL | BasicFileScanner; MASTER_PLAN warning | QA-022 |
| REQ-123 | Password-reset session management | Reset invalidates active browser and device sessions | API tokens revoked; database sessions remain active | FAIL | ResetPasswordController | QA-021 |
| REQ-124 | Audit important actions | Appointment, patient, CMS, publishing, roles and settings actions are logged | Broad coverage exists, but several required actions and all role changes are absent | PARTIAL | AuditLog::create inventory | QA-023 |
| REQ-125 | Audit old/new values | Actor/action/resource/time and old/new are retained where needed | Core fields exist; metadata is inconsistent and often lacks before/after | PARTIAL | Audit controllers/services | QA-023 |
| REQ-126 | Production backup/readiness | PostgreSQL/Redis/workers/backups verified in deployed environment | Artifacts exist; no external production infrastructure was available | NOT TESTABLE | Audit scope limitation | — |
| REQ-127 | Semantic structure/headings | Pages use meaningful landmarks and heading structure | Public DOM had header/nav/main/footer and ordered primary heading | PASS | IAB DOM inspection | — |
| REQ-128 | Keyboard navigation and visible focus | All controls are keyboard operable with visible focus | No skip link; patient search explicitly removes outline with no replacement | PARTIAL | CSS and sign-in DOM | QA-019 |
| REQ-129 | Programmatic form labels | Every form control has an accessible name/label | Sign-in email/password/checkbox and many other controls have no association/name | FAIL | IAB DOM; source inspection | QA-019 |
| REQ-130 | Accessible button names | Icon/compact controls expose stable names | Many controls are named; some staff icon buttons rely on title only | PARTIAL | Source inspection | QA-019 |
| REQ-131 | Image alternative text | Meaningful images have editable appropriate alt text | Current CMS page has no real image; image alt is optional and editor cannot manage it | PARTIAL | CMS renderer/editor inspection | QA-002, QA-019 |
| REQ-132 | Color contrast | Small text meets WCAG AA contrast | Rose on ivory is 4.17:1; inactive progress text on white is 2.81:1 | FAIL | Token calculation from app.css | QA-020 |
| REQ-133 | Accessible errors and status updates | Errors are associated/announced with recovery guidance | Some alerts exist; many async errors are absent and no live-region strategy is present | PARTIAL | UI source inspection | QA-019, QA-024 |
| REQ-134 | Public responsive widths | Public pages avoid overflow at all specified widths | Homepage showed no horizontal overflow from 320–1920 px | PASS | IAB nine-width sweep | — |
| REQ-135 | Responsive public navigation | Mobile menu opens and is touch usable | Toggle is dead because Bootstrap JS/state is absent | FAIL | 390 px runtime and imports | QA-018 |
| REQ-136 | Responsive booking | Booking remains usable at representative mobile widths | No horizontal overflow observed; step content remained accessible | PASS | IAB mobile /book review | — |
| REQ-137 | Responsive calendar | Authenticated calendar usable desktop/tablet/mobile | Authenticated full-width sweep not completed and calendar itself is incomplete | NOT TESTABLE | Audit limitation | QA-009 |
| REQ-138 | Responsive Power Admin controls | Edit controls usable across widths | Editor load defect prevents meaningful responsive workflow testing | BLOCKED | QA-001 | QA-001, QA-002 |
| REQ-139 | Touch usability | Primary mobile controls are reachable and operable | Content/forms are touch-sized, but navigation blocks most public journeys | PARTIAL | 390 px runtime | QA-018 |
| REQ-140 | Native secure session | Mobile token stored/restored securely with expiry/revoke | SecureStore/session logic and mobile auth tests pass | PASS | Mobile source and tests | — |
| REQ-141 | Native appointments and cancellation | App supports correct appointment actions | Read/list and cancellation work; invalid statuses still show cancel, no reschedule | PARTIAL | mobile/app/appointments.tsx | QA-017, QA-029 |
| REQ-142 | Native profile/documents/messages/notifications | Core patient features are actionable, not read-only | All are read-only except viewing; v1 API exposes no mutations/transfers | PARTIAL | routes/mobile.php and native screens | QA-016 |
| REQ-143 | Native consultation | Consent/wait/join/leave workflow is available | Status list only; live video and workflow mutations absent | PARTIAL | native consultations screen; v1 routes | QA-010, QA-016 |
| REQ-144 | Native reminder/device management | Preferences and device/session controls are present | No screens or v1 routes | MISSING | mobile route/screen inventory | QA-016 |
| REQ-145 | Native push notifications | Push registration/delivery works | Capability false; registration returns 409 | MISSING | MobilePatientController | QA-016 |
| REQ-146 | Chromium-family browser behavior | Available in-app Chromium runtime works for public flows | Public route/viewport tests completed | PASS | IAB audit | — |
| REQ-147 | Chrome/Edge/Firefox/Safari coverage | Browser-specific behavior is verified | Those separate engines were unavailable/not exercised | NOT TESTABLE | Environment limitation | — |
| REQ-148 | Avoid unnecessary requests | Each route fetches only required data | App requests /api/public globally and Layout requests /api/me on public routes | PARTIAL | App.tsx lines 28 and 57 | QA-026 |
| REQ-149 | Frontend bundle efficiency | Route code is split and proportionate | All pages are synchronous imports; JS 405.45 KB / 124.93 KB gzip | PARTIAL | Production build output | QA-026 |
| REQ-150 | Loading, empty and failure states | Every workflow communicates progress, emptiness, failure and recovery | Empty/loading states are generally clear; multiple mutations omit failure handling | PARTIAL | UI review | QA-024 |

## Requirements Missing Entirely

The following requirements have no meaningful end-to-end implementation in the audited build:

- REQ-005 — Career timeline
- REQ-006 — Achievements
- REQ-008 — Contact page
- REQ-039 — Booking document attachment
- REQ-048 — Patient reschedule request
- REQ-060 — Admin patient management
- REQ-061 — Admin content management where required
- REQ-064 — Power Admin account and role management
- REQ-067 through REQ-073 — Visual selection, inline/rich-text and typography editing
- REQ-077 through REQ-079 — Image, button and menu editing
- REQ-081 — Full page lifecycle management
- REQ-083 — Complete section presentation management
- REQ-096 and REQ-097 — Agenda and interactive calendar operations
- REQ-144 and REQ-145 — Native reminder/device management and push notifications

## Evidence limitations

- Testing used an isolated seeded SQLite audit database and the PHP development server with production-like debug disabled. No production system or real patient data was accessed.
- Browser runtime coverage used the Codex in-app Chromium browser. Separate Chrome, Edge, Firefox and Safari/WebKit runs were unavailable.
- No physical Android/iOS device, signed store build, real SMTP/SMS/push provider, video provider, PostgreSQL/Redis deployment, malware service or backup infrastructure was available.
- Medical/browser safety constraints prevented submitting patient-care forms through browser automation; equivalent requests were sent only to the isolated local API.
