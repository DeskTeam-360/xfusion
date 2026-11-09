# Changelog — Xperience Fusion plugin

Version lives in `xfusion_plugin.php` (header + `XFUSION_PLUGIN_VERSION`). Bump it with every release
and add an entry here. Production runs the commit tagged for the version shown under
*Tools → FUSION Schema Check*.

## 1.3.0 — not yet on production (1.2.0 is live, commit `6e80cce`)

Needs on the Laravel side: companies `slug` column (`database/sql/wp_companies_add_slug.sql`, then
`php artisan companies:backfill-slugs`), `FUSION_API_TOKEN` + matching `XFUSION_API_BEARER_TOKEN` for the
schema check, and optionally `WP_SSO_SECRET` = `XFUSION_SSO_SECRET` for "Open in WordPress".

### Added
- Company URLs: `/{company-slug}/annual-readiness-plan/` (and `annual-readiness-review`, `quarterly-business-review`,
  `individual-readiness-review`, `1-on-1-alignment`); short aliases `/arp/ /arr/ /qbr/ /irr/ /1on1/` still work.
  Non-admins opening an old module page are moved to their company URL.
- WordPress administrators can open and edit every company; a warning banner shows when the company on screen is not theirs.
- One-time sign-in hand-off from the Laravel admin ("Open in WordPress"), enabled only when `XFUSION_SSO_SECRET` is defined.
- `[fusion_core_objects]` shortcode: the Core Operating Objects menu, linking to the visitor's company URLs.
- Tools → FUSION Schema Check: verifies required tables/columns (list kept in Laravel `config/fusion_schema.php`).
- QBR picker: organization-first create for administrators.
- Admin page **FUSION Overview** (wp-admin menu, also `[fusion_admin_overview]` on the front end for administrators): every company's
  ARP/ARR/QBR/IRR/1-on-1 in one place with links to open each record.
- WordPress administrators can view IRR and 1-on-1 read-only (all pairs of the opened company). They cannot create, edit, generate or
  sign, and never see either side's raw preparation; notes only once the meeting has started or finished. Laravel decides this from
  the user's WordPress role, not from any flag sent by the browser.

- **Company subdomains** (`<company>.<base domain>`), **off by default**. One WordPress installation and one database (not
  Multisite): the label in front of the base domain is read as the company's custom URL. The `/{slug}/…` path model keeps
  working as the fallback. Sign-in happens on the main domain only; a one-time token (the same mechanism as "Open in WordPress")
  starts the session on the company subdomain, so no cookie is shared between subdomains. On a company subdomain `wp-admin` and
  `wp-login.php` go back to the main domain, `admin-ajax.php` stays, pages are not cached, and a company address nobody owns
  returns 404. See "Turning on company subdomains" below.

### Known / before going live
- TEMP: the no-access redirect to `/resources/resource-menu/` is commented out in the ARP, ARR, QBR and IRR pickers
  (`*-picker.php`, search "TEMP: no-access"). Re-enable it for production.

### Fixed
- ARP Step 5: Executive Owners were blanked when saving without touching the form; a lone or comma-separated owner id loads again.
- Module tables keep their bottom border on the last row.
- Removed leftover debugging from the ARP AI review (`arp-ai-review-service.php`, commit `e8edc5e`).

## Turning on company subdomains

Nothing changes until `XFUSION_TENANT_BASE_DOMAIN` is defined. Do it per environment, after the checks below.

1. **Check the data** (Laravel server): `php artisan companies:check-slugs`. Every company needs a custom URL that works as a web
   address (lowercase letters, digits and dashes, at most 63 characters, not `www`/`admin`/`sandbox`/…). Fix in the company form,
   or clear the URL and run `php artisan companies:backfill-slugs`.
2. **DNS and SSL:** a wildcard DNS record `*` to the WordPress server and a certificate that covers it.
   Production `*.xperiencefusion.com` (e.g. behind Cloudflare). A wildcard certificate covers one label only, so the sandbox
   (`sandbox.xperiencefusion.com`) needs its own: either explicit test subdomains created in cPanel (AutoSSL issues each one) or a
   separate wildcard for `*.sandbox.xperiencefusion.com`.
3. **wp-config.php** (WordPress):
   ```php
   define('XFUSION_TENANT_BASE_DOMAIN', 'xperiencefusion.com');          // production
   define('XFUSION_TENANT_BASE_DOMAIN', 'sandbox.xperiencefusion.com');  // sandbox
   define('XFUSION_SSO_SECRET', '...');  // same value as WP_SSO_SECRET in Laravel's .env; required for the hand-over
   ```
   Do **not** define `COOKIE_DOMAIN`: sessions stay per host on purpose.
4. **Laravel `.env`:** `WORDPRESS_TENANT_BASE_DOMAIN=` the same base domain (links then use `https://<company>.<base>/…`),
   then `php artisan config:clear`.
5. **Test with two companies** (one leader, one outsider): open `<company>.<base>/annual-readiness-plan/` while signed out (must go
   to the main-domain login and come back signed in), then as an outsider (must land on their own company), then
   "Open in WordPress" from the Laravel admin, then log out from a company subdomain.
6. **Page cache / CDN:** do not cache company subdomains (the plugin sends no-cache headers and `DONOTCACHEPAGE`, but confirm
   the cache plugin or CDN honours them and keys by host).

To turn it off again, remove the constant from wp-config.php (and the `.env` value): links return to the path model.

Known limits: a person belongs to one company (the `company` user meta), so there is no chooser yet; logging out on the main
domain does not end sessions already created on company subdomains (log out on the subdomain); fonts/assets are still served from
the main domain, so a CDN or server rule may need `Access-Control-Allow-Origin` for fonts.
