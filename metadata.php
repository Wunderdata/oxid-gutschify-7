<?php

$sMetadataVersion = '2.1';

$aModule = [
    'id' => 'gutschify',
    'title' => [
        'de' => 'Gutschify Embedded Home Widget',
        'en' => 'Gutschify Embedded Home Widget',
    ],
    'description' => [
        'de' => 'Zeigt die Gutschify eingebettete Startseite als konfigurierbares Widget an. Unterstützt das Laden verschiedener Sammlungen basierend auf der Konfiguration.',
        'en' => 'Displays Gutschify embedded home page as a configurable widget. Supports loading different collections based on configuration.',
    ],
    'thumbnail' => 'out/pictures/picture.png',
    'version' => '1.0.0',
    'author' => 'Gutschify Team',
    'url' => 'https://gutschify.xxiii.tools',
    'email' => 'support@gutschify.xxiii.tools',
    'extend' => [],
    'controllers' => [
        'GutschifyWidgetController' => \Gutschify\Controller\GutschifyWidgetController::class,
    ],
    // Twig templates live in views/twig/ and are auto-registered under the
    // "@gutschify" namespace on activation; the templates key is Smarty-only.
    'templates' => [],
    'blocks' => [],
    'settings' => [
        [
            'group' => 'main',
            'name' => 'gutschify_base_url',
            'type' => 'str',
            'value' => 'https://gutschify.xxiii.tools',
        ],
        [
            'group' => 'main',
            'name' => 'organization_id',
            'type' => 'str',
            'value' => '',
        ],
        [
            'group' => 'main',
            'name' => 'collection_slug',
            'type' => 'str',
            'value' => 'default',
        ],
        [
            'group' => 'main',
            'name' => 'widget_title',
            'type' => 'str',
            'value' => '',
        ],
        [
            'group' => 'main',
            'name' => 'cache_enabled',
            'type' => 'bool',
            'value' => true,
        ],
        [
            'group' => 'main',
            'name' => 'cache_ttl',
            'type' => 'str',
            'value' => '3600',
        ],
    ],
    'events' => [],
];
