# Admin Panel Setup & Access Guide

## For Web Hosting Deployment

### Initial Setup (First Time Only)

1. **Upload all files** to your web hosting server via FTP or file manager.

2. **Create the database**:
   - Log into your hosting control panel (cPanel, Plesk, etc.)
   - Create a new MySQL database named `tms_db`
   - Create a new MySQL user with full privileges on `tms_db`
   - Update `database.php` with your database credentials:
     ```php
     $servername = "your_server";
     $username = "your_db_user";
     $password = "your_db_password";
     $database = "tms_db";
     ```

3. **Run the database migration**:
   - Go to phpMyAdmin in your control panel
   - Select the `tms_db` database
   - Click "SQL" tab and paste the contents of `migrations.sql`
   - Execute the SQL queries

4. **Initialize the admin account**:
   - Visit: `http://yourdomain.com/TMS/setup.php`
   - Fill in the admin details (Full Name, Username, Password)
   - Click "Create Admin Account"
   - You'll be redirected to login

### Accessing the Admin Panel

**Method 1: Direct URL**
- After logging in, navigate to: `http://yourdomain.com/TMS/admin.php`
- Only admin users can access this page

**Method 2: Dashboard Link**
- Log in with your admin account
- An "Admin Panel" button (yellow) will appear in the top right of the navbar
- Click it to go to the admin panel

### Admin Panel Features

- **Pending User Approvals**: Approve or reject new teacher registrations
- **Active Users Management**: View all teachers and assign/remove Adviser role
- **Role Management**: 
  - `teacher` = Regular teacher (can create classes, seating, scores)
  - `adviser` = Class adviser (can manage advisory and respond to seating requests)
  - `admin` = Full access (manage all users and roles)

### Workflow Summary

1. **New teachers register** → Status set to `pending`
2. **Admin approves in admin panel** → Status changes to `active`
3. **Teacher can now login** → Access dashboard
4. **Teacher marks a class as Advisory** → Becomes adviser for that class
5. **Other teachers request seating** → Adviser receives and approves requests

### Important URLs

- Login/Register: `http://yourdomain.com/TMS/`
- Setup (first admin only): `http://yourdomain.com/TMS/setup.php`
- Dashboard: `http://yourdomain.com/TMS/dashboard.php`
- Admin Panel: `http://yourdomain.com/TMS/admin.php`
- Logout: `http://yourdomain.com/TMS/logout.php`

### Database File

All database schema is in `migrations.sql` - keep this safe for future migrations.
