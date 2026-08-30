# True In-Place Visual Editor — Row-by-Row Gap Matrix

Prepared: 30 August 2026  
Scope: QA-002, QA-003, REQ-065 through REQ-085, and VE-001 through VE-243  
Source: `TRUE_IN_PLACE_VISUAL_EDITOR_ACCEPTANCE_CHECKLIST.md`

## Status rules

- **EXISTING EVIDENCE — REVERIFY** means an existing implementation may contribute but is not a fresh PASS under the reviewed contract.
- **PARTIAL — REBUILD/EXTEND** means related code exists but does not satisfy the exact actual-page interaction or complete journey.
- **MISSING** means no qualifying implementation was found.
- **PENDING ACCEPTANCE** covers continuous journeys and independent release gates; these cannot pass through source inspection alone.
- No row in this matrix is a PASS. PASS remains reserved for fresh automated and runtime evidence, followed by independent QA where required.

## Delivery slices

| Slice | Outcome |
| --- | --- |
| S1 | Structured editor document, actual-page shell, atomic draft/preview/publish/version/rollback, undo/redo bridge, authorization and audit foundation |
| S2 | True cursor-in-element text editing plus typography, buttons and links |
| S3 | Section presentation/responsive controls, all 18 component types, hierarchy, direct operations and drag reorder |
| S4 | Rendered navigation editing and actual-site blank/template page creation workflow |
| S5 | Persistent scanned media library and in-place image editing |
| S6 | Save recovery, conflicts, accessibility, responsive editor widths and failure-path resilience |
| S7 | VE-232–VE-236 uninterrupted journeys, complete regression, row evidence and independent QA handoff |

## Atomic inventory

| ID | Current assessment | Slice | Required behavior | Current gap / evidence rule |
| --- | --- | --- | --- | --- |
| VE-001 | EXISTING EVIDENCE — REVERIFY | S1 | A Power Admin can switch from normal View Mode to Edit Mode while on an actual rendered public page. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-002 | EXISTING EVIDENCE — REVERIFY | S1 | The Edit Mode switch does not redirect to a dashboard, page-management form, or substitute canvas. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-003 | EXISTING EVIDENCE — REVERIFY | S1 | View Mode is visually indistinguishable from the normal public site and contains no editing affordances. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-004 | EXISTING EVIDENCE — REVERIFY | S1 | Only Power Admin can see the View/Edit Mode control. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-005 | PARTIAL — REBUILD/EXTEND | S1 | Only Power Admin can activate Edit Mode through the frontend. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-006 | PARTIAL — REBUILD/EXTEND | S1 | Direct API attempts to enter or mutate Edit Mode are denied for Public users. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-007 | PARTIAL — REBUILD/EXTEND | S1 | Direct API attempts to enter or mutate Edit Mode are denied for Patients. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-008 | PARTIAL — REBUILD/EXTEND | S1 | Direct API attempts to enter or mutate Edit Mode are denied for Moderators. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-009 | PARTIAL — REBUILD/EXTEND | S1 | Direct API attempts to enter or mutate Edit Mode are denied for Admins. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-010 | EXISTING EVIDENCE — REVERIFY | S1 | Power Admin direct API requests for authorized editor operations succeed. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-011 | EXISTING EVIDENCE — REVERIFY | S1 | Edit Mode remains active while the Power Admin navigates between editable public pages. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-012 | MISSING | S1 | A persistent editor toolbar remains available without obscuring essential page content. | No qualifying actual-page implementation or evidence was found. |
| VE-013 | MISSING | S1 | The persistent toolbar includes Select. | No qualifying actual-page implementation or evidence was found. |
| VE-014 | MISSING | S1 | The persistent toolbar includes Add Section. | No qualifying actual-page implementation or evidence was found. |
| VE-015 | MISSING | S1 | The persistent toolbar includes Undo. | No qualifying actual-page implementation or evidence was found. |
| VE-016 | MISSING | S1 | The persistent toolbar includes Redo. | No qualifying actual-page implementation or evidence was found. |
| VE-017 | MISSING | S1 | The persistent toolbar includes Preview. | No qualifying actual-page implementation or evidence was found. |
| VE-018 | MISSING | S1 | The persistent toolbar includes Save Draft. | No qualifying actual-page implementation or evidence was found. |
| VE-019 | MISSING | S1 | The persistent toolbar includes Publish. | No qualifying actual-page implementation or evidence was found. |
| VE-020 | MISSING | S1 | The persistent toolbar includes Exit Edit Mode. | No qualifying actual-page implementation or evidence was found. |
| VE-021 | EXISTING EVIDENCE — REVERIFY | S1 | Editable areas are identifiable on the actual page without permanently cluttering it. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-022 | PARTIAL — REBUILD/EXTEND | S1 | Exiting Edit Mode returns to the real public-page presentation and handles unsaved work safely. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-023 | PARTIAL — REBUILD/EXTEND | S2 | Double-clicking a heading edits that heading at its rendered location. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-024 | PARTIAL — REBUILD/EXTEND | S2 | Double-clicking a paragraph edits that paragraph at its rendered location. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-025 | MISSING | S2 | Double-clicking a subtitle edits that subtitle at its rendered location. | No qualifying actual-page implementation or evidence was found. |
| VE-026 | MISSING | S2 | Double-clicking a label edits that label at its rendered location. | No qualifying actual-page implementation or evidence was found. |
| VE-027 | MISSING | S2 | Double-clicking card text edits that text at its rendered location. | No qualifying actual-page implementation or evidence was found. |
| VE-028 | MISSING | S2 | Double-clicking button text edits that text at its rendered location. | No qualifying actual-page implementation or evidence was found. |
| VE-029 | MISSING | S2 | Double-clicking footer text edits that text at its rendered location. | No qualifying actual-page implementation or evidence was found. |
| VE-030 | MISSING | S2 | Double-clicking CTA text edits that text at its rendered location. | No qualifying actual-page implementation or evidence was found. |
| VE-031 | MISSING | S2 | The editing cursor appears inside the selected text on the page. | No qualifying actual-page implementation or evidence was found. |
| VE-032 | PARTIAL — REBUILD/EXTEND | S2 | Typed text appears immediately in the rendered element without leaving the page. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-033 | EXISTING EVIDENCE — REVERIFY | S2 | Text edits preserve valid structured content instead of saving arbitrary whole-page HTML. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-034 | MISSING | S2 | Contextual text controls expose font family. | No qualifying actual-page implementation or evidence was found. |
| VE-035 | MISSING | S2 | Contextual text controls expose font size. | No qualifying actual-page implementation or evidence was found. |
| VE-036 | MISSING | S2 | Contextual text controls expose font weight. | No qualifying actual-page implementation or evidence was found. |
| VE-037 | PARTIAL — REBUILD/EXTEND | S2 | Contextual text controls expose bold. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-038 | PARTIAL — REBUILD/EXTEND | S2 | Contextual text controls expose italic. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-039 | PARTIAL — REBUILD/EXTEND | S2 | Contextual text controls expose underline. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-040 | MISSING | S2 | Contextual text controls expose text color. | No qualifying actual-page implementation or evidence was found. |
| VE-041 | MISSING | S2 | Contextual text controls expose text alignment. | No qualifying actual-page implementation or evidence was found. |
| VE-042 | MISSING | S2 | Contextual text controls expose line height. | No qualifying actual-page implementation or evidence was found. |
| VE-043 | MISSING | S2 | Contextual text controls expose letter spacing. | No qualifying actual-page implementation or evidence was found. |
| VE-044 | MISSING | S2 | Contextual text controls expose text decoration. | No qualifying actual-page implementation or evidence was found. |
| VE-045 | MISSING | S2 | Each typography change renders live on the selected element. | No qualifying actual-page implementation or evidence was found. |
| VE-046 | PARTIAL — REBUILD/EXTEND | S2 | Typography changes save to the draft and survive a full refresh. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-047 | PARTIAL — REBUILD/EXTEND | S2 | Typography controls can be used with a keyboard and expose accessible names and state. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-048 | MISSING | S5 | Selecting an image on the rendered page selects that exact image element. | No qualifying actual-page implementation or evidence was found. |
| VE-049 | MISSING | S5 | The selected image can be replaced by uploading a new image. | No qualifying actual-page implementation or evidence was found. |
| VE-050 | MISSING | S5 | The selected image can be replaced from the media library. | No qualifying actual-page implementation or evidence was found. |
| VE-051 | MISSING | S5 | Image alternative text can be edited. | No qualifying actual-page implementation or evidence was found. |
| VE-052 | MISSING | S5 | Image caption can be added, changed, or removed. | No qualifying actual-page implementation or evidence was found. |
| VE-053 | MISSING | S5 | Image crop can be adjusted. | No qualifying actual-page implementation or evidence was found. |
| VE-054 | MISSING | S5 | Image width can be adjusted. | No qualifying actual-page implementation or evidence was found. |
| VE-055 | MISSING | S5 | Image height can be adjusted. | No qualifying actual-page implementation or evidence was found. |
| VE-056 | MISSING | S5 | Image alignment can be adjusted. | No qualifying actual-page implementation or evidence was found. |
| VE-057 | MISSING | S5 | Image object-fit behavior can be adjusted. | No qualifying actual-page implementation or evidence was found. |
| VE-058 | MISSING | S5 | Image border radius can be adjusted. | No qualifying actual-page implementation or evidence was found. |
| VE-059 | MISSING | S5 | An image link can be added, changed, or removed. | No qualifying actual-page implementation or evidence was found. |
| VE-060 | MISSING | S5 | Image overlay can be configured. | No qualifying actual-page implementation or evidence was found. |
| VE-061 | MISSING | S5 | Image opacity can be adjusted. | No qualifying actual-page implementation or evidence was found. |
| VE-062 | MISSING | S5 | Every image change is visibly reflected at the image's rendered location. | No qualifying actual-page implementation or evidence was found. |
| VE-063 | PARTIAL — REBUILD/EXTEND | S5 | Image edits save to the draft and survive a full refresh. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-064 | PARTIAL — REBUILD/EXTEND | S5 | Published image edits and alt text are visible to a logged-out visitor. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-065 | MISSING | S5 | Invalid, unsafe, unscanned, or unauthorized media cannot be published. | No qualifying actual-page implementation or evidence was found. |
| VE-066 | MISSING | S2 | Selecting a rendered button selects that exact button. | No qualifying actual-page implementation or evidence was found. |
| VE-067 | PARTIAL — REBUILD/EXTEND | S2 | Button text can be edited in place. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-068 | MISSING | S2 | Button action type can be selected. | No qualifying actual-page implementation or evidence was found. |
| VE-069 | PARTIAL — REBUILD/EXTEND | S2 | Button destination can be an external URL. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-070 | PARTIAL — REBUILD/EXTEND | S2 | Button destination can be an internal page or route. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-071 | MISSING | S2 | Button icon can be added, changed, positioned, or removed. | No qualifying actual-page implementation or evidence was found. |
| VE-072 | MISSING | S2 | Button position/alignment can be adjusted. | No qualifying actual-page implementation or evidence was found. |
| VE-073 | PARTIAL — REBUILD/EXTEND | S2 | Button size can be adjusted. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-074 | PARTIAL — REBUILD/EXTEND | S2 | Button typography can be adjusted. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-075 | PARTIAL — REBUILD/EXTEND | S2 | Button background color can be adjusted. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-076 | PARTIAL — REBUILD/EXTEND | S2 | Button text color can be adjusted. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-077 | MISSING | S2 | Button border can be adjusted. | No qualifying actual-page implementation or evidence was found. |
| VE-078 | MISSING | S2 | Button border radius can be adjusted. | No qualifying actual-page implementation or evidence was found. |
| VE-079 | MISSING | S2 | Button internal and external spacing can be adjusted. | No qualifying actual-page implementation or evidence was found. |
| VE-080 | PARTIAL — REBUILD/EXTEND | S2 | Button visibility can be controlled. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-081 | PARTIAL — REBUILD/EXTEND | S2 | Button changes render live, save to draft, survive refresh, and publish correctly. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-082 | EXISTING EVIDENCE — REVERIFY | S2 | Unsafe button destinations are rejected or sanitized. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-083 | PARTIAL — REBUILD/EXTEND | S2 | Selecting linked text selects that exact link without unintentionally navigating away. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-084 | PARTIAL — REBUILD/EXTEND | S2 | Link display text can be edited. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-085 | PARTIAL — REBUILD/EXTEND | S2 | Link destination can be changed. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-086 | PARTIAL — REBUILD/EXTEND | S2 | An internal-page link can be selected. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-087 | PARTIAL — REBUILD/EXTEND | S2 | An external URL can be entered and validated. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-088 | MISSING | S2 | An email link can be configured. | No qualifying actual-page implementation or evidence was found. |
| VE-089 | MISSING | S2 | A telephone link can be configured. | No qualifying actual-page implementation or evidence was found. |
| VE-090 | PARTIAL — REBUILD/EXTEND | S2 | Same-tab or new-tab behavior can be selected safely. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-091 | PARTIAL — REBUILD/EXTEND | S2 | A link can be removed without deleting its text. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-092 | PARTIAL — REBUILD/EXTEND | S2 | Link changes render live, save to draft, survive refresh, and publish correctly. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-093 | EXISTING EVIDENCE — REVERIFY | S3 | Hovering a section reveals a subtle section boundary or identifier. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-094 | EXISTING EVIDENCE — REVERIFY | S3 | Section controls do not permanently distort the public design. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-095 | EXISTING EVIDENCE — REVERIFY | S3 | A whole section can be selected independently of its child elements. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-096 | MISSING | S3 | Section background color can be edited. | No qualifying actual-page implementation or evidence was found. |
| VE-097 | MISSING | S3 | Section background gradient can be edited. | No qualifying actual-page implementation or evidence was found. |
| VE-098 | MISSING | S3 | Section background image can be edited. | No qualifying actual-page implementation or evidence was found. |
| VE-099 | MISSING | S3 | Section background pattern can be edited. | No qualifying actual-page implementation or evidence was found. |
| VE-100 | MISSING | S3 | Section breast-contour visual treatment can be edited where supported by the design system. | No qualifying actual-page implementation or evidence was found. |
| VE-101 | MISSING | S3 | Section overlay can be edited. | No qualifying actual-page implementation or evidence was found. |
| VE-102 | MISSING | S3 | Section padding can be edited. | No qualifying actual-page implementation or evidence was found. |
| VE-103 | MISSING | S3 | Section margins can be edited. | No qualifying actual-page implementation or evidence was found. |
| VE-104 | MISSING | S3 | Section width can be edited. | No qualifying actual-page implementation or evidence was found. |
| VE-105 | MISSING | S3 | Section minimum height can be edited. | No qualifying actual-page implementation or evidence was found. |
| VE-106 | MISSING | S3 | Section maximum height can be edited. | No qualifying actual-page implementation or evidence was found. |
| VE-107 | MISSING | S3 | Section layout type can be edited. | No qualifying actual-page implementation or evidence was found. |
| VE-108 | MISSING | S3 | Section column count or column structure can be edited. | No qualifying actual-page implementation or evidence was found. |
| VE-109 | MISSING | S3 | Section content alignment can be edited. | No qualifying actual-page implementation or evidence was found. |
| VE-110 | MISSING | S3 | Section gap can be edited. | No qualifying actual-page implementation or evidence was found. |
| VE-111 | MISSING | S3 | Section border can be edited. | No qualifying actual-page implementation or evidence was found. |
| VE-112 | MISSING | S3 | Section border radius can be edited. | No qualifying actual-page implementation or evidence was found. |
| VE-113 | MISSING | S3 | Section shadow can be edited. | No qualifying actual-page implementation or evidence was found. |
| VE-114 | MISSING | S3 | Section visibility can be edited. | No qualifying actual-page implementation or evidence was found. |
| VE-115 | MISSING | S3 | Section presentation values can be set independently for desktop. | No qualifying actual-page implementation or evidence was found. |
| VE-116 | MISSING | S3 | Section presentation values can be set independently for tablet. | No qualifying actual-page implementation or evidence was found. |
| VE-117 | MISSING | S3 | Section presentation values can be set independently for mobile. | No qualifying actual-page implementation or evidence was found. |
| VE-118 | MISSING | S3 | Responsive changes are visible in the matching editor preview. | No qualifying actual-page implementation or evidence was found. |
| VE-119 | PARTIAL — REBUILD/EXTEND | S3 | A section can be moved up. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-120 | PARTIAL — REBUILD/EXTEND | S3 | A section can be moved down. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-121 | MISSING | S3 | A section can be reordered by drag-and-drop. | No qualifying actual-page implementation or evidence was found. |
| VE-122 | PARTIAL — REBUILD/EXTEND | S3 | A section can be duplicated. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-123 | PARTIAL — REBUILD/EXTEND | S3 | A section can be hidden without being destructively deleted. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-124 | PARTIAL — REBUILD/EXTEND | S3 | A section can be deleted with a safe confirmation or undo path. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-125 | PARTIAL — REBUILD/EXTEND | S3 | A section's properties can be reopened and edited. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-126 | PARTIAL — REBUILD/EXTEND | S3 | Section operations render immediately on the actual page. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-127 | PARTIAL — REBUILD/EXTEND | S3 | Section order and properties save to draft, survive refresh, and publish correctly. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-128 | MISSING | S3 | Add Section opens a component library from the actual page. | No qualifying actual-page implementation or evidence was found. |
| VE-129 | MISSING | S3 | The library can add a Hero section. | No qualifying actual-page implementation or evidence was found. |
| VE-130 | MISSING | S3 | The library can add a Rich Text section. | No qualifying actual-page implementation or evidence was found. |
| VE-131 | MISSING | S3 | The library can add an Image section. | No qualifying actual-page implementation or evidence was found. |
| VE-132 | MISSING | S3 | The library can add a Text + Image section. | No qualifying actual-page implementation or evidence was found. |
| VE-133 | MISSING | S3 | The library can add a Cards section. | No qualifying actual-page implementation or evidence was found. |
| VE-134 | MISSING | S3 | The library can add a Services section. | No qualifying actual-page implementation or evidence was found. |
| VE-135 | MISSING | S3 | The library can add a CTA section. | No qualifying actual-page implementation or evidence was found. |
| VE-136 | MISSING | S3 | The library can add a Publications section. | No qualifying actual-page implementation or evidence was found. |
| VE-137 | MISSING | S3 | The library can add a Career Timeline section. | No qualifying actual-page implementation or evidence was found. |
| VE-138 | MISSING | S3 | The library can add an Achievements section. | No qualifying actual-page implementation or evidence was found. |
| VE-139 | MISSING | S3 | The library can add an FAQ section. | No qualifying actual-page implementation or evidence was found. |
| VE-140 | MISSING | S3 | The library can add a Gallery section. | No qualifying actual-page implementation or evidence was found. |
| VE-141 | MISSING | S3 | The library can add a Statistics section. | No qualifying actual-page implementation or evidence was found. |
| VE-142 | MISSING | S3 | The library can add a Contact section. | No qualifying actual-page implementation or evidence was found. |
| VE-143 | MISSING | S3 | The library can add an Appointment Widget section. | No qualifying actual-page implementation or evidence was found. |
| VE-144 | MISSING | S3 | The library can add a Video section. | No qualifying actual-page implementation or evidence was found. |
| VE-145 | MISSING | S3 | The library can add a Divider section. | No qualifying actual-page implementation or evidence was found. |
| VE-146 | MISSING | S3 | The library can add a Spacer section. | No qualifying actual-page implementation or evidence was found. |
| VE-147 | MISSING | S3 | The chosen section appears immediately at the intended location on the actual page. | No qualifying actual-page implementation or evidence was found. |
| VE-148 | MISSING | S3 | Every newly inserted component can be selected, edited, reordered, saved, refreshed, previewed, and published. | No qualifying actual-page implementation or evidence was found. |
| VE-149 | PARTIAL — REBUILD/EXTEND | S3 | The editor distinguishes the selected page. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-150 | PARTIAL — REBUILD/EXTEND | S3 | The editor distinguishes the selected section. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-151 | MISSING | S3 | The editor distinguishes the selected card or other nested component. | No qualifying actual-page implementation or evidence was found. |
| VE-152 | PARTIAL — REBUILD/EXTEND | S3 | The editor distinguishes a selected image. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-153 | PARTIAL — REBUILD/EXTEND | S3 | The editor distinguishes a selected heading. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-154 | PARTIAL — REBUILD/EXTEND | S3 | The editor distinguishes a selected paragraph. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-155 | PARTIAL — REBUILD/EXTEND | S3 | The editor distinguishes a selected button. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-156 | PARTIAL — REBUILD/EXTEND | S3 | A visible selection breadcrumb communicates the current hierarchy, such as Page > Section > Card > Element. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-157 | PARTIAL — REBUILD/EXTEND | S3 | Selection can move between parent and child without accidentally editing or deleting the wrong level. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-158 | PARTIAL — REBUILD/EXTEND | S4 | Public navigation can be selected and edited from its rendered location. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-159 | PARTIAL — REBUILD/EXTEND | S4 | A navigation item can be renamed. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-160 | PARTIAL — REBUILD/EXTEND | S4 | A navigation item can target an internal page. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-161 | PARTIAL — REBUILD/EXTEND | S4 | A navigation item can target an external URL. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-162 | PARTIAL — REBUILD/EXTEND | S4 | A navigation item can be hidden. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-163 | PARTIAL — REBUILD/EXTEND | S4 | A hidden navigation item can be shown again. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-164 | PARTIAL — REBUILD/EXTEND | S4 | A navigation item can be deleted safely. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-165 | PARTIAL — REBUILD/EXTEND | S4 | Navigation items can be reordered. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-166 | PARTIAL — REBUILD/EXTEND | S4 | A navigation item can be added. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-167 | PARTIAL — REBUILD/EXTEND | S4 | A submenu can be created and edited. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-168 | EXISTING EVIDENCE — REVERIFY | S4 | Draft navigation changes remain private until publication. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-169 | EXISTING EVIDENCE — REVERIFY | S4 | Published navigation changes are visible and functional for logged-out visitors. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-170 | PARTIAL — REBUILD/EXTEND | S4 | A Power Admin can create a page with a page name. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-171 | PARTIAL — REBUILD/EXTEND | S4 | A page slug can be entered or safely generated and collision-validated. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-172 | MISSING | S4 | A blank page can be selected as the starting point. | No qualifying actual-page implementation or evidence was found. |
| VE-173 | MISSING | S4 | A template can be selected as the starting point. | No qualifying actual-page implementation or evidence was found. |
| VE-174 | PARTIAL — REBUILD/EXTEND | S4 | The new page opens on the actual rendered site for visual editing. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-175 | PARTIAL — REBUILD/EXTEND | S4 | The new page can be previewed before publication. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-176 | PARTIAL — REBUILD/EXTEND | S4 | The new page can be published. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-177 | PARTIAL — REBUILD/EXTEND | S4 | The new page can be linked from the editable navigation. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-178 | PARTIAL — REBUILD/EXTEND | S4 | Invalid or duplicate slugs cannot overwrite or shadow existing routes. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-179 | EXISTING EVIDENCE — REVERIFY | S1 | Draft content is stored separately from published content. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-180 | EXISTING EVIDENCE — REVERIFY | S1 | A saved draft is not visible to logged-out visitors. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-181 | EXISTING EVIDENCE — REVERIFY | S1 | A Power Admin can continue editing an existing draft. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-182 | EXISTING EVIDENCE — REVERIFY | S1 | A Power Admin can save a draft manually. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-183 | PARTIAL — REBUILD/EXTEND | S1 | A Power Admin can discard draft changes safely. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-184 | PARTIAL — REBUILD/EXTEND | S1 | A Power Admin can preview the exact current draft. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-185 | MISSING | S1 | Preview supports a desktop viewport. | No qualifying actual-page implementation or evidence was found. |
| VE-186 | MISSING | S1 | Preview supports a tablet viewport. | No qualifying actual-page implementation or evidence was found. |
| VE-187 | MISSING | S1 | Preview supports a mobile viewport. | No qualifying actual-page implementation or evidence was found. |
| VE-188 | PARTIAL — REBUILD/EXTEND | S1 | Preview is private, access-controlled, and cannot be treated as the public published URL. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-189 | EXISTING EVIDENCE — REVERIFY | S1 | Only a Power Admin can publish through the frontend. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-190 | EXISTING EVIDENCE — REVERIFY | S1 | Only a Power Admin can publish through the API. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-191 | EXISTING EVIDENCE — REVERIFY | S1 | Publication atomically makes the intended version public without exposing partial edits. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-192 | EXISTING EVIDENCE — REVERIFY | S1 | Each publication retains the immediately previous published version. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-193 | PARTIAL — REBUILD/EXTEND | S1 | Rollback restores a previous published version. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-194 | MISSING | S1 | Rolled-back content is visible to logged-out visitors after refresh. | No qualifying actual-page implementation or evidence was found. |
| VE-195 | PARTIAL — REBUILD/EXTEND | S1 | Undo reverses the most recent edit in the current session. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-196 | PARTIAL — REBUILD/EXTEND | S1 | Redo reapplies the most recently undone edit in the current session. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-197 | PARTIAL — REBUILD/EXTEND | S1 | Undo/redo handles text, style, image, section, and order changes without corrupting the document. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-198 | EXISTING EVIDENCE — REVERIFY | S1 | Draft history is retained if implemented and accurately identifies versions and actors. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-199 | MISSING | S6 | Autosave runs periodically if enabled. | No qualifying actual-page implementation or evidence was found. |
| VE-200 | PARTIAL — REBUILD/EXTEND | S6 | The editor clearly indicates unsaved work. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-201 | PARTIAL — REBUILD/EXTEND | S6 | The editor clearly indicates save-in-progress and save-success states. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-202 | EXISTING EVIDENCE — REVERIFY | S6 | Save failure is clearly shown and never reported as success. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-203 | PARTIAL — REBUILD/EXTEND | S6 | A save failure preserves recoverable local edits and offers retry or manual save. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-204 | PARTIAL — REBUILD/EXTEND | S6 | Refresh after a confirmed draft save restores the exact saved text, styles, media, structure, and responsive values. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-205 | EXISTING EVIDENCE — REVERIFY | S6 | Concurrent Power Admin edits are conflict-detected or safely merged; silent overwrites do not occur. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-206 | MISSING | S5 | The editor uses a persistent media library rather than an upload field that loses reusable assets. | No qualifying actual-page implementation or evidence was found. |
| VE-207 | MISSING | S5 | A Power Admin can upload an approved media asset. | No qualifying actual-page implementation or evidence was found. |
| VE-208 | MISSING | S5 | A Power Admin can browse existing media. | No qualifying actual-page implementation or evidence was found. |
| VE-209 | MISSING | S5 | A Power Admin can search media. | No qualifying actual-page implementation or evidence was found. |
| VE-210 | MISSING | S5 | An existing asset can be reused without duplicate upload. | No qualifying actual-page implementation or evidence was found. |
| VE-211 | MISSING | S5 | Media metadata can be viewed and maintained. | No qualifying actual-page implementation or evidence was found. |
| VE-212 | MISSING | S5 | Alternative text is required or an explicit decorative choice is recorded where appropriate. | No qualifying actual-page implementation or evidence was found. |
| VE-213 | MISSING | S5 | Media metadata, alt text, and references survive refresh and publication. | No qualifying actual-page implementation or evidence was found. |
| VE-214 | MISSING | S5 | Media authorization, privacy, validation, malware scanning, failure handling, and deletion/reference safety pass. | No qualifying actual-page implementation or evidence was found. |
| VE-215 | EXISTING EVIDENCE — REVERIFY | S1 | The persisted model represents Page. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-216 | EXISTING EVIDENCE — REVERIFY | S1 | The persisted model represents Section. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-217 | MISSING | S1 | The persisted model represents Component. | No qualifying actual-page implementation or evidence was found. |
| VE-218 | MISSING | S1 | The persisted model represents Element. | No qualifying actual-page implementation or evidence was found. |
| VE-219 | EXISTING EVIDENCE — REVERIFY | S1 | The persisted model represents Props. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-220 | EXISTING EVIDENCE — REVERIFY | S1 | The persisted model represents Styles. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-221 | MISSING | S1 | The persisted model represents Responsive Styles. | No qualifying actual-page implementation or evidence was found. |
| VE-222 | EXISTING EVIDENCE — REVERIFY | S1 | The persisted model distinguishes Draft from Published. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-223 | EXISTING EVIDENCE — REVERIFY | S1 | The system does not persist arbitrary whole-page HTML as its primary editing model. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-224 | PARTIAL — REBUILD/EXTEND | S1 | Structured content and style schemas are versioned and migration-tested. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-225 | PARTIAL — REBUILD/EXTEND | S1 | Rich text, URLs, media, CSS-like values, and component props are allow-listed, validated, and safely rendered. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-226 | PARTIAL — REBUILD/EXTEND | S1 | XSS, unsafe URL, malicious media, and invalid-schema tests fail closed. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-227 | PARTIAL — REBUILD/EXTEND | S6 | Editor controls are keyboard operable with visible focus. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-228 | PARTIAL — REBUILD/EXTEND | S6 | Editor controls expose accessible names, roles, states, errors, and announcements. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-229 | PARTIAL — REBUILD/EXTEND | S6 | Editor overlays and controls remain usable at desktop, tablet, and mobile editor widths. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-230 | PARTIAL — REBUILD/EXTEND | S6 | Network interruption, session expiry, validation error, and server error do not cause false success or silent data loss. | Related code exists, but is incomplete, dashboard-bound, modal-based, or lacks the exact persistence/runtime journey. |
| VE-231 | EXISTING EVIDENCE — REVERIFY | S6 | Audit records identify actor, page, action, before/after version, and publication/rollback events without recording secrets. | Existing behavior or coverage may contribute, but needs fresh proof against the strict actual-page contract. |
| VE-232 | PENDING ACCEPTANCE | S7 | Log in as Power Admin; open the actual public website; enter Edit Mode; double-click a visible heading; change the text; change its font styling; save the draft; fully refresh; confirm the draft text and styling persist; preview it; publish it; log out; confirm the public page shows the exact published result. | Requires completed slices plus uninterrupted and independent runtime acceptance. |
| VE-233 | PENDING ACCEPTANCE | S7 | Log in as Power Admin; open the actual public website; enter Edit Mode; select a visible image where it appears; replace it; update its alt text; save the draft; fully refresh; confirm both persist; preview; publish; log out; confirm the public page shows the replacement and correct alt text. | Requires completed slices plus uninterrupted and independent runtime acceptance. |
| VE-234 | PENDING ACCEPTANCE | S7 | Log in as Power Admin; open the actual public website; enter Edit Mode; add a section from the component library on that page; edit its content and presentation; drag it to a new order; save the draft; fully refresh; confirm content, presentation, and order persist; preview; publish; log out; confirm the public page shows the exact result. | Requires completed slices plus uninterrupted and independent runtime acceptance. |
| VE-235 | PENDING ACCEPTANCE | S7 | After VE-232, VE-233, or VE-234 is published, roll back to the previous published version and confirm the logged-out public page exactly returns to the previous state. | Requires completed slices plus uninterrupted and independent runtime acceptance. |
| VE-236 | PENDING ACCEPTANCE | S7 | Repeat UI-route and direct-API editor entry, save, preview, publish, media, navigation, and rollback attempts as Public, Patient, Moderator, and Admin; every attempt is denied without mutation, while Power Admin succeeds. | Requires completed slices plus uninterrupted and independent runtime acceptance. |
| VE-237 | PENDING ACCEPTANCE | S7 | Every checklist ID VE-001 through VE-236 has one evidence record and a fresh independent result. | Requires completed slices plus uninterrupted and independent runtime acceptance. |
| VE-238 | PENDING ACCEPTANCE | S7 | No checklist row is `FAIL`, `BLOCKED`, unchecked, or supported only by an isolated mock/unit test where runtime behavior is required. | Requires completed slices plus uninterrupted and independent runtime acceptance. |
| VE-239 | PENDING ACCEPTANCE | S7 | QA-002 is not marked PASS until VE-001 through VE-238 pass. | Requires completed slices plus uninterrupted and independent runtime acceptance. |
| VE-240 | PENDING ACCEPTANCE | S7 | QA-003 is not marked PASS until draft isolation, rendered navigation/theme publication, preview, version retention, and rollback rows pass. | Requires completed slices plus uninterrupted and independent runtime acceptance. |
| VE-241 | PENDING ACCEPTANCE | S7 | The public renderer, editor renderer, preview renderer, and published logged-out result are visually and structurally consistent for the mandatory journeys. | Requires completed slices plus uninterrupted and independent runtime acceptance. |
| VE-242 | PENDING ACCEPTANCE | S7 | Independent QA confirms that the implementation is a true in-place editor on the actual website, not a conventional dashboard manager or substitute canvas. | Requires completed slices plus uninterrupted and independent runtime acceptance. |
| VE-243 | PENDING ACCEPTANCE | S7 | Independent QA issues the visual-editor release recommendation only after all preceding rows pass. | Requires completed slices plus uninterrupted and independent runtime acceptance. |

## Integrity check

This matrix contains exactly one row for every identifier from VE-001 through VE-243. It must be updated with commit, test, environment, route and observed result as each row is implemented and freshly verified. Release rows VE-237 through VE-243 remain independent gates and cannot be self-certified by implementation work alone.

