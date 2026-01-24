<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Casts\FullUrl;

class CompanyInfo extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;
    
    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'logo_path',
        'invoice_signature',
        'printer_path',
        'auto_print_immediate_sale',
        'auto_print_credit',
        'auto_print_credit_payment',
        'auto_print_reservation',
        'auto_print_reservation_complete',
        'auto_print_cash_count',
    ];

    protected $casts = [
        'logo_path' => FullUrl::class,
        'auto_print_immediate_sale' => 'boolean',
        'auto_print_credit' => 'boolean',
        'auto_print_credit_payment' => 'boolean',
        'auto_print_reservation' => 'boolean',
        'auto_print_reservation_complete' => 'boolean',
        'auto_print_cash_count' => 'boolean',
    ];
}