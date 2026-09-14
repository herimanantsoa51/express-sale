<?php

namespace App\Helpers;

class FrontendRoutes
{
    // Base URL du frontend (à configurer dans .env)
    private static function baseUrl(): string
    {
        return config('app.frontend_url', 'http://localhost:3000');
    }

    // VENTES
    public static function sale(int $id): string
    {
        return "/sales/{$id}";
    }

    public static function salesList(): string
    {
        return '/sales';
    }

    // CRÉDITS
    public static function credit(int $id): string
    {
        return "/credits/{$id}";
    }

    public static function creditsList(): string
    {
        return '/credits';
    }

    // RÉSERVATIONS
    public static function reservation(int $id): string
    {
        return "/reservations/{$id}";
    }

    public static function reservationsList(): string
    {
        return '/reservations';
    }

    // CLIENTS
    public static function customer(int $id): string
    {
        return "/customers/{$id}";
    }

    public static function customersList(): string
    {
        return '/customers';
    }

    // PRODUITS
    public static function product(int $id): string
    {
        return "/products/{$id}";
    }

    public static function productsList(): string
    {
        return '/products';
    }

    // STOCK
    public static function stockReceipt(int $id): string
    {
        return "/stock/receipts/{$id}";
    }

    public static function stockReceiptsList(): string
    {
        return '/stock/receipts';
    }

    public static function stockMovement(int $id): string
    {
        return "/stock/movements/{$id}";
    }

    public static function location(int $id): string
    {
        return "/stock/locations/{$id}";
    }

    // FOURNISSEURS
    public static function supplier(int $id): string
    {
        return "/suppliers/{$id}";
    }

    // TRANSITAIRES
    public static function freightForwarder(int $id): string
    {
        return "/freight-forwarders/{$id}";
    }

    // COMPTES
    public static function account(int $id): string
    {
        return "/accounts/{$id}";
    }

    public static function accountsList(): string
    {
        return '/accounts';
    }

    public static function transaction(int $id): string
    {
        return "/transactions/{$id}";
    }

    // UTILISATEURS
    public static function user(int $id): string
    {
        return "/users/{$id}";
    }

    // TABLEAUX DE BORD
    public static function dashboard(): string
    {
        return '/dashboard';
    }

    public static function salesStatistics(): string
    {
        return '/statistics/sales';
    }

    public static function financialStatistics(): string
    {
        return '/statistics/financial';
    }

    // Générer avec URL complète si besoin
    public static function fullUrl(string $path): string
    {
        return self::baseUrl().$path;
    }
}
