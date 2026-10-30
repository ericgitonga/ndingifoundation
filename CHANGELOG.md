# Changelog

## 0.4.10 — 2026-10-30

Harden contact form against automated spam bots and protect Zoho SMTP sender reputation (#14):
- **7-layer defense-in-depth contact form protection (#14)**:
  1. **Multi-field disguised honeypots**: Added off-screen `.contact-form__aux` text trap (`name="website"`, `autocomplete="nope"`) alongside `botcheck` in `wp-content/themes/ndingi-wp/template-parts/contact-form.php` and `assets/css/main.css`.
  2. **Signed timestamp time-trap & single-use replay lock**: Added `ndingi_ts`, `ndingi_seed`, and HMAC-SHA256 `ndingi_sig` verification in `wp-content/plugins/ndingi-wp-content/includes/contact-form-handler.php`, enforcing a 4-second minimum fill time, 2-hour maximum token lifetime, and transient-backed single-use signature replay prevention.
  3. **Client-side human interaction proof token**: Updated `wp-content/themes/ndingi-wp/assets/js/modal.js` to populate `ndingi_js_token` from `data-contact-seed` only after real user interaction (`focusin`, `keydown`, `pointerdown`, or `touchstart`), verified server-side via `ndingi_contact_expected_js_token()`.
  4. **Heuristic & content spam filter (`ndingi_is_spam_submission()`)**: Blocked URLs in `Name`/`Subject`, `> 1` URL in `Message`, high-risk spam/shortener/paste domains (`telegra.ph`, `t.me`, `bit.ly`, `tinyurl.com`, `.ru`, `.xyz`, `.top`, `.click`, etc.), ALL-CAPS shouting (`> 60%` uppercase across `>= 15` letters), single-word CamelCase bot names (`EdwardWaw`), financial/crypto/SEO scam lures (`earn $... per month`, etc.), and WordPress Disallowed Comment Keys, plus sanitized `Reply-To` headers against header injection.
  5. **Per-IP & global rate limiting (`ndingi_contact_is_rate_limited()`)**: Enforced transient-backed caps of 2 submissions per 15 minutes per IP, 5 per 24 hours per IP, and a global circuit breaker of 15 contact emails per hour.
  6. **Unique form DOM IDs**: Updated `template-parts/contact-form.php` to prefix input IDs with `wp_unique_id('contact-')` so the header Contact modal and `/contact/` page no longer duplicate HTML IDs.
  7. **Host & staging SMTP hardening**: Rotated placeholder `wp-config.php` security keys/salts on `ndingifoundation.org` and enabled FluentSMTP `simulate_emails: "yes"` on staging `ndingi.ericgitonga.com`.
- Bumped `VERSION`, theme `style.css`, and plugin `ndingi-wp-content.php` headers to `0.4.10`.

## 0.4.9 — 2026-10-29

Add recovered Archbishop Ndingi portraits to **Who We Are** and standardize Trustee & Board of Management titles/names (#10, #11):
- **Archbishop Ndingi portraits on Who We Are (#10)**: Bundled the two portraits recovered from the legacy site (`archbishop-ndingi-portrait.jpg` and `archbishop-ndingi-star.png`), added responsive `.founder-gallery` styling in `assets/css/main.css`, and updated `includes/page-setup.php` and `local-preview/seed.php` (plus live `ndingifoundation.org` Media Library & **Who We Are** page content) to display both portraits in the *Archbishop Raphael Simon Ndingi Mwana’a Nzeki* section.
- **Trustee & Board of Management naming & title updates (#11)**: Updated **Bishop Dominic Kimengich** to **Archbishop Dominic Kimengich** on the Trustees roster, standardized **Mr. James Nyiha** to **James Nyiha** across Trustees and Board of Management, and ensured **Fr. Antony Mwituria** remains exclusively on the Board of Management roster (both on live `ndingifoundation.org` and idempotently in `ndingi_page_setup_run()`).
- Bumped `VERSION`, theme `style.css`, and plugin `ndingi-wp-content.php` headers to `0.4.9`.

## 0.4.8 — 2026-10-27

Deactivate and remove the one-time **Tools → Ndingi People Import** utility now that `ndingifoundation.org` is live on WordPress (#9):
- Removed `includes/people-import.php` and `local-preview/import-real-people.php` (and its `require_once` in `ndingi-wp-content.php`) so existing `Team Members` entries can never be overwritten from the legacy Sanity dataset.
- Bumped `VERSION`, theme `style.css`, and plugin `ndingi-wp-content.php` headers to `0.4.8`.

## 0.4.7 — 2026-10-26

Production cutover to `ndingifoundation.org`, frontend & `wp-admin` parity sync, and contact form Zoho SMTP delivery fix (#3, #4, #5, #6, #7):
- **Database cutover (#4)**: Resolved HTTP 500 database connection failure on `ndingifoundation.org` caused by `wp-config.php` referencing `ndingi.ericgitonga.com`'s internal MySQL host (`angry-sd-1` / `egimba_ndingi`), cutting over to `localhost` (`dukkapu1_ndingi1`).
- **Pretty permalink routing (#5)**: Deployed standard WordPress `mod_rewrite` rules to `/public_html/.htaccess` on `ndingifoundation.org` (and tracked `.htaccess.example` in the repo), fixing HTTP 404 errors across all nested routes (`/about/`, `/our-work/`, `/partners/`, `/resources/`, `/donate/`, `/contact/`).
- **Contact form recipient fix (#6)**: Updated `ndingi_handle_contact_submission()` in `wp-content/plugins/ndingi-wp-content/includes/contact-form-handler.php` to send to `ndingi_contact_email()` (`admin@ndingifoundation.org`) instead of `get_option('admin_email')` (which was still set to `gitonga@gmail.com`), and updated `wp_options.admin_email` on production so Zoho FluentSMTP (`smtp.zoho.com:465`) delivers to `admin@ndingifoundation.org`.
- **Full `wp-admin` editable content & editor preference parity (#7)**: Synced `wp_persisted_preferences` (`metaBoxesMainIsOpen: true`, `welcomeGuide: false`) for both admin users on `ndingifoundation.org`, copied all 19 `ndingi_*` post meta entries on `Home` (`post=53`), synced block editor content on `Who We Are` (`55`), `Mission` (`56`), `Education` (`61`), and `Today here` (`116`), seeded the 5 `partner` CPT entries via `ndingi_page_setup_run()`, and synced the `Longonot` draft post plus all 35 Media Library attachment records.

## 0.4.6 — 2026-10-08

Sync live site download, update `.gitignore` for WordPress core, and make `seed.php` docroot-safe (#2):
- Update `.gitignore` to exclude WordPress core files, `wp-config.php`, default themes, and live runtime directories (`uploads/`, `languages/`, `upgrade/`, `ai1wm-backups/`), ensuring live production database credentials are never committed.
- Guard `local-preview/seed.php` to conditionally locate `wp-load.php` in both docroot and `.wp-runtime` environments without fatal errors when run via WP-CLI.
- Sync theme and plugin version headers to 0.4.6.

## 0.4.5 — 2026-09-15

Fixed the sticky navbar breaking partway down long pages (reported on Home: scroll past "Our
Work" and the navbar disappears instead of staying pinned). Root cause: `assets/css/main.css`
set `html, body { height: 100%; }`, which pins `body`'s box to exactly one viewport height —
`body`'s own `min-height: 100%` a few lines down never took effect, since `height` was already
an explicit value, not `auto`. The sticky header's containing block is `body`, so it could only
stay "stuck" within that first viewport's worth of scroll; past it, content kept rendering
(via `overflow: visible`) but the header was no longer contained and just scrolled away with
the rest of the page. Fix: `height: 100%` now applies to `html` only — `body` keeps its
existing `min-height: 100%`, which now actually works as intended (full-height on short pages,
grows normally on long ones). Verified via Playwright: `body`'s rendered height went from a
fixed 900px to the page's real 2277px, and the header stayed at `top: 0` across the entire
scroll range on both Home and Our Work.

## 0.4.4 — 2026-09-15

New **Tools → Ndingi People Import** wp-admin page (same pattern as Ndingi Page Setup) —
one click imports the real Management Team/Trustees/Board of Management roster (names, roles,
bios, photos) live from Sanity, for a host with no WP-CLI/SSH access to run it themselves.
`local-preview/import-real-people.php` is now a thin CLI wrapper around the same
`ndingi_people_import_run()` the admin page calls, instead of duplicating the logic.

Also fixed version drift: the theme (`style.css`) and plugin (`ndingi-wp-content.php`) header
`Version:` fields had been stuck at 0.4.0 since three VERSION bumps ago — now synced to match
`VERSION` on every release, going forward.

## 0.4.3 — 2026-09-15

New `local-preview/import-real-people.php` — an opt-in sibling to `seed.php` that replaces the
local preview's placeholder "Sample …" team members with the real roster pulled live from
`ndingi-foundation`'s public Sanity dataset (same project/dataset the Next.js site reads).
Not run automatically by `setup.sh` — `seed.php` stays offline-friendly by design — but useful
whenever a demo or client-facing screenshot needs to show real content instead of placeholders:

```bash
php .wp-runtime/wp-cli.phar --path=.wp-runtime eval-file local-preview/import-real-people.php
```

Deletes every existing `team_member` post first, so it's safe to re-run without duplicating.
Used to populate all 12 real Management Team/Trustees/Board of Management entries (names,
roles, bios, photos) for a client-facing page-snapshot PDF.

## 0.4.2 — 2026-09-15

Three more items from Valerie's client feedback:

- **Partners page** — removed the placeholder note under "Programme & Donor Partners"
  ("Additional partner organisations… will be listed here once their authorisation…
  has been received"); the section heading stays for when those logos are added.
- **Donate page** — removed the "A dedicated online payment platform… is in development"
  note from `template-parts/donate-info.php`; not meant for public visibility per the
  client.
- **"Board of Directors" renamed to "Board of Management"** throughout — the People page
  section, the `ndingi_roster` taxonomy term, the roster page's title/slug handling, and
  the Page Setup tool's page title and card blurb (`local-preview/seed.php` too, for local
  dev). The Page Setup tool and the roster-term seeder now rename an already-created page
  or term in place instead of creating a duplicate under the new title — verified via
  WP-CLI against a locally-seeded "Board of Directors" page/term, which came back renamed
  with no duplicate and its original `board-of-directors` slug still resolving correctly.

## 0.4.1 — 2026-09-15

Home and About no longer show the small navbar logo alongside their large prominent logo
(client feedback via Valerie: "remove the small logo on the home page to leave the
big/major one"). `header.php` now skips the navbar logo on `is_front_page()` and the About
Hub template, and shifts the nav to the right edge in its place (new
`.site-header--no-logo` modifier in `main.css`) so the header row doesn't look
left-anchored with nothing to balance it. Every other page is unaffected — navbar logo
only, as before.

## 0.4.0 — 2026-09-12

Fixed the live host reporting About, Our Work, and Resources as missing (500/critical
error and 404) after the theme + plugin were uploaded: the `ndingi-wp-content` plugin was
never activated alongside the theme, so every page calling one of its functions
(`ndingi_get_child_pages()`, `ndingi_get_core_values()`, `ndingi_get_roster()`,
`ndingi_publication_url()`) hit a PHP fatal error — the homepage only survived because it
happens to skip that call when Our Work doesn't exist yet. Two changes:

- Every call site now goes through a new `ndingi_safe_get_child_pages()` /
  `ndingi_safe_get_core_values()` / `ndingi_safe_get_roster()` /
  `ndingi_safe_publication_url()` wrapper (`inc/template-helpers.php`) that degrades to an
  empty result instead of a fatal "critical error" white screen if the plugin is ever
  inactive, plus a red `admin_notices` warning naming exactly which pages depend on it.
- New **Tools → Ndingi Page Setup** admin page (in the plugin) creates the full page
  hierarchy — same titles, templates, parents, and real copy as
  `local-preview/seed.php`, minus its placeholder team members/news/publication — with one
  click, so a page missing on a host with no WP-CLI/SSH access (like Our Work here) doesn't
  need creating by hand. Safe to run repeatedly: anything already found by title is left
  untouched.

Verified against `ndingifoundation.org` directly and reproduced the exact failure (and the
fix) in the local preview by deactivating the plugin. Also confirmed live that the
Contact/Donate nav modals open correctly on both desktop and mobile — no code issue found
there.

## 0.3.2 — 2026-09-10

Reverted the logo back to the cream section above the hero — the client felt its colours
didn't sit well on the orange gradient — but trimmed the hero's top padding so "Empowering
Communities…" now sits immediately below the logo with no dead space. Matches the same
revision on the Next.js site.

## 0.3.1 — 2026-09-10

Moved Home's prominent logo into the orange hero section, directly above "Empowering
Communities…" — the hero now starts immediately after the navbar again, matching client
feedback (and the same fix on the Next.js site).

## 0.3.0 — 2026-09-10

Home and About now show a large, centred logo above their content (new
`template-parts/prominent-logo.php`), matching the same change on the Next.js site —
every other page keeps the logo in the header navbar only. About also drops its
"About" heading/text entirely: just the logo, then the Who We Are and People cards.

## 0.2.0 — 2026-09-10

Our Work's programme cards (`/our-work/` and the homepage's preview grid) now show the client's
supplied photograph for each programme, with the title and blurb underneath, instead of an
icon tile — click-to-modal behaviour is unchanged. Along the way, fixed a real bug this surfaced:
the homepage's Our Work grid was rendering zero cards, because its page lookup used a bare
slug (`get_page_by_path('education')`) that can't find a page nested under Our Work — replaced
with the same child-page lookup `page-our-work.php` already uses, removing a hardcoded slug
list in the process. Also replaced `get_page_by_title()` (fully deprecated since WP 6.2) in
`local-preview/seed.php` with a small `WP_Query`-based helper.

## 0.1.0 — 2026-09-10

Initial scaffold: full WordPress port of the ndingi Next.js/Sanity site, evaluated as a
separate repo. Custom theme (`ndingi-wp`) + content-model plugin (`ndingi-wp-content`) covering
every route from the original — homepage, About hub, Who We Are + detail grid, Mission,
Vision, Core Values, People accordion, Management Team/Trustees/Board of Directors rosters,
Our Work hub, the three programme pages, Partners, Resources hub, News list + single post,
Publications, Donate, Contact — plus a native `wp_mail()` contact form handler and a
Docker-free local preview via WordPress's SQLite database integration.
