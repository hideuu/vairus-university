# Vairus University design

## Approved direction
Campus-led university login. Desktop pairs a full-height campus photo with a quiet form panel. Mobile places a compact university header above the form.

## Tokens
- Navy #1E2A5A: headings and university identity.
- Blue #3A5BD9: actions and keyboard focus.
- Pale blue #E4EAFD: dividers and supporting surfaces.
- Off-white #F5F7FF: form background.
- Gold #F5B942: restrained brand accents.
- Supporting ink #536087: secondary text.
- Control border #7C8AB0: input outlines and secondary button borders; at least 3:1 against the white and off-white form surfaces.
- Plus Jakarta Sans: branding and headings; Inter: form and body. Both self-hosted.
- Desktop columns: 54% / 46%, changing to 46% / 54% at 900px and below; a single column at 640px and below.
- Form maximum width: 390px, increasing to 430px at 1600px and above.
- Inputs and primary button: minimum 54px height, 7px radius.

## Behavior
Explicit input labels, credential autofill, visible focus, reduced motion, and password visibility toggle. Login submits to PHP and displays server-side validation errors. Successful administrator login redirects to a protected account page with a POST logout button. No fabricated recovery destinations.

## Portal extension
Operate mode for administrator and student workspaces. Preserve the approved campus-led login composition and identity tokens above. Use the same self-hosted Plus Jakarta Sans for headings and Inter for controls and record data.

- Layout: fixed navy sidebar (244px), reduced to 220px at 1200px and below; matching workspace offset. White topbar (minimum 82px) and off-white workspace; left-aligned content capped at 1600px with 34px 38px 48px padding. White surfaces use pale-blue 1px borders and 10px corners, with tonal separation rather than resting shadows.
- Responsive navigation: at 900px and below, enhanced navigation becomes a toggle-controlled 260px drawer below the sticky 74px topbar. The open drawer uses a restrained shadow. Without enhancement, navigation stays visible in a grid. At 600px and below, content padding becomes 24px 16px 32px, forms and detail grids become single-column, and page actions wrap below headings.
- Hierarchy: page headings are 28px/1.3, weight 700 (24px on small screens); section headings are 17px/1.4, weight 650. Portal body, labels, actions, and table data are 14px; table headings and secondary metadata are 12px. Numeric counts and table values use tabular figures. Mobile form controls use 16px to avoid automatic input zoom.
- Controls: portal fields and primary buttons have a 44px minimum height and 7px corners; login retains its 54px controls. Blue primary actions turn navy on hover; white secondary actions have supporting borders and pale-blue hover. Gold is reserved for the navy welcome banner's action and sidebar accents/focus. Destructive actions use muted red and an explicit confirmation surface.
- Navigation: pale text on navy, blue active item with a current-page marker, lighter navy hover, and gold keyboard focus. Icons accompany text labels. Include a keyboard skip link and visible control focus.
- Data: keep tables inside horizontal scrolling containers. Search, count tags, pagination, and actionable empty states support real records. Table actions keep 44px targets; text remains compact with secondary metadata beneath the primary identifier.
- Forms: use dedicated record pages with visible labels, required markers, two-column fields, and a separated save/cancel row. Optional student groups are expandable Personal details, Family and guardian, and Previous education; groups containing values open automatically. Account forms expose the explicit student link; missing links show an actionable administrator-contact state. Student views remain scoped to the linked student.
- Feedback: status badges always include text. Pending uses warm gold tint (#FFF0CE / #74500A); approved/active uses pale blue with blue ink (#2547B5); rejected/disabled uses rose (#FFF1F3 / #92293E); withdrawn uses off-white with secondary ink. Error notices use the same rose family; success notices use pale blue with navy ink.
- Print: hide navigation, topbar, footer, notices, filters and action/decision controls; remove workspace offset and content padding, use white paper, expose table overflow, avoid splitting surfaces, and add the university name above enrollment detail headings.

## Audit refinements

- Desktop dashboards reserve 280px for the supporting panel and give remaining width to the main table; dashboard panels stack at 1360px and below. Tables with five/six columns retain minimum readable widths of 640px/740px inside their scroll regions. Below 360px, stats become a single column.
- Overflowing tables show horizontal-scroll guidance and expose a keyboard-focusable named region; fitting tables do not add a tab stop. Long values use native expandable details rather than clipping. Pagination and subject-selection labels have 44px minimum touch targets.
- The mobile menu remains a nonmodal disclosure: opening focuses its current link, Escape returns focus to Menu, and focus leaving navigation closes it. A dismissible backdrop clarifies the overlay and the topbar preserves the university name. The skip link targets a focusable main landmark.
- Shared POST-form enhancement supplies aria-busy, a reduced-motion-aware progress indicator, and duplicate-submit prevention. Named submit buttons retain their values. Returning through browser history clears pending state. Server errors receive focus; native validation opens collapsed field groups before focusing invalid inputs.
- Mobile login branding has a stable content-sized header row. The exact five brand colors, campus photography, fonts, routes, and PHP forms remain the design authority.
