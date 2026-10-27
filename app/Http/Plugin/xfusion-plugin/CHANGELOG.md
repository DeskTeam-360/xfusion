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

### Known / before going live
- TEMP: the no-access redirect to `/resources/resource-menu/` is commented out in the ARP, ARR, QBR and IRR pickers
  (`*-picker.php`, search "TEMP: no-access"). Re-enable it for production.

### Fixed
- ARP Step 5: Executive Owners were blanked when saving without touching the form; a lone or comma-separated owner id loads again.
- Module tables keep their bottom border on the last row.
- Removed leftover debugging from the ARP AI review (`arp-ai-review-service.php`, commit `e8edc5e`).
