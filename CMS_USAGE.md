# Placing the widget in content

OXID 7 renders CMS content through Twig, so the widget can be embedded two ways.
Both use the same widget function; neither needs a code change or redeployment
once the module is active.

## In a theme template

```twig
{{ include_widget({cl: 'GutschifyWidgetController'}) }}
```

Use this when the widget belongs in a fixed spot in the theme (for example the
start page or a category template).

## In a CMS content page

Admin → Content → CMS pages. Edit a page and paste the same line into the
content:

```twig
<h2>Our offers</h2>
{{ include_widget({cl: 'GutschifyWidgetController'}) }}
```

OXID renders the page content as a Twig fragment, so the widget loads when the
page is viewed. An editor can add or move it without touching the theme.

## Notes

- The widget reads its configuration (organization, collection, title, cache)
  from the module settings, so no parameters are needed in the tag.
- With `organization_id` unset, the widget renders a "not configured" notice.
- The fetched HTML is cached per organization/collection when `cache_enabled`
  is on; clear the shop cache after changing settings if you cached a stale
  response.
