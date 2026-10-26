# Onboarding — ndingi-wp

## What this is

A WordPress rebuild of [ericgitonga/ndingi](https://github.com/ericgitonga/ndingi) (the
Next.js + Sanity site currently live on Vercel), built in its own repo so the client can
compare the two before committing either way. See that repo's `README.md` for the original.

## Stack

Plain PHP theme + plugin, no Node, no build step. See the top-level `README.md` for the full
content-model mapping and local preview instructions.

## Repo layout

- `wp-content/themes/ndingi-wp/` — presentation (templates, CSS, JS).
- `wp-content/plugins/ndingi-wp-content/` — content model (custom post types, taxonomies,
  meta boxes, the contact form handler, and the Tools → Ndingi Page Setup admin tool). Kept
  separate from the theme deliberately — swapping the theme later shouldn't mean losing the
  content model. **Must be active alongside the theme** — several page templates call its
  functions directly and render empty (with an admin notice) if it isn't; see the theme's
  `inc/template-helpers.php` `ndingi_safe_*` wrappers and CHANGELOG 0.4.0.
- `local-preview/` — `setup.sh` (bootstraps a local WordPress + SQLite instance, no
  Docker/MySQL needed) and `seed.php` (sample content, run automatically by `setup.sh` on
  first install).
- `.wp-runtime/` — gitignored. WordPress core + WP-CLI + the SQLite plugin, downloaded fresh by
  `setup.sh`. Never edit anything in here directly; it's regenerated, not tracked.

## Local development

```bash
./local-preview/setup.sh
```

Then edit files under `wp-content/themes/ndingi-wp/` or `wp-content/plugins/ndingi-wp-content/`
directly — they're symlinked into `.wp-runtime/`, so changes show up on refresh with no rebuild
step. Re-run `setup.sh` any time to restart the server; it skips every step already done.

## Conventions

- No ACF or other paid/heavy plugin dependency — custom fields are plain `register_post_meta` +
  hand-rolled meta boxes, so the whole content model ships in this repo's own plugin.
- Page copy that's just prose (Mission, Vision, programme descriptions, the Partners list) lives
  in the WordPress Page's own content, edited via the block editor — not a custom field.
  Custom fields/CPTs exist only for genuinely structured, repeating data (team rosters, news,
  publications, core values, and each hub page's card icon/blurb).
- British spelling throughout copy, matching the original site and this client's other
  documents.
