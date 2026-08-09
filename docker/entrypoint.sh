#!/bin/bash
# Provisions a real OXID 7 CE shop, installs and activates the Gutschify module,
# then hands off to Apache. Idempotent: work is skipped once the marker exists.
set -uo pipefail

DOCROOT=/var/www/html
SRC="$DOCROOT/source"
MARKER="$DOCROOT/.oxid_installed"

DB_HOST="${DB_HOST:-oxid7-mysql}"
DB_NAME="${DB_NAME:-oxid7}"
DB_USER="${DB_USER:-oxid7}"
DB_PASS="${DB_PASS:-oxid7}"
SHOP_URL="${SHOP_URL:-http://localhost:8081}"
OXID_VERSION="${OXID_VERSION:-dev-b-7.4-ce}"

log() { echo "[entrypoint] $*"; }

log "Waiting for database $DB_HOST ..."
until mysqladmin ping -h "$DB_HOST" -u"$DB_USER" -p"$DB_PASS" --skip-ssl --silent >/dev/null 2>&1; do
    sleep 3
done
log "Database is up."

if [ ! -f "$MARKER" ]; then
    export COMPOSER_ALLOW_SUPERUSER=1
    export COMPOSER_MEMORY_LIMIT=-1
    # The OXID metapackage pins composer/composer to a version flagged by
    # advisories; this is a throwaway test shop, so don't let advisory policy
    # block the install.
    composer config -g policy.advisories.block false || true

    if [ ! -f "$DOCROOT/composer.json" ]; then
        log "Creating OXID 7 CE project ($OXID_VERSION) ..."
        composer create-project --no-interaction --no-progress --stability=dev \
            oxid-esales/oxideshop-project:"$OXID_VERSION" /tmp/oxidproj
        shopt -s dotglob
        mv /tmp/oxidproj/* "$DOCROOT"/
        shopt -u dotglob
    fi

    cd "$DOCROOT" || exit 1

    log "Registering module as a Composer path repository ..."
    composer config repositories.gutschify path /module
    composer config allow-plugins.oxid-esales/oxideshop-composer-plugin true || true
    log "Requiring gutschify/oxid-module ..."
    composer require gutschify/oxid-module:@dev --no-interaction --no-progress \
        || log "WARNING: composer require failed (continuing so we can observe state)"

    # Track the steps that decide whether the shop actually works, so a failed
    # run does not write the marker and silently persist across restarts.
    provisioned=1

    log "Running oe:setup:shop ..."
    vendor/bin/oe-console oe:setup:shop \
        --db-host="$DB_HOST" --db-port=3306 \
        --db-name="$DB_NAME" --db-user="$DB_USER" --db-password="$DB_PASS" \
        --shop-url="$SHOP_URL" \
        --shop-directory="$SRC" \
        --compile-directory="$SRC/tmp" \
        --language=en \
        || { log "WARNING: oe:setup:shop failed"; provisioned=0; }

    log "Creating admin user ..."
    vendor/bin/oe-console oe:admin:create-user \
        --admin-email=admin@oxid.local --admin-password=adminadmin \
        || log "WARNING: admin user creation failed"

    log "Activating apex theme ..."
    vendor/bin/oe-console oe:theme:activate apex \
        || log "WARNING: theme activation failed"

    log "Activating gutschify module ..."
    vendor/bin/oe-console oe:module:activate gutschify \
        || { log "WARNING: module activation failed"; provisioned=0; }

    chown -R www-data:www-data "$SRC" || true

    if [ "$provisioned" = "1" ]; then
        touch "$MARKER"
        log "Provisioning complete."
    else
        log "ERROR: provisioning failed; not writing marker, will retry on next start."
    fi
else
    log "Marker present, skipping provisioning."
fi

# Keep the cache/var dirs writable by Apache on every boot (a root-run cache
# clear can otherwise leave them root-owned and wedge the shop).
if [ -d "$SRC" ]; then
    chown -R www-data:www-data "$SRC/tmp" "$SRC/var" 2>/dev/null || true
fi

log "Starting Apache."
exec apache2-foreground
