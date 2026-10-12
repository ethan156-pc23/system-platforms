# Cebu Emergency Hotlines

A public directory of emergency hotlines for every city/municipality in
Cebu Province, plus an admin panel to manage them. Built from the Figma
prototype (fileKey `Sq2ZU3rgJBs2izpQvhJ42G`) with a PHP + MySQL backend.

## 1. Setup (XAMPP / WAMP / any Apache+PHP+MySQL stack)

1. **Copy the project** into your server's document root, e.g.
   `C:\xampp\htdocs\cebu-emergency-hotlines\` (or `htdocs/` directly if you
   want it at the root URL).
2. **Import the database.** In phpMyAdmin (or the `mysql` CLI), run
  `schema.sql`. It creates the `cebu_emergency` database, all
   three tables, and seeds the categories + every hotline you supplied.
3. **Set your DB credentials** in `db.php` (defaults match a
   stock XAMPP install: host `localhost`, user `root`, empty password).
4. **Set `BASE_URL`** if needed. It's defined in `functions.php`.
   Leave it as `''` if the project *is* your document root. If it lives in
   a subfolder (e.g. `htdocs/cebu-emergency-hotlines/`), change it to
   `'/cebu-emergency-hotlines'`.
5. Visit `http://localhost/` (or your subfolder) — you should see the
   hotline directory.

## 2. Logging in as admin

- URL: `/login.php`
- Mobile Number: `09123456789`
- Password: `admin123`

That seed account's password hash was regenerated with PHP's own
`password_hash()` — the hash originally pasted in the SQL didn't actually
match `admin123`, so this build fixes it. From the dashboard you can add,
edit, and delete hotlines; the category badge is derived automatically
from whichever category you pick.

## 3. What's included

```
index.php                 Home/directory page (search, category chips, results)
about.php                 About page
login.php / signup.php    Citizen + admin auth (shared users table)
forgot-password.php       Mobile number + new password reset
logout.php

db.php                    PDO connection (edit your credentials here)
functions.php             Session/auth helpers, CSRF, BASE_URL
header.php                Shared public header (nav, login/logout button)
footer.php                Shared footer + script loader
admin-header.php          Admin topbar

style.css                 Public site styles (design tokens from Figma)
admin.css                 Admin panel styles
app.js                    Fetches the API, renders chips/cards, call modal
admin.js                  Delete confirmation, flash auto-dismiss

categories.php            GET — categories + live hotline counts
municipalities.php        GET — distinct city/municipality list (for the filter)
hotlines.php              GET — hotlines, filterable by ?category, ?municipality, ?q
favorite-municipalities.php GET/POST — manage a user's priority cities/municipalities

dashboard.php             Searchable table of all hotlines
users.php                 Admin-only registered users and recent activity view
hotline-form.php          Add/edit form (one file handles both)
delete-hotline.php        POST-only delete handler

schema.sql                Tables + seed data (with the fixed admin hash)
```

## 4. Decisions worth knowing about

- **One users table, one login form, role-based redirect.** Citizens can
  request a one-time code by mobile number or email, while the seeded admin
  can still log in with the existing password;
  `role` in the `users` table decides whether you land on the public
  directory or `/dashboard.php`.
- **The "Others" category.** Your `categories` table stores
  `name = 'Disaster'` / `slug = 'others'`. The Figma chip label for that
  bucket reads "Others," so `category_display_name()` in
  `functions.php` is the one place that mapping lives — the
  hotline card badges still show `tag_label` (e.g. `DISASTER`) exactly as
  seeded.
- **A city/municipality filter was added.** The mobile mockup only shows
  category chips, but your real dataset spans ~80 cities/municipalities, so a
  dropdown filter was necessary to make the directory usable.
- **Sign-up uses OTP verification.** Mobile and email signup request a
  six-digit code that expires after 10 minutes. Local XAMPP testing displays
  the generated code on the page; email delivery uses PHP `mail()` when
  configured. Production SMS delivery requires an SMS provider such as
  Semaphore or Twilio and its API credentials.
- **Forgot-password has no verification step**, matching the two-field
  form in the prototype (mobile number + new password, no code). Worth
  adding a verification step before this goes anywhere near production.
- **The admin Users page** shows registered mobile/email accounts, roles,
  registration dates, and whether each user was active within the last five
  minutes. Existing databases are upgraded automatically with `last_seen_at`.
- **Icons are hand-drawn inline SVGs**, not exports from Figma — the
  Figma asset URLs aren't reachable from this environment's network, so
  common glyphs (search, phone, person, envelope, checkmark) were
  recreated as clean generic icons rather than guessed-at custom ones.
  Swap in your own exports from Figma any time.
- **Security basics included:** PDO prepared statements everywhere,
  `password_hash`/`password_verify`, session regeneration on login, CSRF
  tokens on every admin form/POST, and output escaping via the `e()`
  helper.

## 5. Extending it

- Category management UI (currently categories are fixed — add rows to
  the `categories` table directly if you need a 5th).
- Real SMS/email OTP verification for sign-up and password reset.
- A "favorites" or "recently called" feature for logged-in citizens,
  since the `users` table already supports accounts beyond the admin.
