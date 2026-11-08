DecorEase Admin Update - event_decoration_db

1. Backup your existing project first.
2. Copy db.php to the project root (same level as login.php).
3. Copy the admin folder files into your existing admin folder.
4. Keep the database name event_decoration_db in db.php.
5. Ensure your login system sets these session values after login:
   $_SESSION["user_id"], $_SESSION["user_role"], $_SESSION["user_name"]
   and that user_role is "admin" for admin accounts.
6. Open: http://localhost/pwad73_php/Event%20Decoration/admin/dashboard.php

Important:
- These files use the new schema: users, customers, staff, categories, packages,
  inventory, package_items, venues, bookings, booking_addons, payments,
  invoices, booking_staff, reviews.
- bookings has no created_at column in the supplied schema, so the monthly chart
  groups by event_date.
- messages.php displays reviews because the supplied schema has no messages table.
- Existing customer login/register and public-facing pages may also need updates
  if they still use the old database column names.
