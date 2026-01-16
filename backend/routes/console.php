<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Planifier l'expiration automatique des réservations
Schedule::command('reservations:expire')
    ->hourly() // Exécuter toutes les heures
    ->withoutOverlapping() // Éviter les exécutions simultanées
    ->onOneServer(); // Exécuter sur un seul serveur si vous en avez plusieurs
