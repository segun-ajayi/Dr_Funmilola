<?php

return [
    // "basic" is for isolated local development and automated tests only.
    // Production must select an approved provider driver; "unconfigured" fails closed.
    'scanner' => env('UPLOAD_SCANNER', env('APP_ENV') === 'production' ? 'unconfigured' : 'basic'),
    'quarantine_directory' => 'quarantine',
    'released_directory' => 'patient-documents',
    'cms_media_directory' => 'cms-media',
];
