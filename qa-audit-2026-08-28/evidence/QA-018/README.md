# QA-018 evidence

Status: READY FOR RETEST — implementation `d90a2af`.

- Controlled mobile disclosure exposes `aria-controls` and accurate `aria-expanded` state.
- Escape closes the menu and returns focus to its toggle; route navigation closes the menu and focuses `#main-content`.
- CMS submenus implement the same controlled disclosure and Escape/focus-return behavior.
- Mobile/coarse-pointer navigation controls use a 44 px minimum target.
- `AdminNavigation.test.tsx` covers open/close, Escape, focus return, submenu state, skip navigation and route focus.

Independent 320/375/390/414/768 px touch-device and browser acceptance remains required before PASS.
