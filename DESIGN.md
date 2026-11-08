# Vairus University design

## Reference direction

The supplied 01–10 screenshots guide the layout: horizontal navigation, navy university headers, gold accents, rounded white surfaces, campus photography, and clear student shortcuts. Preserve Vairus branding and the existing PHP/database architecture. Use actual records and actions throughout.

## Palette and typography

Navy #1E2A5A, blue #3A5BD9, pale blue #E4EAFD, off-white #F5F7FF, and gold #F5B942 remain the project palette. Supporting text uses #536087 and control borders #7C8AB0. Plus Jakarta Sans supplies headings and Inter supplies body text and controls; both are self-hosted.

## Layout

- Login uses a campus photo backdrop with introductory copy and a white sign-in panel; the layout stacks below 760px.
- Administrator pages use a navy horizontal header and page band. Student pages use a white desktop header and a photo welcome banner. Main content is centered at a maximum 1320px with 32px side padding.
- Primary destinations appear directly in the header. A native More disclosure retains additional destinations. At 900px and below, navigation becomes a toggle-controlled drawer. Student pages also expose four labeled bottom navigation links on phones.
- Dashboard shortcuts lead to working portal pages. Counts and recent requests come from the database.
- Subjects use catalog cards. Enrollment uses native checkbox cards and a sticky selection summary, stacked on smaller screens. Profile pages combine section links with the existing editable contact form and personal record.
- Surfaces use 18px corners, inputs 10px, and buttons 11px. Page headings are 34px desktop and 28px mobile. Body and record text remain 14px; mobile inputs are 16px.

## Interaction and accessibility

Keep visible field labels, keyboard focus, a skip link, current-page markers, text status badges, and minimum 44px control targets. Mobile navigation focuses its current link on opening and returns focus to Menu on Escape. Navigation remains available without JavaScript. Respect reduced motion.

Tables retain searchable, paginated database records and horizontal scrolling with keyboard access and overflow guidance. Long record values remain expandable. Optional student form groups open automatically when populated. Destructive actions use explicit confirmation pages.

POST forms preserve CSRF protection and named submit values, expose pending status, prevent duplicate submission, reset on history navigation, and focus server errors. Student records remain scoped to the linked account.

## Reports and printing

Administrator reports retain filters, paginated previews, full CSV exports, and standalone landscape print views. Print views remove navigation and actions, repeat column headings, and expose complete report text. Existing enrollment printing remains supported.
