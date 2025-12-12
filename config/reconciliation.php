<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mutabakat Plugin Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the Mutabakat plugin.
    |
    */

    /**
     * Enable or disable the plugin
     */
    'enabled' => true,

    /**
     * Default currency for displaying amounts
     */
    'currency' => 'TRY',

    /**
     * Default status options
     */
    'statuses' => [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    /**
     * Navigation settings
     */
    'navigation' => [
        'icon' => 'heroicon-o-document-text',
        'sort' => 10,
        'group' => null,
    ],
];
