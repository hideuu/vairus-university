# Vairus University

PHP and MySQL university enrollment project. Open `http://localhost/vairusuniversity/public/` with Apache and MySQL running in XAMPP.

## Account setup

Run these commands from the project directory:

```powershell
C:\xampp\php\php.exe database/setup_auth.php
C:\xampp\php\php.exe database/setup_portal.php
```

The first command creates authentication tables and generates an `admin` account with a random password, displayed once in the terminal. The second creates missing enrollment structure without importing the old dump’s sample personal data or plaintext passwords. Both preserve existing accounts and records. The current local testing administrator password remains as configured by the user. No public registration or web setup endpoint is available.

The login uses `auth_users`; plaintext accounts in the old SQL dump are not enabled. Administrators and students automatically enter their respective portals after login.

## Available workflows

- Administrators: overview, searchable/paginated students, courses, subjects, sections, schedules, account management, enrollment decisions, and password settings. Records can be created and edited; deletion is blocked while referenced. Disable accounts through their edit form.
- Students: overview, contact/profile updates, subject selection, enrollment history, withdrawal of pending requests, approved schedules, printable request details, and password settings. All student operations are restricted to the linked record.
- Enrollment: one request per student, academic year, and semester. Rejected or withdrawn requests can be resubmitted. Approval checks section capacity; schedules reject overlapping section/room times on the subject’s class day. New requests store subject code/title/unit snapshots. Editing academic assignments that would break active records is blocked.

Start with **Courses**, then **Subjects**, **Sections**, and **Schedules**. Add a student under **Students**, then create an account under **Accounts**, choose the Student role, and select that student record. Sign out and use the new account to test student enrollment. The database starts empty apart from existing accounts; test records are not left in it.

## Data compatibility

Academic screens use the supplied `courses_tbl`, `subjecttbl`, `sections_tbl`, `sched_tbl`, and `studenttbl` structures. Explicit `auth_users.student_id` links replace ambiguous legacy account IDs. New requests use `enrollment_requests`/`enrollment_items`; old `student_subject` and `usertbl` records remain untouched and are not automatically migrated. Full email addresses, contact numbers, and student numbers are supported by widening legacy columns. Missing tables are created with InnoDB; existing table engines are preserved. Section-capacity transaction locking assumes InnoDB sections, as created by setup on this installation. Each subject has one class day, and schedule room identifiers are numeric because those are the existing schema constraints. No invented payment, grading, room-catalog, or instructor-account modules are included.

## Database configuration

Defaults match local XAMPP: localhost, root, blank database password, database `online_enroll`. Set `DB_HOST`, `DB_USER`, `DB_PASSWORD`, and `DB_NAME` in the PHP process environment to override. Use a dedicated database user and HTTPS when hosting publicly. Passwords are stored using PHP's `password_hash`, and session cookies use Secure when served over HTTPS.

## Verification

Set `TEST_ADMIN_PASSWORD` in the current process environment, then run:

```powershell
C:\xampp\php\php.exe tests/auth.php
C:\xampp\php\php.exe tests/portal.php
node --check public/portal.js
```

The tests exercise login, CSRF, SQL injection rejection, role isolation, CRUD round trips, profile privacy, enrollment submission/decisions/capacity/withdrawal, password changes, and logout. Portal tests create uniquely named temporary records and remove them in a finally block. Run against a local development instance without concurrent login traffic; the tests temporarily reset and then restore the loopback IP’s throttle row. Tests never contain the administrator password.

Optional screenshots: launch an isolated headless Chrome with DevTools on port 9333, set `CAPTURE_PORTAL=1`, and run `tests/portal.php`. Its Node CDP helper captures desktop/mobile pages into `.impeccable/review/` and checks assets, navigation, subject-selection totals, overflow, and runtime errors. Screenshot records are temporary integration fixtures, not production data.
