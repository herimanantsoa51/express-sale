<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration de l'impression POS
    |--------------------------------------------------------------------------
    |
    | Chemin de l'imprimante USB. Pour Windows, utilisez:
    | 'connector_path' => 'LPT1' ou 'COM1'
    | Pour Linux: '/dev/usb/lp0' ou '/dev/ttyUSB0'
    |
    */

    'connector_path' => env('PRINTER_CONNECTOR_PATH', '/dev/usb/lp0'),

    // Largeur du papier en caractères
    'paper_width' => env('PRINTER_PAPER_WIDTH', 42),
];
