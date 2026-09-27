# Changelog

What changed for the people using Health Program Software. Newest first.

## 2.1.0 - 2026-09-27

- **Monitoring menu**: Audit Log (who changed which record, with old and new
  values and the record's history), Activity Log (pages opened and actions
  taken), Security Events (sign-ins, failures, lockouts, refused access,
  permission and account changes, database exports), Login History and
  System Health.
- **About** page with the version and what's new.
- Sign-in is rate limited against password guessing: five failed attempts a
  minute for a username from one address.
- The menu is grouped into sections.
- **Patients**: compact list with one search box and quick actions, a
  profile page with visit totals and next steps, a registration form that
  shows every section, and a faster prescription screen.
- **Invoices**: a new screen that loads in a fraction of the time, searches
  patients and products as you type, suggests the batch that expires first
  and adds lines without reloading.

## 2.0.0 - 2026-09-27

- Access control on Laravel roles and permissions; the menu is configured in
  the application and shows only what each user may open.
- Nightly encrypted backups of the database and uploads, off site too.
- Document numbers can no longer be given twice when two people save at the
  same moment, and stock changes made at the same moment all count.
- Every change to a record is kept in the audit log.

## 1.0.0 - 2026-09-26

- The Health Program Software moved from Yii 1.1 to Laravel with every
  module: patients, prescriptions, invoices, purchasing, stock, reports,
  dashboard and administration.
