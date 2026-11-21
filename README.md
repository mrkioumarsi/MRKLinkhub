# MRKLinkhub

Bilingual link page (Persian and English) with an admin panel, visit and click stats, and a one-click update from GitHub. It runs on PHP 8 and MySQL. No Composer and no command-line git are required on the server.

## Install on cPanel

1. In cPanel, open **MultiPHP Manager** and set the domain to **PHP 8.0 or newer**. `pdo_mysql` and `zip` must be enabled. `curl` is used for updates when it is available.
2. Open **MySQL Databases**. Create a database and a user, then add the user to the database with **All Privileges**. Note the host (usually `localhost`), database name, user, and password. cPanel prefixes both names with your account name.
3. Download the release zip from the [releases page](https://github.com/mrkioumarsi/MRKLinkhub/releases). In **File Manager**, open `public_html` (or the addon-domain document root). Upload the zip and extract it so `index.php` sits directly in that folder, next to `admin`, `inc`, `install`, and `uploads`.
4. Select the `uploads` folder, then **Permissions**, and set it to `755`. The installer creates this folder when it can.
5. Visit `https://your-domain.example/install/`.
6. Enter the database host, name, user, and password. Choose an admin username of 3 to 30 letters or digits, and a password of at least 10 characters. Submit **Install**.
7. The installer writes `inc/config.php` and then deletes itself. If the `install` folder is still there, delete it. Open `/admin/` and sign in.
8. In **Settings**, fill in the profile, languages, and PWA icon. The PWA icon should be a square PNG, JPG, or WebP of at least 512 pixels.

`inc/config.php` holds the database password. Do not share that file or commit it. The sample without secrets is `inc/config.sample.php`.

## Update from the admin panel

Sign in, open **Settings**, and use **Update from GitHub**. The panel reads the latest release of `mrkioumarsi/MRKLinkhub`, downloads the zip over HTTPS, and replaces the site files. **Reinstall current version** installs that same release again when the site is already on the latest version.

The update keeps these in place:

- `inc/config.php`
- everything inside `uploads`
- the existing root `.htaccess`, so a cPanel PHP handler is not removed

The server needs PHP `ZipArchive`. It does not need shell access or a `git` binary. If the home directory is writable, the zip is unpacked outside the public site and then removed.

## What is included

- Public page: profile, slider, links, projects, language switch, light and dark theme.
- Admin: dashboard, content, settings, and password. The admin can be added to the home screen from `/admin/`.
- One visit is counted per browser per day. Link, slide, and project clicks are counted whenever they happen.
