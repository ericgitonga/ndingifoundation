#!/usr/bin/env bash
# Bootstraps a local WordPress instance to preview the ndingi-wp theme +
# plugin, using WordPress's official SQLite database integration instead
# of MySQL (nothing else on this machine to install). Safe to re-run —
# skips any step already done.
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RUNTIME_DIR="$REPO_DIR/.wp-runtime"
PORT="${NDINGI_WP_PORT:-8888}"
URL="http://localhost:${PORT}"

echo "== ndingi-wp local preview setup =="

# --- 1. PHP + required extensions ---
if ! command -v php >/dev/null 2>&1; then
	echo "-- Installing PHP (sudo required once) --"
	sudo apt-get update -y
	sudo apt-get install -y php-cli php-sqlite3 php-mbstring php-xml php-curl php-zip php-gd unzip
else
	echo "-- PHP already installed: $(php -v | head -n1) --"
fi

for ext in sqlite3 mbstring xml curl zip; do
	if ! php -m | grep -qi "^${ext}$"; then
		echo "-- Missing PHP extension '$ext' — installing --"
		sudo apt-get install -y "php-${ext}"
	fi
done

mkdir -p "$RUNTIME_DIR"

# --- 2. WordPress core ---
if [ ! -f "$RUNTIME_DIR/wp-load.php" ]; then
	echo "-- Downloading WordPress core --"
	curl -sL https://wordpress.org/latest.zip -o "$RUNTIME_DIR/wp-core.zip"
	unzip -q "$RUNTIME_DIR/wp-core.zip" -d "$RUNTIME_DIR/_extract"
	cp -r "$RUNTIME_DIR/_extract/wordpress/." "$RUNTIME_DIR/"
	rm -rf "$RUNTIME_DIR/_extract" "$RUNTIME_DIR/wp-core.zip"
else
	echo "-- WordPress core already present --"
fi

# --- 3. WP-CLI ---
WPCLI="$RUNTIME_DIR/wp-cli.phar"
if [ ! -f "$WPCLI" ]; then
	echo "-- Downloading WP-CLI --"
	curl -sL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o "$WPCLI"
	chmod +x "$WPCLI"
fi
wp() { php "$WPCLI" --path="$RUNTIME_DIR" --allow-root "$@"; }

# --- 4. SQLite database integration (official WordPress.org plugin) ---
SQLITE_PLUGIN_DIR="$RUNTIME_DIR/wp-content/plugins/sqlite-database-integration"
if [ ! -d "$SQLITE_PLUGIN_DIR" ]; then
	echo "-- Downloading SQLite database integration plugin --"
	curl -sL https://downloads.wordpress.org/plugin/sqlite-database-integration.latest-stable.zip -o "$RUNTIME_DIR/sqlite.zip"
	unzip -q "$RUNTIME_DIR/sqlite.zip" -d "$RUNTIME_DIR/wp-content/plugins/"
	rm -f "$RUNTIME_DIR/sqlite.zip"
fi
if [ ! -f "$RUNTIME_DIR/wp-content/db.php" ]; then
	echo "-- Activating SQLite drop-in --"
	cp "$SQLITE_PLUGIN_DIR/db.copy" "$RUNTIME_DIR/wp-content/db.php"
fi

# --- 5. Symlink the theme + plugin back to the tracked repo files ---
ln -sfn "$REPO_DIR/wp-content/themes/ndingi-wp" "$RUNTIME_DIR/wp-content/themes/ndingi-wp"
ln -sfn "$REPO_DIR/wp-content/plugins/ndingi-wp-content" "$RUNTIME_DIR/wp-content/plugins/ndingi-wp-content"

# --- 6. wp-config.php ---
if [ ! -f "$RUNTIME_DIR/wp-config.php" ]; then
	echo "-- Writing wp-config.php --"
	wp config create \
		--dbname=ndingi_wp --dbuser=unused --dbpass=unused \
		--skip-check --force
	wp config set WP_HOME "$URL" --type=constant
	wp config set WP_SITEURL "$URL" --type=constant
	wp config set WP_DEBUG true --raw --type=constant
fi

# --- 7. Install the site (idempotent: skip if already installed) ---
if ! wp core is-installed 2>/dev/null; then
	echo "-- Running the 5-minute install --"
	wp core install \
		--url="$URL" \
		--title="Ndingi Foundation" \
		--admin_user=admin \
		--admin_password=admin \
		--admin_email=admin@example.com \
		--skip-email
	wp rewrite structure '/%postname%/' --hard
	wp theme activate ndingi-wp
	wp plugin activate ndingi-wp-content
	echo "-- Seeding sample content --"
	wp eval-file "$REPO_DIR/local-preview/seed.php"
else
	echo "-- Site already installed --"
fi

# --- 8. Serve it ---
cp "$REPO_DIR/local-preview/router.php" "$RUNTIME_DIR/router.php"
echo ""
echo "== Ready =="
echo "Starting PHP's built-in server on $URL (Ctrl+C to stop)"
echo "Admin: $URL/wp-admin  (user: admin / pass: admin)"
php -S "localhost:${PORT}" -t "$RUNTIME_DIR" "$RUNTIME_DIR/router.php"
