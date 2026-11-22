# MRKLinkhub

Bilingual link page (Persian and English) with an admin panel, visit and click stats, and a one-click update from GitHub. It runs on PHP 8 and MySQL. No Composer and no command-line git are required on the server.

- Public page in Persian and English, with a light and dark theme
- Profile, slider, links, work, education, projects, and papers
- Admin panel for the content and the settings
- Visit and click statistics
- Optional donate page
- SEO fields and a movable section order
- Update from the admin panel

## Install on cPanel

1. Set the domain to **PHP 8.0 or newer** and turn on `pdo_mysql`, `zip`, and `curl`.
2. In **MySQL Databases**, create a database and a user, then give that user **All Privileges** on the database. Keep the host (usually `localhost`), the full database name, the full username, and the password. cPanel adds your account name in front of the database name and the username.
3. Download the latest **Source code (zip)** from the [releases page](https://github.com/mrkioumarsi/MRKLinkhub/releases). In **File Manager**, upload it to `public_html` (or the addon domain root) and extract it. `index.php` must sit in that folder, next to `admin`, `inc`, `install`, and `uploads`. If the zip created an extra folder, move the files up.
4. Open `https://your-domain.example/install/`. Enter the database details, an admin username (3 to 30 letters or digits), and an admin password (at least 10 characters), then click **Install**.
5. Open `https://your-domain.example/admin/` and sign in. If the `install` folder is still there, delete it. If images fail to save, set the `uploads` folder to `755`.

The installer creates the tables, writes `inc/config.php`, and deletes itself. `inc/config.php` holds the database password. Do not share that file or commit it. The sample without secrets is `inc/config.sample.php`.

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

## License

MRKLinkhub is free and open source under the [MIT License](LICENSE).

## Donate

https://mrkioumarsi.ir/donate.php?lang=en
