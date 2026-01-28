# Nellys Nails

Boutique nail redo studio website with a booking form and an admin dashboard for managing appointments.

## Features
- Marketing site (HTML/CSS)
- Booking form that stores requests in a SQLite database
- Admin dashboard to view, update, and add bookings
- Optional status emails for accepted/rejected bookings

## Tech stack
- PHP (server-side)
- SQLite (database file)
- HTML/CSS (frontend)

## Project structure
- `index.html`: Main website
- `styles.css`: Public site styles
- `book.php`: Booking form handler
- `app/`: Config and bootstrap
- `admin/`: Admin login + dashboard
- `data/`: SQLite database file (`app.db`)

## Local setup
1) Serve the project with PHP (so `book.php` works).
2) Ensure the `data/` folder is writable.
3) Open the site and submit a test booking to create `data/app.db`.
4) Run the admin setup once:
   - Visit `/admin/setup.php`
   - Then delete `admin/setup.php` for security

## Admin access
- Login: `/admin/index.php`
- Change password: `/admin/change-password.php`
- Add booking manually: `/admin/create.php`

## Configuration
Edit `app/config.php`:
- `studio_email`: where booking notifications are sent
- `from_email` / `from_name`: email sender
- `timezone`: default timezone (example: `America/Chicago`)
- `send_status_emails`: send accepted/rejected emails
- `db_path`: SQLite file path (default `data/app.db`)

## Deployment (Hostinger or similar)
1) Upload all files to `public_html/`.
2) Ensure `data/` is writable by PHP.
3) Visit the site and submit a booking to create the database file.
4) Run `/admin/setup.php` once, then delete it.

## Notes
- The booking form requires PHP mail to be configured on the host.
- If you change the admin password, use the change password screen.
- SQLite is file-based; no MySQL setup is needed unless you change the code.
