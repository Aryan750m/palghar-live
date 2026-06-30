# Installation & Hostinger Deployment Guide (Phase 1)

This project has been converted from client-side LocalStorage to a classic, server-rendered PHP + MySQL application with session-based authentication and secure Post/Redirect/Get (PRG) patterns suitable for Hostinger Shared Hosting.

---

## 1. Directory Structure Check

Ensure your project folders look like this:
- `/config/db.php`
- `/includes/functions.php`, `auth.php`
- `/admin/login.php`, `logout.php`, `dashboard.php`, `news.php`, `sections.php`, `users.php`, `settings.php`, `header.php`, `footer.php`
- `/uploads/news/`
- `/uploads/sections/`
- `/assets/css/style.css`
- `/assets/js/app_v2.js`, `admin_v2.js`
- `index.php` (Root homepage)
- `news-detail.php` (Root article viewer)
- `install.php` (Root database installer)
- `schema.sql` (Root SQL schema tables creation)

---

## 2. Hostinger Database Setup

1. Log in to your **Hostinger hPanel**.
2. Navigate to **Databases** -> **MySQL Databases**.
3. Create a new database:
   - **Database Name**: e.g., `u123456789_palghar_db`
   - **MySQL User**: e.g., `u123456789_admin`
   - **Password**: (Generate a strong password and save it)
4. Click **Create**.

---

## 3. Database Connection Configuration

1. Open the file `config/db.php` in your editor.
2. Locate the database connection constants and replace them with your Hostinger database details:
   ```php
   define('DB_HOST', 'localhost'); // Hostinger MySQL server is usually 'localhost'
   define('DB_USER', 'u123456789_admin'); // Replace with your MySQL Username
   define('DB_PASS', 'your_strong_password'); // Replace with your MySQL Password
   define('DB_NAME', 'u123456789_palghar_db'); // Replace with your MySQL Database Name
   ```
3. Save the file.

---

## 4. File Upload & Directory Permissions

1. Compress your project folder into a `.zip` file.
2. In Hostinger hPanel, go to **Files** -> **File Manager**.
3. Navigate to the targeted folder (usually `public_html/`).
4. Upload the `.zip` file and extract it.
5. Ensure the upload folders exist and are writable:
   - Check that folders `/uploads/news/` and `/uploads/sections/` have permissions set to `755` (standard directories on Hostinger are automatically set to write-allowed `755`).

---

## 5. Initialize/Seed Default Datasets

1. Open your browser and navigate to the database setup installer:
   `http://your-domain.com/install.php`
   *(Replace `your-domain.com` with your actual domain name)*
2. Click **Run Installation & Seed Data**.
3. The tables are now created and seeded in MySQL.
4. **Security Notice**: A lock file `install.lock` is automatically created, disabling the installer to prevent unauthorized resets.

---

## 6. Accessing the Admin Dashboard

- **Admin Portal URL**: `http://your-domain.com/admin/login.php`
- **Default Accounts Created during setup**:
  - **Administrator Account**:
    - **Username**: `admin`
    - **Password**: `admin123`
  - **Editor A Account**:
    - **Username**: `usera`
    - **Password**: `user123` (Assigned Categories: Sports, Business)
  - **Editor B Account**:
    - **Username**: `userb`
    - **Password**: `user123` (Assigned Categories: Palghar Local, Art & Culture)
