<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Snappy PDF / Image Configuration
    |--------------------------------------------------------------------------
    |
    | This option contains settings for PDF generation.
    |
    | Enabled:
    |    
    |    Whether to load PDF / Image generation.
    |
    | Binary:
    |    
    |    The file path of the wkhtmltopdf / wkhtmltoimage executable.
    |
    | Timeout:
    |    
    |    The amount of time to wait (in seconds) before PDF / Image generation is stopped.
    |    Setting this to false disables the timeout (unlimited processing time).
    |
    | Options:
    |
    |    The wkhtmltopdf command options. These are passed directly to wkhtmltopdf.
    |    See https://wkhtmltopdf.org/usage/wkhtmltopdf.txt for all options.
    |
    | Env:
    |
    |    The environment variables to set while running the wkhtmltopdf process.
    |
    */
    
    'pdf' => [
        'enabled' => true,
        'binary'  => env('WKHTMLTOPDF_PATH', '/usr/bin/wkhtmltopdf'),
        'timeout' => false,
        'options' => [
            'enable-local-file-access' => true,
        ],
        'env'     => [],
    ],
    'image' => [
        'enabled' => true,
        'binary'  => env('WKHTMLTOIMAGE_PATH', '/usr/bin/wkhtmltoimage'),
        'timeout' => false,
        'options' => [],
        'env'     => [],
    ],
    # config/snappy.php
    'options' => [
        'enable-local-file-access' => true,
        'enable-javascript' => false,        // ✅ Pas de JS = +50% plus rapide
        'no-stop-slow-scripts' => true,
        'disable-smart-shrinking' => true,   // ✅ Pas de calculs layout
        'print-media-type' => true,
        'dpi' => 96,                         // ✅ Standard, pas haute qualité
        'image-quality' => 80,               // ✅ Qualité moyenne suffit
        'load-error-handling' => 'ignore',
        'load-media-error-handling' => 'ignore',
    ],
    'timeout' => 30,  // ✅ 30 secondes MAX

];
