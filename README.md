# MRKLinkhub

Bilingual link page (Persian and English) with an admin panel, visit and click stats, and a one-click update from GitHub. It runs on PHP 8 and MySQL. No Composer and no command-line git are required on the server.

## Install on cPanel

1. Open **MultiPHP Manager** and set the domain to **PHP 8.0 or newer**. In **Select PHP Version** (or MultiPHP INI), enable `pdo_mysql` and `zip`. Enable `curl` as well when you want updates from the admin panel.
2. Open **MySQL Databases**.
   1. Create a database.
   2. Create a database user and set its password.
   3. Add that user to the database with **All Privileges**.
   4. Write down the host (usually `localhost`), the full database name, the full username, and the password. cPanel adds your account name in front of the database name and the username.
3. Open the [releases page](https://github.com/mrkioumarsi/MRKLinkhub/releases) and download the **Source code (zip)** of the latest release.
4. In **File Manager**, go to `public_html`, or to the document root of the addon domain.
5. Upload the zip into that folder and extract it.
6. Move the files up if they were extracted into a subfolder. `index.php` must sit directly in the document root, next to the `admin`, `inc`, `install`, and `uploads` folders.
7. Open the `uploads` folder and set its permission to `755`. The installer creates this folder when the server allows it.
8. Visit `https://your-domain.example/install/`.
9. Fill in the form:
   - Database host, database name, database user, and database password from step 2.
   - Admin username: 3 to 30 letters or digits.
   - Admin password: at least 10 characters.
10. Click **Install**. The installer creates the tables, writes `inc/config.php`, and deletes itself.
11. If the `install` folder is still there, delete it.
12. Open `https://your-domain.example/admin/` and sign in.
13. In **Settings**, save the profile, the languages, and the PWA icon. Use a square PNG, JPG, or WebP of at least 512 pixels for the icon.

`inc/config.php` holds the database password. Do not share that file or commit it. The sample without secrets is `inc/config.sample.php`.

## Update from the admin panel

1. Sign in at `/admin/`.
2. Open **Settings**.
3. Use **Update from GitHub** when a newer release exists.
4. Use **Reinstall current version** to install the latest release again when the site is already on that version.

The update downloads the release zip over HTTPS and replaces the site files. It keeps these in place:

- `inc/config.php`
- everything inside `uploads`
- the existing root `.htaccess`, so a cPanel PHP handler is not removed

The server needs PHP `ZipArchive`. It does not need shell access or a `git` binary. If the home directory is writable, the zip is unpacked outside the public site and then removed.

## What is included

- Public page: profile, slider, links, work, projects, education, papers, language switch, light and dark theme. Empty sections stay hidden. Section order and SEO text are set in the admin.
- Admin: dashboard, content, settings, and password. The admin can be added to the home screen from `/admin/`.
- One visit is counted per browser per day. Link, slide, and project clicks are counted whenever they happen.

## License

MRKLinkhub is free and open source under the [MIT License](LICENSE).

## Donate

https://mrkioumarsi.ir/donate.php?lang=en
