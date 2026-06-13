# ACADEXA — Learning Management System
### ZTF University Institute (ZTF-UI) | Koumé – Bertoua, East Region, Cameroon
**www.ztfuniversity.com**

---

## What is ACADEXA?

ACADEXA is the official online learning platform of ZTF University Institute. Students can enroll in courses, watch video lessons, take quizzes, and earn certificates — all from any device. Instructors can create and publish courses. Admins manage everything from a central dashboard.

---

## Plain-Language Setup Guide (No Coding Experience Required)

Follow these steps **exactly in order**. Each step builds on the previous one.

---

### STEP 1 — Install XAMPP (your local web server)

1. Go to **https://www.apachefriends.org** and download **XAMPP for Windows**
2. Run the installer and accept all defaults
3. After installation, open **XAMPP Control Panel**
4. Click **Start** next to **Apache** and **MySQL**
5. Both should turn green — this means your local server is running

---

### STEP 2 — Place the ACADEXA files

1. Open the folder `C:\xampp\htdocs\`
2. You should see a folder called `acadexa` there already (this project)
3. If not, copy the `acadexa` folder into `C:\xampp\htdocs\`

---

### STEP 3 — Create the database

1. Open your browser and go to: **http://localhost/phpmyadmin**
2. Click **"New"** in the left sidebar
3. Type the database name: **`acadexa`**
4. Set collation to: **`utf8mb4_unicode_ci`**
5. Click **Create**
6. Click on your new `acadexa` database in the left sidebar
7. Click the **Import** tab at the top
8. Click **"Choose File"** and select the file: `C:\xampp\htdocs\acadexa\database\acadexa.sql`
9. Click **Go** at the bottom — wait for the success message

---

### STEP 4 — Install PHP packages (Composer)

1. Download **Composer** from **https://getcomposer.org/download/** (Windows Installer)
2. Run the installer — it will detect PHP from XAMPP automatically
3. Open **Command Prompt** (press Windows key, type `cmd`, press Enter)
4. Type these commands one at a time, pressing Enter after each:

```
cd C:\xampp\htdocs\acadexa
composer install
```

Wait for it to finish (this may take 2–5 minutes — it downloads all required libraries).

---

### STEP 5 — Set up the environment file

In Command Prompt (still in the acadexa folder):

```
copy .env.example .env
php artisan key:generate
```

This creates your application secret key.

---

### STEP 6 — Set up the database tables and demo data

```
php artisan migrate --seed
```

This creates all database tables and adds:
- Admin account
- Sample instructors and courses
- Default settings
- CMS pages (About, Privacy, Terms)

If this step gives errors about the database, open `.env` in Notepad and verify:
```
DB_DATABASE=acadexa
DB_USERNAME=root
DB_PASSWORD=
```
(Leave DB_PASSWORD blank for XAMPP default setup)

---

### STEP 7 — Link the file storage

```
php artisan storage:link
```

This allows uploaded images and certificates to be visible on the website.

---

### STEP 8 — Install front-end assets (Node.js required)

1. Download **Node.js** from **https://nodejs.org** (choose LTS version)
2. Install it with default settings
3. Back in Command Prompt:

```
npm install
npm run build
```

This compiles the CSS and JavaScript for the website.

---

### STEP 9 — Start the application

```
php artisan serve
```

Open your browser and go to: **http://localhost:8000**

You should see the ACADEXA homepage!

---

## Default Login Accounts

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@acadexa.com | ACADEXA@2026 |
| Instructor | marie@acadexa.com | ACADEXA@2026 |
| Instructor | jeanpaul@acadexa.com | ACADEXA@2026 |
| Student | student@acadexa.com | ACADEXA@2026 |

**Admin Panel URL:** http://localhost:8000/acadexa-control/login

---

## Important URLs

| Page | URL |
|------|-----|
| Homepage | http://localhost:8000 |
| All Courses | http://localhost:8000/courses |
| Admin Panel | http://localhost:8000/acadexa-control/login |
| Student Registration | http://localhost:8000/register |
| Become an Instructor | http://localhost:8000/become-an-instructor |
| Contact Page | http://localhost:8000/contact |
| Verify Certificate | http://localhost:8000/verify-certificate/{code} |

---

## How the Trial System Works

- New students get **30 free days** when they register (configurable in Admin → Settings)
- During the trial, they can access all courses
- When the trial expires, they see a "Coming Soon" payment page
- Admins can **extend a student's trial** from Admin → Users → [Student] → Extend Trial
- Instructors and admins are **never affected** by the trial

---

## How to Change the Language

- Click the **globe icon** in the top navigation bar
- Select from: English, Français, Español, Português, 中文, العربية
- Arabic automatically switches the page to right-to-left reading direction
- Your language preference is saved

---

## How the Course Publishing Process Works

1. Instructor creates a course (saved as **Draft**)
2. Instructor adds modules and lessons
3. Instructor clicks **"Submit for Review"**
4. Admin reviews the course in Admin Panel → Courses
5. Admin **approves** (course goes live) or **rejects** (sends feedback to instructor)
6. Students can enroll in published courses

---

## Certificate System

- Certificates are **automatically generated** when a student completes 100% of a course
- Each certificate has a unique verification code (format: `ACADEXA-XXXX-XXXX-YYYY`)
- Anyone can verify a certificate at: `/verify-certificate/{code}`
- Certificates are generated as PDF files using DomPDF
- Certificate template can be customized in Admin → Certificate Template

---

## Setting Up for Production (Live Website)

When you're ready to put ACADEXA live on the internet:

1. **Upload files** to your web hosting (look for `public_html` or `www` folder)
   - Upload all files EXCEPT the `public` folder contents go into `public_html`
2. **Set APP_ENV=production** and **APP_DEBUG=false** in `.env`
3. **Update APP_URL** to your domain: `APP_URL=https://yourdomain.com`
4. **Point your domain** to the `public` folder of your Laravel app
5. **Run migrations** on the live server: `php artisan migrate --seed`
6. Contact your hosting provider if you need help with the document root setting

---

## Common Problems & Solutions

**Problem:** Page shows "No application encryption key has been specified"
**Solution:** Run `php artisan key:generate`

**Problem:** Images don't show / "File not found"
**Solution:** Run `php artisan storage:link`

**Problem:** CSS looks broken / no styles
**Solution:** Run `npm install && npm run build`

**Problem:** "Class not found" errors
**Solution:** Run `composer install`

**Problem:** Database connection error
**Solution:** Make sure MySQL is running in XAMPP Control Panel, and check your `.env` DB settings

**Problem:** Admin login doesn't work
**Solution:** Go to http://localhost:8000/acadexa-control/login (different from regular login)

---

## Customizing Your Site

### Change Site Name, Colors, Contact Info
- Go to **Admin Panel → Settings**
- Update Site Name, Contact Email, Phone, Address, Social Media links

### Add/Edit Categories
- Go to **Admin Panel → Categories**
- Add categories and subcategories with emoji icons

### Create Announcements
- Go to **Admin Panel → Announcements**
- Choose audience: Everyone, Students Only, or Instructors Only

### Edit Pages (About, Privacy, Terms)
- Go to **Admin Panel → CMS Pages**
- Click Edit on any page
- Edit content in the text area (supports HTML)

### Approve/Reject Instructors
- Go to **Admin Panel → Instructor Applications**
- Review each application and approve or reject

---

## Support

For technical support, contact ZTF University Institute:
- Email: info@ztfuniversity.com
- Website: www.ztfuniversity.com
- Location: Koumé – Bertoua, East Region, Cameroon

---

*ACADEXA is built with Laravel 12, PHP 8.2+, MySQL 8, and Bootstrap 5.*
*Powered by ZTF University Institute.*
