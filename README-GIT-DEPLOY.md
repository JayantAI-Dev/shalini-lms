# Shalini Rathod Academy WordPress code deployment

This repository is rooted at WordPress `wp-content`, but deliberately tracks only:

- `themes/sr-academy/`
- `plugins/sr-academy-lms/`

WordPress core, the database, uploads, backups, third-party plugins, and server configuration stay outside Git.

## Deployment model

Push reviewed changes to `main`; GitHub Actions then syncs the theme and custom LMS plugin to their existing Hostinger folders over encrypted FTPS. The two deploy steps target only those folders. They do not clean the WordPress content directory or the full website root.

Do not use Hostinger's single-directory Git deployment targeting the whole `wp-content` folder for this monorepo. That folder also contains uploads and other plugins.

## One-time GitHub setup

Create a **private** repository for this WordPress code and push this repository's `main` branch to it. In that repository, open **Settings → Secrets and variables → Actions** and add:

Repository secrets:

- `HOSTINGER_FTP_SERVER`
- `HOSTINGER_FTP_USERNAME`
- `HOSTINGER_FTP_PASSWORD`

Repository variables:

- `HOSTINGER_WP_CONTENT_DIR`: the relative path from the FTP account's starting directory to this site's `wp-content` directory. Hostinger accounts may use `public_html/wp-content` or a domain-specific path; use the exact path shown by the account's File Manager.
- `HOSTINGER_FTP_PORT`: optional; omit to use port `21`.

Use the Hostinger staging site's FTP account with access limited to the site's `wp-content` directory where possible. Never put its password, Google OAuth credentials, Razorpay keys, or WordPress configuration in the repository.

After the secrets and variable are in place, a push to `main` triggers deployment. Pull GitHub changes locally with `git pull`; make code changes in the repository and deploy via GitHub, not by editing theme/plugin files in WordPress or Hostinger File Manager.
