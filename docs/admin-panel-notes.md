# Liora City Admin Panel Starter

This starter is set up as an internal booking operations panel for an event center using Laravel and AdminLTE.

## Current foundation

- Staff login using the existing `users` table from `liora_city.sql`
- Dashboard summary for bookings, payment status, staff users, and recent booking activity
- Models prepared for `users`, `bookings`, and `payments`
- AdminLTE assets ready for a fuller back-office interface

## Tables already identified from the SQL dump

- `users`
- `bookings`
- `payments`
- `bookings_services`
- `services`
- `event_type`
- `audits`
- `agreement`
- `messages`
- `gallery`
- `contact_page`
- `mailer_creds`

## Suggested next feature modules

1. Booking calendar and conflict checking
2. Booking detail screen with approval / decline workflow
3. Payment history and outstanding balance tracking
4. Event type and service price management
5. Customer and staff account management
6. Printable booking slip / invoice / agreement views
7. Audit trail for internal actions

## Database note

The starter expects the event center database schema from `liora_city.sql`. If you import that dump first, the dashboard and staff login will use the imported records immediately.
