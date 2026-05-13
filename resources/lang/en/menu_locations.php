<?php

return [
    'navigation_label' => 'Menu Locations',
    'model_label' => 'Menu Location',
    'plural_model_label' => 'Menu Locations',
    'key' => 'Key',
    'key_helper' => 'Location keys are registered via code and cannot be changed.',
    'label' => 'Label',
    'assigned_menu' => 'Assigned Menu',
    'no_menu_assigned' => '— No menu assigned —',
    'assigned_menu_helper' => 'Select the menu to display at this location.',
    'not_assigned' => '— Not assigned —',

    /*
     * Labels for locations declared by the default package theme (resources/views/themes/mksine).
     * Shown in admin after sync; keys must match Blade (<x-mksine::menu location="...">).
     */
    'theme_defaults' => [
        'header_primary' => 'Header (primary)',
        'footer_links' => 'Footer links',
    ],
];
