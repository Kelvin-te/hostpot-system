<?php

return [
    /**
     * Options: tailwind | bootstrap-4 | bootstrap-5.
     */
    'theme' => 'tailwind',

    /**
     * Cache Rappasoft Frontend Assets
     */
    'cache_assets' => false,

    /**
     * Enable or Disable automatic injection of core assets.
     */
    'inject_core_assets_enabled' => false,

    /**
     * Enable or Disable automatic injection of third-party assets.
     */
    'inject_third_party_assets_enabled' => false,

    /**
     * Enable Blade Directives (required when not auto-injecting)
     */
    'enable_blade_directives' => true,

    /**
     * Customise Script & Styles Paths
     */
    // 'script_base_path' => '/rappasoft/laravel-livewire-tables',

    /**
     * Filter Default Configuration Options
     */
    'dateFilter' => [
        'defaultConfig' => [
            'format' => 'Y-m-d',
            'pillFormat' => 'd M Y',
        ],
    ],

    'dateTimeFilter' => [
        'defaultConfig' => [
            'format' => 'Y-m-d\TH:i',
            'pillFormat' => 'd M Y - H:i',
        ],
    ],

    'dateRange' => [
        'defaultOptions' => [],
        'defaultConfig' => [
            'allowInput' => true,
            'altFormat' => 'F j, Y',
            'ariaDateFormat' => 'F j, Y',
            'dateFormat' => 'Y-m-d',
            'earliestDate' => null,
            'latestDate' => null,
            'locale' => 'en',
        ],
    ],

    'numberRange' => [
        'defaultOptions' => [
            'min' => 0,
            'max' => 100,
        ],
        'defaultConfig' => [
            'minRange' => 0,
            'maxRange' => 100,
            'suffix' => '',
            'prefix' => '',
        ],
    ],

    'selectFilter' => [
        'defaultOptions' => [],
        'defaultConfig' => [],
    ],

    'multiSelectFilter' => [
        'defaultOptions' => [],
        'defaultConfig' => [],
    ],

    'multiSelectDropdownFilter' => [
        'defaultOptions' => [],
        'defaultConfig' => [],
    ],
];
