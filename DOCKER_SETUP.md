# Docker test harness for the OXID 7 Gutschify module

This directory ships a self-contained Docker setup that provisions a real OXID 7
Community Edition shop, installs this module from the working tree, and activates
it. Use it to verify the module end to end.

## Requirements

- Docker with Compose v2
- ~2 GB free disk and outbound network (first run downloads OXID 7 CE via Composer)

## Start

```bash
docker compose up --build -d
```

First boot takes a few minutes: it runs `composer create-project
oxid-esales/oxideshop-project` (CE 7 branch), sets up the database, installs the
module as a Composer path repository, activates the apex theme and the module.
Watch progress with:

```bash
docker compose logs -f oxid7-web
```

The shop is ready when the log prints `Provisioning complete`.

- Frontend: http://localhost:8081
- Admin: http://localhost:8081/admin (admin@oxid.local / adminadmin)

The provisioned shop lives in a named volume and survives restarts. The module
source is mounted read-write, so edits under `src/`, `views/`, `metadata.php`
and `services.yaml` are picked up live (clear the cache after metadata/service
changes: `docker compose exec oxid7-web rm -rf /var/www/html/source/tmp/*`).

## What the compose provides

- `oxid7-web`: PHP 8.2 + Apache, document root at the OXID `source/` dir. Built
  from `docker/Dockerfile`; provisioning logic in `docker/entrypoint.sh`.
- `oxid7-mysql`: MySQL 8.

Override the OXID branch with the `OXID_VERSION` env var in `docker-compose.yml`
(default `dev-b-7.4-ce`).

## Rendering the widget

The widget controller is `gutschifywidgetcontroller`. Configure it in the admin
under Extensions -> Modules -> Gutschify (set `organization_id` and
`gutschify_base_url`), or embed it in a Twig template:

```twig
{{ render_widget({cl: 'gutschifywidgetcontroller'}) }}
```

With no `organization_id` set, the widget renders its "not properly configured"
notice. With valid settings it fetches `<base_url>/embedded-home/` and renders
the returned HTML.

## Reset

```bash
docker compose down -v   # wipes the shop + database volumes
docker compose up --build -d
```

## Run the unit tests

The service unit tests need no shop:

```bash
docker run --rm -v "$PWD":/app -w /app php:8.2-cli bash -c \
  "curl -sS https://getcomposer.org/installer | php && \
   php composer.phar install && vendor/bin/phpunit"
```
