# ndingi-wp

A WordPress rebuild of the [Ndingi Foundation site](https://github.com/ericgitonga/ndingi)
(currently Next.js + Sanity on Vercel), built as a separate repository so it can be evaluated
side by side without committing to a migration. If the client sticks with the Next.js version,
nothing here needs to be touched or reconciled.

This is a **fully self-hosted, PHP-only stack with no build step** — no Node, no npm install, no
Tailwind/PostCSS pipeline. Everything under `wp-content/` is plain PHP, CSS, and vanilla JS, so
it will run on ordinary shared/cPanel hosting as-is.

## What's here

- `wp-content/themes/ndingi-wp/` — the theme: templates, styling, and the icon/breadcrumb/modal
  helpers that port ndingi's shared React components (`Header`, `Footer`, `Breadcrumb`,
  `TeamGrid`, `ProgrammeGrid`, `DetailGrid`, `ModalController`, `CookieConsent`) into plain PHP.
- `wp-content/plugins/ndingi-wp-content/` — the content model: custom post types (Team Member,
  Publication, Core Value) and taxonomies (Roster, Publication Category), kept in a plugin
  rather than the theme so it survives a future theme change. This is WordPress's answer to
  ndingi-foundation's Sanity schema (`person`, `post`, `resource` document types).
- `local-preview/` — scripts to run this locally with **no Docker and no MySQL**, using
  WordPress's official SQLite database integration instead. See below.

## Content model mapping

| ndingi (Sanity)                  | ndingi-wp (WordPress)                                    |
|-----------------------------------|-----------------------------------------------------------|
| `person` document, `rosters` field | `team_member` CPT + `ndingi_roster` taxonomy             |
| `post` document (News)             | native WP Post                                            |
| `resource` document (Publications) | `publication` CPT + `publication_category` taxonomy      |
| hardcoded `CORE_VALUES` array      | `core_value` CPT (editable without a code change)          |
| hardcoded `PROGRAMMES`/`ABOUT_ITEMS` blurbs | a "Card Settings" meta box on each Page (icon + blurb) |
| Web3Forms contact form (client-side only) | native `admin-post.php` handler → `wp_mail()` (works with JS off, no third-party service or API key) |

Page copy that was plain paragraphs in the original (Mission, Vision, programme descriptions,
Partners' advisor list) is just each WordPress Page's own block-editor content here — no custom
fields needed for prose an editor would normally just write directly on the page.

Education, Sustainable Livelihoods, and Water & Ecosystem Management each carry a real
photograph (the client's own) as their featured image, shown on their Our Work card — see
`local-preview/seed.php`'s `ndingi_attach_programme_photo()` and `local-preview/media/`.

### URL structure — deliberately nested

Mission, Vision, Core Values, People, News, Publications, and the three programme pages are set
up as **child pages** of their hub (About/Who We Are/Our Work/Resources), so their URLs nest —
`/about/who-we-are/`, `/our-work/education/`, `/resources/news/` — rather than the original
Next.js site's flat `/who-we-are`, `/education`, `/news`. This was a deliberate choice (confirmed
with the client 2026-09-10): it's the idiomatic WordPress way to let a hub page list its
children automatically, and matches how WordPress installs normally behave. Don't "fix" this
back to flat URLs without checking first — it's expected, not a bug.

## Local preview (no Docker, no MySQL)

```bash
./local-preview/setup.sh
```

This installs PHP (if missing, one `sudo apt-get` prompt), downloads WordPress core and
WP-CLI into a gitignored `.wp-runtime/` directory, wires up WordPress's SQLite database
integration plugin (no MySQL needed), symlinks this repo's theme and plugin into place, runs
the 5-minute install, seeds sample content (real copy from the original site plus clearly
labelled placeholder team members/news/publications), and starts PHP's built-in server.

Then open **http://localhost:8888** (admin: `http://localhost:8888/wp-admin`, `admin` / `admin`).

Re-running `./local-preview/setup.sh` is safe — every step is skipped if already done, so it
also works as the "start the server again" command.

## Moving to real hosting

`.wp-runtime/` is throwaway — it exists only so this machine can preview the theme without
installing Docker or MySQL. On a real host:

1. Install WordPress normally (most hosts one-click this, or it's a standard 5-minute install
   against real MySQL).
2. Copy `wp-content/themes/ndingi-wp/` and `wp-content/plugins/ndingi-wp-content/` into the
   host's `wp-content/`.
3. Activate **both** the theme and the plugin from wp-admin — Plugins as well as Appearance.
   The theme alone isn't enough: About, Our Work, Resources, Who We Are, People, Core Values,
   and Publications all call functions the plugin defines, and will render empty (with a red
   admin notice) until it's active. (This exact gap — plugin never activated — is what caused
   About/Our Work/Resources to go missing on the live site once already; see CHANGELOG 0.4.0.)
4. Go to **Tools → Ndingi Page Setup** in wp-admin and click "Create / Repair Site Pages" — this
   creates the full page hierarchy (same titles, templates, and real copy as
   `local-preview/seed.php`, minus its placeholder content) in one click, no WP-CLI/SSH needed.
   Safe to re-run any time; it never touches a page that already exists. Then add real content —
   team members, news, publications, programme photos — via wp-admin.
5. If the host's default `mail()` is unreliable for the contact form (common on shared hosting),
   install an SMTP plugin (e.g. WP Mail SMTP) pointed at a transactional email provider — no
   theme/plugin code change needed either way, `wp_mail()` already routes through whatever the
   site's mail setup is.
