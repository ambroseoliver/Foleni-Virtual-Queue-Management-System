# Foleni

Foleni is a lightweight virtual queue for a barbershop. Customers can choose a service and join the queue online, check the live line, and see their place. Staff can sign in to manage the queue.

## Features

- Responsive landing page with services, prices, and estimated wait.
- Customer queue form with server-side validation and CSRF protection.
- Live queue page that refreshes every 30 seconds and displays a customer's ticket.
- Password-protected business dashboard for advancing the queue.
- MySQL persistence using PDO and prepared statements.

## Requirements

- PHP 8.0 or later
- MySQL or MariaDB
- PHP PDO MySQL extension

## Setup

1. Create a database and a dedicated database user. For example, in MySQL:

   ```sql
   CREATE DATABASE foleni_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'foleni_user'@'localhost' IDENTIFIED BY 'replace-with-a-strong-password';
   GRANT SELECT, INSERT, UPDATE ON foleni_db.* TO 'foleni_user'@'localhost';
   ```

2. Import the queue table:

   ```sh
   mysql -u foleni_user -p foleni_db < database/foleni.sql
   ```

3. Configure the database connection in [`config/database.php`](config/database.php) with the database host, name, username, and password you created. Do not commit real credentials.

4. Configure business login credentials in the environment where PHP runs:

   ```sh
   export FOLENI_ADMIN_USER='your-admin-name'
   export FOLENI_ADMIN_PASSWORD='use-a-strong-unique-password'
   ```

5. Start the PHP development server from the project directory:

   ```sh
   php -S 127.0.0.1:8000
   ```

   Open <http://127.0.0.1:8000/> in your browser. The environment variables must be available to the PHP server process.

## Pages

| Page | URL | Purpose |
| --- | --- | --- |
| Home | `/` | Service overview and links to the queue |
| Join queue | `/join_queue.php` | Select a service and join the line |
| Live queue | `/queue_status.php` | View the current line; ticket links include `?id=<queue-entry-id>` |
| Business login | `/login.php` | Sign in with the configured admin credentials |
| Owner dashboard | `/admin.php` | View active customers and advance the queue |

## Project structure

```text
admin.php                 Owner dashboard
assets/css/style.css      Responsive site styles
assets/js/script.js       Mobile navigation behavior
config/database.php       PDO database connection
database/foleni.sql       Queue table schema
includes/functions.php    Shared queue, escaping, and session helpers
includes/header.php       Shared site header
includes/footer.php       Shared site footer
index.php                 Landing page
join_queue.php            Customer queue form
login.php                 Business login
logout.php                Sign-out handler
queue_status.php          Live queue and ticket view
```

## Notes

- The database setup script creates the `queue_entries` table in the `foleni_db` database.
- The dashboard is inaccessible until `FOLENI_ADMIN_USER` and `FOLENI_ADMIN_PASSWORD` are configured.
- The landing page uses Google Fonts and a remote Unsplash photo; those assets require an internet connection.
- For production, use HTTPS, set a strong unique admin password, and configure PHP environment variables and database credentials through your hosting provider's secret-management settings.
