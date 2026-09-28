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

## Mise à jour « LMS complet » (septembre 2026) — déploiement

Cette version ajoute : constructeur de cours (cours → modules → leçons avec volume horaire), upload vidéo par morceaux, lecture YouTube intégrée, quiz, devoirs, ressources, règles de progression, notifications et e-mails, messagerie, annonces, application installable (PWA) avec **mode hors ligne**, rapports, 2FA admin, sauvegardes.

**Sur le serveur, après avoir envoyé le code :**

```
composer install --no-dev --optimize-autoloader
php artisan migrate --force          # 4 migrations additives, aucune donnée supprimée
npm install && npm run build         # puis copier public/build vers public_html/build
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

1. **Tâche cron (obligatoire)** — une seule ligne dans hPanel/cPanel, toutes les minutes :
   `* * * * * php /home/<compte>/<dossier>/artisan schedule:run >> /dev/null 2>&1`
   Elle envoie les e-mails en file d'attente, les rappels de fin d'essai, fait la **sauvegarde quotidienne** de la base (`storage/app/backups`, 14 jours gardés) et nettoie les uploads abandonnés.
2. **E-mails** — renseigner `MAIL_*` dans `.env` (voir `.env.example`). Ensuite seulement, activer si souhaité « Exiger la vérification de l'adresse e-mail » dans Admin → Settings.
3. **HTTPS obligatoire** pour l'installation de l'application et le mode hors ligne (service worker).
4. **Vidéos** — stockées de façon privée dans `storage/app/private/videos` (jamais dans `public/`), servies uniquement aux inscrits. Prévoir l'espace disque. Taille max configurable (`LMS_VIDEO_MAX_MB`).
5. **Sécurité admin** — chaque administrateur active sa double authentification dans *Sécurité du compte*. Changer les mots de passe de démonstration.

**Tests automatisés :** `php vendor/bin/phpunit` (base SQLite en mémoire, aucune donnée réelle touchée).

### Langues (en, fr, es, pt, zh, ar)

Toute l'interface est traduite dans les 6 langues ; l'arabe s'affiche de droite à gauche.

- **Textes de l'interface** : jamais écrits en dur dans les vues. On écrit `{{ __('Texte en anglais') }}` (ou une clé de groupe comme `__('lms.save')`).
  - Chaînes « en anglais » : `resources/lang/{langue}.json` — `en.json` est généré par `node scripts/collect-translations.cjs`.
  - Clés de groupe : `resources/lang/{langue}/*.php` (lms, security, validation, messages…).
  - Les traductions es/pt/zh/ar (et le JSON français) sont écrites dans `resources/lang-src/` puis générées :
    `node scripts/write-locale.cjs es resources/lang-src/es.cjs` (idem pt, zh, ar, fr).
- **Contrôles** (lancés aussi par les tests) :
  - `php scripts/check-locales.php` — même clés et mêmes paramètres (`:name`…) dans chaque langue ;
  - `node scripts/collect-translations.cjs --check` — toute chaîne utilisée dans le code est traduite partout ;
  - `node scripts/find-hardcoded-text.cjs` — aucun texte en dur dans les vues.
- **Contenus** : catégories et pages (À propos, Confidentialité, Conditions) se traduisent dans l'admin (*Traductions*, *Pages du site*) ; chaque formateur peut traduire son cours dans les 6 langues. Un champ vide affiche la version de référence.
- **Certificats PDF** : générés dans la langue de l'étudiant (en, fr, es, pt) ; en anglais pour le chinois et l'arabe, que les polices PDF ne savent pas afficher.

### Évaluations et suivi des connaissances

- **Après chaque leçon** (vidéo/texte) : un quiz d’au moins **10 questions** ; la leçon n’est validée qu’avec **7/10 (70 %)** minimum. Le formateur peut être plus exigeant, jamais moins.
- **Après chaque module** : un exercice complet d’au moins 10 questions (70 %), qui débloque la suite dans un cours séquentiel.
- **Après le cours** : une évaluation finale d’au moins 20 questions (70 %), nécessaire pour le certificat.
- **Progression / régression** : l’évaluation finale sert aussi de *test de positionnement* (avant d’étudier, une seule fois, sans correction affichée) et de *réévaluation* (après réussite, au plus une fois tous les 7 jours ; rappel automatique au bout de 30 jours). La comparaison des scores donne le niveau de départ, le niveau actuel et la tendance ; chaque question peut être rattachée à un module pour mesurer la maîtrise par module.
- **Formateur** : onglet *Évaluations* du cours (plan complet), éditeur de questions avec import en texte, page *Suivi des connaissances* par cours, fiche de chaque étudiant (courbe), encart sur le tableau de bord et notification en cas de régression.
- **Étudiant** : page *Mes résultats* pour chaque cours.
- Un cours ne peut être soumis à validation que si tout le parcours d’évaluation est complet. Les cours déjà publiés sans quiz continuent de fonctionner comme avant.
- Réglages : `config/lms.php` → `assessment` (`LMS_RETAKE_COOLDOWN_DAYS`, `LMS_REASSESS_AFTER_DAYS`).

### Bibliothèque et lecture hors ligne

- Le formateur ajoute des **livres** (PDF, audio, vidéo — onglet *Livres* du cours, `LMS_BOOK_MAX_MB`).
- L’étudiant les ajoute à **Ma bibliothèque** : la liste est enregistrée **dans son compte**, pas dans les fichiers de l’appareil. Sur chaque appareil où il se connecte, l’application en garde une copie privée (effacée à la déconnexion) et la position de lecture est synchronisée.
- PDF, audio, vidéo et images (livres et ressources des leçons) s’ouvrent dans le **lecteur intégré**, en ligne comme hors ligne (`/offline`).
- Hors ligne, les quiz de leçon et exercices de module donnent un résultat provisoire ; la correction du serveur à la reconnexion fait foi. L’évaluation finale se passe en ligne.

**Déploiement de cette version :** `php artisan migrate --force` (1 migration additive), `npm install && npm run build`, et la tâche cron existante (elle lance aussi `acadexa:reassessment-reminders`). Les traductions du groupe `learn` sont générées par `node scripts/write-group.cjs learn resources/lang-src/learn.cjs`.

### Icônes

Aucun emoji : toutes les icônes sont des **SVG** (jeu Bootstrap Icons) regroupés dans `resources/icons/sprite.svg`, servi par `/icons.svg` et disponible hors ligne.

- Blade : `<x-icon name="play-circle" class="me-1" />` — JavaScript : `icon('play-circle', 'me-1')` (`resources/js/icons.js`).
- Après avoir utilisé une nouvelle icône : `npm run icons` (lancé aussi automatiquement par `npm run build`). La liste des noms : https://icons.getbootstrap.com

---

## Déploiement sur Railway

1. **New Project → Deploy from GitHub repo** (ce dépôt), puis **+ New → Database → MySQL**.
2. Variables du service web :
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=base64:...            (php artisan key:generate --show)
   APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}
   DB_CONNECTION=mysql
   DB_URL=${{MySQL.MYSQL_URL}}
   SESSION_SECURE_COOKIE=true
   LOG_CHANNEL=stderr
   PHP_INI_SCAN_DIR=:/app/deploy/php
   MAIL_...                       (SMTP)
   ```
   `PHP_INI_SCAN_DIR` charge `deploy/php/acadexa.ini` (taille des fichiers envoyés).
3. **Volume** monté sur `/app/storage/app` (vidéos, livres, ressources, certificats — sinon perdus à chaque déploiement).
4. **Pre-deploy command** : `composer deploy` (migrations, seeders si la base est vide, lien de stockage, caches — voir `composer.json`)
5. 2ᵉ service (même dépôt, mêmes variables, sans domaine) avec la commande de démarrage `php artisan schedule:work` : e-mails, rappels, nettoyage.
6. **Generate Domain**. Au premier déploiement, les comptes et données de démonstration sont créés automatiquement : changer aussitôt les mots de passe de démonstration.

Vidéos : **20 Mo maximum** par leçon (`LMS_VIDEO_MAX_MB`), envoyées par morceaux de 1 Mo.

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
