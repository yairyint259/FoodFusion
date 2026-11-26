FOODFUSION - PHP SERVER-SIDE ASSIGNMENT PROJECT

Requirements
- XAMPP with Apache, MySQL and PHP 8+
- MySQL 8+ recommended

Install
1. Copy the FoodFusion_Assignment folder into C:\xampp\htdocs\.
2. Start Apache and MySQL in XAMPP.
3. Open http://localhost/phpmyadmin and import FoodFusion_Complete.sql.
4. The complete SQL file includes demo users, recipes, and the dedicated administrator account.
5. Visit http://localhost/FoodFusion_Assignment/index.php.

Database settings
The default db.php settings are host=localhost, user=root, blank password, database=foodfusion.
Set FOODFUSION_DB_HOST, FOODFUSION_DB_USER, FOODFUSION_DB_PASS, and FOODFUSION_DB_NAME environment variables to override them.

Design and functionality
- Responsive pages for home, about, recipes, community, culinary resources, learning resources, contact, privacy policy and terms.
- Public recipe browsing and search; login required for recipe submissions, reviews and community posts.
- Registration and login with password hashing, prepared statements, CSRF tokens, session ID regeneration, and a three-failure temporary lock.
- Contact form saves messages to contact_message.
- Recipe images use remote image URLs or images/recipe-placeholder.svg as a fallback.

Important testing notes
This archive was syntax-checked, but it has not been run against a live XAMPP/MySQL server in this environment. Import the schema before using the pages. Use the sample SQL only after the schema has been imported. Remote image URLs require an internet connection. Update the default database credentials for any non-local deployment. For production, add HTTPS, stronger rate limiting, administrative moderation, and a managed secrets configuration.


SAMPLE DATA INCLUDED
--------------------
Import FoodFusion_Sample_Data.sql AFTER FoodFusion_Complete.sql. It adds demo users, recipes,
recipe ingredients, dietary tags, reviews, community posts, and clearly labelled
demo contact enquiries. The sample-data inserts are designed to avoid duplicates
when re-imported. Demo login accounts: yeyint / yeyint12 and kyaw / Kyaw12!.
Administrator login: foodfusion_admin / FoodFusionAdmin2026!
These are local coursework demo credentials. Change the administrator password after first login and do not use these credentials on a public deployment.


Database: import FoodFusion_Complete.sql for schema, lookup values and sample data (9 recipes including American and Mexican dishes). Cuisine is displayed as “Cuisine Type” in the recipe filters and Share a Recipe form. PDF downloads are handled by culinaryRes.php; there is no separate download.php. Font Awesome icons are loaded in header.php.

Recent interface updates
- Login and registration are shared popup forms included by header.php, so they are available on every PHP page. JavaScript is embedded in the shared PHP files (authentication popup behavior in header.php and cookie/share/image fallback behavior in footer.php). Only the CSS is maintained separately in style.css, as requested.
- Resources is a navigation dropdown with Culinary Resources and Education & Learning; About Us sits next to Resources.
- Recipe search, category, cuisine, difficulty and Search button sit on one row on wide screens and wrap on smaller screens.
- Culinary and education PDF downloads use orange download links.
- The shared theme uses black, white, razor-lime green and yellow.


CONTACT FORM DATABASE NOTE
The contact form now stores one `username` value, not separate first_name and last_name values. Fresh installs should import FoodFusion_Complete.sql. If you already imported the earlier schema, run this once in phpMyAdmin after selecting the foodfusion database:

ALTER TABLE contact_message
  ADD COLUMN username VARCHAR(80) NOT NULL DEFAULT '' AFTER message_id;
UPDATE contact_message SET username = TRIM(CONCAT(first_name, ' ', last_name));
ALTER TABLE contact_message DROP COLUMN first_name, DROP COLUMN last_name;

Back up your database before running a migration.

YOUTUBE VIDEO NOTE
Culinary and education pages embed the Culinary Institute of America instructional playlist in a responsive 16:9 frame (854 x 480 baseline). The iframe size does not force YouTube to stream at 480p; playback quality is chosen by YouTube based on device, browser, and connection.


ADMIN DASHBOARD AND MEMBER PASSWORD RESETS
------------------------------------------
- For new installs, `FoodFusion_Complete.sql` includes the `is_admin` column. Import it to create the tables, demo data, and administrator account.
- Administrator credentials for local coursework testing: 
username - `foodfusion_admin` / password - `FoodFusionAdmin2026!` 
email: `admin@foodfusion.local`). 
The password is stored as a PHP-compatible hash in SQL, never as plain text.
- For an existing database without `is_admin`, back up the database, run `FoodFusion_Admin_Reset_Migration.sql` once, then run `FoodFusion_Admin_Account_Setup.sql` to create/promote the administrator account. If `is_admin` already exists, skip the migration and run only the account setup SQL.
- Log in through the normal FoodFusion login popup. A successful administrator login redirects to `admin-dashboard.php`; regular users do not see password-reset controls.
- Change the demo administrator password after the first login. Do not use this fixed demo password on an internet-facing deployment.
