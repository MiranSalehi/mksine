---
title: Installing MKSine (download)
description: Install MKSine from the release zip on shared hosting (cPanel, DirectAdmin) or on your own server.
---

# Installing MKSine

This guide is for site owners installing MKSine from the download on mksine.com. Developers who add MKSine to an existing Laravel app with Composer should follow [Installation](../../01-installation.md) instead.

## What you need

- **PHP 8.4 or newer** with these extensions: `ctype`, `curl`, `fileinfo`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `zip`, and a database driver (`pdo_mysql` for MySQL/MariaDB). `intl`, `sodium` and `gd` are recommended. Most hosts enable all of these; the installer checks them for you.
- **An empty database** (MySQL 5.7+, MariaDB 10.3+ or PostgreSQL) and a user with full access to it. SQLite also works for small sites.
- **Access to your files**: the hosting panel's File Manager, FTP or SSH.

## Quick install (recommended)

Instead of uploading the whole zip, upload one small file and let it fetch MKSine for you.

1. Create the database and choose PHP 8.4 or newer (steps 1 and 2 under [Shared hosting](#shared-hosting-cpanel-directadmin)).
2. Download `mksine.php` from mksine.com and upload it to `public_html` (your domain's web folder).
3. Open `https://YOURDOMAIN/mksine.php`. It checks PHP, extensions, folder permissions and disk space, and shows where the files will go.
4. Click **Download and install**. Keep the page open. It downloads the newest release from GitHub in small parts (a slow host does not time out), checks its SHA-256 checksum, extracts it and deletes itself. A host placeholder page such as `index.html` is renamed to `index.html.mksine-old`; your host's lines in `.htaccess` (such as the PHP version) are kept.
5. The MKSine installer opens at the *Requirements* step. Continue from [step 4](#4-run-the-installer) below.

Only the browser that clicked **Download and install** can run the installation. If it was closed, open `mksine.php` in another browser: after 10 minutes without activity it offers **Continue here**.

Notes:

- The application goes to a `mksine/` folder **next to** the web folder, as in the manual install. If that folder already contains files, or the web folder already has an `index.php`, it stops and tells you what to remove.
- If your host blocks downloads from the internet, download `mksine-{version}-shared-hosting.zip` yourself and upload it next to `mksine.php`. The installer uses the uploaded zip.
- On your own server, when the web root is a folder named `public` with nothing else beside it (for example `/var/www/example.com/public`), the application goes to its parent folder and `mksine-{version}-server.zip` is used.

## Which file to download

| File | For |
| ---- | --- |
| `mksine.php` | [Quick install](#quick-install-recommended): upload one file, it downloads the rest |
| `mksine-{version}-shared-hosting.zip` | cPanel, DirectAdmin and other hosting panels |
| `mksine-{version}-server.zip` | Your own server (VPS) where you control the web server |
| `SHA256SUMS` | Optional: checksums to confirm the download is intact (`shasum -a 256 -c SHA256SUMS`) |

## Shared hosting (cPanel, DirectAdmin)

The shared-hosting zip contains two folders:

```
mksine/        the application: code, settings, database files — never served to visitors
public_html/   only the public files: index.php, CSS, JavaScript, images
```

The application lives **next to** `public_html`, not inside it, so files such as `.env` (which holds your database password) can never be downloaded from the browser.

### 1. Create the database

- **cPanel:** *Databases → MySQL Database Wizard*. Create a database, a user and a password, and give the user **All privileges**.
- **DirectAdmin:** *Account Manager → MySQL Management → Create new Database*.

Note the database name, user name and password. On most hosts they start with your account name, for example `myuser_mksine`.

### 2. Choose PHP 8.4 or newer

- **cPanel:** *Software → MultiPHP Manager* (or *Select PHP Version*).
- **DirectAdmin:** *Extra Features → Select PHP Version* (or *Domain Setup → PHP version*).

### 3. Upload and extract

1. Open the **File Manager** and go to the folder that **contains** `public_html`:
   - cPanel: your home folder, `/home/USERNAME`
   - DirectAdmin: `/home/USERNAME/domains/YOURDOMAIN`
2. If `public_html` holds a host placeholder page (`index.html`, `default.html`), delete it. Back up anything else that is there.
3. Upload `mksine-{version}-shared-hosting.zip` to this folder. If the panel refuses a large upload, use FTP.
4. Select the zip and choose **Extract**. You now have `mksine/` next to `public_html/`, and the public files inside `public_html/`.
5. Delete the zip.

If your domain's web folder is not called `public_html` (addon domains often use their own folder), copy the **contents** of the extracted `public_html/` into that folder instead. `mksine/` must sit in the **same parent folder** as the web folder and must not be inside a web-accessible folder.

### 4. Run the installer

Open your domain in the browser. Every page goes to the installer at `/install` until installation is finished.

1. **Language.** Choose فارسی or English.
2. **Requirements.** The installer checks PHP, extensions and folder permissions. If something is missing, ask your host to enable it, then click *Check again*.
3. **Database.** Enter the details from step 1. The host is usually `localhost`. The connection is tested before anything is saved.
4. **Site and admin.** Site name and address, site language and your admin account. The default theme is activated; plugins are turned on later in the admin panel (**Plugins**).
5. **Install.** Keep the page open. Each step runs separately so slow hosting does not time out; if a step fails, fix the cause and click *Try again* — it continues where it stopped.

The browser that chose the language runs the installation. If you continue in another browser (or another device), it asks for a code from the file `mksine/SETUP-TOKEN.txt`, so that nobody else who opens your site can take it over. Open that file in the File Manager and enter the code on its first line.

When it finishes, the installer closes itself: `/install` no longer exists and `SETUP-TOKEN.txt` is deleted.

### 5. Add the cron job

MKSine needs one cron job that runs every minute for scheduled tasks (backups, reminders, emails) and the queue. The last installer screen shows the exact line, for example:

```bash
* * * * * php /home/USERNAME/mksine/artisan schedule:run >> /dev/null 2>&1
```

- **cPanel:** *Advanced → Cron Jobs*. Choose *Once Per Minute*, paste the command.
- **DirectAdmin:** *Advanced Features → Cronjobs*. Set every field to `*`, paste the command.

Some hosts need the full PHP path instead of `php`, for example `/usr/local/bin/php` or `/opt/cpanel/ea-php84/root/usr/bin/php`. Your host's documentation or support can tell you which one.

### 6. Check that everything works

Log in at `https://YOURDOMAIN/admin` and open **System → System health**. A few minutes after adding the cron job, the scheduler and the queue should show **OK**. The page lists anything that still needs attention and how to fix it.

On shared hosting it is normal that *Background processes* shows *Disabled*; the admin console is hidden and everything else works.

## Your own server (VPS)

1. Extract `mksine-{version}-server.zip`, for example to `/var/www/mksine`.
2. Let the web server user write to the folders it needs:

   ```bash
   cd /var/www/mksine
   sudo chown -R www-data:www-data storage bootstrap/cache public
   sudo chown www-data:www-data .   # lets the installer create .env
   ```

3. Point the web server at the `public/` folder. MKSine prints a ready configuration:

   ```bash
   php artisan mksine:server-config nginx --domain=example.com
   php artisan mksine:server-config apache --domain=example.com
   ```

4. Install either in the browser (open your domain; the steps are the same as above) or from the terminal:

   ```bash
   cp .env.example .env   # then set DB_* and APP_URL
   php artisan mksine:install --migrate
   ```

5. Add the cron job and a queue worker:

   ```bash
   php artisan mksine:server-config cron
   php artisan mksine:server-config supervisor
   ```

6. Run `php artisan mksine:doctor`, then check **System → System health** in the admin panel.

## Troubleshooting

| Problem | What to do |
| ------- | ---------- |
| A blank page or "500 Server Error" before the installer appears | Check the PHP version (8.4+) and that `mksine/storage` and `mksine/bootstrap/cache` are writable. The error is in `mksine/storage/logs/laravel.log`. |
| A "Composer dependencies require a PHP version" message | Your domain uses an older PHP. Change it in the panel (step 2). |
| The host's placeholder page shows instead of the installer | Delete `index.html` / `default.html` from `public_html`. |
| `mksine.php` says it could not read the latest release | Your host blocks outgoing downloads. Upload the shared-hosting zip next to `mksine.php` and click *Check again*. |
| `mksine.php` asks to be moved to the site's root folder | Upload it directly into the domain's web folder, not into a subfolder. |
| The installer asks for a code | The installation was started in another browser. Enter the code from `mksine/SETUP-TOKEN.txt`, or go back to that browser. If the file cannot be created, make the `mksine/` folder writable (permissions 755). |
| "Could not connect to the database" | Use `localhost` as the host, and the full prefixed database and user names. Make sure the user has all privileges on the database. |
| A step fails during installation | Read the message under the step, fix the cause, and click *Try again*. Finished steps are not repeated. |
| "Public storage link" warning in System health | Your host does not allow symbolic links. Uploaded media still works; ask your host to allow symlinks if a plugin needs files on the public disk. |
| Scheduler shows "Never" or "Late" | The cron job is missing or uses the wrong PHP path. |

## Updating

One-click updates from the admin panel are not available yet for the core. Follow the release notes on mksine.com for each version. Plugins and themes can already be updated from the admin panel.
