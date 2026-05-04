<?php

declare(strict_types=1);

return [
    // Whether the plugin should appear in the settings center.
    'enabled' => true,

    // Let the plugin own its complete settings page.
    'mode' => 'external_route',

    // Optional icon shown in plugin settings catalog.
    'icon' => 'Link',

    // external_route mode is typically owned by the plugin itself.
    'managed_by' => 'plugin',

    // external_route mode keeps only the route declaration in settings center.
    'injection' => [
        'strategy' => 'skip',
    ],

    // external_route mode usually keeps plugin-owned settings on uninstall.
    'cleanup' => [
        'on_uninstall' => 'retain',
    ],

    // Route rendered by the plugin itself.
    // The frontend will navigate to this path instead of requesting hosted schema.
    'path' => '/cms/settings',

    // external_route mode does not need hosted sections.
    'sections' => [],
];
