<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Locations',

    'settings' => [
        'tab' => 'Locations & addresses',
        'section' => 'Countries and address forms',
        'section_intro' => 'Active countries and how state/city fields appear in address forms (checkout, profile, etc.).',
        'enabled_countries' => 'Enabled countries',
        'enabled_countries_helper' => 'Empty = all available countries. Only these ISO2 codes appear in forms and location admin.',
        'default_country' => 'Default country',
        'default_country_helper' => 'Pre-selected country on new address forms.',
        'address_levels' => 'Address field visibility',
        'address_levels_helper' => 'Per country: show state and/or city. Otherwise the customer enters free text.',
        'address_level_country' => 'Country',
        'address_level_show_state' => 'Show state',
        'address_level_show_city' => 'Show city',
    ],

    'countries' => [
        'navigation_label' => 'Countries',
        'model_label' => 'Country',
        'plural_model_label' => 'Countries',
        'iso2' => 'ISO2',
        'iso3' => 'ISO3',
        'name' => 'Name',
        'native' => 'Native name',
        'is_active' => 'Active',
    ],

    'states' => [
        'navigation_label' => 'States / provinces',
        'model_label' => 'State',
        'plural_model_label' => 'States',
        'country' => 'Country',
        'code' => 'Code',
        'name' => 'Name',
        'native' => 'Native name',
        'is_visible' => 'Visible in forms',
        'source' => 'Source',
        'cities' => 'Cities',
    ],

    'cities' => [
        'model_label' => 'City',
        'plural_model_label' => 'Cities',
        'relation_help' => 'Search, edit, or add cities for this state (manual cities can be deleted).',
        'name' => 'Name',
        'native' => 'Native name',
        'is_visible' => 'Visible in forms',
        'source' => 'Source',
        'cannot_delete_seed' => 'Seeded cities cannot be deleted.',
        'cannot_delete_in_use' => 'This city is referenced elsewhere and cannot be deleted.',
    ],
];
