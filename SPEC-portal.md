# University portal implementation

The user confirmed both administrator and student portals with working database forms. Preserve existing PHP/MySQL, account login, palette, typography, and data. No sample personal records or legacy passwords are imported.

| Module | Responsibility | Dependencies |
|---|---|---|
| portal-shell | Responsive navigation, headings, feedback, tables, forms | Existing authentication |
| academic-records | Courses, subjects, sections, schedules | portal-shell |
| student-records | Student profiles and account links | academic-records |
| accounts | Administrator/student accounts, password changes | student-records |
| enrollment | Student subject selection, request history, administrator decisions | academic-records, student-records, accounts |
| reporting | Dashboard counts and printable enrollment detail | enrollment |

## Scope and acceptance
Administrators can create/edit academic records and students, create linked student accounts, disable accounts, review enrollment requests, and see actual database counts. Students can view/update only their own profile, select available subjects and a section, submit one enrollment per academic year/semester, withdraw pending requests, and view/print their own enrollment details and approved schedule. Search and pagination apply to administrative lists. Empty states explain the next action; no fake metrics or inactive navigation.

## Design
Operate mode. Navy sidebar, off-white workspace, white content surfaces, blue primary actions and active navigation, restrained gold accents. Existing local Inter for controls and data; Plus Jakarta Sans for headings. Dedicated record pages rather than modal forms. Left-aligned content, responsive sidebar toggle, horizontally scrollable tables, visible labels/focus, reduced motion. Preserve login composition.

## Implementation and boundaries
Shared PHP controller/view helpers behind role-checked entry routes; allowlisted module definitions for queries/forms; prepared statements for values. Add missing tables from the supplied SQL without importing INSERT statements. Add explicit account-to-student links and normalized enrollment requests/items; preserve legacy tables. All mutations use CSRF and POST, validation, server-side authorization, and post/redirect/get. Passwords stay hashed. No payments, grades, messaging, invented room/instructor catalogs, or public signup: those lack a database/backend model.

## Verification
PHP lint all changed PHP; node --check scripts; HTTP tests for all administrator routes, CRUD round trips, student account/login, own-profile restrictions, enrollment submit/approve/withdraw, invalid CSRF and role boundaries; retain existing authentication tests. Browser screenshots desktop/mobile if a functioning browser can be launched. No extra dependencies required.
