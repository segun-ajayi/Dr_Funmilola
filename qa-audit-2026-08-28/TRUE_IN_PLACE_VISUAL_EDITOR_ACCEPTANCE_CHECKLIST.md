# True In-Place Visual Website Editor — Release Acceptance Checklist

Source: Critical Requirement Addendum — True In-Place Visual Website Editor
Release classification: **RELEASE-CRITICAL**
Primary findings: **QA-002 and QA-003**
Rule: a conventional dashboard page manager, side-panel form editor, or editor that redirects away from the rendered public page does **not** satisfy this checklist.

## How to use this checklist

1. Test on a production-like staging build using disposable content and a Power Admin account.
2. For every row, record a result of `PASS`, `FAIL`, or `BLOCKED` in `REMEDIATION_PROGRESS.md` or a linked evidence file.
3. A `PASS` must include the revision, automated test name where applicable, browser/device, runtime evidence location, and a short observed result.
4. Do not infer one row from another. Test every ID independently.
5. Any unchecked, failed, blocked, or unevidenced row keeps QA-002 open and blocks release.
6. The three mandatory journeys VE-232 through VE-234 must each pass as a continuous browser session; isolated API tests are not substitutes.

Evidence record format:

| Checklist ID | Result | Revision | Automated test | Browser/device | Evidence link | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| VE-000 | PASS/FAIL/BLOCKED | commit | test name | environment | path/URL | observed result |

## A. Entry, authorization, and editor shell

- [ ] **VE-001** — A Power Admin can switch from normal View Mode to Edit Mode while on an actual rendered public page.
- [ ] **VE-002** — The Edit Mode switch does not redirect to a dashboard, page-management form, or substitute canvas.
- [ ] **VE-003** — View Mode is visually indistinguishable from the normal public site and contains no editing affordances.
- [ ] **VE-004** — Only Power Admin can see the View/Edit Mode control.
- [ ] **VE-005** — Only Power Admin can activate Edit Mode through the frontend.
- [ ] **VE-006** — Direct API attempts to enter or mutate Edit Mode are denied for Public users.
- [ ] **VE-007** — Direct API attempts to enter or mutate Edit Mode are denied for Patients.
- [ ] **VE-008** — Direct API attempts to enter or mutate Edit Mode are denied for Moderators.
- [ ] **VE-009** — Direct API attempts to enter or mutate Edit Mode are denied for Admins.
- [ ] **VE-010** — Power Admin direct API requests for authorized editor operations succeed.
- [ ] **VE-011** — Edit Mode remains active while the Power Admin navigates between editable public pages.
- [ ] **VE-012** — A persistent editor toolbar remains available without obscuring essential page content.
- [ ] **VE-013** — The persistent toolbar includes Select.
- [ ] **VE-014** — The persistent toolbar includes Add Section.
- [ ] **VE-015** — The persistent toolbar includes Undo.
- [ ] **VE-016** — The persistent toolbar includes Redo.
- [ ] **VE-017** — The persistent toolbar includes Preview.
- [ ] **VE-018** — The persistent toolbar includes Save Draft.
- [ ] **VE-019** — The persistent toolbar includes Publish.
- [ ] **VE-020** — The persistent toolbar includes Exit Edit Mode.
- [ ] **VE-021** — Editable areas are identifiable on the actual page without permanently cluttering it.
- [ ] **VE-022** — Exiting Edit Mode returns to the real public-page presentation and handles unsaved work safely.

## B. True inline text editing and typography

- [ ] **VE-023** — Double-clicking a heading edits that heading at its rendered location.
- [ ] **VE-024** — Double-clicking a paragraph edits that paragraph at its rendered location.
- [ ] **VE-025** — Double-clicking a subtitle edits that subtitle at its rendered location.
- [ ] **VE-026** — Double-clicking a label edits that label at its rendered location.
- [ ] **VE-027** — Double-clicking card text edits that text at its rendered location.
- [ ] **VE-028** — Double-clicking button text edits that text at its rendered location.
- [ ] **VE-029** — Double-clicking footer text edits that text at its rendered location.
- [ ] **VE-030** — Double-clicking CTA text edits that text at its rendered location.
- [ ] **VE-031** — The editing cursor appears inside the selected text on the page.
- [ ] **VE-032** — Typed text appears immediately in the rendered element without leaving the page.
- [ ] **VE-033** — Text edits preserve valid structured content instead of saving arbitrary whole-page HTML.
- [ ] **VE-034** — Contextual text controls expose font family.
- [ ] **VE-035** — Contextual text controls expose font size.
- [ ] **VE-036** — Contextual text controls expose font weight.
- [ ] **VE-037** — Contextual text controls expose bold.
- [ ] **VE-038** — Contextual text controls expose italic.
- [ ] **VE-039** — Contextual text controls expose underline.
- [ ] **VE-040** — Contextual text controls expose text color.
- [ ] **VE-041** — Contextual text controls expose text alignment.
- [ ] **VE-042** — Contextual text controls expose line height.
- [ ] **VE-043** — Contextual text controls expose letter spacing.
- [ ] **VE-044** — Contextual text controls expose text decoration.
- [ ] **VE-045** — Each typography change renders live on the selected element.
- [ ] **VE-046** — Typography changes save to the draft and survive a full refresh.
- [ ] **VE-047** — Typography controls can be used with a keyboard and expose accessible names and state.

## C. Images edited where they appear

- [ ] **VE-048** — Selecting an image on the rendered page selects that exact image element.
- [ ] **VE-049** — The selected image can be replaced by uploading a new image.
- [ ] **VE-050** — The selected image can be replaced from the media library.
- [ ] **VE-051** — Image alternative text can be edited.
- [ ] **VE-052** — Image caption can be added, changed, or removed.
- [ ] **VE-053** — Image crop can be adjusted.
- [ ] **VE-054** — Image width can be adjusted.
- [ ] **VE-055** — Image height can be adjusted.
- [ ] **VE-056** — Image alignment can be adjusted.
- [ ] **VE-057** — Image object-fit behavior can be adjusted.
- [ ] **VE-058** — Image border radius can be adjusted.
- [ ] **VE-059** — An image link can be added, changed, or removed.
- [ ] **VE-060** — Image overlay can be configured.
- [ ] **VE-061** — Image opacity can be adjusted.
- [ ] **VE-062** — Every image change is visibly reflected at the image's rendered location.
- [ ] **VE-063** — Image edits save to the draft and survive a full refresh.
- [ ] **VE-064** — Published image edits and alt text are visible to a logged-out visitor.
- [ ] **VE-065** — Invalid, unsafe, unscanned, or unauthorized media cannot be published.

## D. Buttons edited where they appear

- [ ] **VE-066** — Selecting a rendered button selects that exact button.
- [ ] **VE-067** — Button text can be edited in place.
- [ ] **VE-068** — Button action type can be selected.
- [ ] **VE-069** — Button destination can be an external URL.
- [ ] **VE-070** — Button destination can be an internal page or route.
- [ ] **VE-071** — Button icon can be added, changed, positioned, or removed.
- [ ] **VE-072** — Button position/alignment can be adjusted.
- [ ] **VE-073** — Button size can be adjusted.
- [ ] **VE-074** — Button typography can be adjusted.
- [ ] **VE-075** — Button background color can be adjusted.
- [ ] **VE-076** — Button text color can be adjusted.
- [ ] **VE-077** — Button border can be adjusted.
- [ ] **VE-078** — Button border radius can be adjusted.
- [ ] **VE-079** — Button internal and external spacing can be adjusted.
- [ ] **VE-080** — Button visibility can be controlled.
- [ ] **VE-081** — Button changes render live, save to draft, survive refresh, and publish correctly.
- [ ] **VE-082** — Unsafe button destinations are rejected or sanitized.

## E. Links edited where they appear

- [ ] **VE-083** — Selecting linked text selects that exact link without unintentionally navigating away.
- [ ] **VE-084** — Link display text can be edited.
- [ ] **VE-085** — Link destination can be changed.
- [ ] **VE-086** — An internal-page link can be selected.
- [ ] **VE-087** — An external URL can be entered and validated.
- [ ] **VE-088** — An email link can be configured.
- [ ] **VE-089** — A telephone link can be configured.
- [ ] **VE-090** — Same-tab or new-tab behavior can be selected safely.
- [ ] **VE-091** — A link can be removed without deleting its text.
- [ ] **VE-092** — Link changes render live, save to draft, survive refresh, and publish correctly.

## F. Sections, layout, and responsive presentation

- [ ] **VE-093** — Hovering a section reveals a subtle section boundary or identifier.
- [ ] **VE-094** — Section controls do not permanently distort the public design.
- [ ] **VE-095** — A whole section can be selected independently of its child elements.
- [ ] **VE-096** — Section background color can be edited.
- [ ] **VE-097** — Section background gradient can be edited.
- [ ] **VE-098** — Section background image can be edited.
- [ ] **VE-099** — Section background pattern can be edited.
- [ ] **VE-100** — Section breast-contour visual treatment can be edited where supported by the design system.
- [ ] **VE-101** — Section overlay can be edited.
- [ ] **VE-102** — Section padding can be edited.
- [ ] **VE-103** — Section margins can be edited.
- [ ] **VE-104** — Section width can be edited.
- [ ] **VE-105** — Section minimum height can be edited.
- [ ] **VE-106** — Section maximum height can be edited.
- [ ] **VE-107** — Section layout type can be edited.
- [ ] **VE-108** — Section column count or column structure can be edited.
- [ ] **VE-109** — Section content alignment can be edited.
- [ ] **VE-110** — Section gap can be edited.
- [ ] **VE-111** — Section border can be edited.
- [ ] **VE-112** — Section border radius can be edited.
- [ ] **VE-113** — Section shadow can be edited.
- [ ] **VE-114** — Section visibility can be edited.
- [ ] **VE-115** — Section presentation values can be set independently for desktop.
- [ ] **VE-116** — Section presentation values can be set independently for tablet.
- [ ] **VE-117** — Section presentation values can be set independently for mobile.
- [ ] **VE-118** — Responsive changes are visible in the matching editor preview.
- [ ] **VE-119** — A section can be moved up.
- [ ] **VE-120** — A section can be moved down.
- [ ] **VE-121** — A section can be reordered by drag-and-drop.
- [ ] **VE-122** — A section can be duplicated.
- [ ] **VE-123** — A section can be hidden without being destructively deleted.
- [ ] **VE-124** — A section can be deleted with a safe confirmation or undo path.
- [ ] **VE-125** — A section's properties can be reopened and edited.
- [ ] **VE-126** — Section operations render immediately on the actual page.
- [ ] **VE-127** — Section order and properties save to draft, survive refresh, and publish correctly.

## G. Add Section component library

- [ ] **VE-128** — Add Section opens a component library from the actual page.
- [ ] **VE-129** — The library can add a Hero section.
- [ ] **VE-130** — The library can add a Rich Text section.
- [ ] **VE-131** — The library can add an Image section.
- [ ] **VE-132** — The library can add a Text + Image section.
- [ ] **VE-133** — The library can add a Cards section.
- [ ] **VE-134** — The library can add a Services section.
- [ ] **VE-135** — The library can add a CTA section.
- [ ] **VE-136** — The library can add a Publications section.
- [ ] **VE-137** — The library can add a Career Timeline section.
- [ ] **VE-138** — The library can add an Achievements section.
- [ ] **VE-139** — The library can add an FAQ section.
- [ ] **VE-140** — The library can add a Gallery section.
- [ ] **VE-141** — The library can add a Statistics section.
- [ ] **VE-142** — The library can add a Contact section.
- [ ] **VE-143** — The library can add an Appointment Widget section.
- [ ] **VE-144** — The library can add a Video section.
- [ ] **VE-145** — The library can add a Divider section.
- [ ] **VE-146** — The library can add a Spacer section.
- [ ] **VE-147** — The chosen section appears immediately at the intended location on the actual page.
- [ ] **VE-148** — Every newly inserted component can be selected, edited, reordered, saved, refreshed, previewed, and published.

## H. Nested selection

- [ ] **VE-149** — The editor distinguishes the selected page.
- [ ] **VE-150** — The editor distinguishes the selected section.
- [ ] **VE-151** — The editor distinguishes the selected card or other nested component.
- [ ] **VE-152** — The editor distinguishes a selected image.
- [ ] **VE-153** — The editor distinguishes a selected heading.
- [ ] **VE-154** — The editor distinguishes a selected paragraph.
- [ ] **VE-155** — The editor distinguishes a selected button.
- [ ] **VE-156** — A visible selection breadcrumb communicates the current hierarchy, such as Page > Section > Card > Element.
- [ ] **VE-157** — Selection can move between parent and child without accidentally editing or deleting the wrong level.

## I. Navigation edited visually

- [ ] **VE-158** — Public navigation can be selected and edited from its rendered location.
- [ ] **VE-159** — A navigation item can be renamed.
- [ ] **VE-160** — A navigation item can target an internal page.
- [ ] **VE-161** — A navigation item can target an external URL.
- [ ] **VE-162** — A navigation item can be hidden.
- [ ] **VE-163** — A hidden navigation item can be shown again.
- [ ] **VE-164** — A navigation item can be deleted safely.
- [ ] **VE-165** — Navigation items can be reordered.
- [ ] **VE-166** — A navigation item can be added.
- [ ] **VE-167** — A submenu can be created and edited.
- [ ] **VE-168** — Draft navigation changes remain private until publication.
- [ ] **VE-169** — Published navigation changes are visible and functional for logged-out visitors.

## J. New-page workflow

- [ ] **VE-170** — A Power Admin can create a page with a page name.
- [ ] **VE-171** — A page slug can be entered or safely generated and collision-validated.
- [ ] **VE-172** — A blank page can be selected as the starting point.
- [ ] **VE-173** — A template can be selected as the starting point.
- [ ] **VE-174** — The new page opens on the actual rendered site for visual editing.
- [ ] **VE-175** — The new page can be previewed before publication.
- [ ] **VE-176** — The new page can be published.
- [ ] **VE-177** — The new page can be linked from the editable navigation.
- [ ] **VE-178** — Invalid or duplicate slugs cannot overwrite or shadow existing routes.

## K. Drafts, preview, publication, versioning, and recovery

- [ ] **VE-179** — Draft content is stored separately from published content.
- [ ] **VE-180** — A saved draft is not visible to logged-out visitors.
- [ ] **VE-181** — A Power Admin can continue editing an existing draft.
- [ ] **VE-182** — A Power Admin can save a draft manually.
- [ ] **VE-183** — A Power Admin can discard draft changes safely.
- [ ] **VE-184** — A Power Admin can preview the exact current draft.
- [ ] **VE-185** — Preview supports a desktop viewport.
- [ ] **VE-186** — Preview supports a tablet viewport.
- [ ] **VE-187** — Preview supports a mobile viewport.
- [ ] **VE-188** — Preview is private, access-controlled, and cannot be treated as the public published URL.
- [ ] **VE-189** — Only a Power Admin can publish through the frontend.
- [ ] **VE-190** — Only a Power Admin can publish through the API.
- [ ] **VE-191** — Publication atomically makes the intended version public without exposing partial edits.
- [ ] **VE-192** — Each publication retains the immediately previous published version.
- [ ] **VE-193** — Rollback restores a previous published version.
- [ ] **VE-194** — Rolled-back content is visible to logged-out visitors after refresh.
- [ ] **VE-195** — Undo reverses the most recent edit in the current session.
- [ ] **VE-196** — Redo reapplies the most recently undone edit in the current session.
- [ ] **VE-197** — Undo/redo handles text, style, image, section, and order changes without corrupting the document.
- [ ] **VE-198** — Draft history is retained if implemented and accurately identifies versions and actors.
- [ ] **VE-199** — Autosave runs periodically if enabled.
- [ ] **VE-200** — The editor clearly indicates unsaved work.
- [ ] **VE-201** — The editor clearly indicates save-in-progress and save-success states.
- [ ] **VE-202** — Save failure is clearly shown and never reported as success.
- [ ] **VE-203** — A save failure preserves recoverable local edits and offers retry or manual save.
- [ ] **VE-204** — Refresh after a confirmed draft save restores the exact saved text, styles, media, structure, and responsive values.
- [ ] **VE-205** — Concurrent Power Admin edits are conflict-detected or safely merged; silent overwrites do not occur.

## L. Real media library

- [ ] **VE-206** — The editor uses a persistent media library rather than an upload field that loses reusable assets.
- [ ] **VE-207** — A Power Admin can upload an approved media asset.
- [ ] **VE-208** — A Power Admin can browse existing media.
- [ ] **VE-209** — A Power Admin can search media.
- [ ] **VE-210** — An existing asset can be reused without duplicate upload.
- [ ] **VE-211** — Media metadata can be viewed and maintained.
- [ ] **VE-212** — Alternative text is required or an explicit decorative choice is recorded where appropriate.
- [ ] **VE-213** — Media metadata, alt text, and references survive refresh and publication.
- [ ] **VE-214** — Media authorization, privacy, validation, malware scanning, failure handling, and deletion/reference safety pass.

## M. Structured model, safety, accessibility, and resilience

- [ ] **VE-215** — The persisted model represents Page.
- [ ] **VE-216** — The persisted model represents Section.
- [ ] **VE-217** — The persisted model represents Component.
- [ ] **VE-218** — The persisted model represents Element.
- [ ] **VE-219** — The persisted model represents Props.
- [ ] **VE-220** — The persisted model represents Styles.
- [ ] **VE-221** — The persisted model represents Responsive Styles.
- [ ] **VE-222** — The persisted model distinguishes Draft from Published.
- [ ] **VE-223** — The system does not persist arbitrary whole-page HTML as its primary editing model.
- [ ] **VE-224** — Structured content and style schemas are versioned and migration-tested.
- [ ] **VE-225** — Rich text, URLs, media, CSS-like values, and component props are allow-listed, validated, and safely rendered.
- [ ] **VE-226** — XSS, unsafe URL, malicious media, and invalid-schema tests fail closed.
- [ ] **VE-227** — Editor controls are keyboard operable with visible focus.
- [ ] **VE-228** — Editor controls expose accessible names, roles, states, errors, and announcements.
- [ ] **VE-229** — Editor overlays and controls remain usable at desktop, tablet, and mobile editor widths.
- [ ] **VE-230** — Network interruption, session expiry, validation error, and server error do not cause false success or silent data loss.
- [ ] **VE-231** — Audit records identify actor, page, action, before/after version, and publication/rollback events without recording secrets.

## N. Mandatory continuous acceptance journeys

- [ ] **VE-232 — Exact text journey:** Log in as Power Admin; open the actual public website; enter Edit Mode; double-click a visible heading; change the text; change its font styling; save the draft; fully refresh; confirm the draft text and styling persist; preview it; publish it; log out; confirm the public page shows the exact published result.
- [ ] **VE-233 — Exact image journey:** Log in as Power Admin; open the actual public website; enter Edit Mode; select a visible image where it appears; replace it; update its alt text; save the draft; fully refresh; confirm both persist; preview; publish; log out; confirm the public page shows the replacement and correct alt text.
- [ ] **VE-234 — Exact section journey:** Log in as Power Admin; open the actual public website; enter Edit Mode; add a section from the component library on that page; edit its content and presentation; drag it to a new order; save the draft; fully refresh; confirm content, presentation, and order persist; preview; publish; log out; confirm the public page shows the exact result.
- [ ] **VE-235 — Exact rollback journey:** After VE-232, VE-233, or VE-234 is published, roll back to the previous published version and confirm the logged-out public page exactly returns to the previous state.
- [ ] **VE-236 — Authorization journey:** Repeat UI-route and direct-API editor entry, save, preview, publish, media, navigation, and rollback attempts as Public, Patient, Moderator, and Admin; every attempt is denied without mutation, while Power Admin succeeds.

## O. Release closure gate

- [ ] **VE-237** — Every checklist ID VE-001 through VE-236 has one evidence record and a fresh independent result.
- [ ] **VE-238** — No checklist row is `FAIL`, `BLOCKED`, unchecked, or supported only by an isolated mock/unit test where runtime behavior is required.
- [ ] **VE-239** — QA-002 is not marked PASS until VE-001 through VE-238 pass.
- [ ] **VE-240** — QA-003 is not marked PASS until draft isolation, rendered navigation/theme publication, preview, version retention, and rollback rows pass.
- [ ] **VE-241** — The public renderer, editor renderer, preview renderer, and published logged-out result are visually and structurally consistent for the mandatory journeys.
- [ ] **VE-242** — Independent QA confirms that the implementation is a true in-place editor on the actual website, not a conventional dashboard manager or substitute canvas.
- [ ] **VE-243** — Independent QA issues the visual-editor release recommendation only after all preceding rows pass.

Expected machine-check set: exactly `VE-001` through `VE-243`, with no gaps or duplicates.
