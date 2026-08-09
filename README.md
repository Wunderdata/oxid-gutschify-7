# Gutschify module for OXID eShop 7

Renders the Gutschify embedded-home page as a configurable widget in an OXID
eShop 7 store. The widget fetches `<base_url>/embedded-home/` for a configured
organization and collection and displays the returned HTML.

## Requirements

- OXID eShop 7 (CE/PE/EE)
- PHP 8.0+
- Composer

## Installation

From a Composer path repository (module checked out locally):

```bash
composer config repositories.gutschify path /path/to/oxid-gutschify-7
composer require gutschify/oxid-module:@dev
vendor/bin/oe-console oe:module:activate gutschify
```

## Configuration

Admin → Extensions → Modules → Gutschify → Settings:

| Setting | Purpose | Default |
| --- | --- | --- |
| `gutschify_base_url` | Base URL of the Gutschify service | `https://gutschify.xxiii.tools` |
| `organization_id` | Organization UUID (required) | empty |
| `collection_slug` | Collection to display | `default` |
| `widget_title` | Optional heading above the widget | empty |
| `cache_enabled` | Cache fetched HTML | on |
| `cache_ttl` | Cache lifetime in seconds | `3600` |

With `organization_id` unset the widget shows a "not configured" notice instead
of failing.

## Displaying the widget

In a Twig template:

```twig
{{ include_widget({cl: 'GutschifyWidgetController'}) }}
```

The same line also works pasted into a CMS content page, so an editor can place
the widget without a deployment. See [CMS_USAGE.md](CMS_USAGE.md).

## Development

- Docker test harness (provisions a real OXID 7 shop): [DOCKER_SETUP.md](DOCKER_SETUP.md).
- Unit tests:

```bash
composer install
vendor/bin/phpunit
```

## Layout

```
metadata.php                     module metadata
composer.json                    dependencies
services.yaml                    DI: Guzzle client, PSR-16 cache, service
src/Controller/                  widget controller
src/Service/                     embedded-home fetch + cache
src/Exception/                   module exception
views/twig/                      widget template (@gutschify namespace)
views/admin_twig/{en,de}/        setting labels
tests/Unit/                      service unit tests
docker/                          test-harness image + provisioning
```

## License

MIT. See [LICENSE](LICENSE).
