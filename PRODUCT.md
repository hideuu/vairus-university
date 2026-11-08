# Vairus University

<!-- impeccable:product-schema 1 -->

## Platform
web

## Product Purpose
University online enrollment portal for administrators and students, with working forms connected to MySQL.

## Capabilities and Constraints
Existing PHP/CSS project. Login uses username and password against auth_users, with password hashes, database-backed IP throttling, CSRF checks, and a 30-minute idle session expiry. The user explicitly requested both administrator and student portals with working database forms.

Administrators manage students, courses, subjects, sections, schedules, secure accounts, and enrollment decisions. They can filter six report types, export all matching rows as CSV, and open full reports for printing or browser PDF saving. Students update their own contact details, choose subjects, submit/withdraw pending enrollment requests, view decisions and approved schedules, and print request details. Both roles can change their passwords and log out.

Existing SQL defines academic record structures. New normalized enrollment requests/items and explicit account-to-student links support private student workflows. Legacy usertbl and student_subject rows remain untouched and are not automatically migrated. No sample personal records or plaintext accounts are imported. Payments, grades, and independent room/instructor catalogs have no backend model and are outside scope.

## Brand Commitments
The user approved a campus-led split login with the existing campus photo, local Plus Jakarta Sans and Inter, a navy photo overlay, gold accent, off-white form panel, and blue login button. Preserve the exact five colors in Palette 1.png.
